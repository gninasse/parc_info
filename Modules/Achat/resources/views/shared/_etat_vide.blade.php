@props(['message', 'icone' => 'fa-info-circle', 'colspan' => null])

{{-- ENF-ERG-08 : une liste vide s'explique, elle ne se contente pas d'être vide. --}}
@if($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="text-center text-muted py-4">
            <i class="fas {{ $icone }} me-1"></i> {{ $message }}
        </td>
    </tr>
@else
    <div class="text-center text-muted py-5">
        <i class="fas {{ $icone }} fa-2x mb-3 d-block opacity-50"></i>
        <span>{{ $message }}</span>
    </div>
@endif
