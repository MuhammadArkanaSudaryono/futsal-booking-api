# 🏟️ Sistem Booking Lapangan Futsal
**Dokumen Desain Sistem — Proyek Ujian SMK**
**Tech Stack:** Laravel 12 (API) · React + Vite · MySQL · JWT

---

## 1. ANALISIS KEBUTUHAN SISTEM

### Latar Belakang
Sistem booking lapangan futsal berbasis web yang memungkinkan pelanggan memesan lapangan secara online, melihat ketersediaan jadwal real-time, dan melakukan pembayaran. Admin dapat mengelola data lapangan, jadwal, dan transaksi.

### Kebutuhan Fungsional
| No | Kebutuhan | Prioritas |
|----|-----------|-----------|
| 1 | Registrasi & Login pengguna (JWT) | Tinggi |
| 2 | Manajemen lapangan (CRUD) | Tinggi |
| 3 | Cek ketersediaan jadwal real-time | Tinggi |
| 4 | Booking lapangan dengan slot waktu | Tinggi |
| 5 | Upload bukti pembayaran | Tinggi |
| 6 | Konfirmasi booking oleh admin | Tinggi |
| 7 | Riwayat booking user | Sedang |
| 8 | Dashboard statistik admin | Sedang |
| 9 | Export laporan PDF/XLSX | Sedang |
| 10 | Notifikasi status booking | Rendah |

### Kebutuhan Non-Fungsional
- **Keamanan**: Autentikasi JWT, middleware role-based access
- **Performa**: Response API < 500ms
- **Skalabilitas**: Struktur MVC yang terpisah antara backend dan frontend
- **Maintainability**: Kode bersih dengan dokumentasi Postman

---

## 2. FITUR ADMIN DAN USER

### 🔴 Fitur Admin

#### Master Data
- CRUD Lapangan (nama, jenis, harga/jam, foto, status)
- CRUD Jenis Lapangan (futsal, mini soccer, dll)
- CRUD Jadwal Operasional (jam buka–tutup per hari)
- CRUD Promo/Diskon (kode, persen diskon, masa berlaku)
- Manajemen User (lihat, nonaktifkan akun)

#### Transaksi
- Lihat semua booking (filter status, tanggal, lapangan)
- Konfirmasi / Tolak booking
- Verifikasi bukti pembayaran
- Buat booking manual (walk-in)

#### Laporan
- Dashboard statistik (total booking, pendapatan, lapangan terpopuler)
- Export laporan booking ke PDF
- Export laporan keuangan ke XLSX
- Grafik pendapatan per bulan

### 🔵 Fitur User / Pelanggan

#### Akun
- Register & Login (JWT)
- Edit profil & foto avatar
- Ganti password

#### Booking
- Lihat daftar lapangan beserta foto & fasilitas
- Cek ketersediaan jadwal per tanggal
- Buat booking (pilih lapangan → tanggal → jam → konfirmasi)
- Input kode promo
- Upload bukti transfer pembayaran
- Lihat status booking (Menunggu / Dikonfirmasi / Ditolak / Selesai)
- Riwayat booking lengkap
- Batalkan booking (jika belum dikonfirmasi)

---

## 3. USE CASE DIAGRAM (Teks)

```
==============================================================
              SISTEM BOOKING LAPANGAN FUTSAL
==============================================================

AKTOR:
  - Guest (belum login)
  - User/Pelanggan (sudah login)
  - Admin

--------------------------------------------------------------
GUEST:
  [UC-01] Melihat daftar lapangan
  [UC-02] Melihat detail lapangan
  [UC-03] Register akun baru
  [UC-04] Login ke sistem

--------------------------------------------------------------
USER (extends Guest):
  [UC-05] Cek ketersediaan jadwal
  [UC-06] Buat booking lapangan
           └─ includes [UC-05] Cek ketersediaan
           └─ includes [UC-07] Input kode promo (opsional)
  [UC-08] Upload bukti pembayaran
  [UC-09] Lihat status booking
  [UC-10] Lihat riwayat booking
  [UC-11] Batalkan booking
  [UC-12] Edit profil

--------------------------------------------------------------
ADMIN:
  [UC-13] Login admin
  [UC-14] Kelola lapangan (CRUD)
           └─ includes Upload foto lapangan
  [UC-15] Kelola jadwal operasional (CRUD)
  [UC-16] Kelola promo/diskon (CRUD)
  [UC-17] Lihat semua booking
  [UC-18] Konfirmasi / Tolak booking
           └─ includes Verifikasi bukti pembayaran
  [UC-19] Buat booking manual
  [UC-20] Lihat dashboard statistik
  [UC-21] Export laporan PDF
  [UC-22] Export laporan XLSX
  [UC-23] Kelola data user

==============================================================
```

