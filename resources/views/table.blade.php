<div class="overflow-y-visible">
    <div>
        @includeIf($this->headerView())
    </div>

    <div class="my-6">

        @if($this->isSearchVisible() && $this->searchableColumnsSet())
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

        <div>
            @if($this->rows->count())
                <div class="relative overflow-x-auto overflow-y-visible">
                    <table class="w-full text-sm text-left text-gray-500" wire:key="table-1">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr class="border-y border-gray-200">

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
                        <tbody @if($this->useLoadingIndicator()) wire:loading.class="{{ $this->loadingClass }}" @endif>

                        @foreach ($this->rows as $row)

                            @if($this->rowPreview($row))
                                {!! $this->injectRow($row) !!}
                            @endif

                            <tr @if($this->isClickable())
                                    {!! $this->renderRowClick($row->id)  !!}
                                @endif

                                wire:key="row-{{ $row->id }}"
                                    @class([
                                        'bg-white border-b group',
                                        'hover:bg-gray-50 cursor-pointer' => $this->isClickable(),
                                    ])>

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
                                <div class="py-2 px-6">{{ $this->rows->links() }}</div>
                            </div>
                        @endif
                    </div>

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

