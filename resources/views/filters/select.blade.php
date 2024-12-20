<div>
    @if($filter->options())
        <div wire:key="filter-{{ $filter->code() }}">
            <label for="filter-{{ $filter->code() }}"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $filter->label() }}</label>
            <select wire:model.live="filterValues.{{ $filter->code() }}" id="filter-{{ $filter->code() }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">{{ $filter->prompt() }}</option>
                @foreach($filter->options() as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @if($filter->optionsInGroups())
        <div wire:key="filter-{{ $filter->code() }}">
            <label for="filter-{{ $filter->code() }}"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $filter->label() }}</label>
            <select wire:model.live="filterValues.{{ $filter->code() }}" id="filter-{{ $filter->code() }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">{{ $filter->prompt() }}</option>
                @foreach($filter->optionsInGroups() as $key => $values)

                    <optgroup label="{{ $key }}">
                        @if($values)
                            @foreach($values as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        @else
                            <option disabled>There are no options</option>
                        @endif
                    </optgroup>

                @endforeach
            </select>
        </div>
    @endif
    @if(! $filter->options() && ! $filter->optionsInGroups())
        <div>No options set.</div>
    @endif
</div>