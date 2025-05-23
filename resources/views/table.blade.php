<div class="overflow-y-visible">
    <div>
        @includeIf($this->headerView())
    </div>

    <div class="my-6">

        @if($this->isSearchVisible() && $this->searchableColumnsSet() && ! $this->areActionsVisible())
            @if($this->isFiltered() || $this->isSearchActive() || $this->rows->count() > 0)
                <div class="p-4 flex items-center gap-2 w-full">
                    @include('query-builder::components.search')
                </div>
            @endif
        @endif

        <div>
            @if ($this->areFiltersAvailable())
                @if($this->isFiltered() || $this->rows->count() > 0)
                    @include('query-builder::filters')
                @endif
            @endif
        </div>

            @if($this->areActionsVisible())
                <div class="p-4 flex items-center gap-2 justify-between bg-gray-50">

                    <div class="p-4 flex items-center gap-2">
                        @if($this->isSearchVisible())
                            @include('query-builder::components.search')
                        @endif
                    </div>

                    <div class="p-4 flex items-center gap-2">

                        @if($this->areActionsVisible())
                            @include('query-builder::components.actions')
                        @endif

                    </div>
                </div>
            @endif


        <div id="{{ $this->identifier() }}">
            @if($this->rows->count())
                <div class="relative overflow-x-auto overflow-y-auto">
                    <table class="w-full text-sm text-left text-gray-500" wire:key="{{ $this->identifier() }}">
                        <thead class="text-xs text-gray-700 bg-gray-50">
                        <tr class="border-y border-gray-200">

                            @if($this->areActionsVisible() && $selectable)
                                <th class="p-0">
                                    <div class="pl-6 flex items-center">
                                        <input wire:model.live="selectPage" id="checkbox-select-page" type="checkbox"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <label for="checkbox-select-page"
                                               class="sr-only ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"></label>
                                    </div>
                                </th>
                            @endif

                            @foreach ($this->columns() as $column)
                                @if(!$column->hidden())
                                    <th @if ($column->isSortable()) wire:click="sort('{{ $column->key }}')" @endif>
                                        @if ($column->showHeader)
                                            <div @class([
                                'flex items-center gap-1 bg-gray-50 px-6 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 ' . $column->justify,
                                'cursor-pointer' => $column->isSortable(),
                            ])>

                                                {{ $column->label }}

                                                @if ($sortBy === $column->key)
                                                    @if ($sortDirection === 'asc')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                             viewBox="0 0 20 20"
                                                             fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                  d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                                                  clip-rule="evenodd"/>
                                                        </svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                             viewBox="0 0 20 20"
                                                             fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                  d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                                                  clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                @endif

                                                @if($this->isSearchableIconVisible() && $column->isSearchable())
                                                    <x-tabler-search class="w-4 h-4 text-gray-300"/>
                                                @endif

                                            </div>
                                        @endif
                                    </th>
                                @endif
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>

                        @if($this->areActionsVisible() && $selectable && $selectPage && $this->rows->count() < $this->rows->total())
                            <tr class="bg-gray-100" wire:key="{{ $this->identifier() }}-row-message">
                                <td colspan="{{ count($displayColumns) + 1 }}" class="px-6 py-4">
                                    @unless($selectAll)
                                        <div>
                                    <span>You have selected <span
                                                class="font-bold">{{ count($selectedRows) }} {{ Str::of('row')->plural(count($selectedRows))  }}</span>. Do you want to select all {{ $this->rows->total() }}?</span>
                                            <button wire:click="selectAll"
                                                    class="ml-2 text-blue-500 hover:text-blue-600">
                                                Select all
                                            </button>
                                        </div>
                                    @else
                                        <span>You have selected all {{ $this->rows->total() }} {{ Str::of('row')->plural(count($selectedRows))  }}.</span>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        @foreach ($this->rows as $row)

                            @if($this->rowPreview($row))
                                {!! $this->injectRow($row) !!}
                            @endif

                            <tr @if($this->isClickable())
                                    {!! $this->renderRowClick($row->id)  !!}
                                @endif

                                wire:key="{{ $this->identifier() }}-row-{{ $row->id }}"
                                    @class([
                                        'bg-white border-b group',
                                        'hover:bg-gray-50 cursor-pointer' => $this->isClickable(),
                                    ])>

                                @if($this->areActionsVisible() && $selectable)
                                    <td class="p-0">
                                        <div class="pl-6 flex items-center">
                                            <input wire:model.live="selectedRows" id="checkbox-{{ $row->id }}"
                                                   type="checkbox"
                                                   value="{{ $row->id }}"
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="checkbox-{{ $row->id }}"
                                                   class="sr-only ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"></label>
                                        </div>
                                    </td>
                                @endif

                                @foreach ($this->columns() as $column)
                                    @if(!$column->hidden())
                                        <td>
                                            <div class="py-3 px-6 flex items-center">
                                                <x-dynamic-component
                                                        :component="$column->component"
                                                        :value="$column->getValue($row)"
                                                        :column="$column"
                                                        :row="$row"
                                                />
                                            </div>
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div>
                        @if($this->isPaginated() && $this->rows->hasPages())
                            <div class="border-b border-gray-200 shadow-sm">
                                @if($this->scroll() === true)
                                    <div class="py-2 px-6">{{ $this->rows->links() }}</div>
                                @else
                                    <div class="py-2 px-6">{{ $this->rows->links(data: ['scrollTo' => $this->scroll()]) }}</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($this->useLoadingIndicator())
                        {{-- Table loading spinners... --}}
                        @if($this->showOverlay)
                            <div
                                    wire:loading
                                    class="absolute inset-0 bg-white {{ $this->loadingClass() }}"
                            >
                                {{--  --}}
                            </div>
                        @endif

                        <div
                                wire:loading.flex
                                class="flex justify-center items-center absolute inset-0"
                        >
                            <svg class="animate-spin h-10 w-10 {{ $this->spinnerColor() }}"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    @endif

                </div>

            @else
                <div>
                    @if($this->messageEnabled())
                        @includeIf($this->emptyView())
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div>

        @includeIf($this->footerView())

    </div>


</div>

