@php
    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $placeholder = $getPlaceholder() ?? 'Pilih rentang tanggal (misal: 1 Sep 2026 s/d 30 Sep 2026)...';
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            picker: null,
            init() {
                this.$nextTick(() => {
                    this.ensureFlatpickr(() => this.setupPicker());
                });
            },
            ensureFlatpickr(callback) {
                if (window.flatpickr) {
                    callback();
                    return;
                }

                if (!document.getElementById('flatpickr-css')) {
                    const link = document.createElement('link');
                    link.id = 'flatpickr-css';
                    link.rel = 'stylesheet';
                    link.href = '{{ asset('vendor/flatpickr/flatpickr.min.css') }}';
                    document.head.appendChild(link);
                }

                if (!document.getElementById('flatpickr-js')) {
                    const script = document.createElement('script');
                    script.id = 'flatpickr-js';
                    script.src = '{{ asset('vendor/flatpickr/flatpickr.min.js') }}';
                    script.onload = () => {
                        const idScript = document.createElement('script');
                        idScript.id = 'flatpickr-id-js';
                        idScript.src = '{{ asset('vendor/flatpickr/l10n/id.js') }}';
                        idScript.onload = () => callback();
                        document.head.appendChild(idScript);
                    };
                    document.head.appendChild(script);
                } else {
                    const interval = setInterval(() => {
                        if (window.flatpickr) {
                            clearInterval(interval);
                            callback();
                        }
                    }, 50);
                }
            },
            setupPicker() {
                if (this.picker) {
                    this.picker.destroy();
                }

                const localeConfig = (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.id) 
                    ? window.flatpickr.l10ns.id 
                    : {};

                const splitRange = (val) => {
                    if (!val) return null;
                    if (val.includes(' - ')) return val.split(' - ');
                    if (val.includes(' to ')) return val.split(' to ');
                    return [val, val];
                };

                this.picker = flatpickr(this.$refs.pickerInput, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'j M Y',
                    locale: localeConfig,
                    defaultDate: splitRange(this.state),
                    onChange: (selectedDates, dateStr) => {
                        if (selectedDates.length === 2) {
                            this.state = dateStr;
                        }
                    },
                    onClose: (selectedDates, dateStr) => {
                        if (selectedDates.length === 1) {
                            const sep = (localeConfig && localeConfig.rangeSeparator) ? localeConfig.rangeSeparator : ' - ';
                            this.state = dateStr + sep + dateStr;
                        } else if (selectedDates.length === 2) {
                            this.state = dateStr;
                        }
                    }
                });

                this.$watch('state', (newVal) => {
                    if (!newVal && this.picker) {
                        this.picker.clear();
                    } else if (newVal && this.picker) {
                        const parts = splitRange(newVal);
                        if (parts) {
                            this.picker.setDate(parts, false);
                        }
                    }
                });
            },
            clearValue() {
                this.state = null;
                if (this.picker) {
                    this.picker.clear();
                }
            }
        }"
        class="relative"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            prefix-icon="heroicon-o-calendar"
        >
            <input
                x-ref="pickerInput"
                type="text"
                placeholder="{{ $placeholder }}"
                class="fi-input block w-full border-none bg-transparent py-1.5 pe-3 ps-3 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 sm:text-sm sm:leading-6 cursor-pointer"
                readonly
            />
            <button
                type="button"
                x-show="Boolean(state)"
                x-on:click="clearValue()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 pe-2 focus:outline-none"
                title="Hapus tanggal"
            >
                <x-heroicon-m-x-mark class="w-4 h-4" />
            </button>
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
