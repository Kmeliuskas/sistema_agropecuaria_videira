<?php

namespace App\Http\Controllers\Web;

use App\Application\Services\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        abort_unless(request()->user()->can('reports.view'), 403);

        $data = $this->dashboard->summary();

        return view('dashboard', [
            'totals' => $data['totals'],
            'stockAlerts' => $data['stock_alerts'],
            'abcCurve' => $data['abc_curve'],
            'pendingRequests' => $data['pending_requests'],
            'activeInventories' => $data['active_inventories'],
            'expiringProducts' => $data['expiring_products'],
            'expiredProducts' => $data['expired_products'],
            'movements30d' => $data['movements_30d'],
        ]);
    }

    public function movementsChart(): JsonResponse
    {
        abort_unless(request()->user()->can('reports.view'), 403);

        $data = $this->dashboard->summary();

        return response()->json($data['movements_30d']);
    }
}