---

## 4. ERD (Entity Relationship Diagram)

### Entitas dan Relasi

```
USERS (1) ──────────────── (N) BOOKINGS
FIELDS (1) ──────────────── (N) BOOKINGS
FIELDS (1) ──────────────── (N) FIELD_IMAGES
FIELD_TYPES (1) ─────────── (N) FIELDS
BOOKINGS (1) ────────────── (N) BOOKING_DETAILS
BOOKINGS (1) ────────────── (1) PAYMENTS
PROMOTIONS (1) ──────────── (N) BOOKINGS
TIME_SLOTS (1) ──────────── (N) BOOKING_DETAILS
FIELDS (1) ──────────────── (N) TIME_SLOTS
```

### Kardinalitas Lengkap
| Relasi | Tipe |
|--------|------|
| User → Bookings | One-to-Many |
| Field → Bookings | One-to-Many |
| FieldType → Fields | One-to-Many |
| Field → FieldImages | One-to-Many |
| Field → TimeSlots | One-to-Many |
| Booking → BookingDetails | One-to-Many |
| Booking → Payment | One-to-One |
| Promotion → Bookings | One-to-Many |

---

## 5. STRUKTUR DATABASE MySQL

```sql
-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE users (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(20),
    avatar      VARCHAR(255),               -- path file foto
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: field_types
-- ============================================
CREATE TABLE field_types (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,       -- Futsal, Mini Soccer, dll
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: fields (lapangan)
-- ============================================
CREATE TABLE fields (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_type_id   BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    description     TEXT,
    price_per_hour  DECIMAL(10,2) NOT NULL,
    facilities      JSON,                   -- ["toilet","parkir","kantin"]
    status          ENUM('active','inactive','maintenance') DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_type_id) REFERENCES field_types(id) ON DELETE RESTRICT
);

-- ============================================
-- TABLE: field_images
-- ============================================
CREATE TABLE field_images (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id    BIGINT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    is_primary  TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: time_slots (jadwal per lapangan)
-- ============================================
CREATE TABLE time_slots (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_id    BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL,           -- 0=Minggu, 1=Senin, ... 6=Sabtu
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: promotions
-- ============================================
CREATE TABLE promotions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30) NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    discount_type   ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value  DECIMAL(10,2) NOT NULL,
    min_booking     DECIMAL(10,2) DEFAULT 0,
    max_uses        INT DEFAULT NULL,       -- NULL = unlimited
    used_count      INT DEFAULT 0,
    valid_from      DATE NOT NULL,
    valid_until     DATE NOT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: bookings
-- ============================================
CREATE TABLE bookings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code    VARCHAR(20) NOT NULL UNIQUE,    -- BK-20240601-001
    user_id         BIGINT UNSIGNED NOT NULL,
    field_id        BIGINT UNSIGNED NOT NULL,
    promotion_id    BIGINT UNSIGNED NULL,
    booking_date    DATE NOT NULL,
    total_hours     DECIMAL(4,1) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','confirmed','rejected','completed','cancelled') DEFAULT 'pending',
    notes           TEXT,
    cancelled_at    TIMESTAMP NULL,
    cancel_reason   TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (field_id)     REFERENCES fields(id) ON DELETE RESTRICT,
    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL
);

-- ============================================
-- TABLE: booking_details (slot jam yang dipesan)
-- ============================================
CREATE TABLE booking_details (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      BIGINT UNSIGNED NOT NULL,
    time_slot_id    BIGINT UNSIGNED NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    price_per_hour  DECIMAL(10,2) NOT NULL,     -- snapshot harga saat booking
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)   REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE RESTRICT
);

-- ============================================
-- TABLE: payments
-- ============================================
CREATE TABLE payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      BIGINT UNSIGNED NOT NULL UNIQUE,
    payment_method  VARCHAR(50),            -- BCA, BNI, GoPay, dll
    payment_proof   VARCHAR(255),           -- path file bukti transfer
    amount          DECIMAL(10,2) NOT NULL,
    payment_status  ENUM('unpaid','pending_verification','verified','refunded') DEFAULT 'unpaid',
    paid_at         TIMESTAMP NULL,
    verified_at     TIMESTAMP NULL,
    verified_by     BIGINT UNSIGNED NULL,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)   REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by)  REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- INDEXES
-- ============================================
CREATE INDEX idx_bookings_date      ON bookings(booking_date);
CREATE INDEX idx_bookings_status    ON bookings(status);
CREATE INDEX idx_bookings_user      ON bookings(user_id);
CREATE INDEX idx_booking_details    ON booking_details(booking_id, start_time, end_time);
CREATE INDEX idx_time_slots_field   ON time_slots(field_id, day_of_week);
```

