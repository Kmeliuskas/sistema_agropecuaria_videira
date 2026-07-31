@php
    $name = $field['name'];
    $value = old($name, $field['value'] ?? '');
    $required = $field['required'] ?? false;
@endphp

<div class="col-span-2 sm:col-span-1">
    <label class="mb-1 block text-sm font-medium text-foreground">
        {{ $field['label'] }}{{ $required ? ' *' : '' }}
    </label>
    <input type="email"
           name="{{ $name }}"
           value="{{ $value }}"
           {{ $required ? 'required' : '' }}
           class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30"
           placeholder="{{ $field['label'] }}">
    @error($name)
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
