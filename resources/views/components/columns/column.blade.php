@props([
    'value',
    'column',
    'row',
])

<div class="flex items-center {{ $column->justify }} w-full">
    {{ $value }}
</div>