---

## 6. CLASS DIAGRAM

```
┌─────────────────────────────┐
│           User              │
├─────────────────────────────┤
│ - id: int                   │
│ - name: string              │
│ - email: string             │
│ - password: string          │
│ - phone: string             │
│ - avatar: string            │
│ - role: enum                │
│ - is_active: bool           │
├─────────────────────────────┤
│ + register(): void          │
│ + login(): Token            │
│ + updateProfile(): void     │
│ + getBookings(): Collection  │
└─────────────────────────────┘
         │ 1
         │
         │ N
┌─────────────────────────────┐
│          Booking            │
├─────────────────────────────┤
│ - id: int                   │
│ - booking_code: string      │
│ - user_id: int              │
│ - field_id: int             │
│ - promotion_id: int         │
│ - booking_date: date        │
│ - total_hours: decimal      │
│ - subtotal: decimal         │
│ - discount_amount: decimal  │
│ - total_amount: decimal     │
│ - status: enum              │
├─────────────────────────────┤
│ + generateCode(): string    │
│ + calculateTotal(): decimal │
│ + confirm(): void           │
│ + reject(): void            │
│ + cancel(): void            │
└─────────────────────────────┘
         │ 1                  │ 1
         │                    │
         │ N                  │ 1
┌────────────────┐  ┌─────────────────────┐
│ BookingDetail  │  │      Payment        │
├────────────────┤  ├─────────────────────┤
│ - booking_id   │  │ - booking_id        │
│ - time_slot_id │  │ - payment_method    │
│ - start_time   │  │ - payment_proof     │
│ - end_time     │  │ - amount            │
│ - price_/hour  │  │ - payment_status    │
│                │  │ - paid_at           │
│                │  │ - verified_by       │
├────────────────┤  ├─────────────────────┤
│ + getDuration()│  │ + uploadProof()     │
│                │  │ + verify()          │
└────────────────┘  └─────────────────────┘

┌─────────────────────────────┐
│           Field             │
├─────────────────────────────┤
│ - id: int                   │
│ - field_type_id: int        │
│ - name: string              │
│ - description: text         │
│ - price_per_hour: decimal   │
│ - facilities: json          │
│ - status: enum              │
├─────────────────────────────┤
│ + getAvailableSlots(): array│
│ + isAvailable(): bool       │
│ + getImages(): Collection   │
└─────────────────────────────┘
         │ 1        │ 1
         │          │
         │ N        │ N
┌──────────────┐  ┌──────────────┐
│  FieldImage  │  │  TimeSlot    │
├──────────────┤  ├──────────────┤
│ - field_id   │  │ - field_id   │
│ - image_path │  │ - day_of_week│
│ - is_primary │  │ - start_time │
│              │  │ - end_time   │
│              │  │ - is_active  │
└──────────────┘  └──────────────┘

┌─────────────────────────────┐
│         Promotion           │
├─────────────────────────────┤
│ - id: int                   │
│ - code: string              │
│ - discount_type: enum       │
│ - discount_value: decimal   │
│ - min_booking: decimal      │
│ - max_uses: int             │
│ - valid_from/until: date    │
├─────────────────────────────┤
│ + validate(): bool          │
│ + calculateDiscount(): dec  │
│ + incrementUses(): void     │
└─────────────────────────────┘
```

