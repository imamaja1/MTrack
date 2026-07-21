# 📄 Product Requirements Document (PRD)

## 📌 Project Overview

- **Project Name**: Multi-Tier Financial, Inventory & Security System
- **Target Audience**:
    1. **Super Admin**: Mengawasi keamanan sistem terpusat, aktivitas pengguna, deteksi anomali, dan _IP Management_.
    2. **Admin (Pemilik Toko)**: Mengelola inventaris produk, staf operator, dan menganalisis laporan keuangan bisnis.
    3. **Operator (Staf)**: Melakukan transaksi penjualan (POS), mencatatkan arus stok, serta mencetak nota pembayaran.
- **Core Goal**: Membangun aplikasi web dan API-ready untuk pencatatan keuangan, inventaris gudang multi-tenant, dan pemantauan keamanan (_SOC Monitoring_).

---

## 🛠️ 1. Technical Stack & Architecture

- **Backend Framework**: Laravel 13.x (PHP 8.3+)
- **API Authentication**: Laravel Sanctum (Pengembangan REST API untuk Aplikasi Android)
- **Starter Kit**: Laravel Starter Kit (Breeze / Jetstream - Livewire Stack)
- **Frontend Reactivity**: Livewire v3, Alpine.js, Tailwind CSS
- **Authentication**:
    - **Super Admin & Admin**: Google OAuth 2.0 (via Laravel Socialite) & Password Login.
    - **Operator**: Email/Username + Password (Akun dibuatkan secara mandiri oleh Admin Toko).
- **Database Engine**: MySQL 8.0+ / PostgreSQL (ACID Compliant for Stock Locking)
- **Execution Environment**: Antigravity Compatible Workflow

---

## 🔐 2. Security & Anomaly Detection Specifications

### 2.1 Brute-Force & Throttle Control

- **Failed Login Threshold**: Maksimal 5x percobaan login gagal berturut-turut.
- **Throttle Mechanism**: Otomatis menerapkan _cooldown_ selama 15 menit menggunakan Laravel Rate Limiter.
- **Manual IP Blocking**: Super Admin dapat meninjau log kegagalan login dan melakukan pemblokiran IP secara manual (_Manual Ban/Block_).

### 2.2 URL Anomaly & Access Violation Logging

- **Mechanism**: Custom Middleware `AuditTrailMiddleware` yang mencegat setiap HTTP Request.
- **Triggers**:
    - Akses halaman terlarang (misal: Operator mencoba membuka route `/admin/*` atau `/superadmin/*`).
    - Percobaan manipulasi payload yang mencurigakan (SQL Injection / XSS patterns).
    - Request yang menghasilkan response HTTP 401 (Unauthorized) dan 403 (Forbidden).
- **Action**: Menyimpan log ke tabel `security_logs` (_IP, User ID, Requested URL, Payload, User-Agent, Timestamp_) dan menampilkan _badge notification_ di Dashboard Super Admin.

---

## 📊 3. Role Access & Permission Matrix

| Feature / Module                 |  Super Admin   |  Admin (Toko)  |    Operator (Staf)    |
| -------------------------------- | :------------: | :------------: | :-------------------: |
| Security Operations & Audit Logs | ✅ Full Access |     ❌ No      |         ❌ No         |
| Manual IP Blocking Manager       | ✅ Full Access |     ❌ No      |         ❌ No         |
| Manage Admin Accounts            | ✅ Full Access |     ❌ No      |         ❌ No         |
| Google OAuth Login               |  ✅ Supported  |  ✅ Supported  |     ❌ Forbidden      |
| Manage Operator Accounts         |     ❌ No      | ✅ Full Access |         ❌ No         |
| Product & Inventory Management   |     ❌ No      | ✅ Full Access | 👁️ View & Select Only |
| Stock Movement Audit Log         |     ❌ No      | ✅ Full Access |    👁️ View Own Log    |
| Point of Sale (POS) & Print Nota |     ❌ No      | ✅ Full Access |    ✅ Full Access     |
| Complex Financial Reporting      |     ❌ No      | ✅ Full Access |         ❌ No         |

---

