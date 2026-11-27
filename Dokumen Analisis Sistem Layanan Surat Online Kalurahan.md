# Dokumen Analisis Sistem Layanan Surat Online Kalurahan

Disusun oleh: Sistem Analis | Versi 1.0 | 18 November 2026

## 1. Pendahuluan

### 1.1 Latar Belakang

Pelayanan surat di kalurahan umumnya masih dilakukan secara manual. Warga harus datang langsung, sering bolak-balik karena persyaratan kurang, menunggu pengantar RT/RW dan tanda tangan Lurah, serta tidak dapat mengetahui status pengajuan. Penomoran dan pengarsipan surat yang manual juga rawan salah dan hilang.

### 1.2 Tujuan

1. Memungkinkan warga mengajukan surat secara online dari HP.
2. Mempercepat proses verifikasi, penerbitan, dan penandatanganan surat.
3. Memberi transparansi status pengajuan kepada warga.
4. Menata arsip, penomoran, dan buku agenda surat secara otomatis.
5. Menyediakan mekanisme verifikasi keaslian surat melalui QR code.

### 1.3 Ruang Lingkup

**Termasuk dalam sistem:** registrasi dan verifikasi warga, pengajuan surat online, unggah berkas persyaratan, alur verifikasi dan persetujuan, penerbitan surat PDF dengan nomor otomatis, tanda tangan digital dan QR, notifikasi, arsip, laporan, serta manajemen pengguna dan data penduduk.

**Di luar lingkup (tahap awal):** pembayaran online, integrasi langsung dengan Dukcapil, tanda tangan elektronik tersertifikasi (direncanakan fase lanjut), aplikasi mobile native.

### 1.4 Definisi dan Istilah

| Istilah | Arti |
| --- | --- |
| Kalurahan | Pemerintahan desa di wilayah DIY |
| Lurah | Kepala kalurahan, penandatangan surat |
| Pamong / Operator | Staf kalurahan (Carik, Kaur, dll.) yang memproses pengajuan |
| SKU | Surat Keterangan Usaha |
| SKTM | Surat Keterangan Tidak Mampu |
| RBAC | Role-Based Access Control, hak akses berbasis peran |
| OTP | One-Time Password |
| UU PDP | Undang-Undang Pelindungan Data Pribadi |
| TTE | Tanda Tangan Elektronik |

## 2. Kondisi Saat Ini dan Asumsi

### 2.1 Kondisi Saat Ini

- Data penduduk tersedia secara digital dalam file **Excel dan CSV** dengan kolom **hanya nama, NIK, dan alamat**.
- Alamat tersimpan dalam satu kolom, belum terpisah menjadi dukuh, RT, dan RW.
- Tidak ada data nomor KK, tempat/tanggal lahir, jenis kelamin, agama, status kawin, dan pekerjaan.
- Keterlibatan RT/RW bersifat **opsional**, hanya untuk jenis surat tertentu.

### 2.2 Asumsi

1. Sebagian besar warga memiliki smartphone dan nomor WhatsApp aktif.
2. Kalurahan memiliki minimal satu operator yang dapat dilatih mengelola sistem.
3. Lurah bersedia menyetujui dan menandatangani surat secara digital.
4. Sistem berjalan di hosting/VPS dengan biaya rendah.
5. Warga yang tidak bisa memakai sistem tetap dilayani langsung di kantor kalurahan.

### 2.3 Batasan

- Kualitas data impor belum diketahui dan harus diaudit lebih dulu.
- Validitas hukum tanda tangan digital tahap awal (gambar TTD + QR) perlu dikonsultasikan dengan kapanewon atau Dinas Kominfo.
- Format dan isi surat harus mengikuti ketentuan dan contoh resmi yang berlaku di kalurahan.

## 3. Aktor Sistem

