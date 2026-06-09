<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\GlobalScriptSetting;
use Illuminate\Http\JsonResponse;

class PublicGlobalScriptController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => GlobalScriptSetting::first() ?? [
                'head_code' => null,
                'body_code' => null,
                'footer_code' => null,
            ],
        ]);
    }
}
