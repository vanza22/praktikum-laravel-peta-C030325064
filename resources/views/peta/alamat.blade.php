@extends('layouts.public')
@section('title', 'Cek Lokasi')

@section('content')
<div class="toolbar">
    <button type="button" id="lokasi-saya">
        Lokasi Saya
    </button>
    <span id="status-lokasi">
        Klik di mana saja pada peta atau gunakan tombol Lokasi Saya.
    </span>
</div>

<div id="peta" class="peta"></div>

<div id="hasil" class="panel"
     style="top: 64px; right: 12px; width: 320px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const panel = document.getElementById('hasil');
    const tombolLokasi = document.getElementById('lokasi-saya');
    const statusLokasi = document.getElementById('status-lokasi');

    const urlRadius = @json(route('peta.data.radius'));

    let semua = [];
    let terakhir = 0;

    const dataSekolah = Peta.ambilSekolah()
        .then((fitur) => {
            semua = fitur;
            return fitur;
        })
        .catch((err) => {
            console.error(err);
            statusLokasi.textContent = 'Gagal memuat data sekolah.';
            throw err;
        });

    // Mencari sekolah terdekat menggunakan Haversine
    function terdekat(titik, n = 3) {
        return semua
            .map((f) => ({
                fitur: f,
                jarak: Peta.jarakKm(titik, Peta.latlng(f))
            }))
            .sort((a, b) => a.jarak - b.jarak)
            .slice(0, n);
    }

    // Mengambil alamat berdasarkan koordinat
    async function ambilAlamat(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse'
            + '?format=jsonv2&addressdetails=1&accept-language=id'
            + `&lat=${lat}&lon=${lng}`;

        const respon = await fetch(url);

        if (!respon.ok) {
            throw new Error('HTTP ' + respon.status);
        }

        return respon.json();
    }

    // Mengambil jumlah sekolah dalam radius menggunakan PostGIS
    async function hitungSekolahRadius(lat, lng) {
        const parameter = new URLSearchParams({
            latitude: lat,
            longitude: lng,
            radius: 1000
        });

        const respon = await fetch(`${urlRadius}?${parameter}`);

        if (!respon.ok) {
            throw new Error('Gagal menghitung radius sekolah: HTTP ' + respon.status);
        }

        return respon.json();
    }

    // Menampilkan lokasi dan melakukan analisis jangkauan
    async function tampilkanLokasi(lat, lng) {
        const sekarang = Date.now();

        if (sekarang - terakhir < 1200) {
            statusLokasi.textContent =
                'Tunggu sebentar sebelum mencari lokasi lagi.';
            return;
        }

        terakhir = sekarang;

        const titik = [lat, lng];

        await dataSekolah;

        // Mengambil jumlah sekolah dari database PostGIS
        const hasilRadius = await hitungSekolahRadius(lat, lng);
        const jumlahDalamJangkauan = hasilRadius.jumlah;

        lapisan.clearLayers();

        // Penanda lokasi yang dipilih
        L.circleMarker(titik, {
            radius: 9,
            color: '#fff',
            weight: 3,
            fillColor: '#ef4444',
            fillOpacity: 1,
        })
        .bindPopup('Lokasi yang dipilih')
        .addTo(lapisan);

        // Lingkaran jangkauan dengan radius 1 km
        L.circle(titik, {
            radius: 1000,
            color: '#2563eb',
            weight: 2,
            fillColor: '#3b82f6',
            fillOpacity: 0.15,
        })
        .addTo(lapisan);

        // Mencari tiga sekolah terdekat menggunakan Haversine
        const dekat = terdekat(titik);

        dekat.forEach((d) => {
            L.polyline([titik, Peta.latlng(d.fitur)], {
                color: '#2563eb',
                weight: 2,
                dashArray: '6 6',
            }).addTo(lapisan);
        });

        map.flyTo(titik, 15);

        panel.style.display = 'block';
        panel.textContent = 'Mencari alamat...';

        let bagianAlamat = '<em>Alamat tidak dapat dimuat.</em>';

        try {
            const data = await ambilAlamat(lat, lng);
            const a = data.address || {};

            const baris = [
                ['Jalan', a.road],
                ['Kelurahan/Desa', a.suburb || a.neighbourhood || a.village],
                ['Kota/Kabupaten', a.city || a.town || a.municipality || a.county],
                ['Provinsi', a.state],
                ['Kode pos', a.postcode],
            ].map(([k, v]) => `
                <tr>
                    <td style="padding: 2px 8px 2px 0; color: #6b7280;">${k}</td>
                    <td>${Peta.esc(v) || '-'}</td>
                </tr>
            `).join('');

            bagianAlamat = `
                <p style="margin: 6px 0;">
                    ${Peta.esc(data.display_name || '-')}
                </p>
                <table style="font-size: 13px;">
                    ${baris}
                </table>
            `;
        } catch (err) {
            console.error(err);
        }

        // Daftar tiga sekolah terdekat
        const daftar = dekat.map((d) => `
            <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(d.fitur.properties.nama)}</strong><br>
                <small>${d.jarak.toFixed(2)} km (garis lurus)</small>
            </li>
        `).join('');

        panel.innerHTML = `
            <strong>Koordinat</strong>
            <div style="font-family: monospace;">
                ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>

            <strong style="display: block; margin-top: 8px;">
                Alamat
            </strong>
            ${bagianAlamat}

            <strong style="display: block; margin-top: 8px;">
                Analisis Jangkauan (Radius 1 km)
            </strong>
            <p style="margin: 4px 0;">
                Terdapat <strong>${jumlahDalamJangkauan} sekolah</strong>
                dalam jangkauan 1 km.
            </p>

            <strong style="display: block; margin-top: 8px;">
                Sekolah terdekat
            </strong>

            <ul style="list-style: none; margin: 4px 0 0; padding: 0;">
                ${daftar || '<li>Tidak ada data sekolah.</li>'}
            </ul>
        `;

        statusLokasi.textContent = 'Analisis jangkauan berhasil dilakukan.';
    }

    // Klik peta untuk melakukan analisis
    map.on('click', (e) => {
        tampilkanLokasi(e.latlng.lat, e.latlng.lng)
            .catch((err) => {
                console.error(err);
                statusLokasi.textContent = 'Gagal memproses lokasi.';
            });
    });

    // Tombol Lokasi Saya
    tombolLokasi.addEventListener('click', async () => {
        if (!navigator.geolocation) {
            statusLokasi.textContent =
                'Browser tidak mendukung fitur lokasi.';
            return;
        }

        tombolLokasi.disabled = true;
        statusLokasi.textContent = 'Sedang mencari lokasi Anda...';

        navigator.geolocation.getCurrentPosition(
            async (posisi) => {
                try {
                    const lat = posisi.coords.latitude;
                    const lng = posisi.coords.longitude;

                    await tampilkanLokasi(lat, lng);
                } catch (err) {
                    console.error(err);
                    statusLokasi.textContent =
                        'Gagal mencari alamat atau sekolah terdekat.';
                } finally {
                    tombolLokasi.disabled = false;
                }
            },

            (err) => {
                let pesan = 'Gagal mendapatkan lokasi.';

                if (err.code === 1) {
                    pesan = 'Izin lokasi ditolak. Izinkan akses lokasi di browser.';
                } else if (err.code === 2) {
                    pesan = 'Lokasi perangkat tidak tersedia.';
                } else if (err.code === 3) {
                    pesan = 'Waktu pencarian lokasi habis. Coba lagi.';
                }

                statusLokasi.textContent = pesan;
                tombolLokasi.disabled = false;
            },

            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            }
        );
    });

    // Klik sekolah terdekat untuk melihat lokasinya
    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');
        if (!li) return;

        const f = semua.find(
            (x) => x.properties.npsn === li.dataset.npsn
        );

        if (f) {
            L.popup()
                .setLatLng(Peta.latlng(f))
                .setContent(Peta.popup(f))
                .openOn(map);

            map.flyTo(Peta.latlng(f), 17);
        }
    });
</script>
@endpush