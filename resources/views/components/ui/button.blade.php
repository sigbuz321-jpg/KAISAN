<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'min-h-11 w-full rounded bg-slate-900 px-4 py-2.5 text-base font-medium text-white '
        . 'hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2',
]) }}>
    {{ $slot }}
</button>
