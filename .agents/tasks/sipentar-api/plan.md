# Implementation Plan: SIPENTAR REST API (Fase 0-5)

This plan implements a complete Laravel 13 REST API for hydroponic greenhouse management (SIPENTAR). Work proceeds sequentially through 6 fases, each building on the previous.

## FASE 0: Fondasi

- [ ] 1. **Delete water_logs artifacts**
      Remove database/migrations/2026_10_28_061637_create_water_logs_table.php, app/Models/WaterLog.php, database/factories/WaterLogFactory.php (telemetry feature removed from system).
      Files: database/migrations/2026_10_28_061637_create_water_logs_table.php, app/Models/WaterLog.php, database/factories/WaterLogFactory.php
      Verify: Files deleted, php artisan migrate:fresh runs without water_logs table error

- [ ] 2. **Update UserRole enum**
      Change app/Enums/UserRole.php: ADMIN→PEMILIK, KARYAWAN→PEGAWAI. Enum string values remain lowercase 'pemilik', 'pegawai' for database storage.
      Files: app/Enums/UserRole.php
      Verify: Enum has PEMILIK and PEGAWAI cases; existing login tests still pass after enum update

- [ ] 3. **Fix model relationship class references**
      app/Models/Budidaya.php: belongsTo(Users::class)→belongsTo(User::class), belongsTo(MejaTanams::class)→belongsTo(MejaTanam::class); add hasMany for panens, diagnoses.
      app/Models/Panen.php: belongsTo(Budidayas::class)→belongsTo(Budidaya::class); add hasMany for transaksiKeuangans.
      app/Models/TransaksiKeuangan.php: belongsTo(Users::class)→belongsTo(User::class), belongsTo(Panens::class)→belongsTo(Panen::class).
      app/Models/User.php: add hasMany for budidayas, diagnoses, penggunaanOperasionals, transaksiKeuangans.
      app/Models/MejaTanam.php: add hasMany for budidayas.
      Files: app/Models/Budidaya.php, app/Models/Panen.php, app/Models/TransaksiKeuangan.php, app/Models/User.php, app/Models/MejaTanam.php
      Verify: php artisan test passes; no class not found errors

- [ ] 4. **Fix SuperAdminSeeder**
      database/seeders/SuperAdminSeeder.php: fix updateOrCreate syntax (remove nested array, first param is attributes array, second is values), change role to UserRole::PEMILIK.
      Files: database/seeders/SuperAdminSeeder.php
      Verify: php artisan db:seed --class=SuperAdminSeeder creates 1 pemilik user without errors

- [ ] 5. **Create MejaTanamSeeder**
      Create database/seeders/MejaTanamSeeder.php inserting 6 records: nomor_meja 1-6, kapasitas_lubang values totaling ~2500 (e.g., 400, 420, 410, 425, 415, 430), status 'kosong'. Update database/seeders/DatabaseSeeder.php to call SuperAdminSeeder and MejaTanamSeeder.
      Files: database/seeders/MejaTanamSeeder.php, database/seeders/DatabaseSeeder.php
      Verify: php artisan db:seed creates 6 meja_tanam records; SELECT SUM(kapasitas_lubang) FROM meja_tanams returns ~2500

- [ ] 6. **Create nullable columns migration**
      php artisan make:migration make_columns_nullable_in_panens_and_transaksi_keuangans: make panens.berat_kg nullable; make transaksi_keuangans.panen_id, persentase_kas, kas_operasional, laba_bersih nullable (for pengeluaran records and terjadwal panen).
      Files: database/migrations/YYYY_MM_DD_HHMMSS_make_columns_nullable_in_panens_and_transaksi_keuangans.php
      Verify: php artisan migrate applies migration; describe panens and transaksi_keuangans shows specified columns as nullable

