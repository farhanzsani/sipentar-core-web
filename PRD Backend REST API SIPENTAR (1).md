# PRD Backend REST API SIPENTAR

Sistem Informasi Manajemen Budidaya Selada Hidroponik dan Deteksi Kesehatan Tanaman (Mobile). Klien: Wonglimo Hidroponik, Jember.

Dokumen ini dibuat agar bisa langsung diberikan ke AI coding assistant (vibe coding). Kerjakan **per fase**, jangan sekaligus.

## 1. Ringkasan

Buat REST API untuk aplikasi mobile pengelolaan greenhouse selada hidroponik. Pengguna: **Pemilik** dan **Pegawai**. Endpoint **login sudah tersedia**, jangan dibuat ulang. Struktur database **mengikuti ERD final** (bagian 5). Fitur telemetri air (MQTT, pH, TDS) dan tabel `water_log` **dihapus dari sistem**.

## 2. Tech Stack (asumsi, ubah jika berbeda)

- Laravel 11, PHP 8.2+
- MySQL
- Auth: Laravel Sanctum (token), sudah ada
- Deploy: shared hosting cPanel (jadi **tanpa worker/daemon**, scheduler cukup lewat cron)
- Storage gambar: `storage/app/public` (disk `public`)
- Dokumentasi: OpenAPI (Scribe atau L5-Swagger)

## 3. Konvensi Wajib

- Prefix: `/api/v1`
- Format response seragam:

```json
{ "success": true, "message": "OK", "data": {}, "errors": null }
```

- Error validasi: HTTP 422, `errors` berisi pesan per field. Tidak punya hak akses: 403. Tidak login: 401. Tidak ada: 404.
- Gunakan Form Request untuk validasi, API Resource untuk output, Service class untuk logika bisnis.
- Penamaan tabel, kolom, field JSON, dan URL endpoint **mengikuti ERD** dan memakai bahasa Indonesia (contoh `/budidaya`, `/stok`, `tanggal_semai`). Jangan diganti ke bahasa Inggris.
- Uang mengikuti ERD: `decimal(14,2)`. Hitung dengan bcmath atau cast string, hindari float.
- Timezone `Asia/Jakarta`.
- Pagination: `?page=1&per_page=15`. Filter tanggal: `?from=YYYY-MM-DD&to=YYYY-MM-DD`.
- Pesan error dan message dalam Bahasa Indonesia.
- Tidak ada hapus permanen untuk akun karyawan (lihat Fase 1).

## 4. Role dan Hak Akses

| Modul | Pemilik | Pegawai |
| --- | --- | --- |
| Profil sendiri | Ya | Tidak |
| Kelola karyawan | Ya | Tidak |
| Penggunaan operasional | Ya | Ya |
| Budidaya dan meja tanam | Ya | Ya |
| Panen (jadwal dan hasil) | Ya | Ya |
| Diagnosis | Ya | Ya |
| Stok | Ya | Ya |
| Notifikasi stok | Ya | Ya |
| Keuangan | Ya | Tidak |

Implementasi: middleware `role:pemilik` pada grup route. Pembatasan dilakukan di backend, bukan hanya di UI.

## 5. Model Data (Mengikuti ERD)

| Tabel | Kolom (sesuai ERD) |
| --- | --- |
| `users` | id, nama varchar(100), username varchar(50) unik, password varchar(255), role varchar(20) (`pemilik` / `pegawai`) |
| `meja_tanam` | id, nomor\_meja int, kapasitas\_lubang int, status varchar(30) |
| `budidaya` | id, user\_id, meja\_id, tanggal\_semai date, jumlah\_tanaman int, status varchar(30) |
| `panen` | id, budidaya\_id, tanggal\_panen date, berat\_kg decimal(10,2), status varchar(30) |
| `diagnosis` | id, user\_id, budidaya\_id, image\_path varchar(255), hasil varchar(30), confidence decimal(5,2), rekomendasi text, tanggal\_diagnosis timestamp |
| `stok` | id, nama\_bahan varchar(100), kategori varchar(50), satuan varchar(20), jumlah\_stok decimal(10,2), batas\_minimum decimal(10,2) |
| `penggunaan_operasional` | id, user\_id, stok\_id, tanggal date, jumlah decimal(10,2), keterangan varchar(255) |
| `transaksi_keuangan` | id, user\_id, panen\_id, jenis varchar(30), tanggal date, jumlah decimal(14,2), persentase\_kas decimal(5,2), kas\_operasional decimal(14,2), laba\_bersih decimal(14,2), keterangan varchar(255) |

