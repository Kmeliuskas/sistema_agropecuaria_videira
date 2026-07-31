<div>
    <label for="{{ $field['name'] }}" class="mb-1 block text-sm font-medium text-foreground">
        {{ $field['label'] }}
        @if(isset($field['required']) && $field['required']) <span class="text-danger ml-1">*</span> @endif
    </label>
    @include('catalogs.partials.field-select-search', ['field' => $field, 'errors' => $errors])
</div>