- [ ] 7. **Create EnsureUserHasRole middleware**
      Create app/Http/Middleware/EnsureUserHasRole.php: handle($request, Closure $next, ...$roles) checks auth()->user()->role->value in $roles array, returns 403 JSON {success: false, message: 'Anda tidak memiliki akses ke resource ini.'} if unauthorized. Register as 'role' alias in bootstrap/app.php withMiddleware callback using $middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]).
      Files: app/Http/Middleware/EnsureUserHasRole.php, bootstrap/app.php
      Verify: Create test route with role:pemilik middleware, login as pegawai (create via factory), request returns 403 with proper JSON format

- [ ] 8. **Add GET /api/v1/me endpoint**
      Add route in routes/api.php under /api/v1 prefix with auth:sanctum middleware: GET /me returns new UserResource(auth()->user()).
      Files: routes/api.php
      Verify: curl -X GET http://localhost:8000/api/v1/me -H 'Authorization: Bearer {token}' returns authenticated user data

- [ ] 9. **Update timezone to Asia/Jakarta**
      config/app.php: change 'timezone' from 'UTC' to 'Asia/Jakarta'.
      Files: config/app.php
      Verify: grep timezone config/app.php shows 'Asia/Jakarta'

- [ ] 10. **Create config/sipentar.php**
       Create config/sipentar.php with: hpp_per_hole => env('HPP_PER_HOLE', 1200), cash_allocation => ['min' => env('CASH_ALLOCATION_MIN', 30), 'max' => env('CASH_ALLOCATION_MAX', 40), 'default' => env('CASH_ALLOCATION_DEFAULT', 35)], diagnosis_recommendations => ['sehat' => 'Tanaman sehat...', 'busuk_batang' => 'Tanaman terkena busuk batang...', 'cacar_daun' => 'Tanaman terkena cacar daun...', 'busuk_akar' => 'Tanaman terkena busuk akar...'], cv_service_url => env('CV_SERVICE_URL').
       Files: config/sipentar.php
       Verify: php artisan tinker, config('sipentar.hpp_per_hole') returns 1200

- [ ] 11. **Update .env.example**
       Add: HPP_PER_HOLE=1200, CASH_ALLOCATION_MIN=30, CASH_ALLOCATION_MAX=40, CASH_ALLOCATION_DEFAULT=35, CV_SERVICE_URL=http://localhost:5000.
       Files: .env.example
       Verify: grep HPP_PER_HOLE .env.example shows the value

- [ ] 12. **Update ApiResponse trait**
       app/Traits/ApiResponse.php: ensure data field always present in response (set to null when $data parameter is null, not omitted). Change successResponse to always include 'data' => $data in response array (remove the conditional).
       Files: app/Traits/ApiResponse.php
       Verify: Test endpoint returns {success: true, message: "OK", data: null} when no data provided

- [ ] 13. **Verify exception handler**
       Confirm app/Exceptions/Handler.php has Bahasa Indonesia messages: 'Data yang diberikan tidak valid.', 'Tidak terautentikasi.', 'Terjadi kesalahan pada server.' (already implemented, just verify).
       Files: app/Exceptions/Handler.php
       Verify: php artisan test existing tests pass; validation error returns Indonesian message

**Fase 0 Verification:**
```bash
php artisan migrate:fresh --seed
php artisan test
curl -X POST http://localhost:8000/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"admin123"}'
# Use returned token:
curl -X GET http://localhost:8000/api/v1/me -H 'Authorization: Bearer {token}'
```

## FASE 1: Akun dan Karyawan (Pemilik Only)

- [ ] 14. **Create ProfilController and requests**
       Create app/Http/Controllers/Api/V1/ProfilController.php: show() returns UserResource for auth()->user(); update(ProfilUpdateRequest) updates nama, username, optionally password (hash if provided).
       Create app/Http/Requests/Api/V1/ProfilUpdateRequest.php: nama (required, string, max:100), username (required, string, max:50, unique:users,username ignoring current user), password (nullable, string, min:8).
       Files: app/Http/Controllers/Api/V1/ProfilController.php, app/Http/Requests/Api/V1/ProfilUpdateRequest.php
       Verify: php artisan route:list shows /api/v1/profil routes