Relasi:

- `users` 1 ke N `budidaya`, `diagnosis`, `penggunaan_operasional`, `transaksi_keuangan` (lewat `user_id`)
- `meja_tanam` 1 ke N `budidaya` (`meja_id`)
- `budidaya` 1 ke N `panen` dan 1 ke N `diagnosis` (`budidaya_id`)
- `stok` 1 ke N `penggunaan_operasional` (`stok_id`)
- `panen` 1 ke N `transaksi_keuangan` (`panen_id`)

Tabel `water_log` pada gambar ERD **tidak dibuat** karena telemetri dihapus. ERD juga tidak punya tabel `settings`, `notifikasi`, dan `treatment_recommendations`. Nilai tetap (HPP Rp1.200 per lubang, persen kas default 35 dengan batas 30 sampai 40, teks rekomendasi tiap kelas diagnosis) disimpan di `config/sipentar.php`, dan notifikasi dihitung langsung dari tabel `stok`. Kolom `timestamps` (created\_at, updated\_at) boleh ditambahkan, tetapi jangan mengubah kolom lain di ERD.

## 6. Fase Pengerjaan dan Endpoint

### Fase 0: Fondasi

- Helper response standar, handler exception global (JSON), middleware role.
- Migration semua tabel di bagian 5 persis seperti ERD (tanpa `water_log`).
- Seeder: 1 akun pemilik dan 6 `meja_tanam` (`nomor_meja` 1 sampai 6, total kapasitas sekitar 2.500 lubang, `status` awal `kosong`).
- Buat `config/sipentar.php` berisi HPP per lubang, persen kas default dan batasnya, serta teks rekomendasi untuk 4 kelas diagnosis.
- Endpoint `GET /me` dan `POST /logout` jika belum ada.

**Selesai jika:** pemilik bisa login, `GET /me` mengembalikan role, route khusus pemilik menolak pegawai dengan 403.

### Fase 1: Akun dan Karyawan

| Method | Endpoint | Akses |
| --- | --- | --- |
| GET | `/profil` | pemilik |
| PUT | `/profil` | pemilik |
| GET | `/karyawan` | pemilik |
| POST | `/karyawan` | pemilik |
| GET | `/karyawan/{id}` | pemilik |
| PUT | `/karyawan/{id}` | pemilik |

Aturan: field mengikuti tabel `users` (`nama`, `username`, `password`, `role`). Karyawan otomatis berrole `pegawai`. Password di-hash. `username` unik. `GET /karyawan` hanya menampilkan role `pegawai`. ERD tidak punya kolom aktif/nonaktif, jadi karyawan tidak bisa dinonaktifkan atau dihapus lewat API.

### Fase 2: Budidaya

| Method | Endpoint | Akses |
| --- | --- | --- |
| GET | `/budidaya` | semua |
| POST | `/budidaya` | semua |
| GET | `/budidaya/{id}` | semua |
| PUT | `/budidaya/{id}` | semua |
| GET | `/meja-tanam` | semua |

Aturan:

- Field `hss` (hari setelah semai) **dihitung otomatis** saat response: `hari ini - tanggal_semai` (accessor, **jangan simpan di DB**).
- `user_id` diisi otomatis dari pengguna yang login.
- `tanggal_semai` tidak boleh di masa depan. `jumlah_tanaman` tidak boleh melebihi `kapasitas_lubang` meja.
- `status` budidaya: `berjalan` atau `selesai`. Meja berstatus `terisi` saat ada budidaya `berjalan`, selain itu `kosong` (nilai status ini usulan).
- Filter: `meja_id`, `status`, `from`, `to`.

### Fase 3: Stok, Penggunaan Operasional, Notifikasi Stok

