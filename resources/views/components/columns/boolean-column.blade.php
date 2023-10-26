@props([
    'value',
    'column',
    'row'
])

<div class="flex items-center {{ $column->justify }} w-full">
    @if ($value)
        @if (! $column->hideTrue())
            <x-tabler-circle-check-filled class="w-5 h-auto text-green-500"/>
        @endif
    @else
        @if (! $column->hideFalse())
            <x-tabler-circle-x-filled class="w-5 h-auto text-red-500"/>
        @endif
    @endif
</div>
