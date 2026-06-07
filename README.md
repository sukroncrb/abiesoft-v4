# abiesoft-4.0
### Instalasi
1. Download file **abiesoft-v4.zip**, kemudian extract zip.
2. Buat file <code>.env</code> ambil template codenya dari <code>env_Sample</code> seting pengaturan yang dibutuhkan di .env seperti konfigurasi database, apikey dan secretkey
3. Mengimpor library yang dibutuhkan golang dengan <code>go mod vendor</code> kenapa go mod vendor lebih dulu dibanding composer update, karena folder vendor yang berisi library php akan hilang digantikan dengan mod vendor tetapi jika go mod vendor lebih dulu kemudian composer update library golangnya tidak akan hilang.
4. Mengupdate composer untuk library php dengan perintah <code>composer update</code>
5. Build binary golang dengan perintah <code>php abiesoft build</code>
6. Mengimport database dengan <code>php abiesoft database:import</code>
7. Sebelum menjalankan aplikasi pastikan folder <code>var</code> sudah ada di root folder (Bukan Public Root Folder), jika belum ada buat folder kosong bernama var
8. Menjalankan aplikasi <code>php abiesoft start</code>