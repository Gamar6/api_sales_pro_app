<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import L from "leaflet";

import "leaflet/dist/leaflet.css";

const props = defineProps({
    exception: {
        type: Object,
        required: true,
    },
});

const mapContainer = ref(null);

let map = null;
let salesMarker = null;
let storeMarker = null;
let connectingLine = null;

const salesIcon = L.divIcon({
    className: "custom-map-marker",
    html: `
        <div style="
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #2563eb;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        "></div>
    `,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
});

const storeIcon = L.divIcon({
    className: "custom-map-marker",
    html: `
        <div style="
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dc2626;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,.35);
        "></div>
    `,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
});

const isValidCoordinate = (latitude, longitude) => {
    const lat = Number(latitude);
    const lng = Number(longitude);

    return (
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        lat >= -90 &&
        lat <= 90 &&
        lng >= -180 &&
        lng <= 180
    );
};

onMounted(() => {
    const salesLatitude = props.exception.sales_latitude;
    const salesLongitude = props.exception.sales_longitude;

    const storeLatitude = props.exception.store?.latitude;
    const storeLongitude = props.exception.store?.longitude;

    const hasSalesCoordinate = isValidCoordinate(
        salesLatitude,
        salesLongitude
    );

    const hasStoreCoordinate = isValidCoordinate(
        storeLatitude,
        storeLongitude
    );

    if (!mapContainer.value) {
        return;
    }

    map = L.map(mapContainer.value, {
        zoomControl: true,
        attributionControl: true,
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    const points = [];

    if (hasSalesCoordinate) {
        const salesPoint = [
            Number(salesLatitude),
            Number(salesLongitude),
        ];

        salesMarker = L.marker(salesPoint, {
            icon: salesIcon,
        })
            .addTo(map)
            .bindPopup(`
                <strong>Lokasi Sales</strong><br>
                ${props.exception.sales.name}
            `);

        points.push(salesPoint);
    }

    if (hasStoreCoordinate) {
        const storePoint = [
            Number(storeLatitude),
            Number(storeLongitude),
        ];

        storeMarker = L.marker(storePoint, {
            icon: storeIcon,
        })
            .addTo(map)
            .bindPopup(`
                <strong>Lokasi Store</strong><br>
                ${props.exception.store.name}
            `);

        points.push(storePoint);
    }

    if (points.length === 2) {
        connectingLine = L.polyline(points, {
            color: "#F48110",
            weight: 4,
            dashArray: "8, 8",
        }).addTo(map);

        map.fitBounds(points, {
            padding: [40, 40],
        });
    } else if (points.length === 1) {
        map.setView(points[0], 16);
    } else {
        map.setView([-6.2, 106.816666], 10);
    }

    setTimeout(() => {
        map?.invalidateSize();
    }, 200);
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }

    salesMarker = null;
    storeMarker = null;
    connectingLine = null;
});
</script>

<template>
    <section>
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-400">
            Location Map
        </h3>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div
                ref="mapContainer"
                class="h-[360px] w-full"
            ></div>
        </div>

        <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-blue-600"></span>
                Lokasi Sales
            </div>

            <div class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-red-600"></span>
                Lokasi Store
            </div>

            <div class="flex items-center gap-2">
                <span class="h-0.5 w-5 bg-orange-500"></span>
                Garis Jarak
            </div>
        </div>
    </section>
</template>
