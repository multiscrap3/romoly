@extends('layouts.auth')

@section('title', __('privacy.terms_title'))

@section('content')
<div class="py-2">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">{{ __('privacy.terms_title') }}</h5>
    </div>

    <div class="alert alert-info py-2 small mb-4">
        <i class="bi bi-info-circle me-1"></i>
        {{ __('privacy.effective_date') }}: <strong>17 Oktober 2024</strong>
    </div>

    <h6 class="fw-bold mb-2">1. Penerimaan Syarat</h6>
    <p class="small text-muted mb-4">
        Dengan mendaftar dan menggunakan Romoly, Anda menyatakan telah membaca, memahami, dan
        menyetujui Syarat & Ketentuan ini. Jika Anda tidak setuju, jangan gunakan layanan ini.
    </p>

    <h6 class="fw-bold mb-2">2. Deskripsi Layanan</h6>
    <p class="small text-muted mb-4">
        Romoly adalah aplikasi manajemen keuangan rumah tangga yang memungkinkan pengguna mencatat
        transaksi, membuat anggaran, melacak tabungan, dan mendapatkan laporan keuangan bersama anggota
        rumah tangga (household).
    </p>

    <h6 class="fw-bold mb-2">3. Akun Pengguna</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Anda bertanggung jawab menjaga kerahasiaan password akun Anda</li>
            <li>Satu email hanya dapat digunakan untuk satu akun</li>
            <li>Anda wajib memberikan informasi yang akurat dan terkini</li>
            <li>Kami berhak menangguhkan akun yang melanggar ketentuan ini</li>
        </ul>
    </div>

    <h6 class="fw-bold mb-2">4. Data Keuangan</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Data keuangan yang Anda masukkan adalah milik Anda sepenuhnya</li>
            <li>Kami tidak menjual atau berbagi data keuangan Anda kepada pihak ketiga untuk keperluan komersial</li>
            <li>Data digunakan semata-mata untuk menjalankan fitur-fitur aplikasi yang Anda aktifkan</li>
            <li>Anda dapat mengunduh atau menghapus data kapan saja</li>
        </ul>
    </div>

    <h6 class="fw-bold mb-2">5. Fitur AI & OCR</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Fitur OCR dan analisis AI menggunakan Google Gemini API</li>
            <li>Gambar struk dan data transaksi yang Anda kirim untuk analisis AI diproses oleh Google</li>
            <li>Anda dapat memilih untuk tidak menggunakan fitur OCR dan AI</li>
            <li>Hasil analisis AI bersifat indikatif dan bukan merupakan nasihat keuangan profesional</li>
        </ul>
    </div>

    <h6 class="fw-bold mb-2">6. Household & Anggota</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Pemilik household (owner) bertanggung jawab atas penggunaan akun oleh anggota yang diundang</li>
            <li>Anggota household dapat mengakses data keuangan bersama sesuai peran yang diberikan</li>
            <li>Owner dapat mengubah peran atau mengeluarkan anggota kapan saja</li>
        </ul>
    </div>

    <h6 class="fw-bold mb-2">7. Batasan Tanggung Jawab</h6>
    <div class="small text-muted mb-4">
        <ul class="mb-0">
            <li>Romoly disediakan "sebagaimana adanya" tanpa jaminan apapun</li>
            <li>Kami tidak bertanggung jawab atas kerugian akibat keputusan keuangan berdasarkan data di aplikasi</li>
            <li>Kami tidak menjamin ketersediaan layanan 100% tanpa gangguan</li>
        </ul>
    </div>

    <h6 class="fw-bold mb-2">8. Hak Kekayaan Intelektual</h6>
    <p class="small text-muted mb-4">
        Seluruh konten, antarmuka, dan kode Romoly adalah milik pengembang dan dilindungi oleh hukum
        kekayaan intelektual yang berlaku. Pengguna tidak diizinkan menyalin, memodifikasi, atau
        mendistribusikan aplikasi tanpa izin tertulis.
    </p>

    <h6 class="fw-bold mb-2">9. Penghentian Layanan</h6>
    <p class="small text-muted mb-4">
        Anda dapat menghapus akun kapan saja melalui Settings. Penghapusan akun akan menghilangkan
        seluruh data dalam 30 hari. Kami berhak menghentikan layanan dengan pemberitahuan 30 hari.
    </p>

    <h6 class="fw-bold mb-2">10. Hukum yang Berlaku</h6>
    <p class="small text-muted mb-4">
        Syarat & Ketentuan ini diatur oleh hukum Republik Indonesia, termasuk UU No. 27 Tahun 2022
        tentang Perlindungan Data Pribadi. Sengketa diselesaikan melalui musyawarah atau pengadilan
        yang berwenang di Indonesia.
    </p>

    <h6 class="fw-bold mb-2">11. Perubahan Syarat</h6>
    <p class="small text-muted mb-4">
        Kami dapat memperbarui syarat ini sewaktu-waktu. Perubahan material akan diberitahukan
        melalui email atau notifikasi aplikasi minimal 14 hari sebelum berlaku.
    </p>

    <hr class="my-4">
    <p class="text-center small text-muted mb-0">
        <a href="{{ route('privacy.policy') }}" class="text-decoration-none me-3">{{ __('messages.privacy_policy') }}</a>
        &copy; {{ date('Y') }} Romoly
    </p>
</div>
@endsection
