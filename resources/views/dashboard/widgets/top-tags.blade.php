<div class="card border-0 shadow-sm h-100" style="border-radius:.75rem;">
    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center py-3 px-4">
        <h6 class="fw-semibold mb-0"><i class="bi bi-tags me-2 text-primary"></i>Tag Bulan Ini</h6>
        <a href="{{ route('tags.index') }}" class="small text-primary text-decoration-none">Kelola →</a>
    </div>
    <div class="card-body p-0">
        @forelse($topTags as $item)
            <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <span class="rounded-circle flex-shrink-0"
                          style="width:10px;height:10px;background:{{ $item['tag']->warna }};display:inline-block;"></span>
                    <a href="{{ route('laporan.tag', $item['tag']) }}"
                       class="small fw-medium text-dark text-decoration-none text-truncate">{{ $item['tag']->nama }}</a>
                </div>
                <span class="small text-danger fw-semibold flex-shrink-0 ms-2">
                    Rp {{ number_format($item['pengeluaran'], 0, ',', '.') }}
                </span>
            </div>
        @empty
            <div class="text-center py-4 px-3">
                <i class="bi bi-tags fs-2 d-block mb-2 text-muted opacity-25"></i>
                <p class="text-muted small mb-1">Belum ada tag dipakai bulan ini.</p>
                <a href="{{ route('tags.index') }}" class="small text-primary text-decoration-none">Buat tag →</a>
            </div>
        @endforelse
    </div>
</div>
