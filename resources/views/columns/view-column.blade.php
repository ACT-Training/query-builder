@props([
    'value',
    'column',
    'row'
])

<div class="flex items-center {{ $column->justify }} w-full">
    <span>A view column should have a view set.</span>
</div>