- [ ] 15. **Create KaryawanController and requests**
       Create app/Http/Controllers/Api/V1/KaryawanController.php: index() paginated list WHERE role='pegawai'; store(KaryawanStoreRequest) creates user with role pegawai; show($id); update($id, KaryawanUpdateRequest).
       Create app/Http/Requests/Api/V1/KaryawanStoreRequest.php: nama, username (unique), password (required, min:8); auto-merge 'role' => 'pegawai' in validated().
       Create app/Http/Requests/Api/V1/KaryawanUpdateRequest.php: same validation, password nullable, username unique excluding $id.
       Files: app/Http/Controllers/Api/V1/KaryawanController.php, app/Http/Requests/Api/V1/KaryawanStoreRequest.php, app/Http/Requests/Api/V1/KaryawanUpdateRequest.php
       Verify: php artisan route:list shows /api/v1/karyawan routes

- [ ] 16. **Add Fase 1 routes**
       routes/api.php: under /api/v1 with auth:sanctum and role:pemilik middleware, add GET /profil, PUT /profil, GET /karyawan (paginated), POST /karyawan, GET /karyawan/{id}, PUT /karyawan/{id}.
       Files: routes/api.php
       Verify: php artisan route:list | grep profil and grep karyawan show routes with role:pemilik middleware

- [ ] 17. **Create Fase 1 tests**
       tests/Feature/Api/V1/ProfilTest.php: pemilik can view/update profile, pegawai gets 403, password update works, username uniqueness validated.
       tests/Feature/Api/V1/KaryawanTest.php: pemilik can CRUD karyawan, pegawai gets 403, role auto-assigned, username unique, password hashed, index only shows pegawai, pagination works.
       Files: tests/Feature/Api/V1/ProfilTest.php, tests/Feature/Api/V1/KaryawanTest.php
       Verify: php artisan test --filter=ProfilTest; php artisan test --filter=KaryawanTest

**Fase 1 Verification:**
```bash
php artisan test --filter=Profil
php artisan test --filter=Karyawan
# Manual: login as pemilik, POST /karyawan creates pegawai, login as that pegawai, GET /profil returns 403
```

## FASE 2: Budidaya dan Meja Tanam (All Roles)

- [ ] 18. **Create BudidayaService**
       Create app/Services/BudidayaService.php: updateMejaStatus($mejaId) queries if meja has budidaya with status 'berjalan', sets meja status to 'terisi' if yes else 'kosong'.
       Files: app/Services/BudidayaService.php
       Verify: Unit test or integration test verifies meja status changes

- [ ] 19. **Add HSS accessor to Budidaya model**
       app/Models/Budidaya.php: add hss accessor using Attribute returning now()->diffInDays($this->tanggal_semai) (integer days since semai).
       Files: app/Models/Budidaya.php
       Verify: Create budidaya with tanggal_semai 5 days ago, $budidaya->hss returns 5

- [ ] 20. **Create BudidayaResource and MejaTanamResource**
       app/Http/Resources/Api/V1/BudidayaResource.php: id, user_id, meja_id, tanggal_semai, jumlah_tanaman, status, hss (accessor), user (UserResource), meja (MejaTanamResource), timestamps.
       app/Http/Resources/Api/V1/MejaTanamResource.php: id, nomor_meja, kapasitas_lubang, status, timestamps.
       Files: app/Http/Resources/Api/V1/BudidayaResource.php, app/Http/Resources/Api/V1/MejaTanamResource.php
       Verify: JSON response includes hss field

