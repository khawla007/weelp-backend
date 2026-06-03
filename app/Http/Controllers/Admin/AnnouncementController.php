<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        // Small volume — return a plain collection (no pagination) so the admin
        // UI never silently drops rows past a page boundary.
        return response()->json([
            'success' => true,
            'data' => Announcement::latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($request));

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $announcement = Announcement::create(array_merge(
            $validator->validated(),
            ['created_by' => Auth::id()]
        ));

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $validator = Validator::make($request->all(), $this->rules($request, sometimes: true));

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $announcement->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $announcement->delete();

        return response()->json(['success' => true, 'message' => 'Announcement deleted']);
    }

    private function rules(Request $request, bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'type' => "$req|in:offer,update,news",
            'title' => "$req|string|max:255",
            'message' => "$req|string|max:5000",
            // Allow relative deep-links (e.g. /cities/dubai/activities/x) AND
            // absolute http(s) URLs. A bare `string` would allow `javascript:`/
            // `data:` which reach the public bell as an <a href> — so constrain
            // the scheme with a regex.
            'link' => ['nullable', 'string', 'max:2048', 'regex:/^(\/(?!\/)|https?:\/\/)/i'],
            'is_active' => 'sometimes|boolean',
            'publish_at' => 'nullable|date',
            // Only enforce ordering when BOTH dates are present. `after:publish_at`
            // fails the comparison when publish_at is null (the common
            // "show now, hide later" case), so guard it with a closure.
            'expires_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $publishAt = $request->input('publish_at');
                    if ($publishAt && strtotime($value) <= strtotime($publishAt)) {
                        $fail('The expires at must be after publish at.');
                    }
                },
            ],
            'display_style' => 'nullable|in:inline,popup',
            'image_url' => 'nullable|string|max:2048',
            'coupon_code' => 'nullable|string|max:64',
        ];
    }
}
