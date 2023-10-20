<div>
    <label for="filter-{{ $filter->code() }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $filter->label() }}</label>
    <select wire:model="filterValues.{{ $filter->code() }}" id="filter-{{ $filter->code() }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
        <option value="">Choose an option</option>
        <option value="1">Yes</option>
        <option value="0">No</option>
    </select>
</div>