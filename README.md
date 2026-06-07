# abiesoft-4.0
### Instalasi
1. Download file **abiesoft-v4.zip**, kemudian extract zip.
2. Buat file <code>.env</code> ambil template codenya dari <code>env_Sample</code> seting pengaturan yang dibutuhkan di .env seperti konfigurasi database, apikey dan secretkey
3. Mengimpor library yang dibutuhkan golang dengan <pre><code>go mod vendor</code></pre> kenapa go mod vendor lebih dulu dibanding composer update, karena folder vendor yang berisi library php akan hilang digantikan dengan mod vendor tetapi jika go mod vendor lebih dulu kemudian composer update library golangnya tidak akan hilang.
4. Mengupdate composer untuk library php dengan perintah <pre><code>composer update</code></pre>
5. Build binary golang dengan perintah <pre><code>php abiesoft build</code></pre>
6. Mengimport database dengan <pre><code>php abiesoft database:import</code></pre>
7. Sebelum menjalankan aplikasi pastikan folder <code>var</code> sudah ada di root folder (Bukan Public Root Folder), jika belum ada buat folder kosong bernama var yang fungsinya untuk menyimpan cache yang dibutuhkan oleh template engine latte, karena abiesoft menggunakan template engine latte.
8. Menjalankan aplikasi <pre><code>php abiesoft start</code></pre>