- [ ] 21. **Create Budidaya requests**
       app/Http/Requests/Api/V1/BudidayaStoreRequest.php: meja_id (exists), tanggal_semai (date, before_or_equal:today), jumlah_tanaman (integer, min:1), status (in:berjalan,selesai); after() validation checks jumlah_tanaman <= meja.kapasitas_lubang; auto-merge user_id from auth()->id().
       app/Http/Requests/Api/V1/BudidayaUpdateRequest.php: same validation.
       Files: app/Http/Requests/Api/V1/BudidayaStoreRequest.php, app/Http/Requests/Api/V1/BudidayaUpdateRequest.php
       Verify: Test validation fails when jumlah_tanaman > kapasitas or tanggal_semai future

- [ ] 22. **Create BudidayaController**
       app/Http/Controllers/Api/V1/BudidayaController.php: index() with filters ?meja_id, ?status, ?from, ?to, eager load user+meja; store(BudidayaStoreRequest, BudidayaService) calls service->updateMejaStatus after save; show($id); update($id, BudidayaUpdateRequest, BudidayaService) calls service after update.
       Files: app/Http/Controllers/Api/V1/BudidayaController.php
       Verify: php artisan route:list shows budidaya routes

- [ ] 23. **Create MejaTanamController**
       app/Http/Controllers/Api/V1/MejaTanamController.php: index() returns paginated MejaTanamResource.
       Files: app/Http/Controllers/Api/V1/MejaTanamController.php
       Verify: GET /meja-tanam returns 6 meja from seed

- [ ] 24. **Add Fase 2 routes**
       routes/api.php: under /api/v1 with auth:sanctum (NO role restriction), add GET /budidaya, POST /budidaya, GET /budidaya/{id}, PUT /budidaya/{id}, GET /meja-tanam.
       Files: routes/api.php
       Verify: Both pemilik and pegawai can access routes

- [ ] 25. **Create Fase 2 tests**
       tests/Feature/Api/V1/BudidayaTest.php: both roles access endpoints, hss calculated correctly, meja status updates on budidaya create/update, validation (capacity, future date), filters work, no N+1.
       tests/Feature/Api/V1/MejaTanamTest.php: authenticated users list meja, pagination.
       Files: tests/Feature/Api/V1/BudidayaTest.php, tests/Feature/Api/V1/MejaTanamTest.php
       Verify: php artisan test --filter=Budidaya; php artisan test --filter=MejaTanam

**Fase 2 Verification:**
```bash
php artisan test --filter=Budidaya
php artisan test --filter=MejaTanam
# Manual: POST /budidaya, check meja.status becomes 'terisi', PUT to 'selesai', check becomes 'kosong'
```

## FASE 3: Stok, Penggunaan Operasional, Notifikasi (All Roles)

- [ ] 26. **Add is_low accessor and relationships**
       app/Models/Stok.php: add is_low accessor returning $this->jumlah_stok <= $this->batas_minimum; add hasMany penggunaanOperasionals.
       app/Models/PenggunaanOperasional.php: add belongsTo stok and user.
       Files: app/Models/Stok.php, app/Models/PenggunaanOperasional.php
       Verify: Stok with jumlah_stok=5, batas_minimum=10, is_low returns true

- [ ] 27. **Create StokService**
       app/Services/StokService.php: reduceStock($stokId, $jumlah) uses DB::transaction, finds stok, validates jumlah_stok >= $jumlah (throw ValidationException 'Stok tidak mencukupi.' if insufficient), decrement jumlah_stok, save, return.
       Files: app/Services/StokService.php
       Verify: Test reduceStock with insufficient stock throws exception

- [ ] 28. **Create Stok resources and requests**
       app/Http/Resources/Api/V1/StokResource.php: id, nama_bahan, kategori, satuan, jumlah_stok, batas_minimum, is_low, timestamps.
       app/Http/Resources/Api/V1/PenggunaanOperasionalResource.php: id, user_id, stok_id, tanggal, jumlah, keterangan, user, stok, timestamps.
       app/Http/Requests/Api/V1/StokStoreRequest.php and StokUpdateRequest.php: validate all stok fields.
       app/Http/Requests/Api/V1/PenggunaanOperasionalStoreRequest.php: stok_id, tanggal, jumlah, keterangan; auto-merge user_id.
       Files: app/Http/Resources/Api/V1/StokResource.php, app/Http/Resources/Api/V1/PenggunaanOperasionalResource.php, app/Http/Requests/Api/V1/StokStoreRequest.php, app/Http/Requests/Api/V1/StokUpdateRequest.php, app/Http/Requests/Api/V1/PenggunaanOperasionalStoreRequest.php
       Verify: Resources return is_low field

