@props([
    'options' => [],
    'placeholder' => 'Search and select...',
    'nameField' => 'name',
    'idField' => 'id',
    'badgeField' => 'code',
    'subtextField' => 'category',
    'disabled' => false,
    'maxHeight' => 'max-h-60',
    'emptyMessage' => 'No matching items found.',
    'chipColor' => 'indigo', // indigo | blue | emerald | purple
])

@php
    $wireModel = $attributes->wire('model');
    $wireProp = $wireModel->value();
    $wireModifier = $wireModel->hasModifier('live') ? '.live' : '';
@endphp

<div
    x-data="{
        selected: @if($wireProp) $wire.entangle('{{ $wireProp }}'){{ $wireModifier }} @else [] @endif,
        options: {{ Js::from($options) }},
        idField: '{{ $idField }}',
        nameField: '{{ $nameField }}',
        badgeField: '{{ $badgeField }}',
        subtextField: '{{ $subtextField }}',
        placeholder: '{{ $placeholder }}',
        disabled: {{ $disabled ? 'true' : 'false' }},
        search: '',
        open: false,
        highlightedIndex: 0,

        init() {
            if (!Array.isArray(this.selected)) {
                this.selected = this.selected ? [this.selected] : [];
            }
        },

        get selectedItems() {
            if (!Array.isArray(this.selected)) return [];
            return this.options.filter(opt => {
                const optId = opt[this.idField];
                return this.selected.some(s => String(s) === String(optId));
            });
        },

        get filteredOptions() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter(opt => {
                const name = String(opt[this.nameField] || '').toLowerCase();
                const badge = String(opt[this.badgeField] || '').toLowerCase();
                const subtext = String(opt[this.subtextField] || '').toLowerCase();
                return name.includes(q) || badge.includes(q) || subtext.includes(q);
            });
        },

        isSelected(id) {
            if (!Array.isArray(this.selected)) return false;
            return this.selected.some(s => String(s) === String(id));
        },

        toggle(id) {
            if (this.disabled) return;
            if (!Array.isArray(this.selected)) this.selected = [];
            
            const numericOrStringId = (typeof id === 'number') ? id : (Number(id) || id);
            const index = this.selected.findIndex(s => String(s) === String(id));

            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                this.selected.push(numericOrStringId);
            }
            this.search = '';
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        remove(id) {
            if (this.disabled) return;
            if (!Array.isArray(this.selected)) return;
            const index = this.selected.findIndex(s => String(s) === String(id));
            if (index > -1) {
                this.selected.splice(index, 1);
            }
        },

        clearAll() {
            if (this.disabled) return;
            this.selected = [];
            this.search = '';
        },

        selectAllFiltered() {
            if (this.disabled) return;
            if (!Array.isArray(this.selected)) this.selected = [];
            this.filteredOptions.forEach(opt => {
                const optId = opt[this.idField];
                if (!this.isSelected(optId)) {
                    this.selected.push(typeof optId === 'number' ? optId : (Number(optId) || optId));
                }
            });
            this.search = '';
        },

        handleBackspace() {
            if (this.search === '' && Array.isArray(this.selected) && this.selected.length > 0) {
                this.selected.pop();
            }
        },

        highlightNext() {
            if (!this.open) {
                this.open = true;
                return;
            }
            if (this.highlightedIndex < this.filteredOptions.length - 1) {
                this.highlightedIndex++;
            } else {
                this.highlightedIndex = 0;
            }
            this.scrollToHighlighted();
        },

        highlightPrev() {
            if (!this.open) {
                this.open = true;
                return;
            }
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            } else {
                this.highlightedIndex = Math.max(0, this.filteredOptions.length - 1);
            }
            this.scrollToHighlighted();
        },

        selectHighlighted() {
            if (this.open && this.filteredOptions.length > 0 && this.highlightedIndex >= 0) {
                const item = this.filteredOptions[this.highlightedIndex];
                if (item) {
                    this.toggle(item[this.idField]);
                }
            } else {
                this.open = true;
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.$refs.optionsList?.children[this.highlightedIndex];
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        }
    }"
    @click.outside="open = false"
    class="relative w-full"
    {{ $attributes->except(['wire:model', 'wire:model.live', 'options']) }}