| Aktor | Deskripsi | Hak Utama |
| --- | --- | --- |
| Warga | Penduduk kalurahan yang terdaftar | Registrasi, kelola profil, ajukan surat, unggah berkas, lacak status, unduh surat |
| Operator | Staf kalurahan pemroses | Verifikasi akun warga, periksa berkas, tolak atau minta perbaikan, buat draf surat, kelola arsip |
| Lurah | Pejabat penandatangan | Menyetujui dan menandatangani surat, melihat laporan |
| Admin Sistem | Pengelola teknis | Kelola pengguna, jenis layanan, template, impor data, konfigurasi, backup |
| RT/RW (fase 2) | Ketua RT/RW atau dukuh | Menyetujui atau menolak pengantar secara online |
| Publik | Pihak ketiga | Memeriksa keaslian surat lewat QR |

## 4. Jenis Layanan

Fase 1 mencakup tiga jenis surat. Jenis lain ditambahkan bertahap melalui fitur jenis layanan dinamis.

| Jenis Surat | Pengantar RT/RW | Fase |
| --- | --- | --- |
| Surat Keterangan Usaha (SKU) | Ya | 1 |
| Surat Keterangan Domisili | Ya | 1 |
| SKTM | Ya | 1 |
| Surat Keterangan Penghasilan | Opsional | 2 |
| Pengantar SKCK | Tidak | 2 |
| Keterangan Belum/Sudah Menikah, Pengantar Nikah | Ya | 3 |
| Keterangan Kelahiran/Kematian | Ya | 3 |
| Pengantar Pindah/Datang | Ya | 3 |

*Catatan: konfigurasi pengantar di atas adalah usulan awal dan perlu dikonfirmasi dengan pihak kalurahan.*

Setiap jenis layanan memiliki pengaturan: nama, deskripsi, daftar syarat berkas, field form tambahan, template surat, dan pengaturan pengantar RT/RW (`butuh_pengantar_rt` dan `mode_pengantar`: unggah atau verifikasi online).

## 5. Alur Bisnis

### 5.1 Registrasi dan Verifikasi Warga

Karena data impor hanya berisi nama, NIK, dan alamat, pencocokan NIK saja tidak aman. Alur yang digunakan:

1. Warga memasukkan NIK, nama, dan nomor HP.
2. Sistem mencocokkan dengan data penduduk hasil impor. Hasil hanya ditampilkan sebagai "cocok" atau "tidak ditemukan" tanpa membuka detail data.
3. Jika tidak ditemukan, permohonan masuk antrean verifikasi manual operator.
4. Sistem mengirim OTP ke nomor HP (WhatsApp/SMS).
5. Warga melengkapi profil dan mengunggah foto KTP serta selfie dengan KTP.
6. Akun berstatus **Menunggu Verifikasi**. Operator mencocokkan data dengan foto KTP, lalu menyetujui atau meminta perbaikan.
7. Setelah disetujui, akun aktif dan data profil terisi otomatis pada setiap pengajuan.

Alternatif: operator dapat mendaftarkan warga langsung di kantor kalurahan.

### 5.2 Pengajuan Surat

1. Warga login dan memilih jenis layanan.
2. Data diri terisi otomatis dari profil. Warga mengisi data tambahan (misalnya nama dan alamat usaha untuk SKU) dan mengunggah syarat.
3. Jika jenis surat butuh pengantar RT/RW, warga mengunggah foto pengantar bertanda tangan (fase 1).
4. Sistem memvalidasi kelengkapan, lalu status menjadi **Diajukan**.
5. Operator memeriksa. Jika kurang, status **Perlu Perbaikan** dan warga diberi notifikasi untuk memperbaiki.
6. Jika lengkap, sistem membuat draf surat dari template dengan nomor surat otomatis.
7. Lurah meninjau dan menyetujui. Sistem menambahkan tanda tangan digital dan QR.
8. Status **Selesai**. Warga mengunduh PDF atau mengambil versi cetak.
9. Surat masuk arsip dan buku agenda.

### 5.3 Diagram Status Pengajuan

```
Draft → Diajukan → Diverifikasi → Disetujui → Selesai
           ↓            ↓
    Perlu Perbaikan ←---+
           ↓
        Ditolak
```

