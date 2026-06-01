@extends('layouts.app')

@section('title', __('anggaran.title'))
@section('page-title', __('anggaran.title'))

@section('content')
<div class="row g-4">

    <div class="col-12 d-flex justify-content-end">
        <a href="{{ route('anggaran.create') }}" class="btn btn-primary btn-sm" data-tour="anggaran-add">
            <i class="bi bi-plus-lg me-1"></i>{{ __('anggaran.add') }}
        </a>
    </div>

    @forelse($anggaran ?? [] as $item)
        @php
            $persen = $item->limit > 0 ? min(100, ($item->terpakai / $item->limit) * 100) : 0;
            $barClass = $persen >= 90 ? 'bg-danger' : ($persen >= 70 ? 'bg-warning' : 'bg-success');
            $textClass = $persen >= 90 ? 'text-danger' : ($persen >= 70 ? 'text-warning' : 'text-success');
        @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <div class="fw-semibold small">{{ $item->kategori?->nama ?? 'Tanpa Kategori' }}</div>
                            <div class="text-muted" style="font-size:.72rem;">
                                {{ __('anggaran.used') }}: Rp {{ number_format($item->terpakai ?? 0, 0, ',', '.') }} / Rp {{ number_format($item->limit, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold {{ $textClass }}">{{ number_format($persen, 0) }}%</span>
                            <a href="{{ route('anggaran.edit', $item) }}" class="small text-primary text-decoration-none">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('anggaran.destroy', $item) }}"
                                  onsubmit="return confirm('{{ __('anggaran.delete_confirm') }}')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="progress" style="height:8px;" data-tour="anggaran-progress">
                        <div class="progress-bar {{ $barClass }}" role="progressbar"
                             style="width:{{ $persen }}%;" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    @if($persen >= 90)
                        <p class="text-danger small mt-1 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ __('anggaran.overspent') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-calculator fs-1 d-block mb-2 text-muted opacity-25"></i>
                    <p class="text-muted small mb-2">{{ __('anggaran.no_budgets') }}</p>
                    <a href="{{ route('anggaran.create') }}" class="small text-primary fw-medium text-decoration-none">
                        + {{ __('anggaran.add') }}
                    </a>
                </div>
            </div>
        </div>
    @endforelse

</div>
@endsection
