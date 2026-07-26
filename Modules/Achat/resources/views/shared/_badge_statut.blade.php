@props(['statut', 'type' => 'bc'])

@php
    $config = config("achat.statuts_{$type}.{$statut}", []);
    $label  = $config['label'] ?? $statut;
    $color  = $config['color'] ?? 'secondary';
    $icon   = $config['icon']  ?? null;
@endphp

<span class="badge bg-{{ $color }}{{ in_array($color, ['warning']) ? ' text-dark' : '' }}">
    @if($icon)<i class="fas {{ $icon }} me-1"></i>@endif{{ $label }}
</span>
