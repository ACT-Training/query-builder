
<div class="flex items-center">
    <label for="simple-search" class="sr-only">Search</label>
    <div class="relative w-full">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <x-tabler-search class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
        </div>
        <input wire:model.debounce.500ms="searchBy" type="text" id="simple-search" class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 shadow" placeholder="Search..." >
    </div>
</div>
