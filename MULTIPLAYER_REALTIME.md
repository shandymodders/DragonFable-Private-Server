# DragonFable Multiplayer v2.3 — Active Class + Native Walk Animation

Bugfix ini melanjutkan v2.2. Sistem koordinat map dan silent remote loader v2.2 dipertahankan.

## Perbaikan v2.3

- Remote player sekarang mengirim state `moving` dan avatar remote memainkan timeline native `Walk` saat bergerak serta `Idle` saat berhenti.
- Posisi remote tetap digerakkan oleh sinkronisasi koordinat/smoothing. `walkTo()` sengaja tidak dipanggil pada remote karena fungsi itu juga menjalankan collision, area trigger, NPC event, dan script map.
- Class aktif tidak lagi hanya bergantung pada class tersimpan di database/PvP projection.
- Client membaca `avatar._url`, yaitu SWF class yang benar-benar sedang dirender pada player saat itu. Ini mencakup temporary/current class yang dimuat melalui `cf-classload.asp`.
- `classFile` dan `classId` dikirim bersama world state ke player lain.
- Saat membangun `Avatar("friend", ...)`, class dari XML PvP dioverride dengan runtime class tersebut sebelum class SWF remote dimuat.
- Jika player mengganti class saat masih berada di map yang sama, effective appearance version berubah dan remote avatar dibangun ulang otomatis dengan class baru.
- Weapon, cape/back, hair dan helm tetap menggunakan real DragonFable assets dan silent loader v2.2.
- Class remote tetap terisolasi dari `game.player` sehingga tidak boleh mengganti class karakter lokal.

## File yang berubah dari v2.2

1. `dev-tools/patch-new-swf/patches/external-feature-chat/replace.txt`
2. `src/server-emulator/hiperesp/server/services/WorldService.php`
3. `src/web/assets/js/game.multiplayer.js`
4. `src/web/play.html`

## Instalasi

1. Timpa empat file di atas.
2. Patch ulang SWF menggunakan `replace.txt` v2.3. Jangan memakai SWF hasil v2.2.
3. Restart server jika proses PHP/server menyimpan source di memory.
4. Tutup semua tab game lama.
5. Buka kembali `play.html` dan lakukan `Ctrl+F5` pada kedua client.

## Test

1. Login dua karakter dengan class berbeda pada map yang sama.
2. Pastikan class remote sama dengan class yang benar-benar sedang dipakai, termasuk class temporary/non-saved.
3. Gerakkan player A. Player B harus melihat timeline Walk, bukan karakter diam yang meluncur.
4. Berhenti bergerak. Remote kembali ke Idle.
5. Ganti class player A tanpa logout, tetap di map yang sama. Remote di player B harus rebuild ke class aktif baru.
6. Pastikan koordinat tetap sama seperti v2.2 dan tidak ada loading screen remote.
