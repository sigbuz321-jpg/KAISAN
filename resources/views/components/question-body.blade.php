@props([
    'number' => 1,
    'stem',
    'options',
    'name' => null,
    'selected' => null,
    'highlight' => null,
    'interactive' => false,
])

{{--
    One rendering of a multiple-choice question, shared by the teacher's
    preview and the live exam screen so the two cannot drift apart.

    It takes plain data -- a stem and a label => text array -- and never a
    Question model, so there is no answer_key in scope to leak by accident.
    `highlight` is set only on the teacher's side.
--}}
<article class="rounded-lg border border-slate-200 bg-white p-4 sm:p-6">
    <p class="text-sm font-medium text-slate-500">Soal nomor {{ $number }}</p>

    <p class="mt-2 whitespace-pre-line text-base leading-relaxed text-slate-900">{{ $stem }}</p>

    <ul class="mt-5 space-y-2">
        @foreach ($options as $key => $text)
            <li>
                <label @class([
                    'flex min-h-11 items-start gap-3 rounded border bg-white p-3',
                    'cursor-pointer hover:border-slate-400' => $interactive,
                    'border-slate-300' => $key !== $highlight && $key !== $selected,
                    'border-slate-900 bg-slate-50 ring-1 ring-slate-900' => $key === $selected && $key !== $highlight,
                    'border-emerald-500 bg-emerald-50' => $key === $highlight,
                ])>
                    <input type="radio"
                           name="{{ $name ?? 'soal-'.$number }}"
                           value="{{ $key }}"
                           @checked($key === $selected)
                           @disabled(! $interactive)
                           {{ $attributes }}
                           class="mt-1 h-4 w-4 border-slate-400">

                    <span class="text-base text-slate-900">
                        <span class="font-semibold">{{ $key }}.</span>
                        {{ $text }}
                    </span>
                </label>
            </li>
        @endforeach
    </ul>

    {{ $slot }}
</article>
