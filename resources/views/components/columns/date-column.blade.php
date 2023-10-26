@php use Carbon\Carbon; @endphp
@props([
    'value',
    'column',
    'row'
])

<div class="flex items-center {{ $column->justify }} w-full">
    @if($value !== null)
        @if ($column->showHumanDiff())
            {{ Carbon::make($value)->diffForHumans() }}
        @else
            {{ Carbon::make($value)->format($column->dateFormat()) }}
        @endif
    @endif
</div>