- [ ] 29. **Create StokController and PenggunaanOperasionalController**
       app/Http/Controllers/Api/V1/StokController.php: CRUD with StokResource.
       app/Http/Controllers/Api/V1/PenggunaanOperasionalController.php: index() with filters ?stok_id, ?from, ?to, eager load; store(PenggunaanOperasionalStoreRequest, StokService) calls service->reduceStock then creates record.
       Files: app/Http/Controllers/Api/V1/StokController.php, app/Http/Controllers/Api/V1/PenggunaanOperasionalController.php
       Verify: POST penggunaan-operasional reduces stok.jumlah_stok

- [ ] 30. **Create NotifikasiController**
       app/Http/Controllers/Api/V1/NotifikasiController.php: index() queries Stok::whereRaw('jumlah_stok <= batas_minimum')->get(), returns collection with {id, nama_bahan, kategori, jumlah_stok, batas_minimum, message: 'Stok {nama_bahan} rendah. Sisa: {jumlah_stok} {satuan}'}.
       Files: app/Http/Controllers/Api/V1/NotifikasiController.php
       Verify: GET /notifikasi returns only low-stock items

- [ ] 31. **Add Fase 3 routes**
       routes/api.php: under /api/v1 with auth:sanctum, add GET /stok, POST /stok, GET /stok/{id}, PUT /stok/{id}, GET /penggunaan-operasional, POST /penggunaan-operasional, GET /notifikasi.
       Files: routes/api.php
       Verify: All routes accessible by both roles

- [ ] 32. **Create Fase 3 tests**
       tests/Feature/Api/V1/StokTest.php: CRUD, is_low flag correct.
       tests/Feature/Api/V1/PenggunaanOperasionalTest.php: list+filters, POST reduces stock, 422 on insufficient, transaction rollback, eager loading.
       tests/Feature/Api/V1/NotifikasiTest.php: returns only low stock, empty when none, message format.
       Files: tests/Feature/Api/V1/StokTest.php, tests/Feature/Api/V1/PenggunaanOperasionalTest.php, tests/Feature/Api/V1/NotifikasiTest.php
       Verify: php artisan test --filter=Stok; php artisan test --filter=PenggunaanOperasional; php artisan test --filter=Notifikasi

**Fase 3 Verification:**
```bash
php artisan test --filter=Stok
php artisan test --filter=PenggunaanOperasional
php artisan test --filter=Notifikasi
# Manual: create stok with jumlah_stok=10, POST penggunaan with jumlah=5, verify stok becomes 5
```

## FASE 4: Panen (All Roles)

- [ ] 33. **Update Panen model relationships**
       app/Models/Panen.php: add belongsTo budidaya, hasMany transaksiKeuangans.
       app/Models/Budidaya.php: add hasMany panens.
       Files: app/Models/Panen.php, app/Models/Budidaya.php
       Verify: Relationships work in tinker

- [ ] 34. **Create PanenResource**
       app/Http/Resources/Api/V1/PanenResource.php: id, budidaya_id, tanggal_panen, berat_kg, status, budidaya (nested with meja), timestamps.
       Files: app/Http/Resources/Api/V1/PanenResource.php
       Verify: Resource includes nested budidaya.meja

