<div class="inline-flex rounded-md shadow-sm">
    <button
        type="button"
        wire:click="toggleViewMode"
        @class([
            'inline-flex items-center px-3 py-2 text-sm font-medium rounded-l-md border',
            'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' => $this->viewMode !== 'cards',
            'bg-gray-100 text-gray-900 border-gray-300 z-10' => $this->viewMode === 'cards',
        ])
        title="Card view"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
        </svg>
    </button>
    <button
        type="button"
        wire:click="toggleViewMode"
        @class([
            'inline-flex items-center px-3 py-2 text-sm font-medium rounded-r-md border -ml-px',
            'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' => $this->viewMode !== 'table',
            'bg-gray-100 text-gray-900 border-gray-300 z-10' => $this->viewMode === 'table',
        ])
        title="Table view"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 010 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 010 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 010 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
        </svg>
    </button>
</div>
