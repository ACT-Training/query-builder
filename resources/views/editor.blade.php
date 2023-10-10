<div class="mb-4">

    @if (! count($criteria))
        <div class="flex justify-between items-center p-2 mb-4 text-sm font-medium leading-5
bg-white rounded-lg text-slate-400 shadow">
            <span wire:click="addCriteria"
                  class="flex items-center gap-1 text-gray-500 hover:text-flamingo-500 text-sm cursor-pointer"><x-tabler-plus
                        class="w-4 h-auto"/> Add a new condition</span>
        </div>
    @else
        <div class="mb-4 text-sm font-medium leading-5 bg-gray-50 rounded-lg text-gray-300 shadow">
            @foreach ($criteria as $criterion)
                <div wire:key="{{ $loop->index }}"
                        @class([
                        'group flex items-center justify-between gap-2 w-full font-medium leading-5 bg-white text-gray-500',
                        'rounded-t' => $loop->first,
                    ])>

                    <div class="w-full p-2 grid grid-cols-4 gap-2 text-sm">
                        <div class="w-full p-1 col-span-1">
                            <label for="conditions-{{ $loop->index }}"
                                   class="sr-only block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Select a condition
                            </label>
                            <select wire:model.debounce="criteria.{{ $loop->index }}.column"
                                    id="conditions-{{ $loop->index }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                @foreach ($this->conditions() as $condition)
                                    <option value="{{ $condition->key }}">{{ $condition->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="p-1 col-span-1">
                            <label for="operators-{{ $loop->index }}"
                                   class="sr-only block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Select an operator
                            </label>
                            <select wire:model.debounce="criteria.{{ $loop->index }}.operation"
                                    id="operators-{{ $loop->index }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                @foreach ($this->operations($criterion['column']) as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($criterion['displayValue'])

                            @if($criterion['inputType'] === 'text')
                                <div class="p-1 col-span-1">
                                    <label for="value-{{ $loop->index }}"
                                           class="sr-only block mb-2 text-sm font-medium text-gray-900 dark:text-white">Text</label>
                                    <input wire:model.debounce.1000ms="criteria.{{ $loop->index }}.value"
                                           type="text"
                                           id="value-{{ $loop->index }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                </div>
                            @endif

                            @if($criterion['inputType'] === 'date')
                                <div class="p-1 col-span-2 grid grid-cols-2 gap-2">
                                    @if(! $criterion['requiresExtra'])
                                        <div>
                                            <label class="sr-only" for="{{ $loop->index }}-start-date">Date</label>
                                            <input  type="date"
                                                    wire:model.lazy="criteria.{{ $loop->index }}.value"
                                                    wire:key="criteria.{{ $loop->index }}.value"
                                                    class="col-span-1"
                                                    id="{{ $loop->index }}-start-date"
                                            />
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1 col-span-2">
                                            <label class="sr-only" for="{{ $loop->index }}-start-date">Start date</label>
                                            <input  type="date"
                                                    wire:model.lazy="criteria.{{ $loop->index }}.value"
                                                    wire:key="criteria.{{ $loop->index }}.value"
                                                    class="col-span-1"
                                                    id="{{ $loop->index }}-start-date"
                                            />
                                            <span class="text-gray-500">and</span>

                                            <label class="sr-only" for="{{ $loop->index }}-end-date">End date</label>
                                            <input  type="date"
                                                    wire:model.lazy="criteria.{{ $loop->index }}.value"
                                                    wire:key="criteria.{{ $loop->index }}.value"
                                                    class="col-span-1"
                                                    id="{{ $loop->index }}-end-date"
                                            />
                                        </div>
                                    @endif
                                </div>
                            @endif

                        @endif
                    </div>

                    <div class="mr-4">
                        <x-tabler-trash wire:click="removeCriteria({{ $loop->index }})"
                                        class="text-gray-300 group-hover:text-red-500 cursor-pointer"/>
                    </div>

                </div>
                @if(! $loop->last)
                    <div class="pl-4 relative bg-white">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white px-2 text-sm text-gray-500">{{ $andOr }}</span>
                        </div>
                    </div>
                @endif
            @endforeach
            <div class="group flex items-center justify-between p-2 font-medium leading-5 rounded-b text-gray-500 border-t border-gray-200">
                <span wire:click="addCriteria"
                      class="pl-2 flex items-center gap-1 text-gray-500 group-hover:text-flamingo-500 text-sm cursor-pointer">
                    <x-tabler-plus class="w-4 h-auto"/> Add</span>

                <div class="flex items-center gap-2">
                    {{--                    <x-button-secondary wire:click="openUserReport(1)">Open Report</x-button-secondary>--}}
                    {{--                    <x-button-secondary wire:click="saveUserReport">Save Report</x-button-secondary>--}}
                    @include('livewire.component.condition-selector')
                </div>


            </div>

        </div>

    @endif

</div>


