# Visit Correction API

API ini mempertahankan laporan kunjungan asli dan menyimpan setiap koreksi sebagai audit record terpisah. Koreksi otomatis berstatus `approved`; koreksi terbaru yang approved menjadi data efektif. Perubahan data stok hanya mengubah laporan visit, bukan inventory atau transaksi resmi.

## Dasar URL dan autentikasi

- Prefix: `/api`
- Authentication: Laravel Sanctum bearer token (atau sesi Sanctum yang dikonfigurasi untuk client).
- Endpoint berikut memerlukan middleware `auth:sanctum` dan `active`.
- Kirim `Accept: application/json`.
- Semua waktu batas dihitung backend dari `store_visits.check_out_at` menggunakan waktu server.

## Ajukan koreksi

`POST /api/store-visits/{visit_id}/corrections`

Header wajib:

| Header | Deskripsi |
| --- | --- |
| `Authorization: Bearer {token}` | Token Sanctum user pemilik visit |
| `Idempotency-Key: {unique-key}` | Key unik per pengajuan; maks. 128 karakter. Kirim key yang sama saat retry request yang sama. |
| `Accept: application/json` | Memastikan error validasi dikirim sebagai JSON |

`visit_id` adalah ID `store_visits` pada path, bukan ID `visit_reports`.

Request body JSON (field laporan bersifat opsional/partial; `reason` wajib):

| Field | Tipe / aturan |
| --- | --- |
| `pic_name` | string atau `null`, maks. 255 |
| `activities` | array string, setiap elemen maks. 255; paling sedikit satu jika dikirim |
| `stock_percentage` | integer 0–100 atau `null` |
| `stock_pcs` | integer >= 0 atau `null` |
| `notes` | string atau `null`, maks. 10.000 |
| `reason` | string wajib, maks. 2.000 |

Foto, koordinat, bukti lokasi, pemilik, dan identitas visit tidak dapat dikoreksi.

Contoh pengajuan:

```http
POST /api/store-visits/481/corrections
Authorization: Bearer {token}
Idempotency-Key: 7cebd5cc-85f4-4c15-96ea-44c9d6253c36
Accept: application/json
Content-Type: application/json
```

```json
{
  "stock_percentage": 65,
  "stock_pcs": 38,
  "reason": "Penghitungan ulang setelah kunjungan menemukan angka stok yang salah."
}
```

Sukses: HTTP `201 Created`.

```json
{
  "status": "success",
  "message": "Koreksi kunjungan berhasil disetujui secara otomatis.",
  "data": {
    "correction": {
      "store_visit_id": 481,
      "visit_report_id": 927,
      "user_id": 33,
      "previous_data": {
        "pic_name": "Budi",
        "activities": ["Pengecekan stok"],
        "stock_percentage": 80,
        "stock_pcs": 25,
        "notes": "Stok terlihat cukup."
      },
      "correction_data": {
        "pic_name": "Budi",
        "activities": ["Pengecekan stok"],
        "stock_percentage": 65,
        "stock_pcs": 38,
        "notes": "Stok terlihat cukup."
      },
      "reason": "Penghitungan ulang setelah kunjungan menemukan angka stok yang salah.",
      "status": "approved",
      "approved_at": "2026-11-12 09:00:00",
      "created_at": "2026-11-12T09:00:00.000000Z",
      "updated_at": "2026-11-12T09:00:00.000000Z",
      "id": 12
    },
    "effective_data": {
      "pic_name": "Budi",
      "activities": ["Pengecekan stok"],
      "stock_percentage": 65,
      "stock_pcs": 38,
      "notes": "Stok terlihat cukup."
    },
    "correction_deadline_at": "2026-11-12 20:00:00"
  }
}
```

Nilai `idempotency_key` dan hash tidak pernah dikembalikan. Retry identik mengembalikan response sukses yang sama dengan header `Idempotent-Replayed: true`; penggunaan key yang sama dengan body berbeda menghasilkan `409`.

## Baca correction history

`GET /api/store-visits/{visit_id}/corrections`

