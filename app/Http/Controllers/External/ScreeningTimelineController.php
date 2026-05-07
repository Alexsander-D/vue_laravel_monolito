<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\External\ScreeningTimeline;
use Illuminate\Http\Request;

class ScreeningTimelineController extends Controller
{
    public function index(Request $request)
    {
        $screening_id = $request->query('screening_id');

        $timelineRecords = $screening_id
            ? ScreeningTimeline::where('screening_id', $screening_id)->get()
            : [];

        return Inertia::render('ExternalService/CustomerService/ScreeningTimeline', [
            'timelineRecords' => $timelineRecords,
            'screening_id' => $screening_id
        ]);
    }

    public function show($screening_id)
    {
        $timeline = ScreeningTimeline::where('screening_id', $screening_id)
            ->orderBy('created_at', 'desc')
            ->get(['screening_id', 'description', 'responsible', 'route', 'created_at']);

        if ($timeline->isEmpty()) {
            return response()->json(['message' => 'Nenhum registro encontrado.'], 404);
        }

        return response()->json(['data' => $timeline]);
    }

}
