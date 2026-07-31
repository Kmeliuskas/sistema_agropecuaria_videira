# 03 — Banco de Dados (MySQL 8.4, 3FN)

## Diagrama Entidade-Relacionamento (resumo)

```
users ──< audit_logs
users ──1 employee (FK opcional)
warehouses 1──< stock_balances >──1 products
products ──1 category / subcategory / brand / manufacturer / unit / warehouse
products ──< movements >── warehouses (origem/destino)
products ──< product_locations >── warehouses
products ──< product_attachments
products ──< lots >── supplier
products ──< serial_numbers
material_requests ──< material_request_items >── products
transfers ──< transfer_items >── products
inventories ──< inventory_items >── products
adjustments >── products (balance before/after)
stock_reservations >── products
cost_centers 1──< sectors
cost_centers 1──< movements
employees 1──< sectors
suppliers 1──< movements
customers / carriers (catálogos)
```

## Tabelas da Fase 1 (implementadas + testadas)

| Tabela | Papel | Chaves |
|--------|-------|--------|
| `users` | Usuários (SoftDeletes, Sanctum, Roles) | PK id, FK employee_id |
| `audit_logs` | Auditoria append-only | PK id, FK user_id, morph(auditable) |
| `warehouses` | Almoxarifados | PK id, unique code |
| `units` | Unidades de medida | PK id, unique code |
| `categories` / `subcategories` | Classificação | PK, FK category_id |
| `brands` / `manufacturers` | Marcas/Fabricantes | PK id, unique code |
| `suppliers` | Fornecedores | PK id, unique code |
| `cost_centers` / `sectors` | Imputação | PK, FK cost_center_id |
| `employees` | Funcionários | PK id, unique code |
| `products` | Cadastro de produtos | PK id, unique internal_code, FKs |
| `product_locations` | Localização física | PK, unique(product,warehouse) |
| `stock_balances` | 6 saldos por prod×almox | PK, unique(product,warehouse) |
| `movements` | Kardex | PK, FKs prod/warehouse/user/cc/supplier |

## Stubs das fases futuras (migrations criadas)

`customers`, `carriers`, `lots`, `serial_numbers`, `material_requests` +
`material_request_items`, `transfers` + `transfer_items`, `inventories` +
`inventory_items`, `adjustments`, `stock_reservations`, `product_attachments`,
`notifications` (tabela do Laravel Notifications).

## Índices e FKs

- FKs em todas as relações (cascade ou nullOnDelete conforme semântica).
- Índices em: `products(category_id, active)`, `products(name)`,
  `stock_balances(warehouse_id)`, `movements(product_id, occurred_at)`,
  `movements(warehouse_id, type)`, `audit_logs(auditable_type, auditable_id)`,
  `audit_logs(created_at)`, `lots(expires_at)`.
- Chaves únicas curtas (limit 64 chars / 3072 bytes) — evitar compostas longas.

## Seeds

Rodados pelo `DatabaseSeeder` **nesta ordem** (não inverta):

```
RolesAndPermissionsSeeder  →  CatalogSeeder  →  AdminUserSeeder
```

- `RolesAndPermissionsSeeder`: 6 papéis + permissões granulares (`recurso.acao`).
  O papel `administrador` recebe `Permission::all()` (wildcard). **Essencial
  rodar antes do admin** — sem ele, o `DashboardController` exige
  `reports.view` e o login dá **403 Forbidden**.
- `CatalogSeeder`: unidades, categorias, marcas, almoxarifados iniciais.
- `AdminUserSeeder`: usuário `admin@wms.local` / `Admin@123456` (trocar em prod).

> ⚠️ Sempre use `php artisan db:seed` (roda tudo na ordem). Se rodar
> `--class=AdminUserSeeder` isolado, o admin é criado mas sem permissões → 403
> no primeiro login. Detalhes em `docs/11-deploy-operacao.md`.
