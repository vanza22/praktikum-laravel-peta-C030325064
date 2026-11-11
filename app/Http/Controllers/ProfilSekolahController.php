<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfilSekolahController extends Controller
{
    public function show(Sekolah $sekolah)
    {
        $profil = $sekolah->profil;

        return view('sekolah.profil-publik', compact('sekolah', 'profil'));
    }

    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $profil = $sekolah->profil ?? new ProfilSekolah();

        return view('sekolah.profil', compact('sekolah', 'profil'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $data = $request->validate([
            'kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'email'          => ['nullable', 'email', 'max:150'],
            'website'        => ['nullable', 'url', 'max:255'],
            'tahun_berdiri'  => ['nullable', 'integer',
                                 'between:1800,' . date('Y')],
            'akreditasi'     => ['nullable', Rule::in(
                                 ['A', 'B', 'C', 'Belum Terakreditasi'])],
            'jumlah_siswa'   => ['nullable', 'integer', 'min:0'],
            'jumlah_guru'    => ['nullable', 'integer', 'min:0'],
            'deskripsi'      => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $profilLama = $sekolah->profil;

            if ($profilLama && $profilLama->logo) {
                Storage::disk('public')->delete($profilLama->logo);
            }

            $data['logo'] = $request->file('logo')->store('logo-sekolah', 'public');
        }

        $sekolah->profil()->updateOrCreate([], $data);

        return back()->with('success', 'Profil sekolah berhasil disimpan.');
    }
}