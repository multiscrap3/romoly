<x-mail::message>
# Anda Diundang Bergabung! 🎉

Halo,

**{{ $inviterName }}** mengundang Anda untuk bergabung mengelola keuangan bersama di household berikut:

<x-mail::panel>
**{{ $householdName }}**
Peran Anda: **{{ ucfirst($role) }}**
</x-mail::panel>

Dengan bergabung, Anda dapat mencatat transaksi, memantau anggaran, dan melihat laporan keuangan keluarga secara bersama-sama di **{{ config('app.name') }}**.

<x-mail::button :url="$registerUrl" color="primary">
Terima Undangan & Daftar
</x-mail::button>

@if($expiresAt)
> ⏳ Undangan ini berlaku hingga **{{ \Carbon\Carbon::parse($expiresAt)->translatedFormat('d F Y, H:i') }} WIB**.
@endif

Jika tombol di atas tidak berfungsi, salin dan tempel tautan ini ke browser Anda:

{{ $registerUrl }}

Jika Anda merasa tidak mengenal pengirim undangan ini, abaikan saja email ini.

Salam hangat,<br>
**Tim {{ config('app.name') }}**
</x-mail::message>