## 🗄️ 4. Database Schema & Migration Spec

### Table: `users`

| Column       | Type          | Constraints                             | Description                           |
| ------------ | ------------- | --------------------------------------- | ------------------------------------- |
| `id`         | BigIncrements | PRIMARY KEY                             | ID Pengguna                           |
| `admin_id`   | BigInteger    | NULLABLE, FK -> `users.id`              | Indikator Toko (jika role = Operator) |
| `name`       | String(255)   | NOT NULL                                | Nama Pengguna                         |
| `email`      | String(255)   | UNIQUE, NOT NULL                        | Email Pengguna                        |
| `password`   | String(255)   | NOT NULL                                | Password Hashed                       |
| `role`       | Enum          | `'superadmin'`, `'admin'`, `'operator'` | Level Otorisasi                       |
| `google_id`  | String(255)   | NULLABLE                                | Google OAuth ID                       |
| `is_active`  | Boolean       | DEFAULT true                            | Status Keaktifan Akun                 |
| `timestamps` | Timestamp     | NULLABLE                                | Standard Timestamps                   |

### Table: `blocked_ips`

| Column       | Type          | Constraints      | Description              |
| ------------ | ------------- | ---------------- | ------------------------ |
| `id`         | BigIncrements | PRIMARY KEY      | ID Log                   |
| `ip_address` | String(45)    | UNIQUE, NOT NULL | Alamat IP yang Dibatasi  |
| `reason`     | Text          | NOT NULL         | Alasan Pemblokiran       |
| `blocked_by` | BigInteger    | FK -> `users.id` | ID Super Admin Eksekutor |
| `timestamps` | Timestamp     | NULLABLE         | Standard Timestamps      |

### Table: `security_logs`

| Column         | Type          | Constraints                                                     | Description                   |
| -------------- | ------------- | --------------------------------------------------------------- | ----------------------------- |
| `id`           | BigIncrements | PRIMARY KEY                                                     | ID Log                        |
| `user_id`      | BigInteger    | NULLABLE, FK -> `users.id`                                      | ID User (jika terautentikasi) |
| `ip_address`   | String(45)    | NOT NULL                                                        | Alamat IP Request             |
| `event_type`   | Enum          | `'failed_login'`, `'unauthorized_url'`, `'suspicious_activity'` | Jenis Anomali                 |
| `url_accessed` | String(255)   | NOT NULL                                                        | Endpoint yang Diakses         |
| `payload`      | Text          | NULLABLE                                                        | Request Payload / Input       |
| `user_agent`   | Text          | NOT NULL                                                        | Informasi Browser/Device      |
| `timestamps`   | Timestamp     | NULLABLE                                                        | Standard Timestamps           |

### Table: `products`

| Column            | Type          | Constraints        | Description                  |
| ----------------- | ------------- | ------------------ | ---------------------------- |
| `id`              | BigIncrements | PRIMARY KEY        | ID Produk                    |
| `admin_id`        | BigInteger    | FK -> `users.id`   | Indikator Toko Milik Admin   |
| `sku`             | String(100)   | NOT NULL           | Kode Stok / Barcode          |
| `name`            | String(255)   | NOT NULL           | Nama Produk                  |
| `cost_price`      | Decimal(15,2) | NOT NULL, UNSIGNED | Harga Modal (HPP)            |
| `selling_price`   | Decimal(15,2) | NOT NULL, UNSIGNED | Harga Jual                   |
| `stock`           | Integer       | NOT NULL, UNSIGNED | Sisa Stok Saat Ini           |
| `min_stock_alert` | Integer       | DEFAULT 5          | Ambang Batas Peringatan Stok |
| `timestamps`      | Timestamp     | NULLABLE           | Standard Timestamps          |

### Table: `stock_movements`

