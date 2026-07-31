# 07 — API REST

## Convenções
- **Base:** `/api/v1` (versionamento no path).
- **Formato:** JSON (`Accept: application/json` forçado por `ForceJsonResponse`).
- **Auth:** Sanctum cookie SPA. Fluxo: `GET /sanctum/csrf-cookie` → `POST /api/v1/login`
  (com `X-XSRF-TOKEN`) → sessão. Rotas protegidas usam `auth:sanctum`.
- **Paginação:** `?per_page=15`; resposta `{ data, links, meta }`.
- **Filtros/Ordenação/Include:** via `spatie/laravel-query-builder`
  (`?filter[search]=`, `?filter[col]=`, `?sort=-created_at`, `?include=category`).
- **Documentação:** Swagger/OpenAPI em `/api/documentation` (l5-swagger).
- **Erros:** 401 (não autenticado), 403 (sem permissão), 422 (validação),
  429 (rate limit), 500 (erro interno).

## Endpoints (Fase 1)

### Autenticação
| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/v1/login` | Autenticar (email+senha) |
| GET | `/api/v1/me` | Perfil + roles + permissões |
| POST | `/api/v1/logout` | Encerrar sessão |

### Produtos / Catálogos
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/api/v1/products` | products.view | Listar (filtros/paginação) |
| POST | `/api/v1/products` | products.create | Criar |
| GET | `/api/v1/products/{id}` | products.view | Detalhar |
| PUT/PATCH | `/api/v1/products/{id}` | products.update | Atualizar |
| DELETE | `/api/v1/products/{id}` | products.delete | Excluir (soft) |
| GET/POST/PUT/DELETE | `/api/v1/warehouses` | warehouses.* | Almoxarifados |
| GET/POST/PUT/DELETE | `/api/v1/units` | categories.view/create | Unidades |
| GET/POST/PUT/DELETE | `/api/v1/categories` | categories.* | Categorias |
| GET/POST/PUT/DELETE | `/api/v1/subcategories` | categories.* | Subcategorias |
| GET/POST/PUT/DELETE | `/api/v1/brands` | categories.* | Marcas |
| GET/POST/PUT/DELETE | `/api/v1/manufacturers` | categories.* | Fabricantes |
| GET/POST/PUT/DELETE | `/api/v1/suppliers` | suppliers.* | Fornecedores |

### Estoque / Movimentação
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/api/v1/movements` | movements.view | Kardex (filtros: type, product, warehouse, periodo) |
| POST | `/api/v1/movements` | stock.move | Registrar entrada/saída/ajuste/transferência |
| GET | `/api/v1/movements/{id}` | movements.view | Detalhe da movimentação |
| GET | `/api/v1/stock-balances` | stock.view | Saldos (6 saldos) por prod×almox |
| GET | `/api/v1/stock-balances/{id}` | stock.view | Saldo específico |

## DTO de Movimentação (entrada)
```json
{
  "product_id": 12,
  "type": "entry",
  "quantity": 50,
  "warehouse_id": 1,
  "unit_cost": 10.00,
  "reason": "compra",
  "source_type": "purchase",
  "cost_center_id": 3,
  "employee_id": 5,
  "supplier_id": 2,
  "document_number": "NF-12345",
  "observation": "Recebimento mensal"
}
```
`type` ∈ {entry, exit, transfer_in, transfer_out, adjust, reserve, release} (enum).

## Resposta de Produto (Resource)
Veja [`ProductResource`](../../app/Http/Resources/ProductResource.php): internal_code,
name, categoria, estoques (current/reserved/available/min/max), custos, tributação,
localização, `is_below_min`, timestamps. Relações via `?include=category,unit`.

## Endpoints (Fase 2)

### Dashboard
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/api/v1/dashboard` | stock.view | KPIs agregados. **Envelopado em `data`** (como os recursos únicos). Shape: `data.totals`, `data.stock_alerts` (`count`, `items[]`), `data.abc_curve` (`total_value`, `classes.A/B/C.{value,count}`), `data.pending_requests` (`count`, `by_status{}`), `data.active_inventories` (`count`, `items[]`), `data.movements_30d` (`entries`, `exits`) |
| GET | `/api/v1/dashboard/lots/expiring-soon` | stock.view | Lotes com validade entre 7 e 90 dias (coleção envelopada) |
| GET | `/api/v1/dashboard/lots/expired` | stock.view | Lotes já vencidos (coleção envelopada) |

