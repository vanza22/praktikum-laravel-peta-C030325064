<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Sekolah;
use Illuminate\Support\Facades\Auth;

class SekolahObserver
{
    public function updated(Sekolah $sekolah): void
    {
        $perubahan = [];

        foreach ($sekolah->getChanges() as $kolom => $nilaiBaru) {
            if (in_array($kolom, ['updated_at'])) {
                continue;
            }

            $perubahan[$kolom] = [
                'sebelum' => $sekolah->getOriginal($kolom),
                'sesudah' => $nilaiBaru,
            ];
        }

        if (empty($perubahan)) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'aktivitas' => 'Memperbarui data sekolah',
            'model' => Sekolah::class,
            'model_id' => $sekolah->id,
            'perubahan' => $perubahan,
        ]);
    }
}