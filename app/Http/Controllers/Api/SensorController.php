<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\Threshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    /**
     * GET /api/sensor/realtime
     * Mengembalikan data sensor terbaru dari database.
     */
    public function realtime(): JsonResponse
    {
        $latest = SensorData::latest('recorded_at')->first();

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data sensor.',
                'data'    => null,
            ]);
        }

        $threshold = Threshold::current();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $latest->id,
                'temperature' => $latest->temperature,
                'humidity'    => $latest->humidity,
                'status'      => $latest->status,
                'recorded_at' => $latest->recorded_at->toISOString(),
                'threshold'   => [
                    'temp_min'     => $threshold->temp_min,
                    'temp_max'     => $threshold->temp_max,
                    'humidity_min' => $threshold->humidity_min,
                    'humidity_max' => $threshold->humidity_max,
                ],
            ],
        ]);
    }

    /**
     * GET /api/sensor/history?start=&end=&limit=
     * Mengembalikan riwayat data sensor dengan filter waktu.
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end'   => 'nullable|date|after_or_equal:start',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = SensorData::query()->orderByDesc('recorded_at');

        if ($request->filled('start')) {
            $query->where('recorded_at', '>=', $request->start);
        }

        if ($request->filled('end')) {
            // Tambah 1 hari agar end date inklusif sampai akhir hari
            $query->where('recorded_at', '<=',
                \Carbon\Carbon::parse($request->end)->endOfDay()
            );
        }

        $limit = $request->input('limit', 100);
        $data  = $query->limit($limit)->get([
            'id', 'temperature', 'humidity', 'status', 'recorded_at',
        ]);

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data->map(fn($row) => [
                'id'          => $row->id,
                'temperature' => $row->temperature,
                'humidity'    => $row->humidity,
                'status'      => $row->status,
                'recorded_at' => $row->recorded_at->toISOString(),
            ]),
        ]);
    }
}
