<div x-data="{show: false}" wire:key="filters-panel">

    <button x-show="!show" @click="show = true" class="p-2 flex items-center gap-1 text-gray-500 hover:text-blue-500 text-sm">
        <x-tabler-filter class="w-4 h-auto"/>
        Show filters
    </button>

    <div x-show="show" class="m-2 mb-4 bg-white rounded shadow" x-transition>
        <form class="p-4 px-6 grid grid-cols-3 gap-6 text-sm" autocomplete="off">
            @foreach($this->filters() as $filter)
                @include('query-builder::filters.' . $filter->component())
            @endforeach
        </form>
        <div class="p-2 flex items-center justify-between gap-2 bg-gray-50 text-sm rounded-b border-t border-t-gray-200">

            <button @click="show = false" class="flex items-center gap-2 text-gray-500 hover:text-blue-500 text-sm">
                <x-tabler-filter-x class="w-4 h-auto"/> Hide filters
            </button>

            <button wire:click="resetFilters" class="flex items-center gap-2 text-gray-500 hover:text-red-500">
                <x-tabler-trash class="w-4 h-auto"/>
                Reset
            </button>
        </div>
    </div>

</div>