| Column             | Type          | Constraints                               | Description                |
| ------------------ | ------------- | ----------------------------------------- | -------------------------- |
| `id`               | BigIncrements | PRIMARY KEY                               | ID Log Pergerakan          |
| `product_id`       | BigInteger    | FK -> `products.id`                       | ID Produk Terkait          |
| `user_id`          | BigInteger    | FK -> `users.id`                          | ID Eksekutor Transaksi     |
| `type`             | Enum          | `'in'`, `'out'`, `'sale'`, `'adjustment'` | Jenis Alur Barang          |
| `qty`              | Integer       | NOT NULL                                  | Jumlah Perubahan           |
| `stock_before`     | Integer       | NOT NULL                                  | Stok Sebelum Transaksi     |
| `stock_after`      | Integer       | NOT NULL                                  | Stok Setelah Transaksi     |
| `reference_number` | String(100)   | NULLABLE                                  | Nomor Invoice/Nota Terkait |
| `notes`            | Text          | NULLABLE                                  | Catatan Keterangan         |
| `timestamps`       | Timestamp     | NULLABLE                                  | Standard Timestamps        |

### Table: `transactions`

| Column           | Type          | Constraints                      | Description           |
| ---------------- | ------------- | -------------------------------- | --------------------- |
| `id`             | BigIncrements | PRIMARY KEY                      | ID Transaksi          |
| `invoice_number` | String(100)   | UNIQUE, NOT NULL                 | Nomor Nota Penjualan  |
| `admin_id`       | BigInteger    | FK -> `users.id`                 | ID Admin Toko         |
| `operator_id`    | BigInteger    | FK -> `users.id`                 | ID Kasir / Operator   |
| `total_cost`     | Decimal(15,2) | NOT NULL                         | Total HPP Transaksi   |
| `total_amount`   | Decimal(15,2) | NOT NULL                         | Total Nilai Penjualan |
| `profit`         | Decimal(15,2) | NOT NULL                         | Total Laba Bersih     |
| `payment_method` | Enum          | `'cash'`, `'qris'`, `'transfer'` | Metode Pembayaran     |
| `timestamps`     | Timestamp     | NULLABLE                         | Standard Timestamps   |

### Table: `transaction_items`

| Column               | Type          | Constraints             | Description          |
| -------------------- | ------------- | ----------------------- | -------------------- |
| `id`                 | BigIncrements | PRIMARY KEY             | ID Item              |
| `transaction_id`     | BigInteger    | FK -> `transactions.id` | ID Transaksi Utama   |
| `product_id`         | BigInteger    | FK -> `products.id`     | ID Produk            |
| `qty`                | Integer       | NOT NULL, UNSIGNED      | Jumlah Dibeli        |
| `unit_cost_price`    | Decimal(15,2) | NOT NULL                | Harga Modal per Unit |
| `unit_selling_price` | Decimal(15,2) | NOT NULL                | Harga Jual per Unit  |
| `subtotal`           | Decimal(15,2) | NOT NULL                | Total Subtotal Item  |

---

## ⚡ 5. Business Logic & Acceptance Criteria

### 5.1 Race Condition Prevention (Stock Locking)

- **Problem Statement**: Menghindari kondisi _overselling_ saat beberapa Operator memproses transaksi barang yang sama secara simultan.
- **Implementation Standard**:

    ```php
    use Illuminate\Support\Facades\DB;

    DB::transaction(function () use ($productId,$quantityRequested) {
        $product = Product::where('id',$productId)->lockForUpdate()->first();

        if ($product->stock <$quantityRequested) {
            throw new \Exception("Stok tidak mencukupi untuk produk {$product->name}.");
        }

        $product->decrement('stock',$quantityRequested);
        // Buat Record Transaction & Stock Movements...
    });
    ```

* [cite_start]**Acceptance Criteria**: Transaksi beruntun kedua akan ditolak dengan pesan error otomatis jika stok telah dihabiskan oleh transaksi terdahulu[cite: 100].

### 5.2 Module Thermal Receipt Print

- [cite_start]**Implementation**: Komponen Livewire menyiarkan event pemicu pencetakan browser yang teroptimasi format Thermal Printer (58mm/80mm) dan dokumen PDF[cite: 101].
- **Receipt Blueprint**:
    - [cite_start]**Header**: Logo Toko, Nama Toko, Nama Operator, Nomor Invoice, Tanggal & Waktu[cite: 102].
    - [cite_start]**Body**: List Rincian Barang (Nama, Qty, Harga Satuan, Subtotal)[cite: 103].
    - [cite_start]**Footer**: Total Belanja, Metode Pembayaran, Nominal Bayar, Kembalian, dan Ucapan Terima Kasih[cite: 104].

