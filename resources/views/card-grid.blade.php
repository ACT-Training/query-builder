@php
    $cardLayout = $this->cardLayout();
    $gridCols = $cardLayout->getGridColumns();
@endphp

<div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $gridCols }} p-4">
    @foreach($this->rows as $row)
        @php
            $imageUrl = $cardLayout->getImageUrl($row);
            $cardView = $cardLayout->getCardView();
        @endphp

        <div
            wire:key="{{ $this->identifier() }}-card-{{ $row->id }}"
            @if($this->isClickable())
                {!! $this->renderRowClick($row->id) !!}
                class="cursor-pointer h-full"
            @else
                class="h-full"
            @endif
        >
            @include($cardView, ['row' => $row, 'imageUrl' => $imageUrl])
        </div>
    @endforeach
</div>