| Status | Arti | Pemicu |
| --- | --- | --- |
| Draft | Belum dikirim | Warga menyimpan |
| Diajukan | Menunggu pemeriksaan | Warga mengirim |
| Perlu Perbaikan | Ada berkas/data kurang | Operator |
| Diverifikasi | Lengkap, menunggu persetujuan | Operator |
| Disetujui | Ditandatangani Lurah | Lurah |
| Selesai | Surat siap diunduh/diambil | Sistem/Operator |
| Ditolak | Pengajuan tidak dapat diproses | Operator/Lurah (wajib alasan) |

## 6. Kebutuhan Fungsional

Prioritas: **M** = wajib (Must), **S** = penting (Should), **C** = opsional (Could).

### 6.1 Manajemen Data Penduduk (Impor)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-01 | Admin dapat mengunggah file Excel/CSV berisi nama, NIK, alamat | M |
| FR-02 | Sistem menampilkan pratinjau dan validasi sebelum data disimpan (NIK 16 digit, tidak ganda, kolom terisi) | M |
| FR-03 | Sistem menampilkan laporan baris gagal beserta alasannya dan dapat diunduh | M |
| FR-04 | Mode impor *update* (tambah/perbarui), tidak menimpa seluruh data | M |
| FR-05 | Sistem mencoba mengurai alamat menjadi dukuh, RT, RW; hasil gagal ditandai untuk koreksi manual | S |
| FR-06 | Riwayat impor tercatat (siapa, kapan, jumlah sukses/gagal) | M |
| FR-07 | Admin/operator dapat mencari, melihat, dan mengedit data penduduk | M |

### 6.2 Registrasi dan Akun Warga

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-10 | Registrasi dengan NIK, nama, dan nomor HP | M |
| FR-11 | Pencocokan dengan data impor tanpa membuka detail data | M |
| FR-12 | Verifikasi nomor HP dengan OTP | M |
| FR-13 | Pengisian profil lengkap: no KK, tempat/tanggal lahir, jenis kelamin, agama, status kawin, pekerjaan, dukuh, RT, RW, email | M |
| FR-14 | Unggah foto KTP dan selfie dengan KTP | M |
| FR-15 | Antrean verifikasi akun untuk operator (setujui, minta perbaikan, tolak) | M |
| FR-16 | Pendaftaran warga langsung oleh operator di kantor | S |
| FR-17 | Persetujuan pengolahan data pribadi saat registrasi | M |
| FR-18 | Login, logout, lupa dan ubah kata sandi | M |
| FR-19 | Perubahan data profil oleh warga memerlukan peninjauan ulang operator untuk field kunci (nama, NIK) | S |

### 6.3 Pengajuan Surat (Warga)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-20 | Daftar jenis layanan beserta syarat dan estimasi waktu | M |
| FR-21 | Form pengajuan dengan data diri terisi otomatis | M |
| FR-22 | Field tambahan sesuai jenis surat | M |
| FR-23 | Unggah berkas syarat (JPG/PNG/PDF) dengan batas ukuran dan validasi tipe | M |
| FR-24 | Unggah pengantar RT/RW jika disyaratkan | M |
| FR-25 | Simpan sebagai draf dan kirim pengajuan | S |
| FR-26 | Pelacakan status dan riwayat pengajuan | M |
| FR-27 | Notifikasi perubahan status via WhatsApp/email | S |
| FR-28 | Unggah ulang berkas pada status Perlu Perbaikan | M |
| FR-29 | Unduh surat PDF setelah selesai | M |

### 6.4 Pemrosesan oleh Operator dan Lurah

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-30 | Dasbor antrean pengajuan dengan filter status, jenis, dan tanggal | M |
| FR-31 | Pemeriksaan berkas dengan pratinjau dokumen | M |
| FR-32 | Minta perbaikan atau tolak dengan alasan wajib | M |
| FR-33 | Pembuatan draf surat dari template dengan data otomatis | M |
| FR-34 | Penomoran surat otomatis sesuai format kalurahan (kode, nomor urut, bulan, tahun), urut per tahun | M |
| FR-35 | Operator dapat menyunting draf sebelum diajukan ke Lurah | S |
| FR-36 | Lurah menyetujui satu per satu atau massal, dengan tanda tangan digital | M |
| FR-37 | QR code verifikasi pada setiap surat | S |
| FR-38 | Pencetakan surat untuk warga yang mengambil langsung | M |
| FR-39 | Pencatatan pengambilan surat (siapa, kapan) | C |

