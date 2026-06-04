<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Presensi;
use App\Models\Karyawan;

class PresensiApiController extends Controller
{
    public function import(Request $request)
    {

        /*
        |--------------------------------------------------------------------------
        | VALIDASI API KEY
        |--------------------------------------------------------------------------
        */

        if ($request->api_key != 'SIMPATIK2026') {

            return response()->json([

                'success' => false,
                'message' => 'API KEY SALAH'

            ], 401);
        }



        /*
        |--------------------------------------------------------------------------
        | VALIDASI DATA
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'data' => 'required|array'

        ]);



        /*
        |--------------------------------------------------------------------------
        | LOOP DATA PRESENSI
        |--------------------------------------------------------------------------
        */

        foreach ($request->data as $item) {

            /*
            |--------------------------------------------------------------------------
            | CEK PIN KARYAWAN
            |--------------------------------------------------------------------------
            */

            $karyawan = Karyawan::where('pin', $item['pin'])
                ->first();

            if (!$karyawan) {

                continue;
            }



            /*
            |--------------------------------------------------------------------------
            | CEK DUPLIKAT
            |--------------------------------------------------------------------------
            */

            $cek = Presensi::where('karyawan_id', $karyawan->id)
                ->where('tanggal', $item['tanggal'])
                ->where('jam_masuk', $item['jam_masuk'])
                ->first();

            if ($cek) {

                continue;
            }



            /*
            |--------------------------------------------------------------------------
            | STATUS PRESENSI
            |--------------------------------------------------------------------------
            */

            $status = 'Tepat Waktu';

            if ($item['jam_masuk'] > '08:00:00') {

                $status = 'Terlambat';
            }



            /*
            |--------------------------------------------------------------------------
            | SIMPAN DATA
            |--------------------------------------------------------------------------
            */

            Presensi::create([

                'karyawan_id' => $karyawan->id,

                'tanggal' => $item['tanggal'],

                'jam_masuk' => $item['jam_masuk'],

                'status' => $status

            ]);
        }



        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,
            'message' => 'Data presensi berhasil di import'

        ]);
    }
}