>
    {{-- Main Container / Input Box with Chips --}}
    <div
        @click="if (!disabled) { open = true; $refs.searchInput.focus(); }"
        class="w-full min-h-[42px] px-3 py-1.5 rounded-xl border border-gray-200 bg-white focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-500 transition-all flex flex-wrap items-center gap-1.5 cursor-text relative shadow-2xs"
        :class="{ 'opacity-60 cursor-not-allowed bg-gray-50': disabled, 'border-indigo-500 ring-2 ring-indigo-500/20': open }"
    >
        {{-- Selected Chips --}}
        <template x-for="item in selectedItems" :key="item[idField]">
            <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 text-xs font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-2xs group transition-all shrink-0">
                <template x-if="item[badgeField]">
                    <span class="px-1 py-0.2 rounded bg-indigo-200/60 text-indigo-900 font-mono text-[10px]" x-text="item[badgeField]"></span>
                </template>
                <span class="max-w-[160px] truncate" x-text="item[nameField]"></span>
                <button
                    type="button"
                    @click.stop="remove(item[idField])"
                    class="w-4 h-4 rounded-full flex items-center justify-center text-indigo-400 hover:text-indigo-800 hover:bg-indigo-200/80 transition-colors ml-0.5"
                    title="Remove item"
                >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>
        </template>

        {{-- Live Search Input --}}
        <div class="flex-1 min-w-[120px] flex items-center">
            <input
                x-ref="searchInput"
                x-model="search"
                :disabled="disabled"
                :placeholder="selectedItems.length === 0 ? placeholder : 'Add more...'"
                @focus="open = true"
                @keydown.arrow-down.prevent="highlightNext()"
                @keydown.arrow-up.prevent="highlightPrev()"
                @keydown.enter.prevent="selectHighlighted()"
                @keydown.escape.prevent="open = false"
                @keydown.backspace="handleBackspace()"
                class="w-full py-1 text-xs text-gray-800 placeholder-gray-400 bg-transparent outline-none border-none focus:ring-0 p-0 font-medium"
            />
        </div>

        {{-- Right Action Buttons (Clear All & Toggle Arrow) --}}
        <div class="flex items-center gap-1.5 ml-auto pl-1 shrink-0">
            <button
                x-show="selected.length > 0 && !disabled"
                type="button"
                @click.stop="clearAll()"
                class="text-[11px] font-bold text-gray-400 hover:text-rose-600 transition-colors px-1"
                title="Clear all selected"
                style="display: none;"
            >
                Clear (<span x-text="selected.length"></span>)
            </button>

            <button
                type="button"
                @click.stop="if(!disabled) { open = !open; if(open) $refs.searchInput.focus(); }"
                class="text-gray-400 hover:text-gray-600 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Floating Dropdown Popover --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-98"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-98"
        class="absolute left-0 right-0 z-50 mt-1.5 bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden animate-fade-in"
        style="display: none;"
    >
        {{-- Header / Quick Select --}}
        <div class="px-3.5 py-2 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between text-[11px] font-bold text-gray-500 uppercase tracking-wider">
            <span>Available (<span x-text="filteredOptions.length"></span>)</span>
            <template x-if="filteredOptions.length > 0">
                <div class="flex items-center gap-2">
                    <button type="button" @click.prevent="selectAllFiltered()" class="text-indigo-600 hover:underline">Select All</button>
                    <span>•</span>
                    <button type="button" @click.prevent="clearAll()" class="text-gray-500 hover:underline">Clear</button>
                </div>
            </template>
        </div>

        {{-- Scrollable List --}}
        <ul x-ref="optionsList" class="{{ $maxHeight }} overflow-y-auto divide-y divide-gray-50 p-1.5 space-y-0.5 custom-scrollbar" role="listbox">
            <template x-for="(item, index) in filteredOptions" :key="item[idField]">
                <li
                    @click="toggle(item[idField])"
                    @mouseenter="highlightedIndex = index"
                    :class="{
                        'bg-indigo-50/70 text-indigo-900 font-semibold': isSelected(item[idField]),
                        'bg-gray-100/70': highlightedIndex === index && !isSelected(item[idField]),
                        'text-gray-700': !isSelected(item[idField]) && highlightedIndex !== index
                    }"
                    class="px-3 py-2 rounded-xl text-xs flex items-center justify-between gap-2.5 cursor-pointer transition-colors select-none"
                    role="option"
                    :aria-selected="isSelected(item[idField])"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        {{-- Checkbox icon --}}
                        <div
                            class="w-4 h-4 rounded-md border flex items-center justify-center shrink-0 transition-colors"
                            :class="isSelected(item[idField]) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 bg-white'"
                        >
                            <svg x-show="isSelected(item[idField])" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>

                        <template x-if="item[badgeField]">
                            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold shrink-0"
                                :class="isSelected(item[idField]) ? 'bg-indigo-200/80 text-indigo-950' : 'bg-gray-100 text-gray-600 border border-gray-200'"
                                x-text="item[badgeField]"></span>
                        </template>

                        <span class="truncate font-medium text-xs text-gray-800" x-text="item[nameField]"></span>
                    </div>

                    <template x-if="item[subtextField]">
                        <span class="text-[10px] text-gray-400 font-medium shrink-0 ml-2" x-text="item[subtextField]"></span>
                    </template>
                </li>
            </template>

            <template x-if="filteredOptions.length === 0">
                <li class="px-4 py-6 text-center text-xs text-gray-400 italic">
                    {{ $emptyMessage }}
                </li>
            </template>
        </ul>
    </div>
</div>
