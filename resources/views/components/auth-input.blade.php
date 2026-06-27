@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'value' => null,
    'autofocus' => false,
    'autocomplete' => null,
])

@php $hasError = $errors->has($name); @endphp

<div class="mb-4">
    <label for="{{ $name }}" class="block text-[13px] font-semibold text-[#18181b] mb-1.5">{{ $label }}</label>
    <div class="relative">
        @if($icon)
            <x-icon :name="$icon" class="w-[18px] h-[18px] absolute left-3 top-1/2 -translate-y-1/2 text-[#a1a1aa] pointer-events-none" />
        @endif
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $value }}"
            @if($autofocus) autofocus @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            required
            class="w-full h-11 {{ $icon ? 'pl-[38px]' : 'pl-3' }} pr-3 border rounded-[10px] text-sm text-[#18181b] outline-none focus:border-[var(--lt-p)] transition {{ $hasError ? 'border-error' : 'border-[#d4d4d8]' }}"
        />
    </div>
    @error($name)
        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
    @enderror
</div>
