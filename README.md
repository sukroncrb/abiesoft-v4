# AbieSoft Framework v4.0

<p align="center">
  <strong>Hybrid High-Performance Web Framework</strong><br>
  Memadukan kecepatan dan konkurensi native <b>Golang</b> dengan keluwesan bisnis & templating <b>PHP 8.2+</b>.
</p>

---

## 📌 Apa itu AbieSoft?

**AbieSoft Framework** adalah framework hybrid modern yang menggabungkan dua ekosistem terbaik ke dalam satu arsitektur terintegrasi:
- **⚡ Golang Native HTTP Engine (Gateway :3000)**: Menangani rute berkinerja tinggi dengan prefix `/api-go/*` secara **100% direct native** tanpa melalui PHP, memanfaatkan goroutine & non-blocking I/O untuk throughput maksimal dan latensi mikrodetik.
- **🐘 PHP Core Backend (Internal :8002)**: Menangani rute `/api/*` dan halaman web UI dengan arsitektur **Action-Domain-Responder (ADR)**, templating engine **Latte**, session, dan middleware keamanan.

Request dari client diterima di satu port publik tunggal (`:3000`), di mana Golang Gateway Engine otomatis menyaring:
- `/api-go/*` &rarr; Diproses langsung oleh Go Native Mux di `src/Modules/handler.go`.
- `/api/*` & halaman web lainnya &rarr; Di-reverse-proxy ke backend PHP internal (`:8002`).

---

## 🚀 Panduan Instalasi Cepat

### 1. Unduh / Clone Repository
```bash
git clone https://github.com/sukroncrb/abiesoft-v4.git
cd abiesoft-v4
```