### 5.3 Advanced Financial & Inventory Analytics

1. [cite_start]**Laporan Laba Rugi (Profit & Loss)**[cite: 105]:
    - [cite_start]`Gross Sales` = Total nilai penjualan kotor[cite: 105].
    - [cite_start]`COGS (HPP)` = Total modal barang terjual[cite: 106].
    - [cite_start]`Gross Profit` = Gross Sales - COGS[cite: 106].
2. **Audit Trailing Pergerakan Stok**:
    - [cite_start]Melacak rekap harian stok masuk, stok keluar, serta log aktivitas spesifik operator (_Who, What, When, Remaining Stock_)[cite: 107].
3. **Peringatan Stok Kritis (Stock Alert)**:
    - [cite_start]Menampilkan daftar otomatis produk yang menyentuh atau berada di bawah nilai `min_stock_alert`[cite: 108].

---

## 🌐 6. API Architecture (For Future Android App)

[cite_start]Sistem wajib menyediakan RESTful API terproteksi token **Laravel Sanctum**[cite: 109]:

- [cite_start]`POST /api/v1/auth/login` - Autentikasi user & penerbitan Bearer Token[cite: 109].
- [cite_start]`GET /api/v1/products` - Pengambilan daftar produk & sisa stok terkini[cite: 110].
- [cite_start]`POST /api/v1/transactions` - Pengiriman transaksi penjualan dari Mobile POS[cite: 111].
- [cite_start]`GET /api/v1/reports/summary` - Penyediaan data statistik ringkas untuk Mobile Dashboard Admin[cite: 112].

---

## 📑 7. Step-by-Step AI Implementation Plan

### Phase 1: Foundation & Security Setup

1. [cite_start]Setup Laravel 13, Starter Kit (Livewire Stack), Laravel Socialite, dan Laravel Sanctum[cite: 113].
2. [cite_start]Jalankan skema migration database sesuai rancangan ERD[cite: 114].
3. [cite_start]Buat custom middleware: `RoleMiddleware`, `CheckBlockedIP`, dan `AuditTrailMiddleware`[cite: 114].

### Phase 2: Super Admin Security Portal

1. [cite_start]Bangun Livewire Component `SecurityDashboard` untuk memantau log keamanan[cite: 115].
2. [cite_start]Implementasikan fitur pendaftaran dan pencabutan blokir IP secara manual[cite: 116].

### Phase 3: Store Management (Admin)

1. [cite_start]Buat modul CRUD Pengelolaan Akun Operator (terisolasi per Admin Toko)[cite: 117].
2. [cite_start]Buat modul CRUD Master Produk (SKU, Harga Modal, Harga Jual, Peringatan Stok Minimal)[cite: 118].

### Phase 4: POS Engine & Print Integration

1. [cite_start]Bangun UI Kasir (POS) berbasis Livewire dengan pencarian produk cepat[cite: 119].
2. [cite_start]Terapkan `lockForUpdate()` dalam penanganan checkout transaksi[cite: 120].
3. [cite_start]Buat modul cetak nota dengan CSS khusus `@media print`[cite: 120].

### Phase 5: Reporting & REST API Expansion

1. [cite_start]Buat visualisasi grafik interaktif (Chart.js) untuk Laporan Laba/Rugi dan Performa Operator[cite: 121].
2. [cite_start]Sediakan API Controller Sanctum untuk kesiapan integrasi aplikasi Android[cite: 122].

===================================================================================================

# 📄 Product Requirements Document (PRD)

## 📌 1. Project Overview & Context
* **Project Name**: Enterprise Multi-Tier Financial, Inventory & Security Management System
* **Target Audience**: 
  1. **Super Admin**: Mengawasi keamanan sistem terpusat, aktivitas pengguna, deteksi anomali akses, dan manajemen pemblokiran IP secara manual.
  2. **Admin (Pemilik Toko)**: Mengelola inventaris produk, akun staf operator, serta menganalisis laporan keuangan dan performa toko.
  3. **Operator (Staf)**: Melakukan transaksi penjualan (POS), mencatatkan pergerakan barang, serta mencetak nota pembayaran.
