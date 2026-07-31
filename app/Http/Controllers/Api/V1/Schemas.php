<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OAT;

/**
 * Schemas OpenAPI reutilizáveis (referenciados via #/components/schemas/*).
 */
class Schemas {}

#[OAT\Schema(schema: 'Product', title: 'Produto', required: ['internal_code', 'name', 'unit_id'])]
class ProductSchema
{
    #[OAT\Property(property: 'id', type: 'integer', example: 1)]
    #[OAT\Property(property: 'internal_code', type: 'string', example: 'PROD-0001')]
    #[OAT\Property(property: 'barcode', type: 'string', nullable: true, example: '7890000000001')]
    #[OAT\Property(property: 'name', type: 'string', example: 'Parafuso Hexagonal 10mm')]
    #[OAT\Property(property: 'category', type: 'object', nullable: true)]
    #[OAT\Property(property: 'unit', type: 'object', nullable: true)]
    #[OAT\Property(property: 'min_stock', type: 'number', example: 10)]
    #[OAT\Property(property: 'max_stock', type: 'number', example: 500)]
    #[OAT\Property(property: 'current_stock', type: 'number', example: 120)]
    #[OAT\Property(property: 'reserved_stock', type: 'number', example: 0)]
    #[OAT\Property(property: 'available_stock', type: 'number', example: 120)]
    #[OAT\Property(property: 'last_cost', type: 'number', example: 1.5)]
    #[OAT\Property(property: 'average_cost', type: 'number', example: 1.6)]
    #[OAT\Property(property: 'sale_price', type: 'number', example: 3.0)]
    #[OAT\Property(property: 'ncm', type: 'string', nullable: true, example: '7318.15.00')]
    #[OAT\Property(property: 'control_batch', type: 'boolean', example: false)]
    #[OAT\Property(property: 'control_expiry', type: 'boolean', example: false)]
    #[OAT\Property(property: 'serialized', type: 'boolean', example: false)]
    #[OAT\Property(property: 'active', type: 'boolean', example: true)]
    #[OAT\Property(property: 'is_below_min', type: 'boolean', example: false)]
    public string $internal_code;
}

#[OAT\Schema(schema: 'User', title: 'Usuário')]
class UserSchema
{
    #[OAT\Property(property: 'id', type: 'integer', example: 1)]
    #[OAT\Property(property: 'name', type: 'string', example: 'Administrador')]
    #[OAT\Property(property: 'email', type: 'string', example: 'admin@wms.local')]
    #[OAT\Property(property: 'roles', type: 'array', items: new OAT\Items(type: 'string'), example: ['administrador'])]
    #[OAT\Property(property: 'permissions', type: 'array', items: new OAT\Items(type: 'string'), example: ['products.view'])]
    public string $name;
}