---

## 7. DAFTAR ENDPOINT REST API

### BASE URL: `http://localhost:8000/api`

### 🔓 Auth (Public)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/register` | Registrasi user baru |
| POST | `/auth/login` | Login & mendapatkan token JWT |
| POST | `/auth/logout` | Logout (invalidate token) |
| POST | `/auth/refresh` | Refresh JWT token |

### 🔓 Public (Tanpa Auth)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/fields` | Daftar semua lapangan aktif |
| GET | `/fields/{id}` | Detail lapangan |
| GET | `/fields/{id}/availability` | Cek ketersediaan (param: date) |
| GET | `/field-types` | Daftar jenis lapangan |
| POST | `/promotions/validate` | Validasi kode promo |

### 🔵 User (Auth Required — role: user)
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/profile` | Lihat profil sendiri |
| PUT | `/profile` | Update profil |
| POST | `/profile/avatar` | Upload foto profil |
| GET | `/bookings` | Riwayat booking saya |
| GET | `/bookings/{id}` | Detail booking |
| POST | `/bookings` | Buat booking baru |
| PUT | `/bookings/{id}/cancel` | Batalkan booking |
| POST | `/bookings/{id}/payment` | Upload bukti pembayaran |

### 🔴 Admin (Auth Required — role: admin)

#### Dashboard & Laporan
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/dashboard` | Statistik & ringkasan |
| GET | `/admin/reports/bookings` | Laporan booking (filter: date range, status) |
| GET | `/admin/reports/revenue` | Laporan pendapatan per bulan |
| GET | `/admin/export/pdf` | Export laporan ke PDF |
| GET | `/admin/export/excel` | Export laporan ke XLSX |

#### Manajemen User
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/users` | Daftar semua user |
| GET | `/admin/users/{id}` | Detail user |
| PUT | `/admin/users/{id}/toggle-status` | Aktif/nonaktifkan user |

#### Manajemen Field
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/fields` | Daftar lapangan (semua status) |
| POST | `/admin/fields` | Tambah lapangan |
| GET | `/admin/fields/{id}` | Detail lapangan |
| PUT | `/admin/fields/{id}` | Update lapangan |
| DELETE | `/admin/fields/{id}` | Hapus lapangan |
| POST | `/admin/fields/{id}/images` | Upload gambar lapangan |
| DELETE | `/admin/fields/{id}/images/{imageId}` | Hapus gambar lapangan |

#### Manajemen Field Types
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/field-types` | Daftar jenis lapangan |
| POST | `/admin/field-types` | Tambah jenis |
| PUT | `/admin/field-types/{id}` | Update jenis |
| DELETE | `/admin/field-types/{id}` | Hapus jenis |

#### Manajemen Time Slots
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/fields/{fieldId}/time-slots` | Daftar slot per lapangan |
| POST | `/admin/fields/{fieldId}/time-slots` | Tambah slot |
| PUT | `/admin/time-slots/{id}` | Update slot |
| DELETE | `/admin/time-slots/{id}` | Hapus slot |

#### Manajemen Booking
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/bookings` | Semua booking (filter & pagination) |
| GET | `/admin/bookings/{id}` | Detail booking |
| POST | `/admin/bookings` | Buat booking manual |
| PUT | `/admin/bookings/{id}/confirm` | Konfirmasi booking |
| PUT | `/admin/bookings/{id}/reject` | Tolak booking |
| GET | `/admin/bookings/{id}/payment` | Lihat bukti pembayaran |

#### Manajemen Promo
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/admin/promotions` | Daftar promo |
| POST | `/admin/promotions` | Buat promo baru |
| PUT | `/admin/promotions/{id}` | Update promo |
| DELETE | `/admin/promotions/{id}` | Hapus promo |

---

## 8. STRUKTUR FOLDER LARAVEL

