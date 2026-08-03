<?php

namespace App\Http\Requests;

use App\Support\SupportItemResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->normalizedInput('email'),
            'city_slug' => $this->normalizedInput('city_slug'),
            'item_slug' => $this->normalizedInput('item_slug'),
            'item_type' => $this->normalizedInput('item_type'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->hasPopulatedHoneypot()) {
            return [
                'website' => ['nullable'],
            ];
        }

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'topic' => ['required', Rule::in([
                'dates_availability',
                'pickup_location',
                'changes_cancellation',
                'before_booking',
                'other',
            ])],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'item_type' => ['required', Rule::in(['activity', 'package', 'itinerary'])],
            'item_id' => ['required', 'integer', 'min:1'],
            'item_title' => ['required', 'string', 'max:255'],
            'city_slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'item_slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'page_url' => ['required', 'url', 'max:2048'],
            'client_request_id' => ['required', 'uuid'],
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        if ($this->hasPopulatedHoneypot()) {
            return [];
        }

        return [
            function (Validator $validator): void {
                if (! $this->isExpectedPageUrl(app(SupportItemResolver::class))) {
                    $validator->errors()->add('page_url', 'The page URL does not match the requested item.');
                }
            },
        ];
    }

    public function isExpectedPageUrl(SupportItemResolver $resolver): bool
    {
        $pageUrl = $this->input('page_url');
        $frontendUrl = config('app.frontend_url');
        $citySlug = $this->input('city_slug');
        $itemType = $this->input('item_type');
        $itemSlug = $this->input('item_slug');

        if (
            ! is_string($pageUrl)
            || ! is_string($frontendUrl)
            || ! is_string($citySlug)
            || ! is_string($itemType)
            || ! is_string($itemSlug)
        ) {
            return false;
        }

        $pageParts = parse_url($pageUrl);
        $frontendParts = parse_url($frontendUrl);

        if (! is_array($pageParts) || ! is_array($frontendParts)) {
            return false;
        }

        $pageOrigin = $this->origin($pageParts);
        $frontendOrigin = $this->origin($frontendParts);

        if ($pageOrigin === null || $frontendOrigin === null || $pageOrigin !== $frontendOrigin) {
            return false;
        }

        try {
            $pathSegment = $resolver->publicPathSegment($itemType);
        } catch (ModelNotFoundException) {
            return false;
        }

        $expectedPath = "/cities/{$citySlug}/{$pathSegment}/{$itemSlug}";

        return ($pageParts['path'] ?? '/') === $expectedPath;
    }

    private function normalizedInput(string $key): mixed
    {
        $value = $this->input($key);

        return is_string($value) ? Str::lower(trim($value)) : $value;
    }

    private function hasPopulatedHoneypot(): bool
    {
        return $this->filled('website');
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function origin(array $parts): ?string
    {
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }

        $scheme = Str::lower($scheme);
        $host = Str::lower($host);

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        $port = $parts['port'] ?? null;

        if ($port !== null && ! is_int($port)) {
            return null;
        }

        $defaultPort = $scheme === 'http' ? 80 : 443;
        $portSuffix = $port !== null && $port !== $defaultPort ? ":{$port}" : '';

        return "{$scheme}://{$host}{$portSuffix}";
    }
}
