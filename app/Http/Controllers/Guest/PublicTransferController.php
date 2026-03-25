<?php

namespace App\Http\Controllers\Guest;

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
            'mediaGallery.media',
            'seo'
        ])->get()->map(function ($transfer) {
            $data = $transfer->toArray();
            $featuredImage = $transfer->mediaGallery->where('is_featured', true)->first();
            $data['featured_image'] = $featuredImage?->media?->url
                ?? $transfer->mediaGallery->first()?->media?->url;
            $data['media_gallery'] = $transfer->mediaGallery->map(function ($media) {
                return [
                    'id' => $media->media->id,
                    'name' => $media->media->name,
                    'alt_text' => $media->media->alt_text,
                    'url' => $media->media->url,
                    'is_featured' => (bool) $media->is_featured,
                ];
            })->toArray();
            unset($data['media_gallery_raw']);
            return $data;
        });

        if ($transfers->isEmpty()) {
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
            'mediaGallery.media',
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

        $data = $transfer->toArray();
        $featuredImage = $transfer->mediaGallery->where('is_featured', true)->first();
        $data['featured_image'] = $featuredImage?->media?->url
            ?? $transfer->mediaGallery->first()?->media?->url;
        $data['media_gallery'] = $transfer->mediaGallery->map(function ($media) {
            return [
                'id' => $media->media->id,
                'name' => $media->media->name,
                'alt_text' => $media->media->alt_text,
                'url' => $media->media->url,
                'is_featured' => (bool) $media->is_featured,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}
