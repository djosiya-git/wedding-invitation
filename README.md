# D-Webin Invitation Admin

Admin sederhana untuk memakai template undangan HTML berulang kali tanpa mengubah file template asli.

## Fitur
- Login admin
- 3 template bawaan dari file yang diberikan
- Buat banyak pesanan dari template yang sama
- Database SQLite otomatis di `storage/database.sqlite`
- Kelola tamu undangan per pesanan
- Link undangan personal per tamu
- Scanner otomatis seluruh teks, gambar, link, dan video dari HTML
- Edit konten per pesanan
- Upload foto/video pengganti
- Preview mobile
- Draft / Published
- Nama tamu dinamis via `?to=Nama Tamu`
- Template sumber tetap immutable

## Cara jalan paling cepat (PHP built-in)

Salin konfigurasi contoh dulu:

```bash
cp config.example.php config.php
```

```bash
php -S localhost:8000
```

Buka `http://localhost:8000/login.php`

Default login mengikuti isi `config.php`.

Ganti credential di `config.php` sebelum production.

## Deploy production
Target admin:

`https://invitation.d-webindigital.web.id/admin`

Upload isi project ini ke folder `admin` pada document root subdomain `invitation.d-webindigital.web.id`, lalu buat `config.php` dari `config.example.php` dan ganti password admin.

Pastikan folder ini writable oleh PHP:

- `storage/invitations`
- `storage/uploads`

## URL undangan
Setelah status Published:

`view.php?slug=nama-slug`

Dengan nama tamu:

`view.php?slug=nama-slug&to=Bapak%20Andi`

Jika Apache + mod_rewrite aktif, tersedia juga:

`/u/nama-slug?to=Bapak%20Andi`

## Struktur data
Database: `storage/database.sqlite`

Template asli: `templates/*.html`

Data pesanan lama di `storage/invitations/*.json` akan dimigrasikan otomatis ke SQLite saat aplikasi dibuka.

Media upload: `storage/uploads/{slug}/`

Sistem menyimpan replacement per undangan sehingga file template asli tidak ditulis ulang.

## Catatan penting
Template yang diberikan masih memanggil banyak asset eksternal dari domain sumber (CSS, JS, font, gambar, plugin Elementor). Agar benar-benar mandiri dan aman untuk production, langkah berikutnya adalah mengunduh asset yang memang berhak digunakan dan mengubah referensinya menjadi asset lokal milik proyek.