- [ ] 35. **Create Panen requests**
       app/Http/Requests/Api/V1/PanenStoreRequest.php: budidaya_id, tanggal_panen, berat_kg (required_if:status,selesai), status (in:terjadwal,selesai); after() validation checks tanggal_panen >= budidaya.tanggal_semai AND no existing panen with same budidaya.meja_id and tanggal_panen.
       app/Http/Requests/Api/V1/PanenUpdateRequest.php: same validation excluding current panen id for uniqueness.
       Files: app/Http/Requests/Api/V1/PanenStoreRequest.php, app/Http/Requests/Api/V1/PanenUpdateRequest.php
       Verify: Validation blocks duplicate meja+date, future panen before semai, selesai without berat_kg

- [ ] 36. **Create PanenController**
       app/Http/Controllers/Api/V1/PanenController.php: index() with filters ?budidaya_id, ?meja_id (via budidaya), ?status, ?from, ?to, eager load budidaya.meja; store, show, update.
       Files: app/Http/Controllers/Api/V1/PanenController.php
       Verify: Filters work correctly

- [ ] 37. **Add Fase 4 routes**
       routes/api.php: under /api/v1 with auth:sanctum, add GET /panen, POST /panen, GET /panen/{id}, PUT /panen/{id}.
       Files: routes/api.php
       Verify: Both roles can access

- [ ] 38. **Create Fase 4 tests**
       tests/Feature/Api/V1/PanenTest.php: both roles access, terjadwal with null berat_kg succeeds, selesai requires berat_kg, uniqueness validation (meja+date), tanggal_panen validation, filters, eager loading.
       Files: tests/Feature/Api/V1/PanenTest.php
       Verify: php artisan test --filter=Panen

**Fase 4 Verification:**
```bash
php artisan test --filter=Panen
# Manual: create two panen same meja same date, verify 422; create terjadwal without berat_kg, verify success
```

## FASE 5: Keuangan (Pemilik Only)

- [ ] 39. **Create KeuanganService**
       app/Services/KeuanganService.php: calculateKasOperasional($jumlah, $persentaseKas) using bcmul/bcdiv; calculateLabaBersih($jumlah, $kasOperasional) using bcsub; calculateHpp($lubang) using config hpp_per_hole and bcmul; getAlokasiKas($tanggal) querying TransaksiKeuangan; getLaporan($from, $to) aggregating using bcadd. All return string for precision.
       Files: app/Services/KeuanganService.php
       Verify: Test calculateKasOperasional(1000000, 35) returns '350000.00'

- [ ] 40. **Create TransaksiKeuangan requests**
       app/Http/Requests/Api/V1/TransaksiKeuanganStoreRequest.php: jenis (in:pemasukan,pengeluaran), tanggal, jumlah, panen_id (nullable), persentase_kas (required_if:jenis,pemasukan, between config min/max), keterangan; auto-merge user_id; in after() validation if jenis=pemasukan, inject KeuanganService, calculate and merge kas_operasional and laba_bersih.
       app/Http/Requests/Api/V1/TransaksiKeuanganUpdateRequest.php: same.
       Files: app/Http/Requests/Api/V1/TransaksiKeuanganStoreRequest.php, app/Http/Requests/Api/V1/TransaksiKeuanganUpdateRequest.php
       Verify: Pemasukan auto-calculates financial fields using bcmath

- [ ] 41. **Create TransaksiKeuanganResource**
       app/Http/Resources/Api/V1/TransaksiKeuanganResource.php: all fields including user, panen (conditional).
       Files: app/Http/Resources/Api/V1/TransaksiKeuanganResource.php
       Verify: Resource structure correct

- [ ] 42. **Create TransaksiKeuanganController**
       app/Http/Controllers/Api/V1/TransaksiKeuanganController.php: index() with filters ?jenis, ?from, ?to; store, show, update (calculations in request).
       Files: app/Http/Controllers/Api/V1/TransaksiKeuanganController.php
       Verify: CRUD operations work