### 6.5 Administrasi dan Pelaporan

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-40 | Manajemen pengguna dan peran | M |
| FR-41 | Manajemen jenis layanan: syarat, field, template, pengaturan pengantar RT/RW | S (fase 1: tetap, fase 3: dinamis) |
| FR-42 | Manajemen data pejabat dan gambar tanda tangan | M |
| FR-43 | Buku agenda dan arsip surat dengan pencarian | M |
| FR-44 | Laporan rekap bulanan/tahunan per jenis surat dan per dukuh, ekspor Excel/PDF | S |
| FR-45 | Audit log aktivitas penting | M |
| FR-46 | Halaman publik verifikasi keaslian surat | S |
| FR-47 | Akun RT/RW untuk persetujuan pengantar online | C (fase 2) |

## 7. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan |
| --- | --- |
| Keamanan | HTTPS wajib; kata sandi di-hash (bcrypt/argon2); RBAC; pembatasan percobaan login; proteksi CSRF, XSS, dan SQL injection; berkas diunggah divalidasi dan disimpan di luar direktori publik |
| Pelindungan data | Sesuai UU PDP: persetujuan eksplisit, minimisasi data, akses terbatas, enkripsi berkas sensitif (KTP, selfie), kebijakan retensi dan penghapusan selfie setelah verifikasi |
| Kegunaan | Responsif untuk HP; bahasa Indonesia sederhana; ukuran tombol dan huruf nyaman untuk lansia; maksimal 3-4 langkah untuk mengajukan surat |
| Kinerja | Halaman utama dimuat di bawah 3 detik pada koneksi 4G; mendukung minimal 50 pengguna bersamaan |
| Ketersediaan | Target 99% pada jam layanan; jadwal pemeliharaan diumumkan |
| Pencadangan | Backup basis data harian otomatis, backup berkas mingguan, uji pemulihan berkala, salinan di lokasi terpisah |
| Auditabilitas | Setiap perubahan status, persetujuan, dan akses data sensitif tercatat |
| Kemudahan pemeliharaan | Kode terdokumentasi, template surat dapat diubah tanpa mengubah kode |
| Kompatibilitas | Browser modern (Chrome, Firefox, Safari, Edge) dan Android 8+ |

## 8. Strategi Data Penduduk

### 8.1 Audit dan Pembersihan File

Sebelum impor pertama, lakukan audit: jumlah baris, NIK tidak 16 digit atau ganda, nama kosong, ejaan alamat tidak konsisten, dan karakter aneh. Perbaikan massal dilakukan di file sumber atau lewat pratinjau impor.

### 8.2 Pembagian Sumber Data

Data impor (nama, NIK, alamat) disimpan terpisah dari profil yang diisi warga. Dengan begitu data asli tetap utuh, sedangkan data tambahan memiliki jejak siapa yang mengisi dan memverifikasi.

### 8.3 Pelengkapan Data Bertahap

Data penduduk menjadi lengkap secara alami melalui profil warga yang diverifikasi operator. Alamat asli selalu disimpan, kolom turunan (dukuh, RT, RW) diisi bertahap.

### 8.4 Keamanan File Sumber

Setelah diimpor, file mentah disimpan di lokasi terbatas dan tidak lagi dikirim lewat chat atau flashdisk.

## 9. Rancangan Data

### 9.1 Entitas dan Atribut

**Penduduk** (hasil impor)

| Atribut | Keterangan |
| --- | --- |
| nik (PK/unik) | 16 digit |
| nama |  |
| alamat\_asli | teks dari file |
| dukuh, rt, rw | hasil parsing, boleh kosong |
| status\_data | terverifikasi / menunggu |
| sumber\_data, tanggal\_impor, id\_riwayat\_impor | jejak impor |

