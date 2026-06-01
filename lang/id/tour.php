<?php

/*
|--------------------------------------------------------------------------
| Guided Tour / User Guide — teks (ID)
|--------------------------------------------------------------------------
| Sumber tunggal langkah tour. Tiap entri:
|   'tour'  => nilai atribut data-tour pada elemen target (anchor di Blade)
|   'title' => judul popover
|   'body'  => penjelasan
|   'roles' => (opsional) hanya tampil untuk role tsb; default: semua role
| Urutan & jumlah langkah HARUS identik dengan lang/en/tour.php.
| Referensi copy: userguide.md Bagian 7.
*/

return [

    'common' => [
        'next'          => 'Lanjut',
        'prev'          => 'Kembali',
        'done'          => 'Selesai',
        'progress'      => 'Langkah {{current}} dari {{total}}',
        'replay_button' => 'Panduan',
        'replay_title'  => 'Putar ulang panduan halaman ini',
        'reset_confirm' => 'Putar ulang semua panduan dari awal? Panduan akan muncul lagi saat kamu membuka tiap halaman.',
    ],

    // ── Tour global "Selamat datang" (sekali seumur akun, di Dashboard) ──
    'welcome' => [
        ['tour' => 'nav-sidebar',    'title' => 'Menu Utama',        'body' => 'Ini menu utama. Semua fitur Romoly ada di sini.'],
        ['tour' => 'fab-add',        'title' => 'Tambah Transaksi',  'body' => 'Tombol ini untuk menambah transaksi cepat: pemasukan, pengeluaran, atau transfer.', 'roles' => ['admin', 'member']],
        ['tour' => 'topbar-notif',   'title' => 'Notifikasi',        'body' => 'Notifikasi anggaran, tagihan, dan aktivitas keluarga muncul di sini.'],
        ['tour' => 'topbar-theme',   'title' => 'Tampilan',          'body' => 'Ganti tampilan terang atau gelap sesuai selera.'],
        ['tour' => 'topbar-profile', 'title' => 'Akun & Pengaturan', 'body' => 'Akun, pengaturan, dan keluar dari aplikasi ada di sini.'],
        ['tour' => 'nav-gamifikasi', 'title' => 'Progres Kamu',      'body' => 'Lihat progres, level, dan pencapaian keuangan kamu di sini.'],
    ],

    // ── Dashboard ──
    'dashboard' => [
        ['tour' => 'dash-hero-saldo',           'title' => 'Total Saldo',      'body' => 'Total saldo seluruh sumber dana kamu ada di kotak ini. Akan terisi setelah kamu mencatat transaksi.'],
        ['tour' => 'dash-card-transaksi',        'title' => 'Ringkasan Bulan',  'body' => 'Ringkasan pemasukan dan pengeluaran bulan ini.'],
        ['tour' => 'dash-card-anggaran',         'title' => 'Anggaran',         'body' => 'Pantau anggaran — hijau berarti aman, merah berarti melewati batas.'],
        ['tour' => 'dash-gamification-insight',  'title' => 'Progres Keuangan', 'body' => 'Kotak progres: XP, momentum, dan misi keuangan kamu.'],
        ['tour' => 'dash-chart-kategori',        'title' => 'Pengeluaran',      'body' => 'Pengeluaran terbesar per kategori divisualkan di sini.'],
        ['tour' => 'dash-edit-layout',           'title' => 'Atur Tampilan',    'body' => 'Susun ulang widget dashboard sesuai kebutuhanmu.'],
        ['tour' => 'fab-add',                    'title' => 'Mulai Mencatat',   'body' => 'Siap mulai? Catat transaksi pertamamu dari tombol ini.', 'roles' => ['admin', 'member']],
    ],

    // ── Transaksi ──
    'transaksi.index' => [
        ['tour' => 'transaksi-add',    'title' => 'Tambah Transaksi', 'body' => 'Tombol ini untuk mencatat pemasukan, pengeluaran, atau transfer baru.', 'roles' => ['admin', 'member']],
        ['tour' => 'transaksi-filter', 'title' => 'Filter',           'body' => 'Saring transaksi berdasarkan jenis, rentang tanggal, atau sumber dana.'],
        ['tour' => 'transaksi-search', 'title' => 'Pencarian',        'body' => 'Ketik kata kunci di sini untuk mencari transaksi dari keterangannya.'],
        ['tour' => 'transaksi-export', 'title' => 'Export',           'body' => 'Unduh daftar transaksi ke Excel, CSV, atau PDF untuk arsip atau laporan.'],
        ['tour' => 'transaksi-list',   'title' => 'Daftar Transaksi', 'body' => 'Klik salah satu baris untuk melihat detail lengkap atau mengubahnya.'],
    ],

    'transaksi.create' => [
        ['tour' => 'tx-jenis',      'title' => 'Jenis Transaksi', 'body' => 'Pilih dulu: pemasukan (uang masuk), pengeluaran (uang keluar), atau transfer antar sumber dana.'],
        ['tour' => 'tx-jumlah',     'title' => 'Jumlah',          'body' => 'Masukkan nominal di sini. Angka otomatis diformat ke Rupiah, jadi cukup ketik angkanya.'],
        ['tour' => 'tx-kategori',   'title' => 'Kategori',        'body' => 'Kelompokkan transaksi (mis. Makan, Transportasi) supaya laporan dan anggaran kamu rapi.'],
        ['tour' => 'tx-sumber',     'title' => 'Sumber Dana',     'body' => 'Pilih dari rekening atau dompet mana uang ini berasal atau masuk.'],
        ['tour' => 'tx-ocr',        'title' => 'Scan Struk (OCR)','body' => 'Punya struk belanja? Foto saja di sini, biar AI mengisi jumlah dan rinciannya otomatis.'],
        ['tour' => 'tx-keterangan', 'title' => 'Keterangan & Tag','body' => 'Tambahkan catatan dan tag agar transaksi lebih mudah dicari nanti.'],
        ['tour' => 'tx-submit',     'title' => 'Simpan',          'body' => 'Selesai? Simpan transaksi. Saldo sumber dana langsung ikut terupdate.'],
    ],

    // ── Import Bank ──
    'import-bank.web.index' => [
        ['tour' => 'import-start',    'title' => 'Impor Mutasi',  'body' => 'Daripada catat manual satu per satu, impor mutasi rekening dari file bank kamu sekaligus.', 'roles' => ['admin', 'member']],
        ['tour' => 'import-history',  'title' => 'Riwayat Impor', 'body' => 'Semua impor sebelumnya tercatat di sini, lengkap dengan statusnya.'],
        ['tour' => 'import-template', 'title' => 'Template',      'body' => 'Belum punya format yang pas? Unduh template ini sebagai acuan.'],
    ],

    // ── Laporan ──
    'laporan.index' => [
        ['tour' => 'laporan-jenis',   'title' => 'Jenis Laporan', 'body' => 'Pilih sudut pandang: harian, mingguan, bulanan, tahunan, atau bandingkan antar periode.'],
        ['tour' => 'laporan-periode', 'title' => 'Periode',       'body' => 'Atur rentang waktu yang ingin kamu tinjau di sini.'],
        ['tour' => 'laporan-export',  'title' => 'Export',        'body' => 'Unduh laporan sebagai PDF atau Excel untuk dibagikan atau diarsipkan.'],
    ],

    // ── Gamifikasi ──
    'gamifikasi.index' => [
        ['tour' => 'game-level',       'title' => 'Level & XP',    'body' => 'Setiap kali kamu disiplin mencatat keuangan, kamu dapat XP dan naik level. Ini progresmu.'],
        ['tour' => 'game-momentum',    'title' => 'Momentum',      'body' => 'Momentum menjaga kebiasaan harianmu — makin rajin, makin tinggi. Jangan sampai putus!'],
        ['tour' => 'game-achievement', 'title' => 'Pencapaian',    'body' => 'Lencana pencapaian yang kamu kumpulkan dari kebiasaan finansial yang baik.'],
        ['tour' => 'game-challenge',   'title' => 'Challenge',     'body' => 'Misi untuk memacu kebiasaan sehat — selesaikan untuk XP tambahan.'],
    ],

    // ── Anggaran ──
    'anggaran.index' => [
        ['tour' => 'anggaran-add',      'title' => 'Tambah Anggaran', 'body' => 'Tetapkan batas pengeluaran per kategori supaya belanja tetap terkendali.', 'roles' => ['admin', 'member']],
        ['tour' => 'anggaran-progress', 'title' => 'Progres Anggaran','body' => 'Bar ini menunjukkan berapa yang sudah terpakai dari batas — hijau aman, merah berarti lewat.'],
    ],

    // ── Tabungan ──
    'tabungan.index' => [
        ['tour' => 'tabungan-add',      'title' => 'Tambah Target',   'body' => 'Buat target nabung (mis. Dana Darurat, Liburan) dan pantau perkembangannya.', 'roles' => ['admin', 'member']],
        ['tour' => 'tabungan-progress', 'title' => 'Progres Tabungan','body' => 'Bar ini menunjukkan berapa yang sudah terkumpul dari targetmu.'],
    ],

    // ── Hutang & Piutang ──
    'hutang-piutang.index' => [
        ['tour' => 'hp-ringkasan', 'title' => 'Hutang & Piutang', 'body' => 'Hutang = uang yang kamu pinjam; Piutang = uang yang dipinjam orang dari kamu. Keduanya dipantau di sini.'],
        ['tour' => 'hp-add',       'title' => 'Tambah Catatan',   'body' => 'Catat hutang atau piutang baru di sini, lengkap dengan jatuh tempo dan opsi cicilan.', 'roles' => ['admin', 'member']],
    ],

    // ── Transaksi Rutin ──
    'recurring.index' => [
        ['tour' => 'recurring-add',    'title' => 'Transaksi Rutin', 'body' => 'Untuk transaksi yang berulang (gaji, langganan, cicilan), atur sekali dan biarkan tercatat otomatis.', 'roles' => ['admin', 'member']],
        ['tour' => 'recurring-toggle', 'title' => 'Aktif / Nonaktif','body' => 'Hidupkan atau jeda transaksi rutin kapan saja tanpa menghapusnya.'],
    ],

    // ── Kategori ──
    'kategori.index' => [
        ['tour' => 'kategori-add', 'title' => 'Tambah Kategori', 'body' => 'Buat kategori pemasukan atau pengeluaran sesuai kebutuhan keluargamu. Ini fondasi laporan dan anggaran.', 'roles' => ['admin', 'member']],
    ],

    // ── Sumber Dana ──
    'sumber-transaksi.index' => [
        ['tour' => 'sumber-add',   'title' => 'Tambah Sumber Dana', 'body' => 'Daftarkan rekening, dompet digital, atau kas tunai kamu di sini.', 'roles' => ['admin', 'member']],
        ['tour' => 'sumber-saldo', 'title' => 'Saldo Otomatis',     'body' => 'Saldo dihitung otomatis dari transaksi — kamu tidak perlu mengubahnya manual.'],
    ],

    // ── Tags ──
    'tags.index' => [
        ['tour' => 'tags-add', 'title' => 'Tag', 'body' => 'Tag memberi label lintas kategori (mis. liburan, anak) supaya transaksi terkait mudah dikumpulkan.', 'roles' => ['admin', 'member']],
    ],

    // ── Household (Keluarga) ──
    'household.index' => [
        ['tour' => 'household-invite', 'title' => 'Undang Anggota', 'body' => 'Ajak pasangan atau anggota keluarga agar bisa mengelola keuangan bersama dalam satu ruang data.', 'roles' => ['admin']],
        ['tour' => 'household-join',   'title' => 'Gabung',         'body' => 'Punya kode undangan? Masukkan di sini untuk bergabung ke household lain.'],
    ],

    // ── Notifikasi ──
    'notifikasi.index' => [
        ['tour' => 'notif-list',     'title' => 'Notifikasi',      'body' => 'Peringatan anggaran lewat batas, tabungan tercapai, hutang jatuh tempo, dan aktivitas keluarga muncul di sini.'],
        ['tour' => 'notif-read-all', 'title' => 'Tandai Dibaca',   'body' => 'Bersihkan semua notifikasi belum dibaca sekali klik.'],
    ],

    // ── Settings ──
    'settings.index' => [
        ['tour' => 'settings-profile',     'title' => 'Profil & Akun',     'body' => 'Atur nama, foto, kata sandi, dan data keluargamu di sini.'],
        ['tour' => 'settings-preferensi',  'title' => 'Preferensi',        'body' => 'Sesuaikan bahasa, tema terang/gelap, dan format mata uang.'],
        ['tour' => 'settings-replay-tour', 'title' => 'Putar Ulang Panduan','body' => 'Ingin melihat tur fitur lagi dari awal? Reset panduan di sini.'],
    ],

];