```
futsal-booking-api/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── UpdateBookingStatus.php      ← Scheduled command
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── FieldController.php
│   │   │   │   ├── FieldTypeController.php
│   │   │   │   ├── TimeSlotController.php
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── PromotionController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── User/
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── BookingController.php
│   │   │   └── Public/
│   │   │       ├── FieldController.php
│   │   │       └── PromotionController.php
│   │   ├── Middleware/
│   │   │   ├── JwtAuthenticate.php          ← Cek & validasi JWT
│   │   │   ├── IsAdmin.php                  ← Cek role admin
│   │   │   ├── IsUser.php                   ← Cek role user
│   │   │   └── LogApiRequest.php            ← Logging request
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Admin/
│   │   │   │   ├── StoreFieldRequest.php
│   │   │   │   ├── StorePromotionRequest.php
│   │   │   │   └── ...
│   │   │   └── User/
│   │   │       ├── StoreBookingRequest.php
│   │   │       └── UploadPaymentRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── FieldResource.php
│   │       ├── BookingResource.php
│   │       ├── BookingDetailResource.php
│   │       ├── PaymentResource.php
│   │       └── PromotionResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Field.php
│   │   ├── FieldType.php
│   │   ├── FieldImage.php
│   │   ├── TimeSlot.php
│   │   ├── Booking.php
│   │   ├── BookingDetail.php
│   │   ├── Payment.php
│   │   └── Promotion.php
│   ├── Services/
│   │   ├── BookingService.php               ← Logic booking + transaction
│   │   ├── PaymentService.php               ← Logic payment + file upload
│   │   ├── AvailabilityService.php          ← Cek ketersediaan slot
│   │   ├── PromotionService.php             ← Validasi & hitung diskon
│   │   ├── ReportService.php               ← Logic export PDF/XLSX
│   │   └── FileUploadService.php            ← Handle upload file
│   └── Traits/
│       ├── ApiResponse.php                  ← Format response JSON
│       └── GeneratesCode.php                ← Generate booking code
├── bootstrap/
├── config/
│   ├── jwt.php
│   └── filesystems.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_create_users_table.php
│   │   ├── 2024_01_02_create_field_types_table.php
│   │   ├── 2024_01_03_create_fields_table.php
│   │   ├── 2024_01_04_create_field_images_table.php
│   │   ├── 2024_01_05_create_time_slots_table.php
│   │   ├── 2024_01_06_create_promotions_table.php
│   │   ├── 2024_01_07_create_bookings_table.php
│   │   ├── 2024_01_08_create_booking_details_table.php
│   │   └── 2024_01_09_create_payments_table.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── UserSeeder.php
│   │   ├── FieldTypeSeeder.php
│   │   ├── FieldSeeder.php
│   │   └── TimeSlotSeeder.php
│   └── factories/
│       ├── UserFactory.php
│       ├── FieldFactory.php
│       └── BookingFactory.php
├── routes/
│   └── api.php                              ← Semua route API
├── storage/
│   └── app/
│       └── public/
│           ├── avatars/                     ← Foto profil user
│           ├── fields/                      ← Foto lapangan
│           └── payments/                    ← Bukti pembayaran
├── .env
├── composer.json
└── README.md
```

---

## 9. STRUKTUR FOLDER REACT

