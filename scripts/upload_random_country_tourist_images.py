#!/usr/bin/env python3
"""
Download public tourist-place images from Wikimedia Commons and upload them to
an S3-compatible MinIO bucket.

Credentials are intentionally read from CLI args/environment so secrets do not
land in the repository.
"""

from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import hmac
import mimetypes
import os
import re
import sys
import time
from typing import Iterable
from urllib.parse import quote, urlencode, urlparse

import requests


COUNTRIES = [
    "United Arab Emirates", "Saudi Arabia", "Qatar", "Oman", "Bahrain",
    "Kuwait", "Turkey", "United Kingdom", "France", "Germany", "Italy",
    "Spain", "Switzerland", "Netherlands", "Japan", "Singapore", "Thailand",
    "Malaysia", "India", "China", "Indonesia", "Vietnam", "South Korea",
    "Australia", "New Zealand", "United States", "Canada", "Mexico", "Brazil",
    "Argentina", "Chile", "Peru", "Colombia", "South Africa", "Egypt",
    "Morocco", "Kenya", "Tanzania", "Greece", "Portugal", "Austria",
    "Belgium", "Denmark", "Norway", "Sweden", "Finland", "Iceland", "Ireland",
    "Poland", "Czech Republic", "Hungary", "Croatia", "Slovenia", "Romania",
    "Bulgaria", "Serbia", "Jordan", "Israel", "Lebanon", "Nepal", "Sri Lanka",
    "Maldives", "Philippines", "Cambodia", "Laos", "Myanmar", "Taiwan",
    "Hong Kong", "Pakistan", "Bangladesh", "Kazakhstan", "Uzbekistan",
    "Azerbaijan", "Georgia", "Armenia", "Russia", "Ukraine", "Ethiopia",
    "Rwanda", "Uganda", "Ghana", "Nigeria", "Senegal", "Tunisia", "Algeria",
    "Mauritius", "Seychelles", "Madagascar", "Costa Rica", "Panama", "Cuba",
    "Jamaica", "Dominican Republic", "Ecuador",
]

SEARCH_PATTERNS = [
    "{country} tourist attraction",
    "{country} landmark",
    "{country} tourism",
    "{country} monument",
    "{country} national park",
]

COMMONS_API = "https://commons.wikimedia.org/w/api.php"
USER_AGENT = "WeelpMediaSeeder/1.0 (https://weelp.com; contact@weelp.com)"


def slugify(value: str) -> str:
    value = value.lower()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-") or "image"


def signing_key(secret: str, date_stamp: str, region: str, service: str) -> bytes:
    key = ("AWS4" + secret).encode()
    for msg in (date_stamp, region, service, "aws4_request"):
        key = hmac.new(key, msg.encode(), hashlib.sha256).digest()
    return key


def s3_put(
    endpoint: str,
    bucket: str,
    key: str,
    access_key: str,
    secret_key: str,
    region: str,
    body: bytes,
    content_type: str,
    timeout: int,
) -> None:
    parsed = urlparse(endpoint)
    scheme = parsed.scheme or "http"
    host = parsed.netloc or parsed.path
    canonical_uri = "/" + "/".join(quote(part, safe="") for part in [bucket, *key.split("/")])
    url = f"{scheme}://{host}{canonical_uri}"

    now = dt.datetime.utcnow()
    amz_date = now.strftime("%Y%m%dT%H%M%SZ")
    date_stamp = now.strftime("%Y%m%d")
    payload_hash = hashlib.sha256(body).hexdigest()

    headers = {
        "content-type": content_type,
        "host": host,
        "x-amz-content-sha256": payload_hash,
        "x-amz-date": amz_date,
    }
    signed_headers = ";".join(sorted(headers))
    canonical_headers = "".join(f"{name}:{headers[name]}\n" for name in sorted(headers))
    canonical_request = "\n".join([
        "PUT",
        canonical_uri,
        "",
        canonical_headers,
        signed_headers,
        payload_hash,
    ])
    credential_scope = f"{date_stamp}/{region}/s3/aws4_request"
    string_to_sign = "\n".join([
        "AWS4-HMAC-SHA256",
        amz_date,
        credential_scope,
        hashlib.sha256(canonical_request.encode()).hexdigest(),
    ])
    signature = hmac.new(
        signing_key(secret_key, date_stamp, region, "s3"),
        string_to_sign.encode(),
        hashlib.sha256,
    ).hexdigest()
    headers["authorization"] = (
        "AWS4-HMAC-SHA256 "
        f"Credential={access_key}/{credential_scope}, "
        f"SignedHeaders={signed_headers}, Signature={signature}"
    )

    response = requests.put(url, data=body, headers=headers, timeout=timeout)
    if response.status_code not in (200, 201):
        raise RuntimeError(f"MinIO PUT failed {response.status_code}: {response.text[:300]}")


