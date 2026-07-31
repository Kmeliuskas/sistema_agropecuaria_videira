<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Habilita auditoria automática em um model (via AuditObserver).
 * Define $auditableFields com as colunas rastreadas. Sem soft-deletes
 * no log em si; o model pode (e deve) usar SoftDeletes para recuperação.
 */
trait Auditable
{
    /**
     * Colunas persistidas no histórico de auditoria.
     * Override no model para restringir o escopo.
     */
    public function getAuditableFields(): array
    {
        return $this->auditableFields ?? $this->getFillable();
    }

    /**
     * Snapshot completo das colunas rastreadas no momento.
     */
    public function getAuditSnapshot(): array
    {
        return $this->diffAgainst($this->getAttributes());
    }

    /**
     * Extrai apenas as colunas rastreadas de um array de atributos,
     * normalizando booleanos para string (true/false) para legibilidade.
     */
    public function diffAgainst(array $attributes): array
    {
        $tracked = $this->getAuditableFields();
        $snapshot = [];

        foreach ($tracked as $field) {
            if (array_key_exists($field, $attributes)) {
                $value = $attributes[$field];
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $snapshot[$field] = $value;
            }
        }

        return $snapshot;
    }
}