```
futsal-booking-frontend/
├── public/
│   └── favicon.ico
├── src/
│   ├── assets/
│   │   ├── images/
│   │   └── icons/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.jsx
│   │   │   ├── Input.jsx
│   │   │   ├── Modal.jsx
│   │   │   ├── Table.jsx
│   │   │   ├── Pagination.jsx
│   │   │   ├── Badge.jsx                    ← Status booking
│   │   │   ├── FileUpload.jsx               ← Drag & drop upload
│   │   │   ├── LoadingSpinner.jsx
│   │   │   └── EmptyState.jsx
│   │   ├── layout/
│   │   │   ├── Navbar.jsx
│   │   │   ├── Sidebar.jsx                  ← Admin sidebar
│   │   │   ├── Footer.jsx
│   │   │   ├── AdminLayout.jsx
│   │   │   └── UserLayout.jsx
│   │   ├── field/
│   │   │   ├── FieldCard.jsx                ← Kartu lapangan di listing
│   │   │   ├── FieldGallery.jsx             ← Slider foto lapangan
│   │   │   ├── FieldFilter.jsx              ← Filter jenis, harga
│   │   │   └── AvailabilityCalendar.jsx     ← Kalender ketersediaan
│   │   └── booking/
│   │       ├── BookingForm.jsx              ← Form step-by-step
│   │       ├── TimeSlotPicker.jsx           ← Pilih jam
│   │       ├── BookingSummary.jsx           ← Ringkasan sebelum bayar
│   │       ├── BookingStatusBadge.jsx
│   │       └── PaymentUpload.jsx            ← Upload bukti bayar
│   ├── pages/
│   │   ├── auth/
│   │   │   ├── LoginPage.jsx
│   │   │   └── RegisterPage.jsx
│   │   ├── public/
│   │   │   ├── HomePage.jsx                 ← Landing page
│   │   │   ├── FieldListPage.jsx            ← Daftar lapangan
│   │   │   └── FieldDetailPage.jsx          ← Detail + booking
│   │   ├── user/
│   │   │   ├── ProfilePage.jsx
│   │   │   ├── BookingHistoryPage.jsx
│   │   │   └── BookingDetailPage.jsx
│   │   └── admin/
│   │       ├── DashboardPage.jsx
│   │       ├── fields/
│   │       │   ├── FieldListPage.jsx
│   │       │   ├── FieldFormPage.jsx
│   │       │   └── FieldDetailPage.jsx
│   │       ├── bookings/
│   │       │   ├── BookingListPage.jsx
│   │       │   └── BookingDetailPage.jsx
│   │       ├── users/
│   │       │   └── UserListPage.jsx
│   │       ├── promotions/
│   │       │   ├── PromotionListPage.jsx
│   │       │   └── PromotionFormPage.jsx
│   │       └── reports/
│   │           └── ReportPage.jsx
│   ├── hooks/
│   │   ├── useAuth.js                       ← Auth state & actions
│   │   ├── useBooking.js                    ← Booking logic
│   │   ├── useField.js                      ← Field data fetching
│   │   └── useUpload.js                     ← File upload hook
│   ├── services/
│   │   ├── api.js                           ← Axios instance + interceptors
│   │   ├── authService.js
│   │   ├── fieldService.js
│   │   ├── bookingService.js
│   │   └── reportService.js
│   ├── store/
│   │   ├── index.js                         ← Redux store / Zustand
│   │   ├── authSlice.js
│   │   └── bookingSlice.js
│   ├── utils/
│   │   ├── formatCurrency.js                ← Rp 150.000
│   │   ├── formatDate.js
│   │   ├── formatTime.js
│   │   └── constants.js                     ← Status booking, dll
│   ├── router/
│   │   ├── index.jsx                        ← React Router setup
│   │   ├── PrivateRoute.jsx                 ← Route auth protected
│   │   └── AdminRoute.jsx                   ← Route admin only
│   ├── App.jsx
│   ├── main.jsx
│   └── index.css
├── .env
├── .env.example
├── vite.config.js
├── tailwind.config.js
└── package.json
```

---

## 10. ALUR BOOKING LAPANGAN (Flow Lengkap)