**ProfilWarga** (diisi warga)

| Atribut | Keterangan |
| --- | --- |
| id, nik (FK) |  |
| no\_kk, tempat\_lahir, tanggal\_lahir, jenis\_kelamin |  |
| agama, status\_kawin, pekerjaan |  |
| dukuh, rt, rw, alamat\_lengkap |  |
| no\_hp, email |  |
| foto\_ktp, foto\_selfie | terenkripsi |
| status\_verifikasi | menunggu / disetujui / perbaikan / ditolak |
| diverifikasi\_oleh, tanggal\_verifikasi, catatan |  |

**Pengguna**

| Atribut | Keterangan |
| --- | --- |
| id, nik (FK, untuk warga), nama |  |
| no\_hp, email, password\_hash |  |
| peran | warga, operator, lurah, admin, rt\_rw |
| status\_akun, terakhir\_login |  |

**JenisLayanan**

| Atribut | Keterangan |
| --- | --- |
| id, nama, kode, deskripsi |  |
| butuh\_pengantar\_rt, mode\_pengantar |  |
| daftar\_syarat, skema\_field\_tambahan | JSON |
| template\_surat, format\_nomor |  |
| estimasi\_hari, aktif |  |

**Pengajuan**

| Atribut | Keterangan |
| --- | --- |
| id, kode\_tiket, id\_pengguna, id\_jenis\_layanan |  |
| data\_form | JSON |
| status, status\_pengantar\_rt, berkas\_pengantar |  |
| tanggal\_ajuan, tanggal\_selesai |  |

**BerkasPengajuan:** id, id\_pengajuan, nama\_syarat, path\_file, tipe, ukuran, diunggah\_pada.

**Surat:** id, id\_pengajuan, nomor\_surat, tahun, nomor\_urut, file\_pdf, tanggal\_terbit, id\_penandatangan, kode\_verifikasi, tanggal\_diambil.

**RiwayatStatus:** id, id\_pengajuan, status\_lama, status\_baru, catatan, oleh, waktu.

**PejabatKalurahan:** id, nama, jabatan, file\_ttd, aktif.

**RiwayatImpor:** id, nama\_file, oleh, waktu, baris\_total, baris\_sukses, baris\_gagal, laporan\_gagal.

**Notifikasi:** id, id\_pengguna, kanal, isi, status\_kirim, waktu.

**AuditLog:** id, id\_pengguna, aksi, objek, detail, ip, waktu.

### 9.2 Relasi Utama

- Penduduk 1 — 0..1 ProfilWarga (melalui NIK)
- Pengguna 1 — 1 ProfilWarga (untuk peran warga)
- Pengguna 1 — N Pengajuan
- JenisLayanan 1 — N Pengajuan
- Pengajuan 1 — N BerkasPengajuan
- Pengajuan 1 — N RiwayatStatus
- Pengajuan 1 — 0..1 Surat
- PejabatKalurahan 1 — N Surat
- RiwayatImpor 1 — N Penduduk

Diagram ERD visual dapat disusun pada tahap desain berikutnya.

### 9.3 Aturan Penomoran Surat

Format nomor mengikuti ketentuan kalurahan, misalnya `[kode klasifikasi]/[nomor urut]/[bulan romawi]/[tahun]`. Nomor urut direset setiap tahun, dibuat atomik (transaksi basis data) untuk mencegah duplikasi, dan tidak boleh dipakai ulang. Format pasti harus dikonfirmasi dengan kalurahan.

## 10. Matriks Hak Akses

| Fitur | Warga | Operator | Lurah | Admin |
| --- | --- | --- | --- | --- |
| Registrasi dan profil sendiri | ✔ |  |  |  |
| Ajukan dan lacak surat sendiri | ✔ |  |  |  |
| Verifikasi akun warga |  | ✔ |  | ✔ |
| Periksa dan proses pengajuan |  | ✔ |  |  |
| Setujui dan tanda tangan surat |  |  | ✔ |  |
| Lihat arsip dan agenda surat |  | ✔ | ✔ | ✔ |
| Impor dan kelola data penduduk |  | ✔ (terbatas) |  | ✔ |
| Kelola jenis layanan dan template |  |  |  | ✔ |
| Kelola pengguna dan peran |  |  |  | ✔ |
| Laporan |  | ✔ | ✔ | ✔ |
| Audit log |  |  |  | ✔ |

