<?php

namespace Database\Factories;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'content' => fake()->paragraphs(3, true),
            'excerpt' => fake()->sentence(),
            'publish' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'publish' => false,
        ]);
    }

    public function richContentFixture(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Rich content fixture',
            'slug' => fake()->unique()->slug(),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 2],
                        'content' => [['type' => 'text', 'text' => 'Rich content fixture']],
                    ],
                    [
                        'type' => 'blockquote',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [['type' => 'text', 'text' => 'Quoted fixture copy.']],
                        ]],
                    ],
                    [
                        'type' => 'iframe',
                        'attrs' => [
                            'src' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
                            'title' => 'Fixture iframe',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'publish' => true,
        ]);
    }
}