```
LANGKAH 1 — User membuka daftar lapangan
  └─ GET /api/fields
  └─ Tampilkan kartu lapangan dengan foto, harga, jenis

LANGKAH 2 — User memilih lapangan
  └─ GET /api/fields/{id}
  └─ Tampilkan detail: foto gallery, fasilitas, jam operasional

LANGKAH 3 — User pilih tanggal
  └─ GET /api/fields/{id}/availability?date=2024-06-15
  └─ Sistem cek time_slots yang BELUM ada di booking_details
     pada tanggal tersebut dengan status bukan 'cancelled'/'rejected'
  └─ Tampilkan slot tersedia (hijau) vs tidak tersedia (merah)

LANGKAH 4 — User pilih slot jam
  └─ User klik satu atau lebih slot waktu yang tersedia
  └─ Frontend hitung subtotal berdasarkan jumlah jam × harga

LANGKAH 5 — (Opsional) Input kode promo
  └─ POST /api/promotions/validate { code, amount }
  └─ Sistem validasi: kode ada, aktif, belum expired, kuota tersisa
  └─ Tampilkan nominal diskon yang didapat

LANGKAH 6 — Review & Konfirmasi Booking
  └─ Tampilkan BookingSummary: lapangan, tanggal, jam, subtotal, diskon, TOTAL
  └─ User klik "Pesan Sekarang"

LANGKAH 7 — Proses Booking (DATABASE TRANSACTION)
  └─ POST /api/bookings
     ┌─────────────── BEGIN TRANSACTION ───────────────┐
     │  1. Re-check ketersediaan slot (race condition)  │
     │  2. Insert ke tabel bookings                     │
     │  3. Insert ke tabel booking_details (per slot)   │
     │  4. Insert ke tabel payments (status: unpaid)    │
     │  5. Update promotions.used_count (jika ada promo)│
     └─────────────── COMMIT / ROLLBACK ───────────────┘
  └─ Response: booking_code, total_amount, nomor rekening admin

LANGKAH 8 — Upload Bukti Pembayaran
  └─ POST /api/bookings/{id}/payment (multipart/form-data)
  └─ Validasi: file jpg/png/pdf, max 2MB
  └─ Simpan file di storage/app/public/payments/
  └─ Update payments.payment_proof & payment_status → 'pending_verification'

LANGKAH 9 — Admin Verifikasi
  └─ Admin buka GET /api/admin/bookings (filter status: pending)
  └─ Admin lihat bukti bayar
  └─ Admin klik Konfirmasi → PUT /api/admin/bookings/{id}/confirm
     ┌─────────────── BEGIN TRANSACTION ───────────────┐
     │  1. Update bookings.status → 'confirmed'         │
     │  2. Update payments.payment_status → 'verified'  │
     │  3. Catat payments.verified_by & verified_at     │
     └─────────────── COMMIT ──────────────────────────┘

LANGKAH 10 — User Notified
  └─ User cek GET /api/bookings/{id}
  └─ Status berubah menjadi "Dikonfirmasi" ✅
  └─ User datang dan bermain sesuai jadwal
  └─ (Opsional) Admin tandai sebagai 'completed'
```

---

## 11. DATABASE TRANSACTION

### Transaction 1: Proses Booking Baru
```php
// BookingService.php
DB::transaction(function () use ($data) {
    // 1. Lock baris untuk cegah double booking
    $conflicts = BookingDetail::whereIn('time_slot_id', $data['slot_ids'])
        ->whereHas('booking', function($q) use ($data) {
            $q->whereDate('booking_date', $data['date'])
              ->whereNotIn('status', ['cancelled', 'rejected']);
        })->lockForUpdate()->exists();

    if ($conflicts) {
        throw new SlotNotAvailableException('Slot sudah dipesan orang lain.');
    }

    // 2. Buat booking
    $booking = Booking::create([...]);

    // 3. Insert detail per slot
    foreach ($data['slots'] as $slot) {
        BookingDetail::create([
            'booking_id'    => $booking->id,
            'time_slot_id'  => $slot['id'],
            'start_time'    => $slot['start'],
            'end_time'      => $slot['end'],
            'price_per_hour'=> $field->price_per_hour,
        ]);
    }

    // 4. Buat payment record
    Payment::create(['booking_id' => $booking->id, ...]);

    // 5. Update promo jika ada
    if ($booking->promotion_id) {
        Promotion::where('id', $booking->promotion_id)
                 ->increment('used_count');
    }

    return $booking;
});
```

### Transaction 2: Konfirmasi Booking oleh Admin
```php
// Admin/BookingController.php
DB::transaction(function () use ($booking, $adminId) {
    $booking->update(['status' => 'confirmed']);
    $booking->payment()->update([
        'payment_status' => 'verified',
        'verified_at'    => now(),
        'verified_by'    => $adminId,
    ]);
});
```

### Transaction 3: Pembatalan Booking
```php
DB::transaction(function () use ($booking) {
    $booking->update([
        'status'        => 'cancelled',
        'cancelled_at'  => now(),
        'cancel_reason' => request('reason'),
    ]);
    // Kembalikan kuota promo jika ada
    if ($booking->promotion_id) {
        Promotion::where('id', $booking->promotion_id)
                 ->decrement('used_count');
    }
});
```

---

## 12. MIDDLEWARE YANG DIPERLUKAN

### 1. JwtAuthenticate.php
```
Fungsi : Validasi token JWT di header Authorization: Bearer {token}
Berlaku: Semua route yang butuh login
Cara   : Decode JWT, cari user, set request->user()
```

