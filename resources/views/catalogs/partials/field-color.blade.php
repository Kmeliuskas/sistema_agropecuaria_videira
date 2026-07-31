<div>
    <label for="{{ $field['name'] }}" class="mb-1 block text-sm font-medium text-foreground">
        {{ $field['label'] }}
        @if($field['required']) <span class="text-red-500 ml-1">*</span> @endif
    </label>
    <input type="color"
        id="{{ $field['name'] }}"
        name="{{ $field['name'] }}"
        value="{{ old($field['name'], $field['value'] ?? '#000000') }}"
        class="w-10 h-10 rounded-lg border border-border cursor-pointer @error($field['name']) border-red-500 @enderror"
        title="{{ $field['label'] }}">
    @error($field['name'])
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>