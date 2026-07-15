<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RichContentFixtureSeeder extends Seeder
{
    public const RICH_BLOG_SLUG = 'step-6-rich-content-browser-coverage';

    public const SIMPLE_BLOG_SLUG = 'step-6-simple-content-browser-coverage';

    public const DRAFT_BLOG_SLUG = 'step-6-hidden-rich-content-browser-coverage';

    public const RICH_PAGE_SLUG = 'step-6-rich-cms-browser-coverage';

    public const SIMPLE_PAGE_SLUG = 'step-6-simple-cms-browser-coverage';

    public const DRAFT_PAGE_SLUG = 'step-6-hidden-rich-cms-browser-coverage';

    private const LONG_URL = 'https://example.com/travel/research/step-six-rich-content-browser-coverage/this-path-is-intentionally-very-long-to-prove-inline-links-and-plain-urls-wrap-without-breaking-the-public-layout-or-hydration';

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        DB::transaction(function (): void {
            Blog::query()
                ->whereIn('slug', [self::RICH_BLOG_SLUG, self::SIMPLE_BLOG_SLUG, self::DRAFT_BLOG_SLUG])
                ->delete();

            Page::query()
                ->whereIn('slug', [self::RICH_PAGE_SLUG, self::SIMPLE_PAGE_SLUG, self::DRAFT_PAGE_SLUG])
                ->delete();

            $media = Media::query()->orderBy('id')->limit(3)->get();
            $category = Category::query()->firstOrCreate(
                ['slug' => 'fixture-coverage'],
                ['name' => 'Fixture Coverage', 'description' => 'Local rich content coverage fixtures', 'status' => 'active'],
            );
            $tag = Tag::query()->firstOrCreate(
                ['slug' => 'rich-content'],
                ['name' => 'Rich Content', 'description' => 'Local rich content coverage tag', 'status' => 'active'],
            );

            $richBlog = Blog::query()->create([
                'name' => 'Step 6 Rich Content Browser Coverage',
                'slug' => self::RICH_BLOG_SLUG,
                'content' => $this->document($this->richContentNodes('/blogs/'.self::SIMPLE_BLOG_SLUG)),
                'publish' => true,
                'excerpt' => 'Published fixture covering links, quotations, long URLs, videos, iframes, embedded media, long copy, and headings.',
                'meta_title' => 'Step 6 Rich Blog Fixture',
                'meta_description' => 'Local fixture for browser coverage of rich blog content.',
                'schema_type' => 'Article',
                'schema_data' => ['@context' => 'https://schema.org', '@type' => 'Article'],
            ]);

            $simpleBlog = Blog::query()->create([
                'name' => 'Step 6 Simple Content Browser Coverage',
                'slug' => self::SIMPLE_BLOG_SLUG,
                'content' => $this->document([
                    $this->heading('Simple comparison article', 2),
                    $this->paragraph('This published comparison fixture keeps the body intentionally plain so Step 6 checks can compare normal article rendering against the rich content case.'),
                ]),
                'publish' => true,
                'excerpt' => 'Simple published comparison fixture.',
            ]);

            Blog::query()->create([
                'name' => 'Step 6 Hidden Rich Content Browser Coverage',
                'slug' => self::DRAFT_BLOG_SLUG,
                'content' => $this->document($this->richContentNodes('/blogs/'.self::SIMPLE_BLOG_SLUG)),
                'publish' => false,
                'excerpt' => 'Draft fixture used to confirm unpublished rich blog content stays hidden.',
            ]);

            foreach ([$richBlog, $simpleBlog] as $blog) {
                $blog->categories()->sync([$category->id]);
                $blog->tags()->sync([$tag->id]);
                $blog->media()->sync($this->mediaAttachmentPayload($media));
            }

            Page::query()->create([
                'title' => 'Step 6 Rich CMS Browser Coverage',
                'slug' => self::RICH_PAGE_SLUG,
                'content' => $this->document($this->richContentNodes('/pages/'.self::SIMPLE_PAGE_SLUG)),
                'excerpt' => 'Published CMS fixture covering links, quotations, long URLs, videos, iframes, embedded media, long copy, and headings.',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'hero_heading' => 'Step 6 CMS fixture with a deliberately long hero heading that must wrap without pushing the page sideways',
                'hero_text' => self::LONG_URL,
                'hero_button_label' => 'Simple CMS fixture',
                'hero_button_url' => '/pages/'.self::SIMPLE_PAGE_SLUG,
                'hero_overlay_color' => '#1f2937',
                'hero_overlay_opacity' => 0.45,
                'hero_content_vertical_position' => 'middle',
                'meta_title' => 'Step 6 Rich CMS Fixture',
                'meta_description' => 'Local fixture for browser coverage of rich CMS page content.',
                'schema_type' => 'WebPage',
                'schema_data' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
            ]);

            Page::query()->create([
                'title' => 'Step 6 Simple CMS Browser Coverage',
                'slug' => self::SIMPLE_PAGE_SLUG,
                'content' => $this->document([
                    $this->heading('Simple CMS page', 2),
                    $this->paragraph('This published CMS fixture keeps the body plain for comparison with the rich CMS page.'),
                ]),
                'excerpt' => 'Simple published CMS comparison fixture.',
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            Page::query()->create([
                'title' => 'Step 6 Hidden Rich CMS Browser Coverage',
                'slug' => self::DRAFT_PAGE_SLUG,
                'content' => $this->document($this->richContentNodes('/pages/'.self::SIMPLE_PAGE_SLUG)),
                'excerpt' => 'Draft CMS fixture used to confirm unpublished rich CMS content stays hidden.',
                'status' => Page::STATUS_DRAFT,
                'published_at' => null,
            ]);
        });
    }

    private function mediaAttachmentPayload($media): array
    {
        return $media
            ->values()
            ->mapWithKeys(fn (Media $item, int $index): array => [
                $item->id => ['is_featured' => $index === 0],
            ])
            ->all();
    }

    private function richContentNodes(string $internalHref): array
    {
        return [
            $this->heading('A very long rich content heading that should wrap cleanly across mobile and desktop article containers without overflowing the viewport', 2),
            $this->paragraph('This published local fixture follows the normal API path and includes an inline internal link plus an external source link for renderer coverage.', [
                ['text' => 'internal comparison link', 'href' => $internalHref],
                ['text' => 'external planning reference', 'href' => 'https://example.com/planning'],
            ]),
            $this->paragraph('Plain long URL coverage: '.self::LONG_URL),
            [
                'type' => 'blockquote',
                'content' => [
                    $this->paragraph('A quoted field note with enough words to check indentation, wrapping, and spacing when the article body is rendered from real seeded content.'),
                ],
            ],
            $this->paragraph('This paragraph is intentionally long so browser checks can verify line-height, container width, and wrapping behavior. It repeats realistic travel-editorial copy about route planning, local transport, booking windows, weather changes, and content previews without relying on handmade placeholder DOM in the frontend test.'),
            [
                'type' => 'image',
                'attrs' => [
                    'src' => '/api/media/1',
                    'alt' => 'Seeded gallery media used inside rich content',
                    'title' => 'Inline rich media fixture',
                ],
            ],
            [
                'type' => 'video',
                'attrs' => [
                    'src' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
                    'title' => 'Fixture video',
                ],
            ],
            [
                'type' => 'iframe',
                'attrs' => [
                    'src' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
                    'title' => 'Fixture iframe',
                ],
            ],
            [
                'type' => 'embed',
                'attrs' => [
                    'src' => 'https://player.vimeo.com/video/76979871',
                    'title' => 'Fixture embedded media',
                ],
            ],
        ];
    }

    private function document(array $content): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => $content,
        ], JSON_THROW_ON_ERROR);
    }

    private function heading(string $text, int $level): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => $level],
            'content' => [$this->text($text)],
        ];
    }

    private function paragraph(string $text, array $links = []): array
    {
        if ($links === []) {
            return [
                'type' => 'paragraph',
                'content' => [$this->text($text)],
            ];
        }

        $content = [$this->text($text.' ')];
        foreach ($links as $link) {
            $content[] = [
                'type' => 'text',
                'text' => $link['text'],
                'marks' => [[
                    'type' => 'link',
                    'attrs' => ['href' => $link['href']],
                ]],
            ];
            $content[] = $this->text(' ');
        }

        return [
            'type' => 'paragraph',
            'content' => $content,
        ];
    }

    private function text(string $text): array
    {
        return [
            'type' => 'text',
            'text' => $text,
        ];
    }
}