Memerlukan autentikasi Sanctum dan hanya dapat dibaca pemilik visit. Tidak membutuhkan parameter query atau body.

```json
{
  "status": "success",
  "data": {
    "visit_id": 481,
    "status": "COMPLETED",
    "completed_at": "2026-11-12 08:00:00",
    "correction_window": {
      "deadline_at": "2026-11-12 20:00:00",
      "can_correct": true
    },
    "original_data": {
      "pic_name": "Budi",
      "activities": ["Pengecekan stok"],
      "stock_percentage": 80,
      "stock_pcs": 25,
      "notes": "Stok terlihat cukup."
    },
    "effective_data": {
      "pic_name": "Budi",
      "activities": ["Pengecekan stok"],
      "stock_percentage": 65,
      "stock_pcs": 38,
      "notes": "Stok terlihat cukup."
    },
    "corrections": [
      {
        "id": 12,
        "store_visit_id": 481,
        "visit_report_id": 927,
        "user_id": 33,
        "previous_data": {
          "pic_name": "Budi",
          "activities": ["Pengecekan stok"],
          "stock_percentage": 80,
          "stock_pcs": 25,
          "notes": "Stok terlihat cukup."
        },
        "correction_data": {
          "pic_name": "Budi",
          "activities": ["Pengecekan stok"],
          "stock_percentage": 65,
          "stock_pcs": 38,
          "notes": "Stok terlihat cukup."
        },
        "reason": "Penghitungan ulang setelah kunjungan menemukan angka stok yang salah.",
        "status": "approved",
        "approved_at": "2026-11-12 09:00:00"
      }
    ]
  }
}
```

`GET /api/store-visits/history` yang sudah ada juga mempertahankan properti `report` dan menambahkan `report_original`, `report_effective`, `corrections`, serta `correction_window` pada setiap visit. `report_original` tidak berubah dari response lama; gunakan `report_effective` untuk tampilan nilai aktif.

## Error responses

| HTTP | Kondisi | Contoh response |
| --- | --- | --- |
| `401` | Token tidak ada/tidak valid | `{"message":"Unauthenticated."}` |
| `404` | Visit tidak ada, bukan milik user, atau tidak mempunyai laporan | `{"message":"Kunjungan tidak ditemukan atau Anda tidak memiliki akses."}` |
| `409` | Idempotency key sudah dipakai untuk koreksi berbeda | `{"status":"error","message":"Idempotency-Key sudah digunakan untuk koreksi yang berbeda."}` |
| `422` | Payload tidak valid, visit belum Completed, laporan hilang, atau deadline terlewati | `{"message":"...","errors":{"visit":["..."]}}` |

Contoh error validasi standar Laravel:

```json
{
  "message": "The reason field is required.",
  "errors": {
    "reason": ["The reason field is required."]
  }
}
```

## Business validation

- Visit harus berstatus `COMPLETED` dan memiliki `check_out_at` serta laporan.
- Hanya user pemilik visit (`store_visits.sales_id`) yang boleh mengajukan/membaca koreksi.
- Deadline adalah tepat 12 jam sesudah `check_out_at`; pengajuan pada waktu deadline masih diterima, setelahnya ditolak.
- Waktu device/client tidak dipakai untuk validasi deadline.
- `reason` dan paling sedikit satu field koreksi wajib ada.
- Koreksi disetujui otomatis, dapat diajukan berkali-kali selama window aktif.
- Setiap koreksi menyimpan snapshot data sebelumnya dan data efektif setelah koreksi. `visit_reports` tidak ditimpa.
- Koreksi yang terakhir dibuat dan berstatus approved adalah sumber data efektif; koreksi parsial membawa nilai efektif sebelumnya untuk field yang tidak dikirim.
- Ulangi request dengan `Idempotency-Key` yang sama untuk retry; body berbeda dengan key yang sama ditolak.
- Field foto/lokasi/evidence tidak dapat diedit. Koreksi stok tidak memutasi inventory atau transaksi resmi.
