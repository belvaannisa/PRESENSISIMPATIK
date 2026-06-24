<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PresensiLog;

class PresensiLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $logs = PresensiLog::with('karyawan')
            ->when($search, function ($query) use ($search) {

                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('pin', 'like', "%{$search}%")
                      ->orWhere('status_sinkron', 'like', "%{$search}%");

            })
            ->latest()
            ->paginate(20);

        return view(
            'presensi_log.index',
            compact('logs', 'search')
        );
    }
}