### 2. Salin Konfigurasi Lingkungan (.env)
```bash
cp env_sample .env
```
> Sesuaikan konfigurasi database MySQL (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`), `APIKEY`, dan `SECRET_KEY` di file `.env`.

### 3. Unduh Dependensi (Urutan Sangat Penting)
Jalankan dependensi Golang terlebih dahulu, baru kemudian dependensi PHP:
```bash
# 1. Download vendor pustaka Golang
go mod vendor

# 2. Download dan install vendor pustaka PHP
composer update
```

### 4. Build Engine Golang & Import Database
```bash
# Kompilasi binary Go Gateway Engine
php abiesoft build

# Impor skema tabel database ke MySQL
php abiesoft database:import
```

### 5. Menjalankan Server Development
```bash
php abiesoft start
```
Server akan menyala dengan output:
```text
==========================================================
  🚀 AbieSoft Hybrid Engine Server Started
==========================================================
  • Gateway URL        : http://127.0.0.1:3000
  • Golang API Route   : http://127.0.0.1:3000/api-go/* (Direct Native Go)
  • PHP API & Web      : http://127.0.0.1:3000/api/* (PHP Core Port :8002)
----------------------------------------------------------
```
> *Catatan: Server dilengkapi **Auto Port Cleaner** (mencegah zombie port) dan **Graceful Shutdown** (cukup tekan `Ctrl+C` untuk menghentikan seluruh proses).*

---

## 🛠️ CLI Console Generator (`php abiesoft`)

AbieSoft menyediakan tool CLI cerdas untuk mempercepat pengembangan aplikasi:

### 1. Membuat Modul Lengkap (`make:module`)
Secara otomatis men-generate Schema SQL, DTO, Service/Repository, Action, Rute, dan Unit Test:

```bash
# Modul PHP Core (src/Modules/Users)
php abiesoft make:module users

# Modul Golang Native (src/GoModules/Users)
php abiesoft make:module users --go
```
*Pada modul Golang (`--go`), CLI otomatis menginjeksi rute ke `src/Modules/handler.go` dan mengompilasi ulang binary Go secara instan.*

---

### 2. Generator Komponen Mandiri

| Komponen | PHP Core (Default) | Golang Native (`--go`) |
| :--- | :--- | :--- |
| **Action** | `php abiesoft make:action <module> <nama>` | `php abiesoft make:action <module> <nama> --go` |
| **Service** | `php abiesoft make:service <module> <nama>` | `php abiesoft make:service <module> <nama> --go` |
| **DTO** | `php abiesoft make:dto <module> <nama>` | `php abiesoft make:dto <module> <nama> --go` |
| **Unit Test** | `php abiesoft make:test <module> [nama]` | `php abiesoft make:test <module> [nama] --go` |

---

### 3. Pemeliharaan & Penghapusan (`delete:*`)

Setiap perintah hapus dilengkapi proteksi konfirmasi `(y/n)`:

```bash
# Menghapus modul lengkap (folder modul, tabel MySQL, rute, templates)
php abiesoft delete:module <nama>
php abiesoft delete:module <nama> --go   # Otomatis bersihkan handler.go & recompile Go

# Menghapus komponen satuan
php abiesoft delete:action <module> <nama> [--go]
php abiesoft delete:service <module> <nama> [--go]
php abiesoft delete:dto <module> <nama> [--go]
php abiesoft delete:test <module> [nama] [--go]
```

---

## 🧪 Automated Unit Testing (`php abiesoft test`)

Framework menyediakan runner unit test terpadu untuk PHP dan Golang:

```bash
# Jalankan seluruh unit test (PHP & Golang)
php abiesoft test

# Jalankan unit test spesifik modul PHP
php abiesoft test users

# Jalankan unit test spesifik modul Golang
php abiesoft test users --go

# Jalankan seluruh unit test Golang
php abiesoft test --go
```

- **PHP Test**: Memanfaatkan base assertion class `Abiesoft\System\Testing\TestCase` (`assertEquals`, `assertTrue`, `assertArrayHasKey`, dll).
- **Golang Test**: Memanfaatkan native runner `go test` dengan model `shared.PiGoRequest`.

---

## 🛣️ Melihat Daftar Rute (`php abiesoft route`)

Untuk memeriksa seluruh endpoint yang terdaftar di aplikasi:

```bash
php abiesoft route
```

Output menyajikan dua bagian terpisah secara transparan:
1. **🐘 [PHP Core Routes]**: Rute dari `routes/web.php` (Web pages & `/api/*`).
2. **⚡ [Golang Native Direct Routes]**: Rute direct bypass dari `src/Modules/handler.go` (`/api-go/*`).

---

## 📋 Tabel Referensi Cepat CLI

| Perintah | Opsi / Argumen | Deskripsi |
| :--- | :--- | :--- |
| `start` | - | Menjalankan server hybrid development (`:3000` & `:8002`) |
| `route` | - | Menampilkan daftar seluruh rute aktif (PHP Core & Direct Go) |
| `build` | - | Mengompilasi ulang binary engine Golang (`sys/pigo/bin/pigo-engine`) |
| `test` | `[module] [--go]` | Menjalankan automated test runner PHP dan Golang |
| `database:import` | - | Mengimpor skema file SQL di `database/schemas/` ke database |
| `make:module` | `<nama> [--go]` | Membuat modul lengkap (PHP atau Golang) |
| `make:action` | `<module> <nama> [--go]` | Membuat file Action baru |
| `make:service` | `<module> <nama> [--go]` | Membuat file Service / Repository baru |
| `make:dto` | `<module> <nama> [--go]` | Membuat file Data Transfer Object baru |
| `make:test` | `<module> [nama] [--go]` | Membuat file Unit Test baru |
| `delete:module` | `<nama> [--go]` | Menghapus modul beserta tabel, rute, dan skema |
| `delete:action` | `<module> <nama> [--go]` | Menghapus file Action tertentu |
| `delete:service` | `<module> <nama> [--go]` | Menghapus file Service tertentu |
| `delete:dto` | `<module> <nama> [--go]` | Menghapus file DTO tertentu |
| `delete:test` | `<module> [nama] [--go]` | Menghapus file Unit Test tertentu |
| `help` | - | Menampilkan menu panduan bantuan CLI |

---

## 📂 Struktur Direktori Proyek

```text
abiesoft/
├── database/
│   └── schemas/              # Berkas migrasi database SQL otomatis
├── public/                   # Web public root (index.php, docs.html, assets)
├── routes/
│   └── web.php               # Pendaftaran rute PHP Core & Web View
├── src/
│   ├── GoModules/            # ⚡ Modul Golang Native (Dto, Services, Actions, Tests)
│   ├── Modules/              # 🐘 Modul PHP Core (Dto, Services, Actions, Tests)
│   │   └── handler.go        # Sentral routing Direct Go engine (RegisterRoutes)
│   └── Shared/               # Helper shared antara PHP & Golang
├── sys/
│   ├── Console/              # Engine CLI 'php abiesoft' & Commands
│   ├── Database/             # Database connection wrapper (PDO MySQL)
│   ├── Http/                 # Router PHP internal & Middleware runner
│   ├── pigo/                 # Source code & binary Golang Engine Gateway
│   │   ├── bin/pigo-engine   # Hasil binary build engine Go
│   │   └── pigo_engine.go    # HTTP Gateway & Reverse Proxy implementation
│   ├── Testing/              # Base TestCase assertion library
│   └── View/                 # Template View Renderer (Latte)
├── templates/                # Template Latte halaman UI
├── index.html                # Dokumentasi web interaktif (Offline)
└── var/                      # Cache kompiler Latte
```

---

## 📖 Dokumentasi Web Interaktif

Dokumentasi lengkap berbasis web dengan antarmuka modern, diagram arsitektur interaktif, dan pencarian langsung (*Live Search*) tersedia di file:
- **[index.html](index.html)** (buka langsung di browser Anda), atau
- Buka via web server saat aplikasi berjalan: `http://127.0.0.1:3000/docs.html`

---

<p align="center">
  Lisensi &copy; AbieSoft Framework. Dikembangkan untuk efisiensi dan performa maksimal.
</p>