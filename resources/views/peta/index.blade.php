@extends('layouts.public')
@section('title', 'Peta Sekolah')

@push('styles')
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
<link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
@endpush

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

    <label>Status
        <select id="filter-status">
            <option value="">Semua status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>
    </label>

    <label>Bobot Heatmap
        <select id="mode">
            <option value="sekolah">Jumlah sekolah</option>
            <option value="siswa">Jumlah siswa</option>
        </select>
    </label>

    <label>Radius
        <input type="range" id="radius" min="10" max="60" value="30">
    </label>

    <span id="jumlah"></span>

    <button type="button" id="unduh-geojson"
        style="background:#16a34a; color:white; padding:8px 12px; border-radius:6px;">
        Unduh GeoJSON
    </button>
</div>

<div id="peta" class="peta"></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
    const map = Peta.buat('peta');

    // Lapisan marker biasa
    const marker = L.layerGroup();

    // Lapisan klaster
    const klaster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 17,
        chunkedLoading: true,
    });

    // Lapisan heatmap
    const heat = L.heatLayer([], {
        radius: 30,
        blur: 25,
        maxZoom: 17,
        max: 1.0,
        gradient: {
            0.2: '#2563eb',
            0.4: '#06b6d4',
            0.6: '#84cc16',
            0.8: '#facc15',
            1.0: '#dc2626',
        },
    });

    // Kontrol untuk mengaktifkan dan menonaktifkan lapisan
    const lapisanPeta = {
        'Marker': marker,
        'Klaster': klaster,
        'Peta Panas': heat,
    };

    L.control.layers(null, lapisanPeta).addTo(map);

    // Marker menjadi tampilan awal
    marker.addTo(map);

    let data = [];
    let nomorPermintaan = 0;

    // Menggambar ulang heatmap
    function gambarHeatmap() {
        const mode = document.getElementById('mode').value;
        const radius = Number(document.getElementById('radius').value);

        const maks = Math.max(
            1,
            ...data.map((f) => f.properties.jumlah_siswa ?? 0)
        );

        const titik = data.map((f) => {
            const siswa = f.properties.jumlah_siswa ?? 0;

            const bobot = mode === 'siswa'
                ? Math.max(siswa / maks, 0.1)
                : 0.35;

            return [...Peta.latlng(f), bobot];
        });

        heat.setLatLngs(titik);
        heat.setOptions({ radius: radius });
    }

    // Memuat data sekolah berdasarkan batas peta
    async function muat(jenjang = '', status = '') {
        const permintaanSekarang = ++nomorPermintaan;

        try {
            // Mengirim filter dan batas peta ke server
            const hasil = await Peta.ambilSekolah(
                jenjang,
                status,
                map
            );

            // Mengabaikan hasil permintaan lama
            if (permintaanSekarang !== nomorPermintaan) {
                return;
            }

            data = hasil;

            marker.clearLayers();
            klaster.clearLayers();

            data.forEach((f) => {
                const titik = L.marker(Peta.latlng(f))
                    .bindPopup(Peta.popup(f));

                marker.addLayer(titik);
            });

            klaster.addLayers(
                data.map((f) =>
                    L.marker(Peta.latlng(f))
                        .bindPopup(Peta.popup(f))
                )
            );

            gambarHeatmap();

            document.getElementById('jumlah').textContent =
                data.length + ' sekolah';

        } catch (e) {
            if (permintaanSekarang !== nomorPermintaan) {
                return;
            }

            document.getElementById('jumlah').textContent =
                'Gagal memuat data';

            console.error(e);
        }
    }

    // Mengunduh data sekolah dalam format GeoJSON
    document.getElementById('unduh-geojson')
        .addEventListener('click', function () {
            const geojson = {
                type: 'FeatureCollection',
                features: data,
            };

            const file = new Blob(
                [JSON.stringify(geojson, null, 2)],
                { type: 'application/geo+json' }
            );

            const url = URL.createObjectURL(file);
            const link = document.createElement('a');

            link.href = url;
            link.download = 'data-sekolah.geojson';
            link.click();

            URL.revokeObjectURL(url);
        });

    // Menggunakan filter jenjang dan status
    function terapkanFilter() {
        const jenjang = document.getElementById('filter-jenjang').value;
        const status = document.getElementById('filter-status').value;

        muat(jenjang, status);
    }

    document.getElementById('filter-jenjang')
        .addEventListener('change', terapkanFilter);

    document.getElementById('filter-status')
        .addEventListener('change', terapkanFilter);

    document.getElementById('mode')
        .addEventListener('change', gambarHeatmap);

    document.getElementById('radius')
        .addEventListener('input', gambarHeatmap);

    // Memuat ulang data ketika peta digeser atau di-zoom
    map.on('moveend', terapkanFilter);

    // Menampilkan data pertama kali berdasarkan area peta
    muat();
</script>
@endpush