@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-900">{{ $label }}</label>

    <input id="{{ $name }}"
           name="{{ $name }}"
           type="{{ $type }}"
           value="{{ old($name, $value) }}"
           {{ $attributes->merge([
               'class' => 'mt-1 block w-full rounded border px-3 py-2 text-base min-h-11 focus:ring-slate-900 '
                   . ($errors->has($name) ? 'border-red-500 focus:border-red-500' : 'border-slate-300 focus:border-slate-900'),
           ]) }}>

    @if ($hint)
        <p class="mt-1 text-sm text-slate-600">{{ $hint }}</p>
    @endif

    {{-- Error sits next to its own field, not only at the top of the page. --}}
    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
