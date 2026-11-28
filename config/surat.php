<?php

return [

    'kalurahan' => env('KALURAHAN_NAMA', 'Sindumartani'),
    'kapanewon' => env('KALURAHAN_KAPANEWON', 'Ngemplak'),
    'kabupaten' => env('KALURAHAN_KABUPATEN', 'Sleman'),
    'alamat_kalurahan' => env('KALURAHAN_ALAMAT', 'Sorobayan, Sindumartani, Ngemplak, Sleman, DIY 55584'),
    'laman' => env('KALURAHAN_LAMAN', 'www.sindumartani.desa.id'),
    'surel' => env('KALURAHAN_SUREL', 'desasindumartani@slemankab.go.id'),
    'nama_lurah' => env('KALURAHAN_LURAH', 'Drs. H. Anang Zamroni, M.SI'),

    /*
    | Contoh dari dokumen analisis: [kode]/[nomor urut]/[bulan romawi]/[tahun].
    | Jangan dianggap resmi sebelum kalurahan mengunci formatnya.
    */
    'kata_sandi_percobaan' => env('SEED_PASSWORD', 'kalurahan-dev'),

    /*
    | kanal=catat menyimpan pesan di aplikasi.
    | kanal=whatsapp mengirim JSON {pengirim, tujuan, pesan} ke gateway, lalu email jika gagal.
    | Surat selesai dikirim sebagai dokumen ke whatsapp_dokumen_url. Jika URL itu kosong
    | dan whatsapp_url berakhiran /kirim, pintu dokumen memakai jalur yang sama dengan akhiran /dokumen.
    | pengirim adalah nomor WhatsApp kalurahan yang mengirim kode verifikasi.
    */
    /*
    | Tanda tangan tersertifikasi dilakukan di SIMARDA.
    | Aplikasi ini menyimpan PDF bersegel yang diunggah operator.
    */
    'tte' => [
        'mode' => env('TTE_MODE', 'gambar'),
        'url' => env('BSRE_URL'),
        'token' => env('BSRE_TOKEN'),
        'nik' => env('BSRE_NIK'),
    ],

    'notifikasi' => [
        'kanal' => env('NOTIFIKASI_KANAL', 'catat'),
        'nomor_whatsapp' => env('KALURAHAN_WHATSAPP', '0881080712007'),
        'whatsapp_url' => env('WHATSAPP_GATEWAY_URL'),
        'whatsapp_dokumen_url' => env('WHATSAPP_GATEWAY_DOKUMEN_URL'),
        'whatsapp_token' => env('WHATSAPP_GATEWAY_TOKEN'),
    ],

    /*
    | lokal membaca KTP di komputer ini. mati melewati pembacaan foto.
    | Nama dan alamat yang cocok tetap diambil dari data impor, bukan dari foto.
    */
    'pembaca_ktp' => env('PEMBACA_KTP', 'lokal'),

    'format' => env('SURAT_NOMOR_FORMAT', '{kode}/{nomor}/{bulan_romawi}/{tahun}'),
    'format_dikonfirmasi' => filter_var(env('SURAT_NOMOR_DIKONFIRMASI', false), FILTER_VALIDATE_BOOLEAN),

    'pertanyaan_terbuka' => [
        'Apa format resmi nomor surat di kalurahan ini?',
        'Apakah tersedia contoh template tiap jenis surat yang berlaku?',
        'Apakah Lurah bersedia menyetujui lewat sistem, dan apakah perlu cetak basah untuk keperluan tertentu?',
        'Berapa perkiraan jumlah penduduk dan jumlah surat per bulan?',
        'Siapa operator dan penanggung jawab teknis setelah sistem berjalan?',
        'Apakah sudah tersedia hosting atau domain, dan berapa anggarannya?',
        'Bagaimana kebijakan retensi arsip dan berkas warga di kalurahan?',
        'Apakah dibutuhkan jenis surat lain pada fase pertama, selain SKU, domisili, dan SKTM?',
    ],

];
