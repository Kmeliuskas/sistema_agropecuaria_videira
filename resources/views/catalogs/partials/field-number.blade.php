<div>
    <label for="{{ $field['name'] }}" class="mb-1 block text-sm font-medium text-foreground">
        {{ $field['label'] }}
        @if($field['required']) <span class="text-red-500 ml-1">*</span> @endif
    </label>
    <input type="number"
        id="{{ $field['name'] }}"
        name="{{ $field['name'] }}"
        value="{{ old($field['name'], $field['value'] ?? '') }}"
        step="{{ $field['step'] ?? '1' }}"
        min="{{ $field['min'] ?? '' }}"
        max="{{ $field['max'] ?? '' }}"
        class="w-full rounded-lg border border-border px-3 py-2 text-sm shadow-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 @error($field['name']) border-red-500 focus:border-red-500 focus:ring-red-500/30 @enderror"
        placeholder="{{ $field['placeholder'] ?? '' }}">
    @error($field['name'])
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>