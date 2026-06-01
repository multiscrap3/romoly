@extends('layouts.app')

@section('title', __('tags.title'))
@section('page-title', __('tags.title'))

@section('content')
<div class="row g-4 justify-content-center">
<div class="col-12 col-lg-8">

    {{-- Form tambah --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-3">{{ __('tags.add') }}</h6>
            <form method="POST" action="{{ route('tags.store') }}" class="d-flex align-items-end gap-2" data-tour="tags-add">
                @csrf
                <div class="flex-grow-1">
                    <input type="text" name="nama" value="{{ old('nama') }}" required placeholder="{{ __('tags.name_ph') }}"
                           maxlength="50"
                           class="form-control form-control-sm @error('nama') is-invalid @enderror">
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">Warna</label>
                    <input type="color" name="warna" value="{{ old('warna', '#6B7280') }}"
                           class="form-control form-control-color form-control-sm" style="width:40px;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('tags.save') }}</button>
            </form>
            @error('nama')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- Daftar tags --}}
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;">
        <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:.75rem .75rem 0 0;">
            <h6 class="fw-semibold mb-0">Tags ({{ $tags->count() }})</h6>
        </div>
        <div class="card-body p-0">
            @forelse($tags as $tag)
                <div class="border-bottom" id="tag-row-{{ $tag->id }}">
                    {{-- Display mode --}}
                    <div class="d-flex align-items-center gap-3 px-4 py-3" id="tag-display-{{ $tag->id }}">
                        <span class="rounded-circle flex-shrink-0"
                              style="width:14px;height:14px;background:{{ $tag->warna }};display:inline-block;"></span>
                        <div class="flex-grow-1 small fw-medium text-dark">{{ $tag->nama }}</div>
                        <span class="text-muted" style="font-size:.72rem;">{{ $tag->transaksi_count }} transaksi</span>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="button" class="small text-secondary text-decoration-none btn btn-link p-0"
                                    style="font-size:.78rem;"
                                    onclick="toggleTagEdit({{ $tag->id }})">{{ __('messages.edit') }}</button>
                            <form method="POST" action="{{ route('tags.destroy', $tag) }}"
                                  onsubmit="return confirm('{{ __('tags.delete_confirm') }}')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" style="font-size:.78rem;">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                    {{-- Edit mode (hidden) --}}
                    <div class="d-none px-4 py-3" id="tag-edit-{{ $tag->id }}">
                        <form method="POST" action="{{ route('tags.update', $tag) }}"
                              class="d-flex align-items-center gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="nama" value="{{ $tag->nama }}" required maxlength="50"
                                   class="form-control form-control-sm flex-grow-1">
                            <input type="color" name="warna" value="{{ $tag->warna }}"
                                   class="form-control form-control-color form-control-sm flex-shrink-0" style="width:36px;">
                            <button type="submit" class="btn btn-link btn-sm text-primary p-0">{{ __('tags.save') }}</button>
                            <button type="button" class="btn btn-link btn-sm text-muted p-0"
                                    onclick="toggleTagEdit({{ $tag->id }})">{{ __('tags.cancel') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="py-5 text-center">
                    <p class="text-muted small mb-1">{{ __('tags.no_tags') }}</p>
                    <p class="text-muted" style="font-size:.72rem;">{{ __('tags.no_tags') }}</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
function toggleTagEdit(id) {
    document.getElementById('tag-display-' + id).classList.toggle('d-none');
    document.getElementById('tag-edit-' + id).classList.toggle('d-none');
}
</script>
@endpush
