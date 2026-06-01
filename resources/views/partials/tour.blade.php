{{-- ============================================================
     Guided Tour / User Guide — bootstrapping (Driver.js)
     driver.css di-load di <head> layout. Skrip di-push ke stack 'scripts'.
     ============================================================ --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<script>
    // Sumber tunggal langkah + label (dari lang/{locale}/tour.php), ikut locale aktif.
    window.TOUR_I18N = @json(__('tour'));
</script>
<script src="{{ asset('js/tour/tour-core.js') }}?v={{ config('app.version') }}"></script>
@endpush