def commons_candidates(country: str, limit: int) -> Iterable[dict]:
    seen_titles: set[str] = set()
    session = requests.Session()
    session.headers.update({"User-Agent": USER_AGENT})

    for pattern in SEARCH_PATTERNS:
        params = {
            "action": "query",
            "format": "json",
            "generator": "search",
            "gsrnamespace": "6",
            "gsrlimit": "30",
            "gsrsearch": pattern.format(country=country),
            "prop": "imageinfo",
            "iiprop": "url|mime|size",
            "iiurlwidth": "1200",
        }
        response = session.get(COMMONS_API + "?" + urlencode(params), timeout=25)
        response.raise_for_status()
        pages = response.json().get("query", {}).get("pages", {})

        for page in pages.values():
            title = page.get("title", "")
            if title in seen_titles:
                continue
            seen_titles.add(title)
            info = (page.get("imageinfo") or [{}])[0]
            mime = info.get("mime", "")
            if not mime.startswith("image/") or mime == "image/svg+xml":
                continue
            url = info.get("thumburl") or info.get("url")
            if not url:
                continue
            yield {"title": title, "url": url, "mime": mime}
            if len(seen_titles) >= limit * len(SEARCH_PATTERNS):
                return


def loremflickr_candidates(country: str, limit: int, start_lock: int) -> Iterable[dict]:
    tags = quote(f"{slugify(country)},landmark,tourism", safe=",")
    for index in range(limit):
        lock = start_lock + index
        yield {
            "title": f"{country} tourist place {index + 1}",
            "url": f"https://loremflickr.com/1200/800/{tags}/all?lock={lock}",
            "mime": "image/jpeg",
        }


def download_image(url: str, timeout: int) -> tuple[bytes, str]:
    response = requests.get(url, headers={"User-Agent": USER_AGENT}, timeout=timeout)
    response.raise_for_status()
    content_type = response.headers.get("content-type", "image/jpeg").split(";")[0]
    if not content_type.startswith("image/"):
        raise RuntimeError(f"Unexpected content type: {content_type}")
    return response.content, content_type


def extension_for(content_type: str, url: str) -> str:
    ext = mimetypes.guess_extension(content_type) or os.path.splitext(urlparse(url).path)[1]
    if ext == ".jpe":
        return ".jpg"
    return ext if ext in {".jpg", ".jpeg", ".png", ".webp", ".gif"} else ".jpg"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--endpoint", default=os.getenv("MINIO_ENDPOINT", "http://localhost:9000"))
    parser.add_argument("--bucket", default=os.getenv("MINIO_BUCKET", "weelp-media"))
    parser.add_argument("--access-key", default=os.getenv("MINIO_KEY"))
    parser.add_argument("--secret-key", default=os.getenv("MINIO_SECRET"))
    parser.add_argument("--region", default=os.getenv("MINIO_REGION", "ap-southeast-1"))
    parser.add_argument("--prefix", default="countries/random-tourist-places")
    parser.add_argument("--target", type=int, default=470)
    parser.add_argument("--per-country", type=int, default=5)
    parser.add_argument("--provider", choices=["commons", "loremflickr"], default="commons")
    parser.add_argument("--timeout", type=int, default=35)
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    if not args.access_key or not args.secret_key:
        print("ERROR: --access-key and --secret-key are required", file=sys.stderr)
        return 2

    uploaded = 0
    failures: list[str] = []
    started = time.time()

    for country in COUNTRIES:
        if uploaded >= args.target:
            break
        country_uploaded = 0
        print(f"\n--- {country} ---", flush=True)

        candidates = (
            loremflickr_candidates(country, args.per_country, uploaded + 1000)
            if args.provider == "loremflickr"
            else commons_candidates(country, args.per_country)
        )

        for candidate in candidates:
            if uploaded >= args.target or country_uploaded >= args.per_country:
                break
            try:
                body, content_type = download_image(candidate["url"], args.timeout)
                ext = extension_for(content_type, candidate["url"])
                digest = hashlib.sha1(candidate["url"].encode()).hexdigest()[:10]
                key = (
                    f"{args.prefix}/{slugify(country)}/"
                    f"{country_uploaded + 1:02d}-{slugify(candidate['title'])}-{digest}{ext}"
                )
                s3_put(
                    args.endpoint,
                    args.bucket,
                    key,
                    args.access_key,
                    args.secret_key,
                    args.region,
                    body,
                    content_type,
                    args.timeout,
                )
                uploaded += 1
                country_uploaded += 1
                print(f"uploaded {uploaded:03d}/{args.target}: {key}", flush=True)
            except Exception as exc:  # noqa: BLE001 - keep import moving.
                failures.append(f"{country}: {candidate.get('title', candidate.get('url'))}: {exc}")
                print(f"skip: {country}: {exc}", flush=True)

    elapsed = int(time.time() - started)
    print("\n=== Summary ===")
    print(f"uploaded: {uploaded}")
    print(f"target:   {args.target}")
    print(f"failed:   {len(failures)}")
    print(f"elapsed:  {elapsed}s")
    if failures:
        print("\nFirst failures:")
        for failure in failures[:20]:
            print(f"  - {failure}")
    return 0 if uploaded >= args.target else 1


if __name__ == "__main__":
    raise SystemExit(main())
