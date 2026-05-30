@extends('layouts.app')

@section('title', __('dashboard.title'))
@section('page-title', __('dashboard.title'))

@push('styles')
<style>
/* ── Widget grid ── */
#widgetGrid { position: relative; }
.widget-item { position: relative; transition: opacity .2s; display: flex; flex-direction: column; }
.widget-item > .card,
.widget-item > a   { flex: 1 0 auto; }

/* ── Height classes (applied to widget-item) ── */
.widget-height-compact { max-height: 200px; overflow: hidden; }
.widget-height-compact .card { max-height: 200px; overflow: hidden; }

.widget-height-normal { min-height: 300px; }
.widget-height-normal .card { min-height: 300px; }

.widget-height-tall { min-height: 460px; }
.widget-height-tall .card { min-height: 460px; }

/* ── Edit mode overlays ── */
.widget-item .widget-overlay {
    display: none;
    position: absolute;
    inset: 0;
    z-index: 20;
    border-radius: .75rem;
    border: 2px dashed var(--primary);
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.widget-item .widget-drag-bar {
    display: none;
    position: absolute;
    top: 0; left: 0; right: 0;
    z-index: 25;
    height: 34px;
    background: var(--primary);
    border-radius: .75rem .75rem 0 0;
    align-items: center;
    padding: 0 .6rem;
    gap: .4rem;
    cursor: grab;
    color: #fff;
    font-size: .76rem;
    font-weight: 600;
    user-select: none;
    pointer-events: all;
}
.widget-item .widget-drag-bar:active { cursor: grabbing; }
.widget-item .widget-hide-btn {
    display: none;
    position: absolute;
    top: 6px; right: 8px;
    z-index: 30;
    background: rgba(239,68,68,.92);
    border: none;
    color: #fff;
    border-radius: 50%;
    width: 26px; height: 26px;
    font-size: .7rem;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    transition: background .15s;
    padding: 0;
    pointer-events: all;
}
.widget-item .widget-hide-btn:hover { background: #dc2626; }

body.dashboard-editing .widget-item .widget-overlay  { display: block; }
body.dashboard-editing .widget-item .widget-drag-bar  { display: flex; }
body.dashboard-editing .widget-item .widget-hide-btn  { display: flex; }

.widget-item.sortable-ghost  { opacity: .3; }
.widget-item.sortable-chosen { box-shadow: 0 8px 32px rgba(0,0,0,.2) !important; z-index: 100; }

/* ── Offcanvas list ── */
.widget-list-item { transition: background .12s; }
.widget-list-item:hover { background: rgba(0,0,0,.025); }
.widget-sort-handle { cursor: grab; opacity: .5; transition: opacity .15s; }
.widget-sort-handle:hover { opacity: 1; }

/* Size button groups */
.size-btn-group .btn {
    padding: 2px 7px;
    font-size: .7rem;
    line-height: 1.4;
    border-color: #dee2e6;
    color: #6c757d;
}
.size-btn-group .btn.active,
.size-btn-group .btn:focus {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: none;
}

/* ── Offcanvas toggle fix (override Dompet theme) ── */
.widget-toggle {
    background-color: #dee2e6 !important;
    border-color: #dee2e6 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    background-size: 1.2em 1.2em !important;
    background-position: left center !important;
    background-repeat: no-repeat !important;
    transition: background-color .2s, background-position .15s !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    border-radius: 2em !important;
}
.widget-toggle:checked {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    background-position: right center !important;
}

/* ── Offcanvas icon boxes ── */
.widget-icon-box {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(21,114,232,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.widget-icon-box i {
    color: #1572e8;
    font-size: .95rem;
}

/* ── Floating edit toolbar ── */
#editToolbar {
    position: fixed;
    bottom: 5.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1050;
    background: #1e293b;
    color: #fff;
    border-radius: 2rem;
    padding: .5rem 1rem;
    display: none;
    align-items: center;
    gap: .65rem;
    box-shadow: 0 8px 32px rgba(0,0,0,.35);
    font-size: .8rem;
    white-space: nowrap;
}
#editToolbar.show { display: flex; }
#editToolbar .tb-div { width: 1px; height: 18px; background: rgba(255,255,255,.2); }
@media (max-width: 575px) {
    #editToolbar {
        left: .75rem;
        right: .75rem;
        transform: none;
        border-radius: 1rem;
        flex-wrap: wrap;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .75rem;
        font-size: .75rem;
    }
    #editToolbar .tb-div { display: none; }
    #editToolbar span:first-of-type { width: 100%; text-align: center; font-size: .72rem; }
}
</style>
@endpush

@section('content')

{{-- ===== Page Header ===== --}}
<div class="d-flex justify-content-between align-items-center mt-2 mb-4">
    <div>
        <h5 class="fw-bold mb-0">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</h5>
        <p class="text-muted small mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" id="btnEditLayout"
                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
            <i class="bi bi-grid"></i>
            <span class="d-none d-sm-inline">{{ __('dashboard.edit_layout') }}</span>
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                data-bs-toggle="offcanvas" data-bs-target="#offcanvasCustomize">
            <i class="bi bi-sliders"></i>
            <span class="d-none d-sm-inline">{{ __('dashboard.customize') }}</span>
        </button>
    </div>
</div>

{{-- ===== Widget Grid ===== --}}
<div class="row g-4" id="widgetGrid">
    @foreach($widgetLayout as $widget)
        @php
            $def      = $widgetDefs[$widget['id']];
            $colClass = $widthOptions[$widget['width']] ?? $widthOptions[$def['default_width']];
            $hClass   = $widget['height'] !== 'auto' ? ' widget-height-' . $widget['height'] : '';
        @endphp
        <div class="widget-item{{ $hClass }} {{ $colClass }}{{ !$widget['visible'] ? ' d-none' : '' }}"
             data-widget-id="{{ $widget['id'] }}"
             data-visible="{{ $widget['visible'] ? '1' : '0' }}"
             data-width="{{ $widget['width'] }}"
             data-height="{{ $widget['height'] }}">

            <div class="widget-drag-bar">
                <i class="bi bi-grip-horizontal"></i>
                <span>{{ $def['label'] }}</span>
            </div>

            <button type="button" class="widget-hide-btn" title="{{ __('dashboard.hide') }}"
                    data-widget-id="{{ $widget['id'] }}">
                <i class="bi bi-eye-slash"></i>
            </button>

            <div class="widget-overlay"></div>

            @include('dashboard.widgets.' . str_replace('_', '-', $widget['id']))
        </div>
    @endforeach
</div>

{{-- ===== Floating Edit Toolbar ===== --}}
<div id="editToolbar">
    <i class="bi bi-arrows-move text-primary"></i>
    <span>{{ __('dashboard.drag_info') }}</span>
    <div class="tb-div"></div>
    <button type="button" id="btnResetInline"
            class="btn btn-sm btn-outline-light py-1 px-2" style="font-size:.76rem;">
        <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('dashboard.reset') }}
    </button>
    <button type="button" id="btnCancelEdit"
            class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:.76rem;">
        {{ __('dashboard.cancel') }}
    </button>
    <button type="button" id="btnSaveInline"
            class="btn btn-sm btn-primary py-1 px-2" style="font-size:.76rem;">
        <i class="bi bi-check2 me-1"></i>{{ __('dashboard.save') }}
    </button>
