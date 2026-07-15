<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => fake()->paragraph(),
                    ]],
                ]],
            ]),
            'excerpt' => fake()->sentence(),
            'status' => Page::STATUS_DRAFT,
            'published_at' => null,
            'hero_background_image_url' => null,
            'hero_heading' => null,
            'hero_text' => null,
            'hero_button_label' => null,
            'hero_button_url' => null,
            'hero_overlay_color' => null,
            'hero_overlay_opacity' => null,
            'hero_content_vertical_position' => null,
            'hero_heading_size' => null,
            'hero_heading_color' => null,
            'hero_heading_align' => null,
            'hero_heading_bold' => null,
            'hero_heading_italic' => null,
            'hero_heading_underline' => null,
            'hero_text_size' => null,
            'hero_text_color' => null,
            'hero_text_align' => null,
            'hero_text_bold' => null,
            'hero_text_italic' => null,
            'hero_text_underline' => null,
            'hero_button_radius' => null,
            'hero_button_border_width' => null,
            'hero_button_padding' => null,
            'hero_button_margin' => null,
            'hero_button_text_color' => null,
            'hero_button_bg_color' => null,
            'hero_button_border_color' => null,
            'hero_button_text_size' => null,
            'hero_button_align' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function richContentFixture(): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => 'Rich CMS fixture',
            'slug' => fake()->unique()->slug(),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 2],
                        'content' => [['type' => 'text', 'text' => 'Rich CMS fixture']],
                    ],
                    [
                        'type' => 'video',
                        'attrs' => [
                            'src' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
                            'title' => 'Fixture video',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
