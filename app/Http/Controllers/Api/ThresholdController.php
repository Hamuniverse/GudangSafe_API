<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Threshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThresholdController extends Controller
{
    /**
     * GET /api/threshold
     */
    public function show(): JsonResponse
    {
        $threshold = Threshold::with('updatedBy:id,name')->firstOrCreate(
            ['id' => 1],
            [
                'temp_min'     => 15.00,
                'temp_max'     => 30.00,
                'humidity_min' => 40.00,
                'humidity_max' => 70.00,
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'temp_min'     => $threshold->temp_min,
                'temp_max'     => $threshold->temp_max,
                'humidity_min' => $threshold->humidity_min,
                'humidity_max' => $threshold->humidity_max,
                'updated_by'   => $threshold->updatedBy?->name,
                'updated_at'   => $threshold->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * PUT /api/threshold
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'temp_min'     => 'required|numeric|min:-10|max:50',
            'temp_max'     => 'required|numeric|min:-10|max:50|gt:temp_min',
            'humidity_min' => 'required|numeric|min:0|max:100',
            'humidity_max' => 'required|numeric|min:0|max:100|gt:humidity_min',
        ]);

        $threshold = Threshold::firstOrCreate(
            ['id' => 1],
            [
                'temp_min'     => 15.00,
                'temp_max'     => 30.00,
                'humidity_min' => 40.00,
                'humidity_max' => 70.00,
            ]
        );

        $threshold->update([
            'temp_min'     => $request->temp_min,
            'temp_max'     => $request->temp_max,
            'humidity_min' => $request->humidity_min,
            'humidity_max' => $request->humidity_max,
            'updated_by'   => $request->user()->id,
        ]);

        $threshold->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Threshold berhasil diperbarui.',
            'data'    => [
                'temp_min'     => $threshold->temp_min,
                'temp_max'     => $threshold->temp_max,
                'humidity_min' => $threshold->humidity_min,
                'humidity_max' => $threshold->humidity_max,
                'updated_at'   => $threshold->updated_at->toISOString(),
            ],
        ]);
    }
}
