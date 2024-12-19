<div wire:key="filter-{{ $filter->code() }}">
    <label for="filter-{{ $filter->code() }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $filter->label() }}</label>
    <input  type="date"
            wire:model.live.debounce.500ms="filterValues.{{ $filter->code() }}"
            wire:key="filter-{{ $filter->code() }}"
            id="filter-{{ $filter->code() }}"
            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
    />
</div>

