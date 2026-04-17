<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransferZone;
use App\Models\TransferZonePrice;
use Illuminate\Http\Request;

class TransferZonePriceController extends Controller
{
    public function index()
    {
        $zones = TransferZone::query()
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name', 'slug', 'sort_order', 'is_active']);

        $cells = TransferZonePrice::query()
            ->get(['id', 'from_zone_id', 'to_zone_id', 'price', 'currency']);

        return response()->json([
            'zones' => $zones,
            'cells' => $cells,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'from_zone_id' => 'required|integer|exists:transfer_zones,id',
            'to_zone_id'   => 'required|integer|exists:transfer_zones,id',
            'price'        => 'required|numeric|min:0',
            'currency'     => 'nullable|string|max:10',
        ]);

        $cell = TransferZonePrice::updateOrCreate(
            [
                'from_zone_id' => $data['from_zone_id'],
                'to_zone_id'   => $data['to_zone_id'],
            ],
            [
                'price'    => $data['price'],
                'currency' => $data['currency'] ?? 'USD',
            ]
        );

        return response()->json($cell);
    }

    public function bulkUpsert(Request $request)
    {
        $data = $request->validate([
            'cells'                => 'required|array|min:1',
            'cells.*.from_zone_id' => 'required|integer|exists:transfer_zones,id',
            'cells.*.to_zone_id'   => 'required|integer|exists:transfer_zones,id',
            'cells.*.price'        => 'required|numeric|min:0',
            'cells.*.currency'     => 'nullable|string|max:10',
        ]);

        $saved = 0;
        foreach ($data['cells'] as $cell) {
            TransferZonePrice::updateOrCreate(
                [
                    'from_zone_id' => $cell['from_zone_id'],
                    'to_zone_id'   => $cell['to_zone_id'],
                ],
                [
                    'price'    => $cell['price'],
                    'currency' => $cell['currency'] ?? 'USD',
                ]
            );
            $saved++;
        }

        return response()->json(['saved' => $saved]);
    }
}
