<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnomalyLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnomalyController extends Controller
{
    /**
     * GET /api/anomaly
     * Riwayat log anomali dengan filter opsional.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type'        => 'nullable|in:temp_high,temp_low,humidity_high,humidity_low',
            'severity'    => 'nullable|in:warning,critical',
            'is_resolved' => 'nullable|boolean',
            'limit'       => 'nullable|integer|min:1|max:200',
        ]);

        $query = AnomalyLog::with('sensorData:id,recorded_at')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('is_resolved')) {
            $query->where('is_resolved', $request->boolean('is_resolved'));
        }

        $logs = $query->limit($request->input('limit', 50))->get();

        return response()->json([
            'success' => true,
            'count'   => $logs->count(),
            'data'    => $logs->map(fn($log) => [
                'id'              => $log->id,
                'type'            => $log->type,
                'value'           => $log->value,
                'threshold_value' => $log->threshold_value,
                'severity'        => $log->severity,
                'is_resolved'     => $log->is_resolved,
                'resolved_at'     => $log->resolved_at?->toISOString(),
                'recorded_at'     => $log->sensorData?->recorded_at?->toISOString(),
                'created_at'      => $log->created_at->toISOString(),
            ]),
        ]);
    }
}
