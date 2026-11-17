@props(['name', 'label', 'value' => '', 'type' => 'text'])
<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-stone-700">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->class(['mt-2 block w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-stone-900 focus:border-red-500 focus:ring-red-500']) }} aria-describedby="{{ $name }}-error">
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>

