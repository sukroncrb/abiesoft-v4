# abiesoft-4.0
### Apa itu abiesoft?
AbieSoft adalah framework hybrid yang menggunakan bahasa php, golang, javascript dan engine template latte. Abiesoft menggunakan arsitektur Action-Domain-Responder (ADR) dengan sistem Hybrid Engine berbasis Unix Socket.
### Instalasi
1. Download file **abiesoft-v4.zip**, kemudian extract zip. atau menggunakan <pre><code>git clone https://github.com/sukroncrb/abiesoft-v4.git</code></pre>
2. Buat file <code>.env</code> ambil template codenya dari <code>env_Sample</code> seting pengaturan yang dibutuhkan di .env seperti konfigurasi database, apikey dan secretkey
3. Mengimpor library yang dibutuhkan golang dengan <pre><code>go mod vendor</code></pre> kenapa go mod vendor lebih dulu dibanding composer update, karena folder vendor yang berisi library php akan hilang digantikan dengan mod vendor tetapi jika go mod vendor lebih dulu kemudian composer update library golangnya tidak akan hilang.
4. Mengupdate composer untuk library php dengan perintah <pre><code>composer update</code></pre>
5. Build binary golang dengan perintah <pre><code>php abiesoft build</code></pre>
6. Mengimport database dengan <pre><code>php abiesoft database:import</code></pre>
7. Menjalankan aplikasi <pre><code>php abiesoft start</code></pre>