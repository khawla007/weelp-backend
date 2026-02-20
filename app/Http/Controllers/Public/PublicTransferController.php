<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Transfer;

class PublicTransferController extends Controller
{
    public function index(): JsonResponse
    {
        $transfers = Transfer::with([
            'vendorRoutes.vendor',
            'vendorRoutes.route',
            'pricingAvailability.pricingTier',
            'pricingAvailability.availability',
            'media',
            'seo'
        ])->get();

        // return response()->json([
        //     'success' => true,
        //     'data' => $transfers
        // ]);
        if (collect($transfers)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfers not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $transfers
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $transfer = Transfer::with([
            'vendorRoutes.vendor',
            'vendorRoutes.route',
            'pricingAvailability.pricingTier',
            'pricingAvailability.availability',
            'media',
            'seo'
        ])->find($id);

        // if (!$transfer) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Transfer not found'
        //     ], 404);
        // }

        // return response()->json([
        //     'success' => true,
        //     'data' => $transfer
        // ]);
        if (empty($transfer)) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $transfer
        ], 200);
    }
}
