<?php

namespace App\Jobs;

use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SinkronisasiPresensiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $logId;

    public function __construct($logId)
    {
        $this->logId = $logId;
    }

  public function handle()
{
    $log = PresensiLog::find($this->logId);

    if (!$log) {
        return;
    }

    $controller = new PresensiApiController();

    $method = new \ReflectionMethod(
        PresensiApiController::class,
        'prosesSinkronisasi'
    );

    $method->setAccessible(true);

    $method->invoke($controller, $log);
}
}