* **Core Goal**: Membangun aplikasi web dan API-ready untuk pencatatan keuangan, inventaris gudang terpusat (multi-tenant per toko), dan pemantauan keamanan (Security Operations Center / SOC Monitoring).

---

## 🛠️ 2. Technical Stack & Infrastructure Spec
* **Backend Framework**: Laravel 13.x (PHP 8.3+)
* **API Authentication**: Laravel Sanctum (Kesiapan REST API untuk integrasi Aplikasi Android)
* **Starter Kit**: Laravel Starter Kit (Breeze / Jetstream - Livewire Stack)
* **Frontend Reactivity**: Livewire v3, Alpine.js, Tailwind CSS
* **Authentication Rules**:
  * **Super Admin & Admin**: Mendukung Google OAuth 2.0 (via Laravel Socialite) & Login Email/Password.
  * **Operator**: Murni Email/Username + Password (Akun dibuatkan secara mandiri oleh Admin Toko).
* **Database Engine**: MySQL 8.0+ / PostgreSQL (ACID Compliant for Transaction Locking)
* **Execution Environment**: Antigravity Compatible Workflow

---

## 🔐 3. Security & Anomaly Detection Specifications

### 3.1 Algoritma Brute-Force & Throttle Control
1. **Penerimaan Input**: Sistem menerima kredensial login (Email/Username & Password).
2. **Pengecekan IP**: Sistem memeriksa apakah IP pengakses terdaftar pada tabel IP terblokir. Jika terdaftar, akses langsung ditolak.
3. **Pengecekan Ambang Gagal (Failed Login Threshold)**:
   * Batas maksimal kegagalan login adalah **5 kali berturut-turut**.
   * Jika kegagalan $\ge 5$ kali: Sistem otomatis menerapkan *cooldown* (pembatasan waktu) selama 15 menit menggunakan Laravel Rate Limiter.
4. **Verifikasi Kredensial**:
   * **Jika Sukses**: Hitungan kegagalan direset ke 0, dan pengguna diarahkan ke Dashboard sesuai perannya.
   * **Jika Gagal**: Hitungan kegagalan bertambah 1, log kegagalan dicatat ke database, dan pesan kesalahan ditampilkan.
5. **Manual IP Blocking**: Super Admin memiliki dashboard untuk meninjau log kegagalan login dan dapat melakukan pemblokiran IP secara manual (*Manual Ban/Block*).

### 3.2 Algoritma URL Anomaly & Access Violation Logging
1. **Mencegat HTTP Request**: Middleware `AuditTrailMiddleware` menganalisis setiap permintaan halaman yang masuk.
2. **Pemicu Deteksi (Triggers)**:
   * Operator mencoba mengakses route terlarang (misal: `/admin/*` atau `/superadmin/*`).
   * Adanya muatan (*payload*) yang mengindikasikan pola berbahaya (SQL Injection / XSS).
   * Request yang menghasilkan respon HTTP Status `401 Unauthorized` atau `403 Forbidden`.
3. **Aksi Sistem**:
   * Memblokir request dan menyimpan log ke tabel `security_logs` (*IP, User ID, URL, Payload, User-Agent, Timestamp*).
   * Mengirimkan *badge notification* / peringatan visual di Dashboard Super Admin.

---

## 📊 4. Multi-Tenant Role & Permission Matrix

| Feature / Module | Super Admin | Admin (Toko) | Operator (Staf) |
|---|:---:|:---:|:---:|
| Security Operations & Audit Logs | ✅ Full Access | ❌ No | ❌ No |
| Manual IP Blocking Manager | ✅ Full Access | ❌ No | ❌ No |
| Manage Admin Accounts | ✅ Full Access | ❌ No | ❌ No |
| Google OAuth Login | ✅ Supported | ✅ Supported | ❌ Forbidden |
| Manage Operator Accounts | ❌ No | ✅ Full Access | ❌ No |
| Product & Inventory Management | ❌ No | ✅ Full Access | 👁️ View & Select Only |
| Stock Movement Audit Log | ❌ No | ✅ Full Access | 👁️ View Own Log |
| Point of Sale (POS) & Print Nota | ❌ No | ✅ Full Access | ✅ Full Access |
| Advanced Financial Reporting | ❌ No | ✅ Full Access | ❌ No |

