@extends('layouts.app')

@php
    $isEdit = isset($warehouse);
@endphp

@section('title', ($isEdit ? 'Editar' : 'Novo') . ' almoxarifado — WMS')
@section('page_title', ($isEdit ? 'Editar' : 'Novo') . ' almoxarifado')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <a href="{{ route('warehouses.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        ← Voltar
    </a>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $isEdit ? route('warehouses.update', $warehouse) : route('warehouses.store') }}"
        class="space-y-5 card p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Código</label>
                <input type="text" name="code" value="{{ old('code', $warehouse->code ?? '') }}"
                    placeholder="Ex.: CD"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('code')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Nome <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $warehouse->name ?? '') }}"
                    placeholder="Ex.: Almoxarifado Central"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
                @error('name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-muted-foreground">Descrição</label>
            <textarea name="description" rows="3"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">{{ old('description', $warehouse->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Tipo</label>
                @include('catalogs.partials.field-select-search', ['field' => [
                    'name' => 'warehouse_type_id',
                    'options' => $warehouseTypes ?? [],
                    'value' => old('warehouse_type_id', $warehouse->warehouse_type_id ?? '')
                ], 'errors' => $errors])
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Responsável</label>
                <input type="text" name="responsible" value="{{ old('responsible', $warehouse->responsible ?? '') }}"
                    placeholder="Ex.: João Silva"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Documento <span class="text-xs text-muted-foreground/60">(CPF ou CNPJ)</span></label>
                <input type="text" id="document" name="document" value="{{ old('document', $warehouse->document ?? '') }}"
                    placeholder="000.000.000-00 ou 00.000.000/0000-00"
                    maxlength="18"
                    autocomplete="off"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-colors">
                <p id="document-feedback" class="mt-1 text-xs hidden"></p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">Cidade</label>
                <input type="text" name="city" value="{{ old('city', $warehouse->city ?? '') }}"
                    placeholder="Ex.: Videira"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-muted-foreground">UF</label>
                <input type="text" name="state" maxlength="2" value="{{ old('state', $warehouse->state ?? '') }}"
                    placeholder="SC"
                    class="w-full rounded-lg border border-border px-3 py-2 text-sm uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-muted-foreground">Endereço</label>
            <input type="text" name="address" value="{{ old('address', $warehouse->address ?? '') }}"
                placeholder="Rua, número, bairro"
                class="w-full rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/30">
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $warehouse->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                Almoxarifado ativo
            </label>

            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" name="is_default" value="1"
                    {{ old('is_default', $warehouse->is_default ?? false) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-border text-foreground">
                Almoxarifado padrão
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('warehouses.index') }}"
                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                Cancelar
            </a>
            <button type="submit"
                class="btn-primary">
                {{ $isEdit ? 'Salvar alterações' : 'Criar almoxarifado' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('document');
    const feedback = document.getElementById('document-feedback');

    if (!input) return;

    // --- Formatação ---
    function onlyDigits(v) {
        return v.replace(/\D/g, '');
    }

    function formatCPF(digits) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    function formatCNPJ(digits) {
        return digits
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }

    // --- Validação matemática ---
    function validateCPF(digits) {
        if (digits.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(digits)) return false;
        let sum = 0;
        for (let i = 0; i < 9; i++) sum += parseInt(digits[i]) * (10 - i);
        let r = (sum * 10) % 11;
        if (r === 10 || r === 11) r = 0;
        if (r !== parseInt(digits[9])) return false;
        sum = 0;
        for (let i = 0; i < 10; i++) sum += parseInt(digits[i]) * (11 - i);
        r = (sum * 10) % 11;
        if (r === 10 || r === 11) r = 0;
        return r === parseInt(digits[10]);
    }

    function validateCNPJ(digits) {
        if (digits.length !== 14) return false;
        if (/^(\d)\1{13}$/.test(digits)) return false;
        const calcDigit = (d, weights) => {
            let sum = 0;
            for (let i = 0; i < weights.length; i++) sum += parseInt(d[i]) * weights[i];
            const r = sum % 11;
            return r < 2 ? 0 : 11 - r;
        };
        const w1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        const w2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        return calcDigit(digits, w1) === parseInt(digits[12])
            && calcDigit(digits, w2) === parseInt(digits[13]);
    }

    // --- Feedback visual ---
    function showFeedback(valid, digits) {
        input.classList.remove('border-green-500', 'ring-green-500/30', 'border-red-500', 'ring-red-500/30', 'focus:border-primary', 'focus:ring-primary/30');
        feedback.classList.remove('hidden', 'text-green-600', 'text-red-500');

        if (digits.length === 0) {
            feedback.classList.add('hidden');
            input.classList.add('focus:border-primary', 'focus:ring-primary/30');
            return;
        }

        if (valid) {
            input.classList.add('border-green-500');
            feedback.classList.add('text-green-600');
            feedback.textContent = digits.length === 11 ? '✓ CPF válido' : '✓ CNPJ válido';
        } else if (digits.length === 11 || digits.length === 14) {
            input.classList.add('border-red-500');
            feedback.classList.add('text-red-500');
            feedback.textContent = digits.length === 11 ? '✗ CPF inválido' : '✗ CNPJ inválido';
        } else {
            feedback.classList.add('hidden');
            input.classList.add('focus:border-primary', 'focus:ring-primary/30');
        }
    }

    // --- Evento de input ---
    input.addEventListener('input', function (e) {
        const cursorPos = this.selectionStart;
        const prevLen = this.value.length;

        let digits = onlyDigits(this.value);
        if (digits.length > 14) digits = digits.slice(0, 14);

        let formatted = '';
        if (digits.length <= 11) {
            formatted = formatCPF(digits);
        } else {
            formatted = formatCNPJ(digits);
        }

        this.value = formatted;

        // Mantém cursor na posição certa após formatação
        const diff = this.value.length - prevLen;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);

        // Validação
        let valid = false;
        if (digits.length === 11) valid = validateCPF(digits);
        else if (digits.length === 14) valid = validateCNPJ(digits);

        showFeedback(valid, digits);
    });

    // --- Formata o valor inicial (se já houver um salvo) ---
    if (input.value) {
        input.dispatchEvent(new Event('input'));
    }

    // --- Bloqueia submit se CPF/CNPJ inválido ---
    input.closest('form').addEventListener('submit', function (e) {
        const digits = onlyDigits(input.value);
        if (digits.length === 0) return; // campo vazio = opcional, ok
        if (digits.length === 11 && !validateCPF(digits)) {
            e.preventDefault();
            input.focus();
            showFeedback(false, digits);
            feedback.classList.remove('hidden');
        } else if (digits.length === 14 && !validateCNPJ(digits)) {
            e.preventDefault();
            input.focus();
            showFeedback(false, digits);
            feedback.classList.remove('hidden');
        } else if (digits.length !== 11 && digits.length !== 14) {
            e.preventDefault();
            input.focus();
            input.classList.add('border-red-500');
            feedback.classList.remove('hidden');
            feedback.classList.remove('text-green-600');
            feedback.classList.add('text-red-500');
            feedback.textContent = '✗ Digite um CPF (11 dígitos) ou CNPJ (14 dígitos) completo';
        }
    });
})();
</script>
@endpush
