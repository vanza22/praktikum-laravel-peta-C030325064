<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SekolahController extends Controller
{
    public function index(Request $request)
    {
        $sekolahs = Sekolah::query()
            ->when($request->q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nama', 'like', "%{$q}%")
                      ->orWhere('npsn', 'like', "%{$q}%")
                      ->orWhere('kecamatan', 'like', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.sekolah.index', compact('sekolahs'));
    }

    public function create()
    {
        return view('admin.sekolah.create');
    }

    public function store(Request $request)
    {
        Sekolah::create($request->validate($this->rules()));

        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        return view('admin.sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $data = $request->validate($this->rules($sekolah->id));

        if ($request->user()->isSekolah()) {
            // NPSN tidak boleh diubah oleh akun sekolah
            unset($data['npsn']);

            // Menghitung jarak perubahan koordinat
            $latAwal = deg2rad((float) $sekolah->latitude);
            $lngAwal = deg2rad((float) $sekolah->longitude);

            $latBaru = deg2rad((float) $data['latitude']);
            $lngBaru = deg2rad((float) $data['longitude']);

            $deltaLat = $latBaru - $latAwal;
            $deltaLng = $lngBaru - $lngAwal;

            $a = sin($deltaLat / 2) ** 2
                + cos($latAwal) * cos($latBaru)
                * sin($deltaLng / 2) ** 2;

            $jarak = 2 * 6371000 * asin(min(1, sqrt($a)));

            // Maksimal perubahan koordinat adalah 500 meter
            if ($jarak > 500) {
                throw ValidationException::withMessages([
                    'latitude' => 'Perubahan lokasi sekolah tidak boleh lebih dari 500 meter.',
                ]);
            }
        }

        $sekolah->update($data);

        return $this->kembali($request)
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Sekolah $sekolah)
    {
        $sekolah->delete();

        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil dihapus.');
    }

    private function kembali(Request $request)
    {
        return $request->user()->isDinas()
            ? redirect()->route('dinas.sekolah.index')
            : redirect()->route('dashboard');
    }

    private function rules(?int $id = null): array
    {
        return [
            'npsn' => [
                $id ? 'sometimes' : 'required',
                'digits:8',
                Rule::unique('sekolahs', 'npsn')->ignore($id),
            ],
            'nama'      => ['required', 'string', 'max:255'],
            'jenjang'   => ['required', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status'    => ['required', Rule::in(['Negeri', 'Swasta'])],
            'alamat'    => ['required', 'string'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'telepon'   => ['nullable', 'string', 'max:20'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}