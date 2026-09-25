<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Analytics\DashboardReport;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show how the store is performing over the chosen period.
     */
    public function __invoke(Request $request, SiteSettings $settings): Response
    {
        $days = in_array($request->integer('period'), DashboardReport::PERIODS, true)
            ? $request->integer('period')
            : 30;

        return Inertia::render('Dashboard', (new DashboardReport($days, $settings))->toArray());
    }
}