</div>

{{-- ===== Offcanvas Kustomisasi ===== --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCustomize"
     aria-labelledby="offcanvasCustomizeLabel" style="width:min(380px, 100vw);">

    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-bold" id="offcanvasCustomizeLabel">
            <i class="bi bi-sliders me-2 text-primary"></i>{{ __('dashboard.customize_dashboard') }}
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">

        {{-- Info + Reset --}}
        <div class="px-4 py-3 bg-light border-bottom">
            <p class="text-muted small mb-2 lh-sm">
                {{ __('dashboard.drag_to_reorder') }} <i class="bi bi-grip-vertical"></i> &nbsp;·&nbsp;
                {{ __('dashboard.toggle_hide') }} &nbsp;·&nbsp;
                {{ __('dashboard.adjust_size') }}
            </p>
            <button type="button" id="btnResetDefault"
                    class="btn btn-sm btn-outline-secondary w-100">
                <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('dashboard.reset_default') }}
            </button>
        </div>

        {{-- Sortable list --}}
        <div class="flex-grow-1 overflow-auto">
            <ul class="list-unstyled mb-0" id="widgetSortList">
                @foreach($widgetLayout as $widget)
                    @php $def = $widgetDefs[$widget['id']]; @endphp
                    <li class="widget-list-item border-bottom"
                        data-widget-id="{{ $widget['id'] }}"
                        data-width="{{ $widget['width'] }}"
                        data-height="{{ $widget['height'] }}">

                        {{-- Row 1: handle · icon · label · toggle --}}
                        <div class="d-flex align-items-center gap-3 px-4 pt-3 pb-1">
                            <div class="widget-sort-handle flex-shrink-0" title="Drag">
                                <i class="bi bi-grip-vertical fs-5"></i>
                            </div>
                            <div class="widget-icon-box">
                                <i class="bi {{ $def['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-semibold text-dark">{{ $def['label'] }}</div>
                                <div class="text-muted lh-sm" style="font-size:.68rem;">{{ $def['desc'] }}</div>
                            </div>
                            <div class="form-check form-switch mb-0 flex-shrink-0">
                                <input class="form-check-input widget-toggle"
                                       type="checkbox" role="switch"
                                       data-widget-id="{{ $widget['id'] }}"
                                       {{ $widget['visible'] ? 'checked' : '' }}
                                       style="cursor:pointer;width:2.2em;height:1.2em;">
                            </div>
                        </div>

                        {{-- Row 2: Lebar --}}
                        <div class="d-flex align-items-center gap-2 px-4 py-1">
                            <span class="text-muted flex-shrink-0" style="font-size:.68rem;width:42px;">{{ __('dashboard.width') }}</span>
                            <div class="btn-group size-btn-group" role="group"
                                 data-control="width" data-widget-id="{{ $widget['id'] }}">
                                @foreach(['small' => '¼', 'medium' => '½', 'large' => '¾', 'full' => 'Full'] as $key => $label)
                                    <button type="button"
                                            class="btn btn-outline-secondary width-btn{{ $widget['width'] === $key ? ' active' : '' }}"
                                            data-width="{{ $key }}"
                                            data-widget-id="{{ $widget['id'] }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Row 3: Tinggi --}}
                        <div class="d-flex align-items-center gap-2 px-4 pt-1 pb-3">
                            <span class="text-muted flex-shrink-0" style="font-size:.68rem;width:42px;">{{ __('dashboard.height') }}</span>
                            <div class="btn-group size-btn-group" role="group"
                                 data-control="height" data-widget-id="{{ $widget['id'] }}">
                                @foreach(['auto' => 'Auto', 'compact' => 'S', 'normal' => 'M', 'tall' => 'L'] as $key => $label)
                                    <button type="button"
                                            class="btn btn-outline-secondary height-btn{{ $widget['height'] === $key ? ' active' : '' }}"
                                            data-height="{{ $key }}"
                                            data-widget-id="{{ $widget['id'] }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Save --}}
        <div class="px-4 py-3 border-top">
            <button type="button" id="btnSaveLayout" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>{{ __('dashboard.save_layout') }}
            </button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {

    const widgetGrid     = document.getElementById('widgetGrid');
    const widgetSortList = document.getElementById('widgetSortList');
    const editToolbar    = document.getElementById('editToolbar');
    const defaultLayout  = @json($defaultLayout);
    const widthMap       = @json($widthOptions);

    let gridSortable  = null;
    let snapshotState = null;
    let chartTrend    = null;
    let chartKategori = null;

    // ── Width/height CSS helpers ──────────────────────────────────────────
    const HEIGHT_CLASSES = ['widget-height-compact', 'widget-height-normal', 'widget-height-tall'];

    function applyWidth(item, widthKey) {
        // Remove all col-* classes
        const remove = [...item.classList].filter(c => c.startsWith('col-'));
        item.classList.remove(...remove);
        // Add new col classes
        const cols = widthMap[widthKey] || 'col-12';
        cols.split(' ').forEach(c => item.classList.add(c));
        item.dataset.width = widthKey;
    }

    function applyHeight(item, heightKey) {
        item.classList.remove(...HEIGHT_CLASSES);
        if (heightKey !== 'auto') {
            item.classList.add('widget-height-' + heightKey);
        }
        item.dataset.height = heightKey;
        resizeChart(item.dataset.widgetId);
    }

    // ── Offcanvas: width buttons ──────────────────────────────────────────
    document.querySelectorAll('.width-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const wid      = this.dataset.widgetId;
            const widthKey = this.dataset.width;
            const item     = widgetGrid.querySelector(`[data-widget-id="${wid}"]`);

            // Update button group active state
            this.closest('.btn-group').querySelectorAll('.width-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Apply to grid item live
            if (item) applyWidth(item, widthKey);

            // Update li data attribute
            this.closest('li').dataset.width = widthKey;
        });
    });

    // ── Offcanvas: height buttons ─────────────────────────────────────────
    document.querySelectorAll('.height-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const wid       = this.dataset.widgetId;
            const heightKey = this.dataset.height;
            const item      = widgetGrid.querySelector(`[data-widget-id="${wid}"]`);

            this.closest('.btn-group').querySelectorAll('.height-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            if (item) applyHeight(item, heightKey);

            this.closest('li').dataset.height = heightKey;
        });
    });

    // ── Offcanvas: toggle visibility ──────────────────────────────────────
    document.querySelectorAll('.widget-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const wid  = this.dataset.widgetId;
            const item = widgetGrid.querySelector(`[data-widget-id="${wid}"]`);
            if (!item) return;
            item.classList.toggle('d-none', !this.checked);
            item.dataset.visible = this.checked ? '1' : '0';
            if (this.checked) resizeChart(wid);
        });
    });

    // ── Offcanvas: SortableJS list → sync grid ────────────────────────────
    Sortable.create(widgetSortList, {
        handle: '.widget-sort-handle',
        animation: 150,
        ghostClass: 'bg-primary',
        onSort: syncGridToList,
    });

    function syncGridToList() {
        widgetSortList.querySelectorAll('[data-widget-id]').forEach(function (li) {
            const item = widgetGrid.querySelector(`[data-widget-id="${li.dataset.widgetId}"]`);
            if (item) widgetGrid.appendChild(item);
        });
    }

    // ── Inline edit: hide button ──────────────────────────────────────────
    widgetGrid.addEventListener('click', function (e) {
        const btn = e.target.closest('.widget-hide-btn');
        if (!btn) return;
        const wid      = btn.dataset.widgetId;
        const item     = widgetGrid.querySelector(`[data-widget-id="${wid}"]`);
        const offToggle = widgetSortList.querySelector(`.widget-toggle[data-widget-id="${wid}"]`);
        if (item)      { item.classList.add('d-none'); item.dataset.visible = '0'; }
        if (offToggle) offToggle.checked = false;
    });

    // ── Edit Layout mode ──────────────────────────────────────────────────
    document.getElementById('btnEditLayout').addEventListener('click', function () {
        snapshotState = captureState();
        document.body.classList.add('dashboard-editing');
        editToolbar.classList.add('show');
        this.classList.add('active');

        gridSortable = Sortable.create(widgetGrid, {
            handle: '.widget-drag-bar',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            filter: '.d-none',
            onSort: syncListToGrid,
        });
    });

    function exitEditMode() {
        document.body.classList.remove('dashboard-editing');
        editToolbar.classList.remove('show');
        document.getElementById('btnEditLayout').classList.remove('active');
        if (gridSortable) { gridSortable.destroy(); gridSortable = null; }
    }

    document.getElementById('btnCancelEdit').addEventListener('click', function () {
        restoreState(snapshotState);
        exitEditMode();
    });

    document.getElementById('btnResetInline').addEventListener('click', function () {
        if (!confirm('{{ __('dashboard.reset_size_confirm') }}')) return;
        applyDefaultLayout();
    });

    document.getElementById('btnSaveInline').addEventListener('click', function () {
        saveToServer(this, exitEditMode);
    });

    // ── Offcanvas: reset & save ───────────────────────────────────────────
    document.getElementById('btnResetDefault').addEventListener('click', function () {
        if (!confirm('{{ __('dashboard.reset_confirm') }}')) return;
        applyDefaultLayout();
    });

    document.getElementById('btnSaveLayout').addEventListener('click', function () {
        saveToServer(this, function () {
            bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasCustomize'))?.hide();
        });
    });

    // ── Helpers ───────────────────────────────────────────────────────────
    function captureState() {
        return [...widgetGrid.querySelectorAll('.widget-item')].map(el => ({
            id:      el.dataset.widgetId,
            visible: el.dataset.visible === '1',
            width:   el.dataset.width,
            height:  el.dataset.height,
        }));
    }

    function restoreState(state) {
        state.forEach(function (w) {
            const item      = widgetGrid.querySelector(`[data-widget-id="${w.id}"]`);
            const offToggle = widgetSortList.querySelector(`.widget-toggle[data-widget-id="${w.id}"]`);
            const li        = widgetSortList.querySelector(`li[data-widget-id="${w.id}"]`);

            if (item) {
                widgetGrid.appendChild(item);
                item.classList.toggle('d-none', !w.visible);
                item.dataset.visible = w.visible ? '1' : '0';
                applyWidth(item, w.width);
                applyHeight(item, w.height);
            }
            if (offToggle) offToggle.checked = w.visible;
            if (li) {
                // Sync button active states
                li.querySelectorAll('.width-btn').forEach(b  => b.classList.toggle('active', b.dataset.width  === w.width));
                li.querySelectorAll('.height-btn').forEach(b => b.classList.toggle('active', b.dataset.height === w.height));
                li.dataset.width  = w.width;
                li.dataset.height = w.height;
            }
        });
        syncListToGrid();
    }

    function applyDefaultLayout() {
        defaultLayout.forEach(function (w) {
            const item      = widgetGrid.querySelector(`[data-widget-id="${w.id}"]`);
            const offToggle = widgetSortList.querySelector(`.widget-toggle[data-widget-id="${w.id}"]`);
            const li        = widgetSortList.querySelector(`li[data-widget-id="${w.id}"]`);

            if (item) {
                widgetGrid.appendChild(item);
                item.classList.toggle('d-none', !w.visible);
                item.dataset.visible = w.visible ? '1' : '0';
                applyWidth(item, w.width);
                applyHeight(item, w.height);
            }
            if (offToggle) offToggle.checked = w.visible;
            if (li) {
                li.querySelectorAll('.width-btn').forEach(b  => b.classList.toggle('active', b.dataset.width  === w.width));
                li.querySelectorAll('.height-btn').forEach(b => b.classList.toggle('active', b.dataset.height === w.height));
                li.dataset.width  = w.width;
                li.dataset.height = w.height;
                widgetSortList.appendChild(li);
            }
        });
    }

    function syncListToGrid() {
        [...widgetGrid.querySelectorAll('.widget-item')].forEach(function (item) {
            const li = widgetSortList.querySelector(`li[data-widget-id="${item.dataset.widgetId}"]`);
            if (li) widgetSortList.appendChild(li);
        });
    }

    function saveToServer(btn, onSuccess) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

        const layout = [];
        widgetSortList.querySelectorAll('li[data-widget-id]').forEach(function (li) {
            const wid    = li.dataset.widgetId;
            const toggle = li.querySelector('.widget-toggle');
            layout.push({
                id:      wid,
                visible: toggle ? toggle.checked : true,
                width:   li.dataset.width  || 'full',
                height:  li.dataset.height || 'auto',
            });
        });

        fetch('{{ route("dashboard.layout.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ layout }),
        })
        .then(function (r) {
            if (r.status === 419) {
                throw new Error('Sesi habis, silakan muat ulang halaman lalu coba lagi.');
            }
            if (r.status === 401 || r.status === 403) {
                throw new Error('Akses ditolak. Silakan login ulang.');
            }
            if (!r.ok) {
                throw new Error('Server error (' + r.status + '). Coba beberapa saat lagi.');
            }
            return r.json();
        })
        .then(function (data) {
            showToast(data.success ? data.message : (data.message || 'Gagal menyimpan layout.'), data.success ? 'success' : 'danger');
            if (data.success && typeof onSuccess === 'function') onSuccess();
        })
        .catch(function (err) {
            showToast(err.message || 'Tidak dapat terhubung ke server. Periksa koneksi Anda.', 'danger');
        })
        .finally(function () { btn.disabled = false; btn.innerHTML = orig; });
    }

    function resizeChart(widgetId) {
        setTimeout(function () {
            if (widgetId === 'chart_trend'    && chartTrend)    chartTrend.resize();
            if (widgetId === 'chart_kategori' && chartKategori) chartKategori.resize();
        }, 80);
    }

    function showToast(msg, type) {
        const el = document.createElement('div');
        el.className = `toast align-items-center text-white bg-${type} border-0 show`;
        el.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:11000;min-width:280px;';
        el.setAttribute('role', 'alert');
        el.innerHTML = `<div class="d-flex"><div class="toast-body fw-medium">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    onclick="this.closest('.toast').remove()"></button></div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    // ── Chart.js ─────────────────────────────────────────────────────────
    @if(!empty($summary['chart_data']['labels']))
    chartTrend = new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: {
            labels: {!! json_encode($summary['chart_data']['labels']) !!},
            datasets: [
                { label: '{{ __('dashboard.income') }}',   data: {!! json_encode($summary['chart_data']['pemasukan']) !!},   borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.08)', tension: 0.4, fill: true, pointRadius: 4 },
                { label: '{{ __('dashboard.expense') }}', data: {!! json_encode($summary['chart_data']['pengeluaran']) !!}, borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.08)',   tension: 0.4, fill: true, pointRadius: 4 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } },
        },
    });
    @endif

    @if(!empty($pengeluaranPerKategori['labels']))
    chartKategori = new Chart(document.getElementById('chartKategori'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($pengeluaranPerKategori['labels']) !!},
            datasets: [{ data: {!! json_encode($pengeluaranPerKategori['values']) !!}, backgroundColor: ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#84CC16'], borderWidth: 2 }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                tooltip: { callbacks: { label: ctx => ' Rp ' + ctx.parsed.toLocaleString('id-ID') } },
            },
        },
    });
    @endif

})();
</script>
@endpush
