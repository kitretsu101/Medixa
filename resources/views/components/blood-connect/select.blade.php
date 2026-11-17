@props(['name', 'label', 'options', 'value' => '', 'placeholder' => null, 'id' => null])
<div>
    <label for="{{ $id ?? $name }}" class="block text-sm font-semibold text-stone-700">{{ $label }}</label>
    <select id="{{ $id ?? $name }}" name="{{ $name }}" {{ $attributes->class(['mt-2 block w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-stone-900 focus:border-red-500 focus:ring-red-500']) }}>
        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $option)<option value="{{ $option }}" @selected((string) old($name, $value) === (string) $option)>{{ str_replace('_', ' ', ucfirst($option)) }}</option>@endforeach
    </select>
    @error($name)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>

