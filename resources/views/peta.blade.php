<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peta Sekolah</title>
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    
    <link rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">

    <link rel="stylesheet"
        href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: system-ui, sans-serif;
        }

        header {
            height: 56px;
            box-sizing: border-box;
            padding: 0 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: #1e3a8a;
            color: #fff;
        }

        header h1 {
            font-size: 18px;
            margin: 0;
        }

        header a {
            color: #bfdbfe;
            margin-left: auto;
        }

        #map {
            height: calc(100% - 56px);
        }
    </style>
</head>
<body>
    <header>
        <h1>Peta Sekolah</h1>

        <select id="filter-jenjang">
            <option value="">Semua jenjang</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
        </select>

        <select id="filter-status">
            <option value="">Semua status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
        </select>

        <input
            type="text"
            id="cari-sekolah"
            placeholder="Cari nama sekolah..."
        >

        <span id="jumlah"></span>

       @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
        @else
            <a href="{{ route('login') }}">Masuk</a>
        @endauth
    </header>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        const map = L.map('map').setView([-3.3194, 114.5908], 12);

        const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        });

        const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: '&copy; OpenTopoMap contributors'
        });

        const satelit = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri'
            }
        );

        osm.addTo(map);

        const baseMaps = {
            "OpenStreetMap": osm,
            "Topografi": topo,
            "Satelit": satelit
        };

        L.control.layers(baseMaps).addTo(map);

        function esc(teks) {
            const d = document.createElement('div');
            d.textContent = teks ?? '';
            return d.innerHTML;
        }

        // Warna marker berdasarkan jenjang sekolah
        function warnaMarker(jenjang) {
            const warna = {
                'SD': 'green',
                'SMP': 'blue',
                'SMA': 'red',
                'SMK': 'orange'
            };

            return warna[jenjang] || 'gray';
        }

        const kelompokMarker = L.markerClusterGroup();
        map.addLayer(kelompokMarker);

        const lapisan = L.geoJSON(null, {
            pointToLayer: (fitur, latlng) => {
                const warna = warnaMarker(fitur.properties.jenjang);

                return L.marker(latlng, {
                    icon: L.divIcon({
                        className: '',
                        html: `
                            <div style="
                                background-color: ${warna};
                                width: 18px;
                                height: 18px;
                                border: 2px solid white;
                                border-radius: 50%;
                                box-shadow: 0 1px 4px #555;
                            "></div>
                        `,
                        iconSize: [22, 22],
                        iconAnchor: [11, 11]
                    })
                });
            },

            onEachFeature: (fitur, layer) => {
                const p = fitur.properties;
                const [lng, lat] = fitur.geometry.coordinates;

                layer.bindPopup(`
                    <strong>${esc(p.nama)}</strong><br>
                    NPSN: ${esc(p.npsn)}<br>
                    ${esc(p.jenjang)} - ${esc(p.status)}<br>
                    ${esc(p.alamat)}, ${esc(p.kecamatan)}<br>
                    Telp: ${esc(p.telepon) || '-'}<br>
                    Kepala sekolah: ${esc(p.kepala_sekolah) || '-'}<br>
                    Akreditasi: ${esc(p.akreditasi) || '-'}<br>
                    Siswa: ${p.jumlah_siswa ?? '-'} | Guru: ${p.jumlah_guru ?? '-'}<br>
                    <small>Koordinat: ${lat}, ${lng}</small>
                `);
            }
        }).addTo(map);

        async function muatData() {
            const jenjang = document.getElementById('filter-jenjang').value;
            const status = document.getElementById('filter-status').value;

            const url = `{{ route('peta.data') }}?jenjang=${jenjang}&status=${status}`;
            const respon = await fetch(url);
            const data = await respon.json();

           kelompokMarker.clearLayers();
           lapisan.clearLayers();
           
           lapisan.addData(data);
           kelompokMarker.addLayer(lapisan);

            document.getElementById('jumlah').textContent =
                data.features.length + ' sekolah';

            if (data.features.length > 0) {
                map.fitBounds(lapisan.getBounds().pad(0.1));
            }
        }

       document.getElementById('filter-jenjang')
            .addEventListener('change', muatData);

        document.getElementById('filter-status')
            .addEventListener('change', muatData);

        document.getElementById('cari-sekolah')
            .addEventListener('input', function () {
                const kataKunci = this.value.toLowerCase();

                if (kataKunci === '') {
                    return;
                }

                lapisan.eachLayer(function (layer) {
                    const nama = layer.feature.properties.nama.toLowerCase();

                    if (nama.includes(kataKunci)) {
                        map.setView(layer.getLatLng(), 17);
                        layer.openPopup();
                        return;
                    }
                });
            });

        muatData();
    </script>
</body>
</html>