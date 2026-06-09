<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalScriptSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalScriptSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => GlobalScriptSetting::singleton(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'head_code' => 'sometimes|nullable|string|max:20000',
            'body_code' => 'sometimes|nullable|string|max:20000',
            'footer_code' => 'sometimes|nullable|string|max:20000',
        ]);

        $settings = GlobalScriptSetting::singleton();
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }
}
