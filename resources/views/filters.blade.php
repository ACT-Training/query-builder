<div class="p-4 px-6 grid grid-cols-3 gap-6 text-sm">
    @foreach($this->filters() as $filter)
        @include('query-builder::filters.' . $filter->component())
    @endforeach
</div>
<div class="p-2 flex items-center justify-end gap-2 bg-gray-50 text-sm rounded-b border-t border-t-gray-200">
    <button wire:click="resetFilters" class="flex items-center gap-2 text-gray-500 hover:text-red-500">
        <x-tabler-trash class="w-4 h-auto" />
        Reset
    </button>
</div>