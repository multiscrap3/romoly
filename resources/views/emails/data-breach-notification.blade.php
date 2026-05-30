<x-mail::message>
# Pemberitahuan Insiden Keamanan Data

Yth. **{{ $userName }}**,

Kami menulis surat ini untuk memberitahukan bahwa kami telah mendeteksi insiden keamanan yang mungkin berdampak pada data pribadi Anda di **Romoly**.

Sesuai **UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)**, kami berkewajiban memberitahu Anda dalam waktu **14 hari** sejak insiden terdeteksi.

---

## Detail Insiden

**Tanggal Kejadian:** {{ $incidentDate }}

**Data yang Mungkin Terdampak:**
{{ $affectedData }}

---

## Tindakan yang Telah Kami Ambil

{{ $actionsTaken }}

---

## Yang Perlu Anda Lakukan

{{ $userActions }}

<x-mail::button :url="route('settings.index') . '?tab=password'">
Ganti Password Sekarang
</x-mail::button>

---

Jika Anda memiliki pertanyaan atau kekhawatiran, segera hubungi kami di:

**{{ __('superadmin.email') }}:** finanku.app@gmail.com

Kami memohon maaf atas ketidaknyamanan ini dan berkomitmen untuk terus meningkatkan keamanan sistem kami.

Hormat kami,<br>
**Tim Romoly**

---

*Email ini dikirim sesuai kewajiban notifikasi pelanggaran data berdasarkan UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi.*
*Jika Anda merasa tidak memiliki akun Romoly, abaikan email ini.*
</x-mail::message>
