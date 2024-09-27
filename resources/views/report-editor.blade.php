<div x-data="{open: true }" x-cloak
     class="m-4 block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">

    @if(!$selectedColumns)
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
            Please select one or more columns.
        </div>
    @endif

    <div x-show="open">
        @foreach($this->availableColumns() as $section => $columns)
            <h6 class="mb-4 pb-1 text-base font-bold text-gray-600 dark:text-white border-b border-dashed">{{ $section }}</h6>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach($columns as $columnKey => $column)
                    <div class="flex items-center mb-4">
                        <input wire:model.live.debounce="selectedColumns" id="{{$column['key']}}" type="checkbox"
                               value="{{ $column['key'] }}"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="{{$column['key']}}"
                               class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{$column['label']}}</label>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="flex items-center gap-2 justify-between p-1 px-2 bg-gray-50 -mx-6 -mb-6 rounded-b-lg">
        <button @click="open = !open" class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Columns</span>
            <span x-show="!open">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-6 h-6"
                     viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none"
                     stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M6 9l6 6l6 -6"/>
                </svg>
            </span>
            <span x-show="open">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="#2c3e50" fill="none"
                     stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M6 15l6 -6l6 6"/>
            </svg>
            </span>
        </button>
        <div class="flex items-center gap-2">
            <button wire:click="saveReportBuilder">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600 hover:text-blue-500" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                    <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M14 4l0 4l-6 0l0 -4" />
                </svg>
            </button>
        </div>
    </div>
</div>