| Method | Endpoint | Akses |
| --- | --- | --- |
| GET | `/stok` | semua |
| POST | `/stok` | semua |
| PUT | `/stok/{id}` | semua |
| GET | `/penggunaan-operasional` | semua |
| POST | `/penggunaan-operasional` | semua |
| GET | `/notifikasi` | semua |

Aturan:

- `POST /penggunaan-operasional` **mengurangi `stok.jumlah_stok`** dalam satu DB transaction. Jika stok tidak cukup: 422. `user_id` diisi otomatis.
- ERD tidak punya tabel notifikasi, jadi `GET /notifikasi` **dihitung langsung**: daftar `stok` yang `jumlah_stok <= batas_minimum`. Tidak ada status sudah dibaca.
- `GET /stok` menyertakan flag `is_low` (`jumlah_stok <= batas_minimum`).
- Filter `GET /penggunaan-operasional`: `stok_id`, `from`, `to`.

### Fase 4: Panen (Jadwal dan Hasil)

| Method | Endpoint | Akses |
| --- | --- | --- |
| GET | `/panen` | semua |
| POST | `/panen` | semua |
| PUT | `/panen/{id}` | semua |

Aturan:

- ERD tidak punya tabel jadwal terpisah, jadi tabel `panen` dipakai untuk **jadwal sekaligus hasil**. `status`: `terjadwal` atau `selesai`.
- Saat `terjadwal`, `berat_kg` boleh kosong (kolom dibuat nullable). Saat diubah ke `selesai`, `berat_kg` wajib diisi.
- Satu meja tidak boleh punya dua panen pada `tanggal_panen` yang sama (cek lewat `budidaya.meja_id`): 422. `tanggal_panen` tidak boleh sebelum `tanggal_semai`.
- Skala saat ini: 6 meja, panen bertahap tiap 5 hari.
- Filter: `budidaya_id`, `meja_id`, `status`, `from`, `to`.

