<?php

return [
    'title'           => 'Sumber Dana',
    'add'             => 'Tambah Sumber Dana',
    'add_title'       => 'Tambah Sumber Dana',
    'edit'            => 'Edit Sumber Dana',
    'edit_title'      => 'Edit Sumber Dana',
    'all'             => 'Semua Sumber Dana',

    'name'            => 'Nama Rekening',
    'name_ph'         => 'Contoh: BCA Tabungan',
    'type'            => 'Jenis',
    'bank'            => 'Rekening Bank',
    'cash'            => 'Kas/Tunai',
    'ewallet'         => 'Dompet Digital',
    'card_credit'     => 'Kartu Kredit',
    'investment'      => 'Investasi',
    'other'           => 'Lainnya',
    'account_number'  => 'Nomor Rekening',
    'bank_name'       => 'Nama Bank / Penerbit',
    'initial_balance' => 'Saldo Awal',
    'current_balance' => 'Saldo Saat Ini',
    'notes'           => 'Saldo hanya dapat diubah melalui transaksi.',
    'color'           => 'Warna',
    'icon'            => 'Ikon',
    'icon_pick'       => 'Pilih Ikon',

    'save'            => 'Simpan',
    'cancel'          => 'Batal',
    'delete_title'    => 'Hapus Sumber Dana?',
    'delete_confirm'  => 'Hapus sumber dana ini?',
    'no_sources'      => 'Belum ada sumber dana.',
    'no_active'       => 'Belum ada sumber dana aktif.',

    'total_aset'      => 'Total Aset',
    'active'          => 'Aktif',
    'archived'        => 'Diarsipkan',
    'archive_section' => 'Sumber Dana Diarsipkan',

    'deactivate'      => 'Arsipkan',
    'deactivate_hint' => 'Arsipkan agar tidak muncul di daftar aktif. Riwayat transaksi tetap tersimpan.',
    'activate'        => 'Aktifkan',

    // Success messages
    'stored'          => 'Sumber dana berhasil ditambahkan.',
    'updated'         => 'Sumber dana berhasil diperbarui.',
    'deleted'         => 'Sumber dana berhasil dihapus.',
    'deactivated'     => 'Sumber dana diarsipkan. Riwayat transaksi tetap tersimpan.',
    'activated'       => 'Sumber dana berhasil diaktifkan kembali.',
    'saldo_adjusted'  => 'Saldo berhasil disesuaikan.',

    // Error messages
    'error_has_saldo'     => 'Tidak dapat dihapus — saldo masih :saldo. Kosongkan saldo terlebih dahulu melalui transaksi.',
    'error_has_transaksi' => 'Tidak dapat dihapus karena memiliki :count transaksi. Gunakan "Arsipkan" untuk menyembunyikan dari daftar aktif tanpa menghapus riwayat.',
];
