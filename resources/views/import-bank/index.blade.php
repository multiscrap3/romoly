@extends('layouts.app')

@section('title', __('import.title'))
@section('page-title', __('import.title'))

@section('content')
<div class="row g-4">

    <div class="col-12 d-flex align-items-center justify-content-between">
        <p class="text-muted small mb-0">Total {{ $imports->total() }} import</p>
        <div class="d-flex gap-2">
            <a href="{{ route('import-bank.web.template') }}" class="btn btn-outline-secondary btn-sm" download data-tour="import-template">
                <i class="bi bi-download me-1"></i>Template CSV
            </a>
            <a href="{{ route('import-bank.web.form') }}" class="btn btn-primary btn-sm" data-tour="import-start">
                <i class="bi bi-upload me-1"></i>Import Baru
            </a>
        </div>
    </div>

    {{-- Flash success --}}
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    {{-- Info PDP retensi --}}
    <div class="col-12">
        <div class="d-flex align-items-start gap-2 p-3 rounded small"
             style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <i class="bi bi-shield-check text-success flex-shrink-0 mt-1"></i>
            <span style="color:#166534;">
                <strong>Kebijakan Perlindungan Data:</strong>
                File mutasi bank dihapus otomatis setelah import selesai.
                Hanya data transaksi yang disimpan di akun Anda.
                <a href="{{ route('privacy.policy') }}" target="_blank" class="fw-semibold ms-1" style="color:#166534;">Kebijakan Privasi</a>
            </span>
        </div>
    </div>

    <div class="col-12" data-tour="import-history">
        <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
            <div class="card-body p-0">
                @forelse($imports as $import)
                    <div class="d-flex align-items-start gap-3 px-4 py-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0 mt-1"
                             style="width:42px;height:42px;background:rgba(59,130,246,.12);">
                            <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-medium text-dark">
                                {{ $import->sumberTransaksi?->nama ?? 'Import' }}
                                @if($import->file_name)
                                    <span class="text-muted fw-normal ms-1">— {{ $import->file_name }}</span>
                                @endif
                            </div>
                            <div class="text-muted mb-1" style="font-size:.72rem;">
                                {{ $import->created_at->translatedFormat('d M Y H:i') }} oleh {{ $import->user?->name }}
                            </div>

                            {{-- Status file PDP --}}
                            @if($import->file_path)
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                                          style="font-size:.65rem;">
                                        <i class="bi bi-file-earmark me-1"></i>File tersimpan
                                    </span>
                                    <span style="font-size:.65rem;color:#6b7280;">
                                        Akan dihapus otomatis dalam {{ max(0, 30 - $import->created_at->diffInDays(now())) }} hari
                                    </span>
                                    <form method="POST"
                                          action="{{ route('import-bank.web.delete-file', $import) }}"
                                          style="display:inline;"
                                          onsubmit="return confirm('Hapus file bank statement ini? Data transaksi yang sudah diimport tetap tersimpan.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger"
                                                style="font-size:.65rem;text-decoration:none;">
                                            <i class="bi bi-trash me-1"></i>Hapus File
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle mt-1"
                                      style="font-size:.65rem;">
                                    <i class="bi bi-shield-check me-1"></i>File sudah dihapus
                                </span>
                            @endif
                        </div>
                        <div class="text-end flex-shrink-0">
                            <span class="badge rounded-pill
                                {{ $import->status === 'completed' ? 'bg-success' : ($import->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                {{ ucfirst($import->status) }}
                            </span>
                            @if($import->total_rows)
                                <div class="text-muted mt-1" style="font-size:.7rem;">
                                    {{ $import->imported_rows ?? 0 }}/{{ $import->total_rows }} baris
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-5 text-center">
                        <i class="bi bi-upload fs-1 d-block mb-2 text-muted opacity-25"></i>
                        <p class="text-muted small mb-2">{{ __('import.no_imports') }}</p>
                        <a href="{{ route('import-bank.web.form') }}" class="small text-primary fw-medium text-decoration-none">
                            Import sekarang
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if($imports->hasPages())
        <div class="col-12">
            {{ $imports->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif

</div>
@endsection
