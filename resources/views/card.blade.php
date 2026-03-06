<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($imageUrl)
        <div class="aspect-video overflow-hidden">
            <img
                src="{{ $imageUrl }}"
                alt=""
                class="w-full h-full object-cover"
            />
        </div>
    @endif

    <div class="p-4">
        <p class="text-sm text-gray-500">{{ $row->id }}</p>
    </div>
</div>