Curva ABC (`abc_curve.classes`): A = top 80% do valor (valor = current_stock × average_cost), B = 80–95%, C = restante. Cada classe traz `value` (soma em R$) e `count` (nº de SKUs).

### Solicitação de Materiais (ciclo de 6 etapas)
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET/POST | `/api/v1/material-requests` | requests.view / requests.create | Listar / criar |
| GET | `/api/v1/material-requests/{id}` | requests.view | Detalhar (itens aninhados) |
| DELETE | `/api/v1/material-requests/{id}` | requests.view | Excluir (soft) |
| POST | `/api/v1/material-requests/{id}/approve` | requests.approve | Aprovar (valida disponibilidade) |
| POST | `/api/v1/material-requests/{id}/separate` | requests.separate | Separar |
| POST | `/api/v1/material-requests/{id}/check` | requests.separate | Conferir |
| POST | `/api/v1/material-requests/{id}/deliver` | requests.deliver | Entregar (consome estoque via Kardex) |
| POST | `/api/v1/material-requests/{id}/finish` | requests.view | Finalizar |
| POST | `/api/v1/material-requests/{id}/cancel` | requests.view | Cancelar (terminal) |

Fluxo: `solicitado → aprovado → separado → conferido → entregue → finalizado`
(cancelado é terminal alternativo). `code` é gerado automaticamente se omitido.

### Inventário (6 modalidades + ajuste automático)
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET/POST | `/api/v1/inventories` | inventory.view / inventory.create | Listar / criar (gera itens por modalidade) |
| GET | `/api/v1/inventories/{id}` | inventory.view | Detalhar (itens book×counted) |
| DELETE | `/api/v1/inventories/{id}` | inventory.view | Excluir (soft) |
| POST | `/api/v1/inventories/{id}/start` | inventory.execute | Iniciar contagem (draft→in_progress) |
| POST | `/api/v1/inventories/{id}/count` | inventory.execute | Apontar item (book×counted→diferença) |
| POST | `/api/v1/inventories/{id}/finalize` | inventory.execute | Finalizar + ajuste automático de estoque |
| POST | `/api/v1/inventories/{id}/cancel` | inventory.execute | Cancelar (draft) |

Modalidades `type` ∈ {general, partial, rotating, by_category, by_location, by_lot}.
No `finalize`, itens com diferença geram `MovementType::ADJUST` no Kardex (entrada ou
saída) zerando o desvio. `code` é gerado automaticamente se omitido.

### Transferências entre almoxarifados (2 pernas)
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET/POST | `/api/v1/transfers` | stock.view / stock.transfer | Listar / criar (pending) |
| GET | `/api/v1/transfers/{id}` | stock.view | Detalhar (itens) |
| DELETE | `/api/v1/transfers/{id}` | stock.transfer | Excluir (soft) |
| POST | `/api/v1/transfers/{id}/ship` | stock.transfer | Embarcar (pending→in_transit): aplica `TRANSFER_OUT` na **origem** |
| POST | `/api/v1/transfers/{id}/receive` | stock.transfer | Receber (in_transit→received): aplica `TRANSFER_IN` no **destino** (suporta parcial via `items[].quantity_received`) |
| POST | `/api/v1/transfers/{id}/cancel` | stock.transfer | Cancelar (pending) |

Regra: origem ≠ destino; `ship` valida disponibilidade na origem; `code` automático (`TR-AAMMDD-XXXX`).
O destino só é creditado no `receive`, então o saldo fica "em trânsito" entre as pernas.

### Ajustes de estoque (6 motivos)
| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET/POST | `/api/v1/adjustments` | stock.view / stock.adjust | Listar / criar |
| GET | `/api/v1/adjustments/{id}` | stock.view | Detalhar |

`reason` ∈ {erro, quebra, perda, roubo, vencimento, correcao}. `quantity` com sinal:
positivo acrescenta estoque, negativo consome. Criação aplica `MovementType::ADJUST`
via `StockService` e registra `balance_before`/`balance_after`/`movement_id`. `code`
automático (`AD-AAMMDD-XXXX`).

## Envelopamento de resposta
Recursos únicos (`show`, `store`, transições) são envelopados em `data`
(`{"data": {...}}`); coleções (`index`) seguem `{ data, links, meta }`. O Kardex
(`movements`) e saldos também usam esse padrão do `JsonResource`.
