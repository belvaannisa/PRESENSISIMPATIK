<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PresensiLog;
use App\Models\Presensi;
use App\Models\Karyawan;
use Carbon\Carbon;

class PresensiApiController extends Controller
{

    public function upload(Request $request)
    {

        $request->validate([

            'pin'=>'required',

            'nama'=>'required',

            'tanggal'=>'required',

            'jam'=>'required'

        ]);

        $cek = PresensiLog::where('pin',$request->pin)

            ->where('tanggal',$request->tanggal)

            ->where('jam',$request->jam)

            ->exists();

        if($cek){

            return response()->json([

                'success'=>true,

                'message'=>'Duplicate'

            ]);

        }

        $log = PresensiLog::create([

            'pin'=>$request->pin,

            'nama'=>$request->nama,

            'tanggal'=>$request->tanggal,

            'jam'=>$request->jam,

            'verify_code'=>'API',

            'status_sinkron'=>'pending'

        ]);

        $karyawan = Karyawan::where('pin',$log->pin)

            ->orWhere('nama',$log->nama)

            ->first();

        if($karyawan){

            $presensi = Presensi::firstOrCreate([

                'karyawan_id'=>$karyawan->id,

                'tanggal'=>$log->tanggal

            ]);

            if(!$presensi->jam_masuk || $log->jam < $presensi->jam_masuk){

                $presensi->jam_masuk=$log->jam;

            }

            if(!$presensi->jam_keluar || $log->jam > $presensi->jam_keluar){

                $presensi->jam_keluar=$log->jam;

            }

            $presensi->status=

                strtotime($presensi->jam_masuk)>

                strtotime('08:15:00')

                ?

                'Terlambat'

                :

                'Tepat Waktu';

            $presensi->save();

            $log->update([

                'status_sinkron'=>'matched',

                'karyawan_id'=>$karyawan->id

            ]);

        }

        return response()->json([

            'success'=>true,

            'message'=>'Berhasil',

            'data'=>$log

        ]);

    }

}