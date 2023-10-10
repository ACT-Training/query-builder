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