Prinsip: setiap pengguna hanya mengakses data yang diperlukan perannya. Warga tidak pernah dapat melihat data warga lain.

## 11. Arsitektur dan Teknologi

### 11.1 Arsitektur Umum

```
Warga / Operator / Lurah (browser HP & PC)
              ↓ HTTPS
        Aplikasi Web (Laravel)
   ┌──────────┼───────────────┐
   ↓          ↓               ↓
Database   Penyimpanan     Layanan Eksternal
(MySQL/    Berkas          (WhatsApp gateway,
PostgreSQL) (terenkripsi)   email SMTP)
```

### 11.2 Usulan Teknologi

| Lapisan | Pilihan | Alasan |
| --- | --- | --- |
| Backend | Laravel (PHP) | Umum dan mudah mencari developer; bawaan autentikasi dan validasi |
| Frontend | Blade + Tailwind CSS (Livewire/Vue bila perlu) | Ringan dan responsif |
| Basis data | MySQL atau PostgreSQL | Stabil, didukung hosting umum |
| PDF | DomPDF atau Snappy | Cetak surat dari template HTML |
| Impor Excel | Laravel Excel | Baca xlsx/csv, validasi per baris |
| Notifikasi | WhatsApp gateway dan email SMTP | Kanal yang paling sering dipakai warga |
| Penyimpanan | Disk privat terenkripsi atau S3-compatible | Berkas sensitif tidak publik |
| Tanda tangan | Gambar TTD + QR (fase 1); TTE tersertifikasi BSrE (lanjut) | Bertahap sesuai kesiapan |
| Hosting | VPS kecil atau hosting pemerintah daerah | Biaya rendah |

## 12. Keamanan dan Pelindungan Data Pribadi

1. **Dasar pengolahan data:** persetujuan eksplisit saat registrasi, dengan tujuan penggunaan yang jelas.
2. **Minimisasi:** hanya data yang diperlukan untuk surat yang dikumpulkan.
3. **Berkas sensitif:** foto KTP dan selfie disimpan terenkripsi, hanya dapat dibuka operator berwenang, dan tidak diakses lewat URL publik.
4. **Retensi:** selfie dihapus setelah verifikasi selesai; masa simpan berkas lain ditetapkan dalam kebijakan kalurahan.
5. **Anti-enumerasi:** pengecekan NIK tidak membocorkan data; dibatasi jumlah percobaan.
6. **Audit:** akses dan perubahan data sensitif tercatat.
7. **Pemisahan lingkungan:** data uji tidak memakai data penduduk asli.
8. **Penanganan insiden:** prosedur jika terjadi kebocoran data, termasuk pemberitahuan kepada pihak terkait.
9. **Hak warga:** warga dapat melihat dan meminta koreksi data pribadinya.

## 13. Rencana Pengembangan

### Fase 1: MVP (perkiraan 1-2 bulan)

- Impor data penduduk (nama, NIK, alamat) dengan pratinjau dan laporan gagal
- Registrasi warga, OTP, profil, unggah KTP, verifikasi akun oleh operator
- Tiga jenis surat: SKU, Domisili, SKTM, dengan pengantar RT/RW berupa unggah foto
- Dasbor operator, persetujuan Lurah, penomoran otomatis, PDF
- Audit log dasar dan backup

### Fase 2

- Notifikasi WhatsApp
- QR verifikasi keaslian surat dan halaman publik
- Akun RT/RW untuk persetujuan online
- Laporan rekap dan ekspor
- Penambahan jenis surat (Penghasilan, SKCK)

### Fase 3

- Jenis layanan dan template dinamis oleh admin
- TTE tersertifikasi
- Jenis surat lanjutan (nikah, kelahiran, kematian, pindah)
- Pengaduan dan informasi publik

