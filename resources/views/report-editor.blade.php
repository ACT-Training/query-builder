@php
    // 'sections' (default), 'tabs' or 'accordion'. A single section needs neither tabs nor an accordion.
    $sectionCount = count($this->availableColumns()) + ($this->enableGroupBy ? 1 : 0);
    $layout = in_array($this->columnLayout, ['tabs', 'accordion'], true) && $sectionCount > 1
        ? $this->columnLayout
        : 'sections';
    $tabClasses = "tab === %s ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'";
    $badgeClasses = 'ms-1 px-1.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
    $accordionClasses = 'mb-4 pb-1 w-full flex items-center justify-between text-base font-bold text-gray-600 dark:text-white border-b border-dashed';
@endphp
<div x-data="{open: true, search: '', tab: 0, expanded: [0], toggle(section) { this.expanded = this.expanded.includes(section) ? this.expanded.filter(s => s !== section) : [...this.expanded, section] } }" x-cloak
     class="m-4 block p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">

    @if(!$selectedColumns)
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
            <span class="font-bold">Report Builder</span> Please select one or more columns.
        </div>
    @endif

    <div x-show="open">
        @if($this->enableColumnSearch)
            <div class="mb-4">
                <input type="text" x-model="search" placeholder="Search columns..."
                       class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            </div>
        @endif

        @if($layout === 'tabs')
            {{-- Tabs give way to a flat list of matches from every group while searching. --}}
            <div x-show="search === ''" role="tablist" class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 dark:border-gray-700">
                @foreach($this->availableColumns() as $section => $columns)
                    @php
                        $selectedInSection = count(array_intersect(array_column($columns, 'key'), $selectedColumns ?? []));
                    @endphp
                    <button type="button" role="tab" wire:key="report-tab-{{ $loop->index }}"
                            @click="tab = {{ $loop->index }}"
                            :aria-selected="tab === {{ $loop->index }}"
                            :class="{{ sprintf($tabClasses, $loop->index) }}"
                            class="-mb-px px-4 py-2 text-sm font-medium border-b-2">
                        {{ $section }}
                        @if($selectedInSection)
                            <span class="{{ $badgeClasses }}">{{ $selectedInSection }}</span>
                        @endif
                    </button>
                @endforeach

                @if($this->enableGroupBy)
                    <button type="button" role="tab" wire:key="report-tab-group-by"
                            @click="tab = 'group-by'"
                            :aria-selected="tab === 'group-by'"
                            :class="{{ sprintf($tabClasses, "'group-by'") }}"
                            class="-mb-px px-4 py-2 text-sm font-medium border-b-2">
                        Group By
                    </button>
                @endif
            </div>
        @endif

        @foreach($this->availableColumns() as $section => $columns)
            @php
                $selectedInSection = count(array_intersect(array_column($columns, 'key'), $selectedColumns ?? []));
                $matchesSearch = '['.collect($columns)->map(fn ($column) => "'".e($column['label'])."'")->implode(',').'].some(label => label.toLowerCase().includes(search.toLowerCase()))';
                $index = $loop->index;
            @endphp
            <div @if($layout === 'tabs') role="tabpanel" @endif
                 x-show="{!! match ($layout) {
                     'tabs' => "(search === '' && tab === {$index}) || (search !== '' && {$matchesSearch})",
                     'accordion' => "search === '' || {$matchesSearch}",
                     default => $matchesSearch,
                 } !!}">
                @if($layout === 'accordion')
                    {{-- Every panel with a match opens while searching. --}}
                    <button type="button" @click="toggle({{ $index }})"
                            :aria-expanded="search !== '' || expanded.includes({{ $index }})"
                            class="{{ $accordionClasses }}">
                        <span>
                            {{ $section }}
                            @if($selectedInSection)
                                <span class="{{ $badgeClasses }}">{{ $selectedInSection }}</span>
                            @endif
                        </span>
                        <x-tabler-chevron-down class="w-5 h-5 transition-transform" ::class="(search !== '' || expanded.includes({{ $index }})) && 'rotate-180'"/>
                    </button>
                @else
                    <h6 @if($layout === 'tabs') x-show="search !== ''" @endif class="mb-4 pb-1 text-base font-bold text-gray-600 dark:text-white border-b border-dashed">{{ $section }}</h6>
                @endif
                <div @if($layout === 'accordion') x-show="search !== '' || expanded.includes({{ $index }})" @endif
                     class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    @foreach($columns as $columnKey => $column)
                        <div class="flex items-center mb-4" x-show="'{{ e($column['label']) }}'.toLowerCase().includes(search.toLowerCase())">
                            <input wire:model.live.debounce.1000ms="selectedColumns" id="{{$column['key']}}" type="checkbox"
                                   value="{{ $column['key'] }}"
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="{{$column['key']}}"
                                   class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{$column['label']}}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($this->enableGroupBy)
            <div @if($layout === 'tabs') role="tabpanel" x-show="search === '' && tab === 'group-by'" @elseif($layout === 'accordion') x-show="search === ''" @endif>
                @if($layout === 'accordion')
                    <button type="button" @click="toggle('group-by')"
                            :aria-expanded="expanded.includes('group-by')"
                            class="{{ $accordionClasses }}">
                        <span>Group By</span>
                        <x-tabler-chevron-down class="w-5 h-5 transition-transform" ::class="expanded.includes('group-by') && 'rotate-180'"/>
                    </button>
                @elseif($layout === 'sections')
                    <h6 class="mb-4 pb-1 text-base font-bold text-gray-600 dark:text-white border-b border-dashed">Group By</h6>
                @endif
                <div @if($layout === 'accordion') x-show="expanded.includes('group-by')" @endif
                     class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="groupBy" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Group By Column</label>
                        <select wire:model.live="groupBy" id="groupBy"
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">None</option>
                            @foreach($this->groupableColumns() as $column)
                                <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->groupBy)
                        <div>
                            <label for="aggregateFunction" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Function</label>
                            <select wire:model.live="aggregateFunction" id="aggregateFunction"
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($this->aggregateFunctions() as $fn)
                                    <option value="{{ $fn }}">{{ $fn }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="aggregateColumn" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Aggregate Column</label>
                            <select wire:model.live="aggregateColumn" id="aggregateColumn"
                                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($this->aggregatableColumns() as $column)
                                    <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div wire:ignore class="flex items-center gap-2 justify-between p-1 px-2 bg-gray-50 -mx-6 -mb-6 rounded-b-lg border-t border-gray-200">
        <button type="button" @click="open = !open" class="p-2 flex items-center gap-2 text-gray-600 hover:bg-gray-100 rounded">
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
                    type="button"
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
                    type="button"
                    x-data
                    x-tooltip.raw="Export"
                    wire:key="export-button"
                    wire:click="exportReportBuilder"
                    class="p-2 text-gray-600 hover:bg-gray-100 rounded"
            >
                <x-tabler-table-export class="w-6 h-6"/>
            </button>

            <button
                    type="button"
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

            <button
                    type="button"
                    x-data
                    x-tooltip.raw="Load"
                    wire:key="load-button"
                    wire:click="loadReportBuilder" class="p-2 text-gray-600 hover:bg-gray-100 rounded">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                    <path d="M12 17v-6" />
                    <path d="M9.5 14.5l2.5 2.5l2.5 -2.5" />
                </svg>
            </button>

        </div>
    </div>
</div>



