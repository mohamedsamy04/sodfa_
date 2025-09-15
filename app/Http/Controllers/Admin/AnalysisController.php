<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DashboardService;

class AnalysisController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        return response()->json($dashboardService->getStats());
    }
}
