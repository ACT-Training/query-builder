<div class="my-6">
    @if ($enableQueryBuilder)
        <div class="m-4">
            @include('query-builder::editor')

            @if(! $this->data()->count())
                <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400"
                     role="alert">
                    <span class="font-bold">No records found have been found.</span> Try changing your query settings.
                </div>
            @endif
        </div>
    @endif

    @if($this->isToolbarVisible())
        <div class="p-4 flex items-center gap-2 justify-end bg-gray-50">

            @if($this->isColumnSelectorVisible())
                @include('query-builder::components.columns-selector')
            @endif

            @if($this->isRowSelectorVisible())
                @include('query-builder::components.rows-selector')
            @endif

        </div>
    @endif

    @if($this->data()->count())

        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr class="border-y border-gray-200">

                    @foreach ($this->columns() as $column)
                        @if(in_array($column->key, $displayColumns))
                            <th @if ($column->isSortable()) wire:click="sort('{{ $column->key }}')" @endif>
                                @if ($column->showHeader)
                                    <div @class([
                            'flex items-center gap-1 bg-gray-50 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 ' . $column->justify,
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
                                    </div>
                                @endif
                            </th>
                        @endif
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($this->data() as $row)
                    <tr @if($this->isClickable()) wire:click="rowClick('{{ $row->id }}')" @endif
                            @class([
                                'bg-white border-b',
                                'hover:bg-gray-50 cursor-pointer' => $this->isClickable(),
                            ])>
                        @foreach ($this->columns() as $column)
                            @if(in_array($column->key, $displayColumns))
                            <td>
                                <div class="py-3 px-6 flex items-center">
                                    <x-dynamic-component
                                            :component="$column->component"
                                            :value="$column->getValue($row)"
                                            :column="$column"
                                            :row="$row"
                                    >
                                    </x-dynamic-component>
                                </div>
                            </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($this->data()->hasPages())
            <div class="border-b border-gray-200 shadow-sm">
                <div class="py-2 px-6">{{ $this->data()->links() }}</div>
            </div>
        @endif
    @endif

</div>
