@extends('layouts.auth')

@section('title', __('privacy.policy_title'))

@section('content')
<div class="py-2">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">{{ __('privacy.policy_title') }}</h5>
    </div>

    <div class="alert alert-info py-2 small mb-4">
        <i class="bi bi-info-circle me-1"></i>
        {{ __('privacy.last_updated') }}: <strong>{{ $version }}</strong> &mdash; {{ __('privacy.effective_date') }}: <strong>17 Oktober 2024</strong>
    </div>

    {{-- 1. Pendahuluan --}}
    <h6 class="fw-bold mb-2">1. Pendahuluan</h6>
    <p class="small text-muted mb-4">
        Romoly ("kami", "aplikasi") berkomitmen melindungi privasi dan keamanan data pribadi Anda sesuai
        <strong>Undang-Undang No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)</strong>.
        Kebijakan ini menjelaskan jenis data yang kami kumpulkan, cara penggunaannya, dan hak-hak Anda sebagai subjek data.
    </p>

    {{-- 2. Identitas Pengendali Data --}}
    <h6 class="fw-bold mb-2">2. Identitas Pengendali Data</h6>
    <div class="small text-muted mb-4">
        <p class="mb-1"><strong>Nama Aplikasi:</strong> Romoly</p>
        <p class="mb-1"><strong>Fungsi:</strong> Manajemen keuangan rumah tangga</p>
        <p class="mb-1"><strong>Kontak DPO (Data Protection Officer):</strong>
            <a href="mailto:finanku.app@gmail.com">finanku.app@gmail.com</a>
        </p>
    </div>

    {{-- 3. Data yang Dikumpulkan --}}
    <h6 class="fw-bold mb-2">3. Data Pribadi yang Dikumpulkan</h6>
    <div class="small text-muted mb-4">
        <p class="mb-2"><strong>Data Umum:</strong></p>
        <ul class="mb-3">
            <li>Nama lengkap, alamat email, nomor telepon</li>
            <li>Tanggal lahir (opsional)</li>
            <li>Foto profil (opsional)</li>
            <li>Alamat IP dan informasi perangkat (user agent)</li>
        </ul>
        <p class="mb-2"><strong>Data Keuangan (Data Sensitif):</strong></p>
        <ul class="mb-3">
            <li>Catatan transaksi keuangan (pemasukan & pengeluaran)</li>
            <li>Data rekening/sumber transaksi</li>
            <li>Anggaran dan target tabungan</li>
            <li>Data hutang dan piutang</li>
            <li>Riwayat impor rekening bank</li>
        </ul>
        <p class="mb-2"><strong>Data Teknis:</strong></p>
        <ul class="mb-0">
            <li>Log aktivitas (waktu login, perubahan data)</li>
            <li>Riwayat penggunaan OCR (gambar struk belanja)</li>
            <li>Preferensi aplikasi</li>
        </ul>
    </div>

    {{-- 4. Tujuan Pengolahan --}}
    <h6 class="fw-bold mb-2">4. Tujuan Pengolahan Data</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Menyediakan layanan manajemen keuangan rumah tangga</li>
            <li>Autentikasi dan keamanan akun</li>
            <li>Menghasilkan laporan keuangan dan analitik personal</li>
            <li>Pendeteksian anomali dan saran keuangan berbasis AI</li>
            <li>Perbaikan dan pengembangan fitur aplikasi</li>
            <li>Notifikasi terkait aktivitas akun dan keuangan</li>
        </ul>
    </div>

    {{-- 5. Dasar Hukum --}}
    <h6 class="fw-bold mb-2">5. Dasar Hukum Pengolahan</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li><strong>Persetujuan</strong> — Anda memberikan consent eksplisit saat mendaftar</li>
            <li><strong>Pelaksanaan perjanjian</strong> — diperlukan untuk menjalankan layanan yang Anda minta</li>
            <li><strong>Kepentingan sah</strong> — untuk keamanan akun dan deteksi fraud</li>
        </ul>
    </div>

    {{-- 6. Pihak Ketiga --}}
    <h6 class="fw-bold mb-2">6. Pengolahan Data oleh Pihak Ketiga</h6>
    <div class="small text-muted mb-4">
        <p class="mb-2">
            Romoly menggunakan layanan <strong>Google Gemini API</strong> untuk memproses:
        </p>
        <ul class="mb-2">
            <li>Gambar struk belanja (fitur OCR) — untuk mengekstrak nominal dan merchant</li>
            <li>Data transaksi anonim — untuk deteksi anomali dan saran keuangan</li>
        </ul>
        <p class="mb-0">
            Data yang dikirim ke Google Gemini tidak bersifat identifiable secara langsung.
            Kebijakan privasi Google tersedia di
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>.
        </p>
    </div>

    {{-- 7. Hak Subjek Data --}}
    <h6 class="fw-bold mb-2">7. Hak-Hak Anda sebagai Subjek Data</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-2">
            <li><strong>Hak Akses</strong> — Melihat data pribadi yang kami simpan</li>
            <li><strong>Hak Koreksi</strong> — Memperbarui data yang tidak akurat melalui Settings</li>
            <li><strong>Hak Penghapusan</strong> — Menghapus akun dan seluruh data Anda</li>
            <li><strong>Hak Portabilitas</strong> — Mengunduh data pribadi dalam format JSON</li>
            <li><strong>Hak Pembatasan</strong> — Menonaktifkan notifikasi dan fitur tertentu</li>
            <li><strong>Hak Objeksi</strong> — Menolak pemrosesan data untuk tujuan tertentu</li>
            <li><strong>Hak Menarik Consent</strong> — Menghapus akun kapan saja</li>
        </ul>
        <p class="mb-0">
            Untuk menggunakan hak Anda, kunjungi halaman
            <a href="{{ route('privacy.export') }}">Privasi & Data</a> di Settings
            atau hubungi kami di <a href="mailto:finanku.app@gmail.com">finanku.app@gmail.com</a>.
            Kami akan merespons dalam <strong>14 hari kerja</strong>.
        </p>
    </div>

    {{-- 8. Retensi Data --}}
    <h6 class="fw-bold mb-2">8. Retensi Data</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Data akun aktif: selama akun digunakan</li>
            <li>Data setelah penghapusan akun: dihapus dalam <strong>30 hari</strong></li>
            <li>Log audit: disimpan maksimal <strong>2 tahun</strong> untuk keperluan keamanan</li>
            <li>File upload (foto, struk): dihapus bersama akun</li>
        </ul>
    </div>

    {{-- 9. Keamanan --}}
    <h6 class="fw-bold mb-2">9. Keamanan Data</h6>
    <p class="small text-muted mb-4">
        Kami menerapkan enkripsi password (bcrypt), sesi berbasis database, audit trail perubahan data,
        dan pembatasan akses berbasis peran (RBAC) untuk melindungi data Anda.
    </p>

    {{-- 10. Perubahan Kebijakan --}}
    <h6 class="fw-bold mb-2">10. Perubahan Kebijakan</h6>
    <p class="small text-muted mb-4">
        Kami akan memberitahu Anda melalui email atau notifikasi dalam aplikasi jika terjadi perubahan
        material pada kebijakan ini. Penggunaan berkelanjutan setelah perubahan berarti Anda menyetujui
        kebijakan yang diperbarui.
    </p>

    {{-- 11. Kontak --}}
    <h6 class="fw-bold mb-2">11. Hubungi Kami</h6>
    <p class="small text-muted mb-0">
        Pertanyaan tentang kebijakan privasi atau permintaan terkait data:
        <a href="mailto:finanku.app@gmail.com">finanku.app@gmail.com</a>
    </p>

    <hr class="my-4">
    <p class="text-center small text-muted mb-0">
        <a href="{{ route('privacy.terms') }}" class="text-decoration-none me-3">{{ __('messages.terms') }}</a>
        &copy; {{ date('Y') }} Romoly
    </p>
</div>
@endsection
