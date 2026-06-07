# abiesoft-4.0
### Instalasi
1. Download file **abiesoft-v4.zip**, kemudian extract zip.
2. Buat file <code>.env</code> ambil template codenya dari <code>env_Sample</code> seting pengaturan yang dibutuhkan di env seperti konfigurasi database, apikey dan secretkey
3. Mengimpor library yang dibutuhkan golang dengan <code>go mod vendor</code>
4. Build binary golang dengan perintah <code>php abiesoft build</code>
4. Mengupdate composer untuk library php dengan perintah <code>composer update</code>
5. Mengimport database dengan <code>php abiesoft database:import</code>
6. Menjalankan aplikasi <code>php abiesoft start</code>