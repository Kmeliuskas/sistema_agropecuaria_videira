<?php

namespace App\Support;

/**
 * Rótulos e descrições amigáveis para as permissões no padrão "recurso.acao".
 * Usado nas telas de Papéis e Permissões para que o usuário entenda o que
 * cada permissão faz sem precisar decifrar o nome técnico.
 */
class PermissionLabels
{
    /** Rótulo de cada módulo (recurso). */
    public static function moduleLabel(string $module): string
    {
        return match ($module) {
            'products' => 'Produtos',
            'categories' => 'Categorias',
            'subcategories' => 'Subcategorias',
            'attributes' => 'Atributos',
            'brands' => 'Marcas',
            'manufacturers' => 'Fabricantes',
            'units' => 'Unidades',
            'warehouses' => 'Almoxarifados',
            'warehouse-types' => 'Tipos de Almoxarifado',
            'sectors' => 'Setores',
            'stock' => 'Estoque',
            'movements' => 'Movimentações',
            'suppliers' => 'Fornecedores',
            'nfe' => 'NF-E',
            'requests' => 'Solicitações',
            'inventory' => 'Inventário',
            'reports' => 'Relatórios',
            'audit' => 'Auditoria',
            'users' => 'Usuários',
            'roles' => 'Papéis',
            default => ucfirst($module),
        };
    }

    /** Rótulo de cada ação. */
    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'view' => 'Visualizar',
            'create' => 'Criar',
            'update' => 'Editar',
            'delete' => 'Excluir',
            'move' => 'Movimentar',
            'adjust' => 'Ajustar',
            'transfer' => 'Transferir',
            'approve' => 'Aprovar',
            'separate' => 'Separar',
            'deliver' => 'Entregar',
            'execute' => 'Executar',
            'assign' => 'Atribuir',
            default => ucfirst($action),
        };
    }

    /**
     * Descreve uma permissão "recurso.acao" em linguagem natural.
     * Ex.: "products.delete" => "Excluir produtos".
     */
    public static function describe(string $permission): string
    {
        if (! str_contains($permission, '.')) {
            return ucfirst($permission);
        }

        [$module, $action] = explode('.', $permission, 2);

        // Ações que não levam objeto (view/execute/assign) têm frase própria.
        return match ($action) {
            'view' => "Visualizar " . self::moduleLabel($module),
            'create' => "Criar " . self::moduleLabel($module),
            'update' => "Editar " . self::moduleLabel($module),
            'delete' => "Excluir " . self::moduleLabel($module),
            'move' => "Movimentar " . self::moduleLabel($module),
            'adjust' => "Ajustar " . self::moduleLabel($module),
            'transfer' => "Transferir " . self::moduleLabel($module),
            'execute' => "Executar " . self::moduleLabel($module),
            'assign' => "Atribuir " . self::moduleLabel($module),
            default => self::actionLabel($action) . ' ' . self::moduleLabel($module),
        };
    }

    /**
     * Agrupa uma lista de nomes de permissão por módulo, mantendo a ordem
     * dos módulos definida em moduleLabel.
     *
     * @param iterable<string> $permissions
     * @return array<string, array{label: string, items: array<string, string>}>
     */
    public static function groupByModule(iterable $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $name = is_object($permission) ? $permission->name : $permission;
            if (! str_contains($name, '.')) {
                continue;
            }
            [$module, $action] = explode('.', $name, 2);
            $grouped[$module][$action] = $name;
        }

        $ordered = [];
        foreach (array_keys(self::modules()) as $module) {
            if (! isset($grouped[$module])) {
                continue;
            }
            ksort($grouped[$module]);
            $items = [];
            foreach ($grouped[$module] as $action => $name) {
                $items[$name] = self::actionLabel($action);
            }
            $ordered[$module] = [
                'label' => self::moduleLabel($module),
                'items' => $items,
            ];
        }

        return $ordered;
    }

    /** Lista de módulos conhecidos (ordem de exibição). */
    public static function modules(): array
    {
        return [
            'products' => true,
            'categories' => true,
            'subcategories' => true,
            'attributes' => true,
            'brands' => true,
            'manufacturers' => true,
            'units' => true,
            'warehouses' => true,
            'warehouse-types' => true,
            'sectors' => true,
            'stock' => true,
            'movements' => true,
            'suppliers' => true,
            'nfe' => true,
            'requests' => true,
            'inventory' => true,
            'reports' => true,
            'audit' => true,
            'users' => true,
            'roles' => true,
        ];
    }
}