- [ ] 43. **Create KeuanganController**
       app/Http/Controllers/Api/V1/KeuanganController.php injecting KeuanganService: hpp(Request) validates ?lubang or ?panen_id (if panen get budidaya.jumlah_tanaman, if neither use sum meja kapasitas), calls service->calculateHpp; alokasiKas(Request) validates ?tanggal, calls service->getAlokasiKas; laporan(Request) validates ?from, ?to, calls service->getLaporan.
       Files: app/Http/Controllers/Api/V1/KeuanganController.php
       Verify: All keuangan endpoints return correct calculations

- [ ] 44. **Add Fase 5 routes**
       routes/api.php: under /api/v1 with auth:sanctum and role:pemilik, add GET /transaksi-keuangan, POST /transaksi-keuangan, GET /transaksi-keuangan/{id}, PUT /transaksi-keuangan/{id}, GET /keuangan/hpp, GET /keuangan/alokasi-kas, GET /keuangan/laporan.
       Files: routes/api.php
       Verify: Pegawai gets 403 on all routes

- [ ] 45. **Create Fase 5 tests**
       tests/Feature/Api/V1/TransaksiKeuanganTest.php: pemilik CRUD, pegawai 403, pemasukan auto-calculations (bcmath precision), pengeluaran nullable fields, persentase_kas validation, user_id auto, filters.
       tests/Feature/Api/V1/KeuanganTest.php: pemilik access, pegawai 403, hpp calculations (with ?lubang, ?panen_id, without params), alokasiKas totals, laporan aggregations, bcmath precision.
       Files: tests/Feature/Api/V1/TransaksiKeuanganTest.php, tests/Feature/Api/V1/KeuanganTest.php
       Verify: php artisan test --filter=TransaksiKeuangan; php artisan test --filter=Keuangan

**Fase 5 Verification:**
```bash
php artisan test --filter=TransaksiKeuangan
php artisan test --filter=Keuangan
# Manual: POST transaksi-keuangan pemasukan jumlah=1000000 persentase_kas=35, verify kas_operasional=350000.00 laba_bersih=650000.00
# Manual: GET /keuangan/hpp?lubang=500, verify total_hpp=600000.00
```

## Final Verification

After all fases complete:

```bash
# Run all tests
php artisan test

# Fresh migration and seed
php artisan migrate:fresh --seed

# Test complete flow
# 1. Login as pemilik
curl -X POST http://localhost:8000/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"admin123"}'

# 2. Create karyawan (pegawai)
curl -X POST http://localhost:8000/api/v1/karyawan -H 'Authorization: Bearer {pemilik_token}' -H 'Content-Type: application/json' -d '{"nama":"Pegawai 1","username":"pegawai1","password":"password123"}'

# 3. Login as pegawai
curl -X POST http://localhost:8000/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"pegawai1","password":"password123"}'

# 4. Create budidaya (as pegawai)
curl -X POST http://localhost:8000/api/v1/budidaya -H 'Authorization: Bearer {pegawai_token}' -H 'Content-Type: application/json' -d '{"meja_id":1,"tanggal_semai":"2024-01-01","jumlah_tanaman":100,"status":"berjalan"}'

# 5. Verify pegawai cannot access keuangan (403)
curl -X GET http://localhost:8000/api/v1/transaksi-keuangan -H 'Authorization: Bearer {pegawai_token}'

# 6. Test pemilik can access keuangan
curl -X GET http://localhost:8000/api/v1/keuangan/hpp?lubang=100 -H 'Authorization: Bearer {pemilik_token}'
```

## Success Criteria

- All 6 fases implemented
- php artisan test passes with 100% success
- All endpoints return proper {success, message, data, errors} format
- All messages in Bahasa Indonesia
- Role-based access control working (403 for unauthorized)
- Database relationships correct (User::class not Users::class)
- No N+1 queries (eager loading implemented)
- Money calculations use bcmath
- Timezone Asia/Jakarta
- Nullable columns working for pengeluaran and terjadwal panen
- HSS auto-calculated
- Meja status auto-updates
- Stock reduction in transaction
- Notifikasi calculated from stok
- HPP, alokasi kas, laporan calculations correct
