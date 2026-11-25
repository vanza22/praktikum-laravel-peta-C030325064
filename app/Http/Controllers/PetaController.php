<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

                if (count($hull) < 3) {
                    return null;
                }

                $hull[] = $hull[0];

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [$hull],
                    ],
                    'properties' => [
                        'kecamatan' => $kecamatan,
                        'jumlah_sekolah' => $kelompok->count(),
                        'jumlah_siswa' => (int) $kelompok->sum(
                            fn ($s) => $s->profil?->jumlah_siswa ?? 0
                        ),
                    ],
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'type' => 'FeatureCollection',
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

    // Mengambil sekolah dalam radius tertentu menggunakan PostGIS
    public function sekolahDalamRadius(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:10000'],
        ]);

        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        $radius = $data['radius'] ?? 1000;

        $sekolahs = Sekolah::query()
            ->whereNotNull('lokasi')
            ->whereRaw(
                "extensions.ST_DWithin(
                    lokasi,
                    extensions.ST_SetSRID(
                        extensions.ST_MakePoint(?, ?),
                        4326
                    )::extensions.geography,
                    ?
                )",
                [$longitude, $latitude, $radius]
            )
            ->select('id', 'npsn', 'nama', 'latitude', 'longitude')
            ->selectRaw(
                "extensions.ST_Distance(
                    lokasi,
                    extensions.ST_SetSRID(
                        extensions.ST_MakePoint(?, ?),
                        4326
                    )::extensions.geography
                ) AS jarak_meter",
                [$longitude, $latitude]
            )
            ->orderBy('jarak_meter')
            ->get();

        return response()->json([
            'jumlah' => $sekolahs->count(),
            'data' => $sekolahs,
        ]);
    }

    public function geojson(Request $request)
    {
        // Validasi batas peta
        $batas = $request->validate([
            'north' => 'nullable|numeric|between:-90,90',
            'south' => 'nullable|numeric|between:-90,90',
            'east'  => 'nullable|numeric|between:-180,180',
            'west'  => 'nullable|numeric|between:-180,180',
        ]);

        // Membuat kunci cache berdasarkan filter
        $cacheKey = 'geojson_sekolah_' . md5(json_encode([
            'jenjang' => $request->jenjang,
            'status' => $request->status,
            'batas' => $batas,
        ]));

        // Menyimpan hasil query selama 5 menit
        $features = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($request, $batas) {

                $query = Sekolah::with('profil')
                    ->when($request->jenjang, fn ($q, $j) => $q->where('jenjang', $j))
                    ->when($request->status, fn ($q, $s) => $q->where('status', $s));

                if (
                    isset($batas['north'], $batas['south'], $batas['east'], $batas['west'])
                ) {
                    $query->whereBetween('latitude', [
                        $batas['south'],
                        $batas['north'],
                    ]);

                    $query->whereBetween('longitude', [
                        $batas['west'],
                        $batas['east'],
                    ]);
                }

                return $query->get()
                    ->map(fn ($s) => [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$s->longitude, $s->latitude],
                        ],
                        'properties' => [
                            'id' => $s->id,
                            'npsn' => $s->npsn,
                            'nama' => $s->nama,
                            'jenjang' => $s->jenjang,
                            'status' => $s->status,
                            'alamat' => $s->alamat,
                            'kecamatan' => $s->kecamatan,
                            'telepon' => $s->telepon,
                            'kepala_sekolah' => $s->profil?->kepala_sekolah,
                            'akreditasi' => $s->profil?->akreditasi,
                            'jumlah_siswa' => $s->profil?->jumlah_siswa,
                            'jumlah_guru' => $s->profil?->jumlah_guru,
                            'logo' => $s->profil?->logo
                                ? Storage::url($s->profil->logo)
                                : null,
                        ],
                    ])
                    ->all();
            }
        );

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}