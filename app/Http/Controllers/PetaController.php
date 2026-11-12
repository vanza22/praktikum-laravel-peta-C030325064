<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\Geometri;

class PetaController extends Controller
{
    public function index()
    {
        return view('peta.index');
    }

    public function cari()
    {
        return view('peta.cari');
    }

    public function klaster()
    {
        return view('peta.klaster');
    }
    public function panas()
    {
        return view('peta.panas');
    }

    public function wilayah()
    {
        return view('peta.wilayah');
    }

    public function geojsonWilayah(Request $request)
    {
        $features = Sekolah::with('profil')
            ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
            ->get()
            ->groupBy('kecamatan')
            ->map(function ($kelompok, $kecamatan) {
                $titik = $kelompok
                    ->map(fn ($s) => [$s->longitude, $s->latitude])
                    ->all();

                $hull = Geometri::convexHull($titik);

                // Poligon memerlukan minimal 3 titik yang tidak segaris
                if (count($hull) < 3) {
                    return null;
                }

                $hull[] = $hull[0]; // GeoJSON: cincin poligon harus tertutup

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$hull],
                    ],
                    'properties' => [
                        'kecamatan'      => $kecamatan,
                        'jumlah_sekolah' => $kelompok->count(),
                        'jumlah_siswa'   => (int) $kelompok->sum(
                            fn ($s) => $s->profil?->jumlah_siswa ?? 0
                        ),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function rute()
    {
        return view('peta.rute');
    }

    public function alamat()
    {
        return view('peta.alamat');
    }

    public function geojson(Request $request)
    {
        $features = Sekolah::with('profil')
            ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->get()
            ->map(fn ($s) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$s->longitude, $s->latitude],
                ],
                'properties' => [
                    'id'              => $s->id,
                    'npsn'            => $s->npsn,
                    'nama'            => $s->nama,
                    'jenjang'         => $s->jenjang,
                    'status'          => $s->status,
                    'alamat'          => $s->alamat,
                    'kecamatan'       => $s->kecamatan,
                    'telepon'         => $s->telepon,
                    'kepala_sekolah'  => $s->profil?->kepala_sekolah,
                    'akreditasi'      => $s->profil?->akreditasi,
                    'jumlah_siswa'    => $s->profil?->jumlah_siswa,
                    'jumlah_guru'     => $s->profil?->jumlah_guru,
                    'logo' => $s->profil?->logo
                        ? Storage::url($s->profil->logo)
                        : null,
                ],
            ]);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
