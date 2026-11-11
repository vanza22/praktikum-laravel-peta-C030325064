<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil {{ $sekolah->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="max-w-4xl mx-auto px-4 py-8">

        <a href="{{ route('peta.index') }}"
            class="text-blue-600 hover:underline">
            &larr; Kembali ke Peta
        </a>

        <div class="bg-white rounded-lg shadow p-6 mt-4">

            @if ($profil && $profil->logo)
                <div class="text-center mb-6">
                    <img src="{{ asset('storage/' . $profil->logo) }}"
                        alt="Logo {{ $sekolah->nama }}"
                        class="w-28 h-28 object-contain mx-auto">
                </div>
            @endif

            <h1 class="text-2xl font-bold text-gray-800 text-center">
                {{ $sekolah->nama }}
            </h1>

            <p class="text-center text-gray-500 mt-2">
                NPSN: {{ $sekolah->npsn }}
            </p>

            <hr class="my-6">

            <h2 class="text-lg font-semibold mb-4">
                Informasi Sekolah
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <p class="text-sm text-gray-500">Jenjang</p>
                    <p class="font-medium">{{ $sekolah->jenjang }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="font-medium">{{ $sekolah->status }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Alamat</p>
                    <p class="font-medium">{{ $sekolah->alamat }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Kecamatan</p>
                    <p class="font-medium">{{ $sekolah->kecamatan }}</p>
                </div>

                @if ($profil)
                    <div>
                        <p class="text-sm text-gray-500">Kepala Sekolah</p>
                        <p class="font-medium">{{ $profil->kepala_sekolah ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $profil->email ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Website</p>
                        @if ($profil->website)
                            <a href="{{ $profil->website }}"
                                target="_blank"
                                class="text-blue-600 hover:underline">
                                {{ $profil->website }}
                            </a>
                        @else
                            <p class="font-medium">-</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Tahun Berdiri</p>
                        <p class="font-medium">{{ $profil->tahun_berdiri ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Akreditasi</p>
                        <p class="font-medium">{{ $profil->akreditasi ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Siswa</p>
                        <p class="font-medium">{{ $profil->jumlah_siswa ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Guru</p>
                        <p class="font-medium">{{ $profil->jumlah_guru ?? '-' }}</p>
                    </div>
                @endif

            </div>

            @if ($profil && $profil->deskripsi)
                <hr class="my-6">

                <h2 class="text-lg font-semibold mb-3">
                    Deskripsi Sekolah
                </h2>

                <p class="text-gray-700 leading-relaxed">
                    {{ $profil->deskripsi }}
                </p>
            @endif

        </div>
    </div>

</body>
</html>