### Tahapan Kerja per Fase

1. Pengumpulan kebutuhan dan konfirmasi template surat
2. Desain (ERD, mockup, alur)
3. Pengembangan
4. Pengujian (fungsional, keamanan, uji coba pengguna)
5. Pelatihan operator dan Lurah
6. Uji coba terbatas (pilot) di satu atau dua dukuh
7. Peluncuran penuh dan evaluasi

## 14. Rencana Pengujian dan Kriteria Penerimaan

| Area | Kriteria Diterima |
| --- | --- |
| Impor data | File contoh diimpor, baris tidak valid dilaporkan dengan alasan, tidak ada NIK ganda tersimpan |
| Registrasi | Warga dengan NIK terdaftar dapat mendaftar, OTP diterima, akun aktif setelah operator menyetujui |
| Keamanan akun | NIK tidak dikenal masuk antrean manual; pengecekan NIK tidak menampilkan data |
| Pengajuan | Warga dapat mengajukan SKU, Domisili, SKTM dari HP sampai surat PDF diterima |
| Perbaikan | Operator dapat meminta perbaikan dan warga dapat mengunggah ulang |
| Penomoran | Nomor surat berurutan, tidak ganda walau diproses bersamaan |
| Hak akses | Warga tidak dapat membuka pengajuan atau berkas warga lain |
| Arsip | Surat terbit tersimpan dan dapat dicari di buku agenda |
| Pencadangan | Pemulihan dari backup berhasil pada uji coba |

Pengujian meliputi uji fungsional, uji hak akses, uji keamanan dasar (OWASP Top 10), uji beban ringan, dan uji penerimaan pengguna (UAT) bersama operator dan Lurah.

## 15. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Data impor kotor | Pencocokan gagal, antrean manual membengkak | Audit dan pembersihan sebelum impor; pratinjau validasi |
| Warga tidak melek digital | Adopsi rendah | Loket pendampingan, pendaftaran oleh operator, panduan bergambar |
| Akun palsu atas nama orang lain | Penyalahgunaan layanan | Verifikasi KTP + selfie, persetujuan operator |
| Kebocoran data KTP/NIK | Pelanggaran UU PDP dan kepercayaan warga | Enkripsi, RBAC, audit log, retensi terbatas |
| Penolakan keabsahan tanda tangan digital | Surat tidak diterima instansi lain | Konsultasi dengan kapanewon/Kominfo; siapkan opsi cetak basah; rencana TTE tersertifikasi |
| Ketergantungan pada satu operator | Layanan berhenti saat operator berhalangan | Minimal dua operator terlatih dan dokumentasi |
| Pemeliharaan setelah serah terima | Sistem tidak terawat | Tetapkan penanggung jawab teknis dan anggaran pemeliharaan |
| Gangguan layanan WhatsApp gateway | Notifikasi tertunda | Kanal cadangan email; status tetap terlihat di aplikasi |
| Resistensi perangkat kalurahan | Proses lama tetap berjalan paralel | Pelibatan sejak awal, pelatihan, dukungan pimpinan |

## 16. Pertanyaan Terbuka

1. Apa format resmi nomor surat di kalurahan ini?
2. Apakah tersedia contoh template tiap jenis surat yang berlaku?
3. Apakah Lurah bersedia menyetujui lewat sistem, dan apakah perlu cetak basah untuk keperluan tertentu?
4. Berapa perkiraan jumlah penduduk dan jumlah surat per bulan?
5. Siapa operator dan penanggung jawab teknis setelah sistem berjalan?
6. Apakah sudah tersedia hosting atau domain, dan berapa anggarannya?
7. Bagaimana kebijakan retensi arsip dan berkas warga di kalurahan?
8. Apakah dibutuhkan jenis surat lain pada fase 1?

## 17. Langkah Selanjutnya

1. Konfirmasi pertanyaan terbuka dengan pihak kalurahan.
2. Audit file data penduduk.
3. Susun ERD visual, use case diagram, dan mockup layar.
4. Kumpulkan template surat resmi.
5. Mulai pengembangan fase 1.
