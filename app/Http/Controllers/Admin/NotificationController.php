<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'image_media_ids' => 'nullable|array',
            'image_media_ids.*' => 'integer|exists:media,id',
            'action_url' => ['nullable', 'string', 'max:2048', 'regex:/^(\/(?!\/)|https?:\/\/)/i'],
            'display_style' => 'nullable|in:inline,popup',
            'target_type' => 'required|in:user,role',
            'target_user_id' => 'required_if:target_type,user|integer|exists:users,id',
            'target_role' => 'required_if:target_type,role|in:customer,creator,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $v = $validator->validated();

        $data = null;
        if (!empty($v['image_media_ids'])) {
            $urls = Media::whereIn('id', $v['image_media_ids'])->get()->pluck('url')->all();
            $data = ['images' => $urls];
        }

        $now = now();
        $base = [
            'type' => 'custom',
            'title' => $v['title'],
            'message' => $v['message'],
            'data' => $data ? json_encode($data) : null,
            'action_url' => $v['action_url'] ?? null,
            'display_style' => $v['display_style'] ?? 'inline',
            'created_by' => Auth::id(),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $count = 0;

        if ($v['target_type'] === 'user') {
            Notification::create(array_merge($base, [
                'user_id' => $v['target_user_id'],
                'data' => $data, // model casts array → encodes here
            ]));
            $count = 1;
        } else {
            $query = User::query();
            if ($v['target_role'] === 'creator') {
                $query->where('is_creator', true);
            } else {
                $query->where('role', $v['target_role']);
            }

            $query->chunkById(500, function ($users) use (&$count, $base) {
                $rows = [];
                foreach ($users as $u) {
                    $rows[] = array_merge($base, ['user_id' => $u->id]);
                }
                Notification::insert($rows); // bulk: data already json string in $base
                $count += count($rows);
            });
        }

        return response()->json(['success' => true, 'count' => $count]);
    }
}
