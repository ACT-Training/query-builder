<div class="flex justify-center">
    <div
            x-data="{
            open: false,
            toggle() {
                if (this.open) {
                    return this.close()
                }

                this.$refs.button.focus()

                this.open = true
            },
            close(focusAfter) {
                if (! this.open) return

                this.open = false

                focusAfter && focusAfter.focus()
            }
        }"
            x-on:keydown.escape.prevent.stop="close($refs.button)"
            x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
            x-id="['dropdown-button']"
            class="relative"
    >
        <!-- Button -->
        <button
                x-ref="button"
                x-on:click="toggle()"
                :aria-expanded="open"
                :aria-controls="$id('dropdown-button')"
                type="button"
                class="flex items-center gap-2 bg-white text-gray-600 text-sm border border-gray-300 px-5 py-2.5 rounded-md shadow outline-none"
        >
            Columns

            <!-- Heroicon: chevron-down -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                 fill="currentColor">
                <path fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"/>
            </svg>
        </button>

        <!-- Panel -->
        <div
                x-ref="panel"
                x-show="open"
                x-transition.origin.top.left
                x-on:click.outside="close($refs.button)"
                :id="$id('dropdown-button')"
                style="display: none;"
                class="absolute right-0 mt-2 py-2 w-40 rounded-md bg-white shadow-md z-50"
        >
            <ul>
                @foreach ($this->columnsForSelector() as $key => $value)
                    <li class="flex items-center gap-1 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-1 text-left text-sm hover:bg-gray-50 disabled:text-gray-500">
                        <input id="{{ $key }}" type="checkbox" wire:model="displayColumns"
                               value="{{ $key }}"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="{{ $key }}"
                               class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $value }}</label>
                    </li>
                @endforeach
            </ul>

            <div class="p-2">
                <button wire:click="allColumns" type="button" class="w-full text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 text-center justify-center inline-flex items-center dark:focus:ring-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-gray-700">
                    Reset
                </button>
            </div>


        </div>
    </div>
</div>