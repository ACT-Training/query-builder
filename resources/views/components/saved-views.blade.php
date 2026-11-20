{{--
    Saved views bar: favourite views as quick buttons, a menu of every view,
    and modals to save a new view and manage existing ones. Uses Flux's free
    components, so an app that turns saved views on needs livewire/flux.
--}}
@php
    $savedViews = $this->savedViews();
    $favouriteViews = $this->favouriteSavedViews();
    $activeView = $this->activeSavedView();
    $myViews = array_filter($savedViews, fn ($view) => $this->ownsSavedView($view));
    $sharedViews = array_filter($savedViews, fn ($view) => ! $this->ownsSavedView($view));
    $saveModal = $this->identifier().'-save-view';
    $manageModal = $this->identifier().'-manage-view';
@endphp

{{-- No padding of its own: the table views place it at the right of the search or toolbar row. Pass $savedViewsClass to position it. --}}
<div class="flex flex-wrap items-center gap-2 {{ $savedViewsClass ?? '' }}" wire:key="{{ $this->identifier() }}-saved-views">
    @foreach ($favouriteViews as $view)
        <flux:button
            size="sm"
            :variant="$activeView?->id === $view->id ? 'primary' : 'ghost'"
            icon="star"
            wire:click="applySavedView('{{ $view->id }}')"
        >
            {{ $view->name }}
        </flux:button>
    @endforeach

    <flux:dropdown position="bottom" align="end">
        <flux:button size="sm" icon="queue-list" icon:trailing="chevron-down">
            {{ $activeView?->name ?? 'Views' }}
        </flux:button>

        <flux:menu>
            @foreach (['My views' => $myViews, 'Shared with everyone' => $sharedViews] as $heading => $views)
                @if (count($views))
                    <flux:menu.group :heading="$heading">
                        @foreach ($views as $view)
                            <flux:menu.item
                                wire:click="applySavedView('{{ $view->id }}')"
                                :icon="$view->isFavourite ? 'star' : null"
                            >
                                {{ $view->name }}
                                @if ($view->isDefault)
                                    <flux:badge size="sm" class="ms-2">Default</flux:badge>
                                @endif
                            </flux:menu.item>
                        @endforeach
                    </flux:menu.group>
                @endif
            @endforeach

            {{-- Each group above draws its own separator before what follows, so none is added here. --}}
            <flux:modal.trigger :name="$saveModal">
                <flux:menu.item icon="plus">Save as a new view</flux:menu.item>
            </flux:modal.trigger>

            @if ($activeView && $this->ownsSavedView($activeView))
                <flux:menu.item icon="arrow-path" wire:click="updateSavedView('{{ $activeView->id }}')">
                    Update "{{ $activeView->name }}"
                </flux:menu.item>
            @endif

            @if (count($savedViews))
                <flux:modal.trigger :name="$manageModal">
                    <flux:menu.item icon="cog-6-tooth">Manage views</flux:menu.item>
                </flux:modal.trigger>
            @endif
        </flux:menu>
    </flux:dropdown>
</div>

<flux:modal :name="$saveModal" class="md:w-96">
    <form wire:submit="saveView" class="space-y-6">
        <div>
            <flux:heading size="lg">Save view</flux:heading>
            <flux:text class="mt-2">Saves the current filters, search, sort, columns and rows per page.</flux:text>
        </div>

        <flux:input wire:model="savedViewName" label="Name" maxlength="255" required />

        <flux:checkbox wire:model="savedViewShared" label="Share with everyone who can see this table" />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</flux:modal>

<flux:modal :name="$manageModal" class="md:w-[36rem]">
    <div class="space-y-6">
        <flux:heading size="lg">Manage views</flux:heading>

        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($savedViews as $view)
                @php $owned = $this->ownsSavedView($view); @endphp
                <li
                    class="flex flex-wrap items-center gap-2 py-3"
                    wire:key="manage-view-{{ $view->id }}"
                    x-data="{ editing: false, name: @js($view->name) }"
                >
                    <div class="min-w-0 flex-1">
                        <div x-show="! editing" class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $view->name }}</span>
                            @if ($view->isShared)
                                <flux:badge size="sm" color="blue" icon="users">Shared</flux:badge>
                            @endif
                            @if ($view->isDefault)
                                <flux:badge size="sm" color="green">Default</flux:badge>
                            @endif
                        </div>

                        @if ($owned)
                            <form
                                x-show="editing"
                                x-cloak
                                class="flex items-center gap-2"
                                x-on:submit.prevent="$wire.renameSavedView(@js($view->id), name); editing = false"
                            >
                                <flux:input size="sm" x-model="name" maxlength="255" aria-label="View name" />
                                <flux:button size="sm" type="submit" variant="primary">Save</flux:button>
                                <flux:button size="sm" variant="ghost" x-on:click="editing = false">Cancel</flux:button>
                            </form>
                        @endif
                    </div>

                    <div class="flex items-center gap-1" x-show="! editing">
                        <flux:button
                            size="sm"
                            :variant="$view->isFavourite ? 'filled' : 'ghost'"
                            icon="star"
                            :aria-pressed="$view->isFavourite ? 'true' : 'false'"
                            wire:click="toggleSavedViewFavourite('{{ $view->id }}')"
                            :aria-label="$view->isFavourite ? 'Remove from favourites' : 'Add to favourites'"
                            :tooltip="$view->isFavourite ? 'Remove from favourites' : 'Add to favourites'"
                        />
                        <flux:button
                            size="sm"
                            :variant="$view->isDefault ? 'filled' : 'ghost'"
                            icon="home"
                            :aria-pressed="$view->isDefault ? 'true' : 'false'"
                            wire:click="toggleDefaultSavedView('{{ $view->id }}')"
                            :aria-label="$view->isDefault ? 'Stop using as my default' : 'Use as my default'"
                            :tooltip="$view->isDefault ? 'Stop using as my default' : 'Use as my default'"
                        />

                        @if ($owned)
                            <flux:button
                                size="sm"
                                :variant="$view->isShared ? 'filled' : 'ghost'"
                                icon="users"
                                :aria-pressed="$view->isShared ? 'true' : 'false'"
                                wire:click="toggleSavedViewShared('{{ $view->id }}')"
                                :aria-label="$view->isShared ? 'Stop sharing' : 'Share with everyone'"
                                :tooltip="$view->isShared ? 'Stop sharing' : 'Share with everyone'"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pencil-square"
                                x-on:click="editing = true"
                                aria-label="Rename"
                                tooltip="Rename"
                            />
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                x-on:click="$flux.modal('{{ $manageModal }}-delete-{{ $view->id }}').show()"
                                aria-label="Delete"
                                tooltip="Delete"
                            />
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</flux:modal>

{{-- Deleting a view is confirmed in a modal, never the browser's own dialog. --}}
@foreach ($myViews as $view)
    <div wire:key="{{ $manageModal }}-delete-{{ $view->id }}">
    <flux:modal name="{{ $manageModal }}-delete-{{ $view->id }}" class="w-full max-w-md">
        <div class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">Delete the view "{{ $view->name }}"?</flux:heading>
                <flux:text class="mt-2">This can't be undone. Anyone using it as their default goes back to the full list.</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button
                    variant="danger"
                    x-on:click="$flux.modal('{{ $manageModal }}-delete-{{ $view->id }}').close(); if ({{ count($savedViews) }} === 1) $flux.modal('{{ $manageModal }}').close(); $wire.deleteSavedView('{{ $view->id }}')"
                >
                    Delete view
                </flux:button>
            </div>
        </div>
    </flux:modal>
    </div>
@endforeach
