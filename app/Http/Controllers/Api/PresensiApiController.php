<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresensiLog;
use Illuminate\Http\Request;

class PresensiApiController extends Controller
{
    public function importFolder()
    {
        $folder =
        'C:/Users/LENOVO/datapresensi/incoming/';

       $files = glob(
        $incomingPath . '*.{csv,xls,xlsx}',
        GLOB_BRACE
    );
        if(!$files)
        {
            return back()
            ->with(
                'error',
                'File tidak ditemukan'
            );
        }

        foreach($files as $file)
        {
            $file = $request->file('file');

    $this->prosesFile(
        $file->getRealPath()
    );

    $request->validate([
    'file' => 'required|mimes:csv,xls,xlsx,txt'
    ]);
            foreach($rows as $index => $row)
            {
                if($index == 0)
                {
                    continue;
                }

                if(count($row) < 8)
                {
                    continue;
                }

                [
                    $department,
                    $nama,
                    $pin,
                    $datetime,
                    $location,
                    $idNumber,
                    $verifyCode,
                    $cardNo
                ] = $row;

                $dt =
                \Carbon\Carbon::parse(
                    $datetime
                );

                PresensiLog::create([
                    'pin' => trim($pin),
                    'nama' => trim($nama),
                    'tanggal' => $dt->format('Y-m-d'),
                    'jam' => $dt->format('H:i:s'),
                    'verify_code' => $verifyCode,
                    'status_sinkron' => 'pending'
                ]);
            }
        }

        return back()->with(
            'success',
            'Import berhasil'
        );
    }
}