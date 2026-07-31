# 02 — Arquitetura

## Princípio: Clean Architecture + SOLID

O domínio não conhece o framework. A infraestrutura (Eloquent, Redis, Reverb) é
plugada via interfaces. A camada de aplicação orquestra casos de uso.

```
┌─────────────────────────────────────────────────────────────┐
│ INTERFACES (Transporte)                                       │
│  Http/Controllers/Api/V1 · Middleware · Requests · Resources  │
│  Channels (Reverb) · Routes                                    │
└───────────────┬───────────────────────────────────────────────┘
                │ usa
┌───────────────▼───────────────────────────────────────────────┐
│ APPLICATION (Casos de Uso)                                     │
│  Services · DTOs · Events · Jobs · Listeners · Policies        │
└───────────────┬───────────────────────────────────────────────┘
                │ depende de (interface)
┌───────────────▼───────────────────────────────────────────────┐
│ DOMAIN (Entidades/Regras)                                      │
│  Models (entidades) · Enums · RepositoryInterface · Value Obj.  │
└───────────────┬───────────────────────────────────────────────┘
                │ implementa
┌───────────────▼───────────────────────────────────────────────┐
│ INFRASTRUCTURE (Detalhes)                                       │
│  EloquentRepository · Models (Eloquent) · External (ERP/Whats)  │
└─────────────────────────────────────────────────────────────┘
```

## Camadas no código

| Camada | Namespace | Responsabilidade |
|--------|-----------|-----------------|
| Domain | `App\Domain` | `Enums\MovementType`, `Repositories\RepositoryInterface` (contratos) |
| Application | `App\Application` | `Services\*` (casos de uso), `DTOs\*`, `Events`, `Jobs`, `Policies` |
| Infrastructure | `App\Infrastructure` | `Repositories\EloquentRepository` + repositórios concretos |
| Interfaces | `App\Http` | Controllers, Middleware, Requests, Resources |
| Models | `App\Models` | Entidades Eloquent (detêm regras de estado) |
| Observers | `App\Observers` | `AuditObserver` (auditoria append-only) |
| Traits | `App\Traits` | `Auditable` (snapshots de auditoria) |

## Fluxos principais

1. **Requisição HTTP** → `ForceJsonResponse` (JSON + headers de segurança) →
   `throttle:api` (Rate Limit) → `auth:sanctum` + `EnsureUserIsActive` →
   `Gate/Policy` (RBAC) → **Controller** → **Service** (DTO) →
   **RepositoryInterface** → **EloquentRepository** → **Model**.
2. **Mutação de estoque** → `StockService::apply()` (transação atômica) grava
   `Movement` (Kardex) + atualiza `Product`/`StockBalance`.
3. **Auditoria** → `AuditObserver` dispara em created/updated/deleted e grava
   `AuditLog` (append-only, com IP, user-agent, before/after).

## Decisões técnicas

- **Sanctum (cookie SPA) em vez de JWT:** first-party SPA, CSRF nativo, sem
  gerenciar refresh tokens. JWT pode ser adicionado depois para integrações
  de terceiros (guard `api` já existe).
- **Repository Pattern:** `EloquentRepository` genérico usa
  `spatie/laravel-query-builder` para filtros/ordenação/include declarativos;
  subclasses só declaram `$searchable/$filterable/$sortable`.
- **Auditoria centralizada:** `Auditable` trait + `AuditObserver` — todo model
  rastreado ganha auditoria sem duplicar código. `AuditLog` é append-only.
- **Saldo por almoxarifado:** `stock_balances` desnormaliza os 6 saldos por
  produto×almoxarifado para leitura O(1); `products.current_stock` é o consolidado.
- **Reverb** para tempo real (notificações, dashboard ao vivo) — self-hosted,
  sem custo externo.