### Fase 5: Keuangan (khusus pemilik)

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/transaksi-keuangan` | daftar, filter tanggal dan jenis |
| POST | `/transaksi-keuangan` | catat pemasukan/pengeluaran |
| PUT | `/transaksi-keuangan/{id}` | ubah transaksi |
| GET | `/keuangan/hpp` | kalkulasi HPP |
| GET | `/keuangan/alokasi-kas` | alokasi kas operasional |
| GET | `/keuangan/laporan` | laporan per periode |

Aturan:

- Hasil hitungan disimpan langsung di `transaksi_keuangan`: `jenis` (`pemasukan` / `pengeluaran`), `jumlah`, `persentase_kas`, `kas_operasional`, `laba_bersih`. `panen_id` diisi untuk penjualan hasil panen. `user_id` otomatis.
- Untuk `pemasukan`: pemilik mengisi `persentase_kas` (30 sampai 40, default 35 dari config). **Server** menghitung `kas_operasional = jumlah x persentase_kas / 100` dan `laba_bersih = jumlah - kas_operasional`. Klien tidak boleh mengirim dua nilai hasil itu.
- Untuk `pengeluaran`: `persentase_kas`, `kas_operasional`, `laba_bersih`, dan `panen_id` boleh kosong (kolom nullable).
- `GET /keuangan/hpp` menerima `lubang` atau `panen_id`. Hasil: lubang x 1.200 (config). Untuk `panen_id`, lubang diambil dari `budidaya.jumlah_tanaman`. Jika keduanya kosong, pakai total `kapasitas_lubang` semua meja.
- `GET /keuangan/alokasi-kas?tanggal=YYYY-MM-DD` mengembalikan total pemasukan hari itu dan total `kas_operasional`-nya.
- `GET /keuangan/laporan?from=&to=` mengembalikan total pemasukan, total pengeluaran, total `kas_operasional`, dan total `laba_bersih`.
- Pegawai yang mengakses endpoint ini mendapat 403.

&#91;image: ERD FIX (1).png\]

### Fase 6: Diagnosis Kesehatan (Computer Vision)

| Method | Endpoint | Akses |
| --- | --- | --- |
| POST | `/diagnosis` | semua (multipart) |
| GET | `/diagnosis` | semua |
| GET | `/diagnosis/{id}` | semua |

Aturan:

- Body `POST /diagnosis` (multipart): `image` (file) dan `budidaya_id` (wajib, sesuai ERD). `user_id` dan `tanggal_diagnosis` diisi otomatis.
- Validasi gambar: jpg/jpeg/png, maksimum 5 MB. Simpan ke disk `public`, path-nya masuk kolom `image_path`.
- Isi kolom `hasil`: `sehat`, `busuk_batang`, `cacar_daun`, atau `busuk_akar`.
- `confidence` bertipe `decimal(5,2)`, skala **0 sampai 100** (persen).
- Kolom `rekomendasi` diisi saat diagnosis dibuat, diambil dari peta teks di `config/sipentar.php` sesuai `hasil` (tidak ada tabel rekomendasi di ERD).
- Response menambahkan `disclaimer` (tidak disimpan di DB): "Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung."
- **Buat `DiagnosisService` dengan interface** `classify(string $imagePath): array{hasil, confidence}`. Implementasi awal **stub** (hasil dummy). Nanti diganti implementasi yang memanggil model (HTTP ke service Python) tanpa mengubah controller. URL model dari `.env` (`CV_SERVICE_URL`).

## 7. Aturan Bisnis (ringkasan)

1. `hss` dihitung otomatis dari `budidaya.tanggal_semai`.
2. `penggunaan_operasional` mengurangi `stok.jumlah_stok`.
3. Stok dengan `jumlah_stok <= batas_minimum` muncul di `/notifikasi`.
4. HPP = jumlah lubang x Rp1.200 (dari config).
5. `kas_operasional` = `jumlah` x `persentase_kas` / 100 (30 sampai 40 persen), dihitung server.
6. Pegawai tidak boleh mengakses keuangan, profil, dan karyawan.
7. Diagnosis selalu menyertakan `confidence` dan disclaimer.

## 8. Non-Fungsional

- Semua endpoint (kecuali login) butuh token.
- Rate limit pada login dan upload.
- Index database pada kolom filter.
- Tidak ada query N+1 (gunakan eager loading).
- Gambar tidak bisa diakses tanpa path yang valid, nama file di-random.

## 9. Testing (minimal)

Feature test untuk: login dan role, 403 pegawai ke endpoint keuangan, HSS otomatis, pengurangan stok dan notifikasi, validasi panen ganda di tanggal sama, kalkulasi HPP dan kas, upload diagnosis (dengan stub).

## 10. Di Luar Cakupan

Telemetri air, MQTT, pH/TDS, tabel water\_log, sensor fisik, multi-greenhouse, hapus akun permanen.

## 11. Asumsi yang Perlu Dikonfirmasi

- Omzet dicatat sebagai `transaksi_keuangan` dengan `jenis` = `pemasukan`, dan `panen_id` terisi untuk penjualan hasil panen.
- Rumus `laba_bersih` dipakai `jumlah - kas_operasional` (belum dikurangi HPP). Konfirmasi ke tim apakah rumusnya benar.
- `persentase_kas` diinput per transaksi (30 sampai 40), bukan satu nilai global.
- Nilai `status` pada `meja_tanam`, `budidaya`, dan `panen` adalah usulan, karena ERD hanya menyebut varchar.
- Kolom yang perlu nullable: `panen.berat_kg` (saat terjadwal) serta `panen_id`, `persentase_kas`, `kas_operasional`, `laba_bersih` (untuk pengeluaran).
- ERD tidak punya kolom aktif/nonaktif di `users`, tabel notifikasi, tabel settings, dan tabel rekomendasi. Semuanya diganti config dan hitungan langsung. Tambahkan ke ERD jika tim membutuhkannya.
- Model CV berjalan di server terpisah (bukan on-device).

## 12. Cara Memakai PRD Ini untuk Vibe Coding

Berikan ke AI per fase, dengan pola prompt:

> Baca PRD ini. Kerjakan **Fase N** saja. Ikuti konvensi di bagian 3 dan struktur tabel di bagian 5 (ERD). Buat migration, model, form request, resource, service, controller, route, dan feature test. Jangan ubah endpoint login. Setelah selesai, jelaskan file yang dibuat dan cara mengetesnya.

Setelah tiap fase: jalankan test, cek manual di Postman, lalu commit sebelum lanjut.
