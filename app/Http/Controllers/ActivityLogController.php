<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Reports/ActivityLog', [
            'logs' => $logs,
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }
}
