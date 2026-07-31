<div class="sm:col-span-2">
    <div class="flex items-center gap-2">
        <input type="hidden" name="{{ $field['name'] }}" value="0">
        <input type="checkbox"
            id="{{ $field['name'] }}"
            name="{{ $field['name'] }}"
            value="1"
            class="h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-primary/30 @error($field['name']) border-red-500 @enderror"
            {{ old($field['name'], $field['value'] ?? false) ? 'checked' : '' }}>
        <label for="{{ $field['name'] }}" class="cursor-pointer text-sm font-medium text-foreground">
            {{ $field['label'] }}
        </label>
    </div>
    @error($field['name'])
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>