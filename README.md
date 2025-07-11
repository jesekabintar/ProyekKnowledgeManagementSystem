# Knowledge Management System

Aplikasi manajemen pengetahuan berbasis web menggunakan Slim Framework, Medoo, dan Twig. Dibuat untuk mendukung kolaborasi pengetahuan antara admin, kontributor, dan pengguna umum.

## 🛠️ Teknologi yang Digunakan
- Slim Framework
- Medoo
- Twig
- PHP 8+
- MySQL / MariaDB

## ⚙️ Fitur Utama
- Login Multi-Role (admin, kontributor, user)
- CRUD Postingan
- Kategori & Tag
- Komentar
- Middleware Role
- Tampilan dengan Twig

## 🔐 Hak Akses

| Role        | Akses                                       |
|-------------|----------------------------------------------|
| admin       | Kelola user, halaman admin                   |
| kontributor | Kelola posting, halaman kontributor          |
| user        | Akses baca dan komentar                      |
