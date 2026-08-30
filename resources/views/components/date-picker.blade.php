@props([
    'name',
    'id' => null,
    'value' => null,
    'min' => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
@endphp

{{--
    A native <input type="date"> renders its popup calendar in whatever
    language the OS/browser is set to, not the page's own language — on this
    app that showed up as an English calendar on desktop Chrome next to a
    Greek one on mobile, for the same user. This renders the calendar
    ourselves so it's always Greek, regardless of device settings.
--}}
<div
    x-data="{
        open: false,
        min: @js($min),
        selected: @js($value),
        viewYear: @js($value ? (int) explode('-', $value)[0] : (int) date('Y')),
        viewMonth: @js($value ? (int) explode('-', $value)[1] - 1 : (int) date('n') - 1),
        months: ['Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος', 'Μάιος', 'Ιούνιος', 'Ιούλιος', 'Αύγουστος', 'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος'],
        dayLabels: ['Δε', 'Τρ', 'Τε', 'Πε', 'Πα', 'Σα', 'Κυ'],
        pad(n) { return n < 10 ? '0' + n : '' + n },
        toIso(y, m, d) { return y + '-' + this.pad(m + 1) + '-' + this.pad(d) },
        get displayValue() {
            if (! this.selected) return '';
            const [y, m, d] = this.selected.split('-');
            return d + '/' + m + '/' + y;
        },
        get daysInMonth() { return new Date(this.viewYear, this.viewMonth + 1, 0).getDate() },
        get leadingBlanks() { return (new Date(this.viewYear, this.viewMonth, 1).getDay() + 6) % 7 },
        isDisabled(d) { return this.min && this.toIso(this.viewYear, this.viewMonth, d) < this.min },
        select(d) {
            if (this.isDisabled(d)) return;
            this.selected = this.toIso(this.viewYear, this.viewMonth, d);
            this.open = false;
        },
        prevMonth() { this.viewMonth--; if (this.viewMonth < 0) { this.viewMonth = 11; this.viewYear-- } },
        nextMonth() { this.viewMonth++; if (this.viewMonth > 11) { this.viewMonth = 0; this.viewYear++ } },
    }"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selected" @if($required) required @endif>

    <button
        type="button"
        id="{{ $id }}"
        @click="open = ! open"
        {{ $attributes->merge(['class' => 'w-full text-left border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}
    >
        <span x-text="displayValue || 'Επιλέξτε ημερομηνία'" :class="{ 'text-gray-400': ! selected }"></span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        x-transition
        class="absolute z-20 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3 w-64"
    >
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="prevMonth()" class="p-1 rounded hover:bg-gray-100 text-gray-500">&laquo;</button>
            <div class="text-sm font-semibold text-gray-900" x-text="months[viewMonth] + ' ' + viewYear"></div>
            <button type="button" @click="nextMonth()" class="p-1 rounded hover:bg-gray-100 text-gray-500">&raquo;</button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-400 mb-1">
            <template x-for="d in dayLabels" :key="d">
                <div x-text="d"></div>
            </template>
        </div>
        <div class="grid grid-cols-7 gap-1">
            <template x-for="n in leadingBlanks" :key="'blank-' + n">
                <div></div>
            </template>
            <template x-for="d in daysInMonth" :key="d">
                <button
                    type="button"
                    @click="select(d)"
                    :disabled="isDisabled(d)"
                    :class="{
                        'bg-[#0e6e73] text-white': selected === toIso(viewYear, viewMonth, d),
                        'text-gray-300 cursor-not-allowed': isDisabled(d),
                        'hover:bg-gray-100 text-gray-700': ! isDisabled(d) && selected !== toIso(viewYear, viewMonth, d),
                    }"
                    class="aspect-square flex items-center justify-center rounded-md text-sm"
                    x-text="d"
                ></button>
            </template>
        </div>
    </div>
</div>
