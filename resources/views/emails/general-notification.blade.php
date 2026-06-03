<x-mail::message>
# {{ $judul }}

@if($greetingName)
Halo **{{ $greetingName }}**,
@else
Halo,
@endif

{{ $pesan }}

@if($ctaUrl)
<x-mail::button :url="$ctaUrl" :color="$buttonColor">
{{ $ctaLabel ?? 'Buka ' . config('app.name') }}
</x-mail::button>
@endif

Anda menerima email ini karena mengaktifkan notifikasi email di **{{ config('app.name') }}**.
Anda dapat menonaktifkannya kapan saja melalui **Pengaturan → Notifikasi**.

Salam hangat,<br>
**Tim {{ config('app.name') }}**
</x-mail::message>
