@props([
    'seconds' => 0,
    'label' => 'Sisa waktu',
])

{{--
    Tahap 2 · Timer ujian.

    The countdown runs in the browser so 150 students do not poll the server
    once a second. It measures elapsed time since the page loaded rather than
    reading the wall clock, so changing the device clock does nothing.

    It is a display. The server decides whether a submission is on time, and
    grades an abandoned attempt on its own.
--}}
<div x-data="{
        sisa: Math.max(0, {{ (int) $seconds }}),
        mendesak: false,
        init() {
            const total = this.sisa;
            const mulai = Date.now();
            const id = setInterval(() => {
                this.sisa = Math.max(0, total - Math.floor((Date.now() - mulai) / 1000));
                this.mendesak = this.sisa <= 60;
                if (this.sisa === 0) {
                    clearInterval(id);
                    $wire.kumpulkan();
                }
            }, 1000);
        },
        get jam() {
            const m = String(Math.floor(this.sisa / 60)).padStart(2, '0');
            const d = String(this.sisa % 60).padStart(2, '0');
            return m + ':' + d;
        },
     }"
     {{ $attributes->merge([
         'class' => 'inline-flex items-center gap-2 rounded-full px-3 py-1 font-mono text-xs font-semibold tracking-[0.04em] tabular-nums transition-colors duration-150',
     ]) }}
     :class="mendesak ? 'bg-danger-soft text-danger-text' : 'bg-accent-soft text-accent-text'"
     role="timer"
     aria-live="off">
    <span class="h-1.5 w-1.5 rounded-full"
          :class="mendesak ? 'bg-danger animate-blink' : 'bg-accent'"></span>

    <span class="sr-only">{{ $label }}</span>
    <span x-text="jam">--:--</span>
</div>
