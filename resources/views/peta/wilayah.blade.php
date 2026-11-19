@extends('layouts.public')
@section('title', 'Wilayah')

@section('content')
<div class="toolbar">
    <label>Jenjang
        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>
    </label>
    <span id="jumlah"></span>
</div>

<div id="peta" class="peta"></div>

<div class="panel" style="right: 12px; bottom: 24px; font-size: 13px;">
    <strong>Jumlah sekolah</strong>
    <div><span style="color: #dc2626;">&#9632;</span> Terbanyak</div>
    <div><span style="color: #ea5808;">&#9632;</span> Banyak</div>
    <div><span style="color: #f59e0b;">&#9632;</span> Sedang</div>
    <div><span style="color: #3b82f6;">&#9632;</span> Sedikit</div>
    <small style="color: #6b7280;">Relatif terhadap kecamatan terbanyak</small>
</div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    let lapisan = null;

    const urlBatas = @json(asset('geojson/semua-kecamatan.geojson'));
    
    let batasWilayah = null;

    function warna(rasio) {
        if (rasio >= 0.8) return '#dc2626';
        if (rasio >= 0.6) return '#ea5808';
        if (rasio >= 0.4) return '#f59e0b';
        return '#3b82f6';
    }

    async function muat(jenjang = '') {
        try {
            // Mengambil jumlah sekolah dari Laravel
            const url = `${window.PETA_URL.wilayah}?jenjang=${encodeURIComponent(jenjang)}`;
            const responsData = await fetch(url);

            if (!responsData.ok) {
                throw new Error('Gagal mengambil data jumlah sekolah');
            }

            const data = await responsData.json();

            // Menyimpan jumlah sekolah berdasarkan kecamatan
            const hitung = {};

            data.features.forEach((f) => {
                const p = f.properties;
                const key = p.kecamatan.trim().toLowerCase();

                hitung[key] = p;
            });

            // Mengambil batas kecamatan resmi dari BIG
            if (!batasWilayah) {
                const responsBatas = await fetch(urlBatas);

                if (!responsBatas.ok) {
                    throw new Error('Gagal mengambil GeoJSON batas kecamatan');
                }

                batasWilayah = await responsBatas.json();
            }

            if (lapisan) {
                map.removeLayer(lapisan);
            }

            const maks = Math.max(
                1,
                ...Object.values(hitung).map((p) => Number(p.jumlah_sekolah))
            );

            // Menampilkan batas kecamatan resmi
            lapisan = L.geoJSON(batasWilayah, {
                style: (f) => {
                    const key = f.properties.WADMKC.trim().toLowerCase();
                    const p = hitung[key] || {};
                    const rasio = Number(p.jumlah_sekolah || 0) / maks;
                    const w = warna(rasio);

                    return {
                        color: w,
                        fillColor: w,
                        fillOpacity: 0.3,
                        weight: 3
                    };
                },

                onEachFeature: (f, layer) => {
                    const kecamatan = f.properties.WADMKC;
                    const key = kecamatan.trim().toLowerCase();
                    const p = hitung[key] || {};

                    layer.bindTooltip(Peta.esc(kecamatan), {
                        sticky: true
                    });

                    layer.bindPopup(`
                        <strong>${Peta.esc(kecamatan)}</strong><br>
                        Jumlah sekolah: ${p.jumlah_sekolah || 0}<br>
                        Jumlah siswa: ${p.jumlah_siswa || 0}
                    `);
                }
            }).addTo(map);

            document.getElementById('jumlah').textContent =
                batasWilayah.features.length + ' wilayah';

            if (batasWilayah.features.length > 0) {
                map.fitBounds(lapisan.getBounds().pad(0.1));
            }

        } catch (error) {
            console.error(error);
            alert('Gagal memuat peta wilayah. Periksa koneksi dan file GeoJSON.');
        }
    }

    document.getElementById('filter-jenjang')
        .addEventListener('change', (e) => muat(e.target.value));

    muat();
</script>
@endpush