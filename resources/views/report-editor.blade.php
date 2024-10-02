<div x-data="{open: true }" x-cloak
     class="m-4 block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">

    @if(!$selectedColumns)
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
            <span class="font-bold">Report Builder</span> Please select one or more columns.
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

    <div wire:ignore class="flex items-center gap-2 justify-between p-1 px-2 bg-gray-50 -mx-6 -mb-6 rounded-b-lg border-t border-gray-200">
        <button @click="open = !open" class="p-2 flex items-center gap-2 text-gray-600 hover:bg-gray-100 rounded">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 6l5.5 0" />
                <path d="M4 10l5.5 0" />
                <path d="M4 14l5.5 0" />
                <path d="M4 18l5.5 0" />
                <path d="M14.5 6l5.5 0" />
                <path d="M14.5 10l5.5 0" />
                <path d="M14.5 14l5.5 0" />
                <path d="M14.5 18l5.5 0" />
            </svg>
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
        <div class="flex items-center">
            <button
                    x-data
                    x-tooltip.raw="Reset"
                    wire:key="reset-button"
                    wire:click="resetReportBuilder" class="p-2 text-gray-600 hover:bg-gray-100 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M15 4.55a8 8 0 0 0 -6 14.9m0 -4.45v5h-5" />
                    <path d="M18.37 7.16l0 .01" />
                    <path d="M13 19.94l0 .01" />
                    <path d="M16.84 18.37l0 .01" />
                    <path d="M19.37 15.1l0 .01" />
                    <path d="M19.94 11l0 .01" />
                </svg>
            </button>

            <button
                    x-data
                    x-tooltip.raw="Export"
                    wire:key="export-button"
                    wire:click="exportReportBuilder"
            >
                <x-tabler-table-export class="p-2 text-gray-600 hover:bg-gray-100 rounded"/>
            </button>

            <button
                    x-data
                    x-tooltip.raw="Save"
                    wire:key="save-button"
                    wire:click="saveReportBuilder" class="p-2 text-gray-600 hover:bg-gray-100 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                    <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M14 4l0 4l-6 0l0 -4" />
                </svg>
            </button>

            <x-popover wire:key="load-button">
                <x-slot name="trigger">
                    <div
                            x-data
                            x-tooltip.raw="Reports"
                            class="mt-2 p-2 text-gray-600 hover:bg-gray-100 rounded"
                    >
                        <x-tabler-file-download/>
                    </div>
                </x-slot>

                <x-slot name="panel">
                    @foreach($this->reports as $report)
                        <x-popover.menu-item wire:click="loadReportBuilder('{{ $report->uuid }}')">
                            {{ $report->name }}
                        </x-popover.menu-item>
                    @endforeach
                </x-slot>
            </x-popover>

        </div>
    </div>
</div>



