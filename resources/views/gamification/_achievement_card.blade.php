@php
    $rarityBorderColor = [
        'bronze'   => '#cd7f32',
        'platinum' => '#8b5cf6',
        'gold'     => '#f59e0b',
        'silver'   => '#9e9e9e',
    ];

    $rarityIcon = [
        'bronze'   => 'bi-circle-fill',
        'silver'   => 'bi-circle-fill',
        'gold'     => 'bi-star-fill',
        'platinum' => 'bi-gem',
    ];

    $rarity      = $achievement->rarity ?? 'silver';
    $borderColor = $rarityBorderColor[$rarity];
    $icon        = $rarityIcon[$rarity];
    $isHiddenLocked = !$isEarned && $achievement->is_hidden;
@endphp

<div class="achievement-card border rounded p-3 text-center h-100 position-relative
            {{ $isEarned ? 'achievement-' . $rarity : 'achievement-locked' }}"
     style="{{ $isEarned ? 'border-color:' . $borderColor . ' !important;' : '' }}
            {{ ($isEarned && in_array($rarity, ['gold', 'platinum'])) ? 'box-shadow: 0 0 0 1px ' . $borderColor . '33, 0 4px 12px ' . $borderColor . '1a;' : '' }}">

    {{-- Rarity badge (top-right, earned only) --}}
    @if($isEarned)
    <span class="position-absolute top-0 end-0 m-2 badge"
          style="background:{{ $borderColor }}; font-size:.6rem; padding:2px 5px;">
        <i class="bi {{ $icon }}" style="font-size:.55rem;"></i>
        {{ ucfirst($rarity) }}
    </span>
    @endif

    @if($isHiddenLocked)
        {{-- Hidden locked: mystery slot --}}
        <div class="fw-semibold small mb-1 text-muted">???</div>
        <div class="text-muted" style="font-size:.72rem;">Hidden Achievement</div>
        <div class="mt-2">
            <i class="bi bi-lock-fill text-muted" style="font-size:.9rem;"></i>
        </div>
    @elseif(!$isEarned)
        {{-- Visible locked --}}
        <div class="fw-semibold small mb-1">{{ $achievement->name }}</div>
        <div class="text-muted" style="font-size:.72rem;">{{ $achievement->description }}</div>
        <div class="mt-2">
            <i class="bi bi-lock text-muted" style="font-size:.85rem;"></i>
        </div>
    @else
        {{-- Earned --}}
        <div class="fw-semibold small mb-1">{{ $achievement->name }}</div>
        <div class="text-muted" style="font-size:.72rem; line-height:1.35;">{{ $achievement->description }}</div>
        <div class="mt-2">
            <span class="badge bg-light text-dark" style="font-size:.65rem;">+{{ $achievement->xp_reward }} XP</span>
        </div>
        @if($earnedAt)
        <div class="text-muted mt-1" style="font-size:.65rem;">{{ $earnedAt->format('d M Y') }}</div>
        @endif
    @endif
</div>

<style>
.achievement-locked {
    filter: grayscale(1);
    opacity: .45;
    transition: opacity 250ms ease, filter 250ms ease;
}
.achievement-locked:hover {
    opacity: .6;
}
.achievement-card {
    transition: transform 250ms cubic-bezier(0.4,0,0.2,1),
                box-shadow 250ms cubic-bezier(0.4,0,0.2,1);
}
.achievement-card:hover:not(.achievement-locked) {
    transform: translateY(-2px);
}
</style>
