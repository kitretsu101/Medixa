<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabReportController extends Controller
{
    /**
     * Display the specified lab report.
     */
    public function show(Request $request, LabReport $labReport): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $labReport,
        ]);
    }
}
