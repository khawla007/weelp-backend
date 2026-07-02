<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\RichTextContent;
use App\Support\SeoPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    private const HERO_STYLE_FIELDS = [
        'hero_overlay_color',
        'hero_overlay_opacity',
        'hero_content_vertical_position',
        'hero_heading_size',
        'hero_heading_color',
        'hero_heading_align',
        'hero_heading_bold',
        'hero_heading_italic',
        'hero_heading_underline',
        'hero_text_size',
        'hero_text_color',
        'hero_text_align',
        'hero_text_bold',
        'hero_text_italic',
        'hero_text_underline',
        'hero_button_radius',
        'hero_button_border_width',
        'hero_button_padding',
        'hero_button_margin',
        'hero_button_text_color',
        'hero_button_bg_color',
        'hero_button_border_color',
        'hero_button_text_size',
        'hero_button_align',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 3), 1), 3);
        $page = (int) $request->get('page', 1);
        $search = $request->get('search');
        $status = $request->get('status');
        $sortBy = $request->get('sort_by', 'latest');

        $query = Page::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            }))
            ->when(in_array($status, [Page::STATUS_DRAFT, Page::STATUS_PUBLISHED], true), fn ($query) => $query->where('status', $status));

        match ($sortBy) {
            'oldest', 'id_asc' => $query->orderBy('id', 'asc'),
            'title_asc', 'name_asc' => $query->orderBy('title', 'asc'),
            'title_desc', 'name_desc' => $query->orderBy('title', 'desc'),
            'published_first' => $query->orderByRaw("case when status = ? then 0 else 1 end", [Page::STATUS_PUBLISHED])->orderByDesc('id'),
            'draft_first' => $query->orderByRaw("case when status = ? then 0 else 1 end", [Page::STATUS_DRAFT])->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        $allItems = $query->get();
        $paginatedItems = $allItems->forPage($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedItems->map(fn (Page $page): array => $this->serialize($page, false))->values(),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $allItems->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'content' => ['required', 'string', fn ($attribute, $value, $fail) => RichTextContent::hasContent($value) ?: $fail('The content field is required.')],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'status' => ['required', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'hero_background_image_url' => ['sometimes', 'nullable', 'string'],
            'hero_heading' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_text' => ['sometimes', 'nullable', 'string'],
            'hero_button_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_button_url' => ['sometimes', 'nullable', 'string'],
        ], $this->heroStyleRules(), SeoPayload::rules()));

        $page = Page::create(array_merge(
            collect($validated)->except('seo')->all(),
            SeoPayload::normalize((array) $request->input('seo', []))
        ));

        return response()->json([
            'message' => 'Page created successfully',
            'data' => $this->serialize($page),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        return response()->json([
            'data' => $this->serialize($page),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate(array_merge([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page->id)],
            'content' => ['sometimes', 'string', fn ($attribute, $value, $fail) => RichTextContent::hasContent($value) ?: $fail('The content field is required.')],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'hero_background_image_url' => ['sometimes', 'nullable', 'string'],
            'hero_heading' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_text' => ['sometimes', 'nullable', 'string'],
            'hero_button_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_button_url' => ['sometimes', 'nullable', 'string'],
        ], $this->heroStyleRules(), SeoPayload::rules()));

        $page->fill(collect($validated)->except('seo')->all());

        if ($request->has('seo')) {
            $page->fill(SeoPayload::normalize((array) $request->input('seo', []), false));
        }

        $page->save();

        return response()->json([
            'message' => 'Page updated successfully',
            'data' => $this->serialize($page),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        Page::findOrFail($id)->delete();

        return response()->json(['message' => 'Page deleted successfully']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_ids' => ['required', 'array'],
            'page_ids.*' => ['integer'],
        ]);

        $existingIds = Page::whereIn('id', $validated['page_ids'])->pluck('id')->all();

        if ($existingIds !== []) {
            Page::whereIn('id', $existingIds)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pages deleted successfully.',
            'deleted_ids' => $existingIds,
            'ignored_ids' => array_values(array_diff($validated['page_ids'], $existingIds)),
        ]);
    }

    private function serialize(Page $page, bool $includeContent = true): array
    {
        $payload = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $includeContent ? $page->content : null,
            'excerpt' => $page->excerpt,
            'status' => $page->status,
            'published_at' => $page->published_at,
            'hero_background_image_url' => $page->hero_background_image_url,
            'hero_heading' => $page->hero_heading,
            'hero_text' => $page->hero_text,
            'hero_button_label' => $page->hero_button_label,
            'hero_button_url' => $page->hero_button_url,
            'seo' => SeoPayload::fromModel($page),
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ];

        foreach (self::HERO_STYLE_FIELDS as $field) {
            $payload[$field] = $page->{$field};
        }

        return array_filter($payload, fn ($value, string $key): bool => str_starts_with($key, 'hero_') || $value !== null, ARRAY_FILTER_USE_BOTH);
    }

    private function heroStyleRules(): array
    {
        return [
            'hero_overlay_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_overlay_opacity' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1'],
            'hero_content_vertical_position' => ['sometimes', 'nullable', Rule::in(['top', 'middle', 'bottom'])],
            'hero_heading_size' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_heading_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_heading_align' => ['sometimes', 'nullable', Rule::in(['left', 'center', 'right'])],
            'hero_heading_bold' => ['sometimes', 'nullable', 'boolean'],
            'hero_heading_italic' => ['sometimes', 'nullable', 'boolean'],
            'hero_heading_underline' => ['sometimes', 'nullable', 'boolean'],
            'hero_text_size' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_text_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_text_align' => ['sometimes', 'nullable', Rule::in(['left', 'center', 'right'])],
            'hero_text_bold' => ['sometimes', 'nullable', 'boolean'],
            'hero_text_italic' => ['sometimes', 'nullable', 'boolean'],
            'hero_text_underline' => ['sometimes', 'nullable', 'boolean'],
            'hero_button_radius' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_border_width' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_padding' => ['sometimes', 'nullable', 'string', 'max:64'],
            'hero_button_margin' => ['sometimes', 'nullable', 'string', 'max:64'],
            'hero_button_text_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_bg_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_border_color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_text_size' => ['sometimes', 'nullable', 'string', 'max:32'],
            'hero_button_align' => ['sometimes', 'nullable', Rule::in(['left', 'center', 'right'])],
        ];
    }
}
