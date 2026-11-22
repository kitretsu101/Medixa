@props(['name', 'label', 'value' => '', 'type' => 'text'])
<div class="min-w-0">
    <label for="{{ $name }}" class="block text-sm font-semibold text-stone-700">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->class(['mt-2 block min-w-0 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-stone-900 shadow-sm transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20']) }} aria-describedby="{{ $name }}-error">
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>