---

## 🗄️ 5. Database Schema Spec

### Table: `users`
* `id` (BigIncrements, PK)
* `admin_id` (BigInteger, Nullable, FK -> users.id) *(Indikator Toko jika role = Operator)*
* `name` (String)
* `email` (String, Unique)
* `password` (String)
* `role` (Enum: `'superadmin'`, `'admin'`, `'operator'`)
* `google_id` (String, Nullable)
* `is_active` (Boolean, Default: true)
* `timestamps`

### Table: `blocked_ips`
* `id` (BigIncrements, PK)
* `ip_address` (String, Unique)
* `reason` (Text)
* `blocked_by` (BigInteger, FK -> users.id)
* `timestamps`

### Table: `security_logs`
* `id` (BigIncrements, PK)
* `user_id` (BigInteger, Nullable, FK -> users.id)
* `ip_address` (String)
* `event_type` (Enum: `'failed_login'`, `'unauthorized_url'`, `'suspicious_activity'`)
* `url_accessed` (String)
* `payload` (Text, Nullable)
* `user_agent` (Text)
* `timestamps`

### Table: `products`
* `id` (BigIncrements, PK)
* `admin_id` (BigInteger, FK -> users.id) *(Indikator Toko Milik Admin)*
* `sku` (String, Unique per Admin Toko)
* `name` (String)
* `cost_price` (Decimal 15,2) *(Harga Modal / HPP)*
* `selling_price` (Decimal 15,2) *(Harga Jual)*
* `stock` (Integer, Unsigned)
* `min_stock_alert` (Integer, Default: 5)
* `timestamps`

### Table: `stock_movements`
* `id` (BigIncrements, PK)
* `product_id` (BigInteger, FK -> products.id)
* `user_id` (BigInteger, FK -> users.id) *(Eksekutor)*
* `type` (Enum: `'in'`, `'out'`, `'sale'`, `'adjustment'`)
* `qty` (Integer)
* `stock_before` (Integer)
* `stock_after` (Integer)
* `reference_number` (String, Nullable)
* `notes` (Text, Nullable)
* `timestamps`

### Table: `transactions`
* `id` (BigIncrements, PK)
* `invoice_number` (String, Unique)
* `admin_id` (BigInteger, FK -> users.id)
* `operator_id` (BigInteger, FK -> users.id)
* `total_cost` (Decimal 15,2)
* `total_amount` (Decimal 15,2)
* `profit` (Decimal 15,2)
* `payment_method` (Enum: `'cash'`, `'qris'`, `'transfer'`)
* `timestamps`

### Table: `transaction_items`
* `id` (BigIncrements, PK)
* `transaction_id` (BigInteger, FK -> transactions.id)
* `product_id` (BigInteger, FK -> products.id)
* `qty` (Integer, Unsigned)
* `unit_cost_price` (Decimal 15,2)
* `unit_selling_price` (Decimal 15,2)
* `subtotal` (Decimal 15,2)

---

## ⚡ 6. Core Logic & Functional Specifications

### 6.1 Algoritma Pencegahan Race Condition (Optimistic/Pessimistic Locking)
1. **Mulai Transaksi Checkout**: Operator mengirim data keranjang belanja.
2. **Jalankan Terisolasi**: Sistem membuka blok transaksi database (`DB::transaction`).
3. **Pemberian Kunci Data**: Untuk setiap item, sistem mengunci baris data produk terkait (`lockForUpdate()`).
4. **Validasi Ketersediaan Stok**:
   * **Jika Stok Terkini < Qty Dibeli**: Transaksi dibatalkan secara penuh (*Rollback*). Sistem menampilkan pesan error: *"Stok barang X telah diperbarui oleh pengguna lain dan tidak mencukupi."*
   * **Jika Stok Terkini $\ge$ Qty Dibeli**:
     * Kurangi stok produk secara atomik.
     * Buat catatan log pergerakan stok (`stock_movements`).
     * Hitung total HPP, total harga jual, dan laba kotor.
