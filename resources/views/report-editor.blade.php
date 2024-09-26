<div class="m-4">
    @foreach($this->availableColumns() as $section => $columns)
        <h5 class="mb-2 text-xl font-bold dark:text-white">{{ $section }}</h5>
    <div class="grid grid-cols-1 md:grid-cols-6">
        @foreach($columns as $columnKey => $column)
            <div class="flex items-center mb-4">
                <input id="{{$column['key']}}" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="{{$column['key']}}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{$column['label']}}</label>
            </div>
        @endforeach
    </div>
    @endforeach
</div>