### 2. IsAdmin.php
```
Fungsi : Pastikan user yang login memiliki role = 'admin'
Berlaku: Semua route /api/admin/*
Cek    : $request->user()->role === 'admin'
```

### 3. IsUser.php
```
Fungsi : Pastikan yang mengakses adalah user biasa (role = 'user')
Berlaku: Route booking user
```

### 4. LogApiRequest.php (Nilai Plus)
```
Fungsi : Log semua request (method, endpoint, IP, user, waktu)
Berlaku: Semua route
Simpan : ke database tabel api_logs atau ke file storage/logs/
```

### 5. EnsureFieldIsActive.php (Opsional)
```
Fungsi : Pastikan lapangan yang akan dipesan berstatus 'active'
Berlaku: Route cek ketersediaan & buat booking
```

### Registrasi di api.php (Laravel 12)
```php
Route::middleware(['jwt.auth'])->group(function () {

    // User routes
    Route::middleware(['role:user'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/bookings', [BookingController::class, 'store']);
        // ...
    });

    // Admin routes
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        // ...
    });
});
```

---

## 13. FITUR TAMBAHAN (NILAI PLUS SAAT PRESENTASI)

### 🌟 High Impact (Sangat Direkomendasikan)

1. **Real-time Slot Availability**
   - Gunakan interval polling setiap 30 detik di frontend
   - Slot yang baru dipesan orang lain langsung berubah merah
   - Poin presentasi: "Sistem kami mencegah double booking!"

2. **Invoice PDF Otomatis**
   - Setelah booking dikonfirmasi, generate PDF invoice
   - User bisa download: nama lapangan, jam, harga, nomor booking
   - Library: `barryvdh/laravel-dompdf`

3. **Dashboard Statistik Admin (Grafik)**
   - Grafik pendapatan per bulan (Chart.js / Recharts)
   - Top 3 lapangan terpopuler
   - Booking per status (pie chart)

4. **Kode Booking yang Unik & Traceable**
   - Format: `FK-20240601-0001`
   - User bisa search booking dengan kode ini
   - Terlihat profesional saat presentasi

### 💡 Medium Impact

5. **Sistem Notifikasi In-App**
   - Notifikasi bell di navbar
   - "Booking #FK-001 Anda telah dikonfirmasi"

6. **Export Excel dengan Format Rapi**
   - Gunakan `maatwebsite/excel`
   - Warnai baris dengan status berbeda
   - Header frozen row

7. **Validasi Upload File yang Ketat**
   - Cek mimetype sesungguhnya (bukan hanya ekstensi)
   - Compress gambar sebelum simpan
   - Generate thumbnail untuk preview

8. **Soft Delete**
   - Lapangan yang "dihapus" sebenarnya hanya disembunyikan
   - Data tetap ada untuk keperluan laporan historis

### 🎯 Untuk Nilai Sempurna

9. **API Rate Limiting**
   - Throttle endpoint login: 5 kali/menit per IP
   - Mencegah brute force attack

10. **Postman Collection Lengkap**
    - Semua endpoint terdokumentasi dengan contoh request/response
    - Environment variables (base_url, token)
    - Pre-request script untuk auto-refresh token

11. **README.md Profesional**
    - Cara install step-by-step
    - Screenshot tampilan
    - Daftar endpoint API
    - Penjelasan struktur folder

---

## RINGKASAN TEKNOLOGI & PACKAGE

### Laravel (Backend)
```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "php-open-source-saver/jwt-auth": "^2.0",
    "barryvdh/laravel-dompdf": "^3.0",
    "maatwebsite/excel": "^3.1",
    "intervention/image": "^3.0"
  }
}
```

### React (Frontend)
```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-router-dom": "^6.22.0",
    "axios": "^1.6.0",
    "zustand": "^4.5.0",
    "react-query": "^5.0.0",
    "recharts": "^2.12.0",
    "react-datepicker": "^6.6.0",
    "react-dropzone": "^14.2.3",
    "react-hot-toast": "^2.4.1"
  }
}
```

---

*Dokumen ini dibuat sebagai panduan desain sistem untuk proyek ujian SMK.*
*Silakan adaptasi sesuai kebutuhan dan kemampuan implementasi.*