5. **Komit & Selesai**: Simpan data transaksi utama beserta rincian item, lalu tutup kunci transaksi.

### 6.2 Modul Thermal Receipt Print
* **Implementation**: Livewire menyiarkan event browser (`dispatchBrowserEvent`) untuk memicu fungsi cetak otomatis menggunakan CSS khusus `@media print`.
* **Cetak Format**: Teroptimasi untuk Thermal Printer 58mm/80mm dan Ekspor PDF.
* **Receipt Blueprint**:
  * **Header**: Logo Toko, Nama Toko/Admin, Nama Operator, Nomor Invoice, Tanggal & Waktu.
  * **Body**: Rincian Barang (Nama Produk, Qty, Harga Satuan, Subtotal).
  * **Footer**: Total Belanja, Metode Pembayaran, Nominal Bayar, Kembalian, dan Ucapan Terima Kasih.

### 6.3 Algoritma Analytics & Laporan Keuangan

1. **Laporan Laba Rugi (Profit & Loss Statement)**:
   * `Gross Sales` = Sum(`total_amount`) seluruh transaksi pada rentang tanggal terpilih.
   * `COGS / HPP` = Sum(`total_cost`) seluruh transaksi pada rentang tanggal terpilih.
   * `Gross Profit` = `Gross Sales` - `COGS`.
2. **Audit Trailing Pergerakan Stok**:
   * Rekapitulasi harian stok masuk, stok keluar, serta pencatatan mendetail aktivitas spesifik operator (*Who, What, When, Remaining Stock*).
3. **Peringatan Stok Kritis (Stock Alert)**:
   * Evaluasi sistem otomatis untuk memfilter produk yang nilai `stock` saat ininya $\le$ `min_stock_alert`.
   * Menampilkan daftar produk paling kritis pada Widget Dashboard Admin.

---

## 🌐 7. API Architecture (For Future Android App)

Sistem wajib menyediakan RESTful API terproteksi token **Laravel Sanctum**:

* `POST /api/v1/auth/login` - Autentikasi user & penerbitan Bearer Token.
* `GET /api/v1/products` - Pengambilan daftar produk & sisa stok terkini.
* `POST /api/v1/transactions` - Pengiriman transaksi penjualan dari Mobile POS.
* `GET /api/v1/reports/summary` - Penyediaan data statistik ringkas untuk Mobile Dashboard Admin.

---

## 📑 8. Step-by-Step AI Implementation Plan

### Phase 1: Foundation & Security Setup
1. Setup Laravel 13, Starter Kit (Livewire Stack), Laravel Socialite, dan Laravel Sanctum.
2. Jalankan skema migration database sesuai rancangan ERD.
3. Buat custom middleware: `RoleMiddleware`, `CheckBlockedIP`, dan `AuditTrailMiddleware`.

### Phase 2: Super Admin Security Portal
1. Bangun Livewire Component `SecurityDashboard` untuk memantau log keamanan.
2. Implementasikan fitur pendaftaran dan pencabutan pemblokiran IP secara manual.

### Phase 3: Store Management (Admin)
1. Buat modul CRUD Pengelolaan Akun Operator (terisolasi per Admin Toko).
2. Buat modul CRUD Master Produk (SKU, Harga Modal, Harga Jual, Peringatan Stok Minimal).

### Phase 4: POS Engine & Print Integration
1. Bangun UI Kasir (POS) berbasis Livewire dengan pencarian produk cepat.
2. Terapkan `lockForUpdate()` dalam penanganan checkout transaksi.
3. Buat modul cetak nota dengan CSS khusus `@media print`.

### Phase 5: Reporting & REST API Expansion
1. Buat visualisasi grafik interaktif (Chart.js) untuk Laporan Laba/Rugi dan Performa Operator.
2. Sediakan API Controller Sanctum untuk kesiapan integrasi aplikasi Android.