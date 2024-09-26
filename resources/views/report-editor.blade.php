<div class="m-6 block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
    @foreach($this->availableColumns() as $section => $columns)
        <h6 class="mb-2 text-lg font-bold text-gray-800 dark:text-white">{{ $section }}</h6>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        {{ json_encode($selectedColumns) }}
        @foreach($columns as $columnKey => $column)
            <div class="flex items-center mb-4">
                <input wire:model="selectedColumns" id="{{$column['key']}}" type="checkbox" value="{{ $columnKey }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="{{$column['key']}}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{$column['label']}}</label>
            </div>
        @endforeach
    </div>
    @endforeach
</div>


