# WMS — Warehouse Management System

Sistema completo de Gestão de Almoxarifado (WMS) empresarial, modular, preparado
para integração com ERPs (TOTVS Protheus, SAP, Senior, Sankhya, Oracle).

## Stack

- **Backend:** Laravel 13 (PHP 8.4), API REST, Sanctum (SPA cookie+CSRF), Clean Architecture, Repository Pattern, Services, Events, Queues, Policies, Middlewares, Observers.
- **Frontend (fase 3):** Angular 21, Angular Material, Bootstrap 5, TailwindCSS, RxJS, Signals, Lazy Loading, Guards, Interceptors.
- **Banco:** MySQL 8.4 (3FN).
- **Infra:** Docker, Redis, Laravel Reverb (WebSockets), Filas, Exportação PDF/Excel.
- **Qualidade:** Auditoria append-only, RBAC configurável, Rate Limit, Logs, Criptografia.

## Documentação

| Arquivo | Conteúdo |
|---------|----------|
| [01-requisitos.md](01-requisitos.md) | Requisitos funcionais e não-funcionais |
| [02-arquitetura.md](02-arquitetura.md) | Clean Architecture, camadas, decisões técnicas |
| [03-banco-de-dados.md](03-banco-de-dados.md) | DER, tabelas, relacionamentos, índices |
| [04-fluxos-navegacao.md](04-fluxos-navegacao.md) | Fluxos de navegação por módulo |
| [05-casos-de-uso.md](05-casos-de-uso.md) | Casos de uso principais |
| [06-regras-de-negocio.md](06-regras-de-negocio.md) | Regras de negócio e invariantes |
| [07-api.md](07-api.md) | Contratos REST, versionamento, DTOs, paginação |
| [10-frontend.md](10-frontend.md) | Front-end: Blade + Turbo + Alpine + Tailwind v4, macetes para criar/editar telas |
| [11-deploy-operacao.md](11-deploy-operacao.md) | Deploy Dokploy/Docker, ordem de seeders, phpMyAdmin, troca de senha, troubleshooting |

## Status da implementação

### Fase 1 — Fundação e fatia vertical (13 testes verdes)
- Fundação Laravel (Clean Architecture, Sanctum+CSRF, ForceJson, Rate Limit, Auditoria).
- RBAC (6 níveis, permissões granulares, seeds).
- Fatia vertical: **Produtos** (CRUD completo) e **Estoque/Movimentações** (Kardex, 6 saldos, custo médio, transferências).
- Catálogos: Almoxarifados, Unidades, Categorias, Subcategorias, Marcas, Fabricantes, Fornecedores.
- Migrations completas (50+ tabelas) incluindo stubs das fases futuras.

### Fase 2 — Operação (21 testes verdes)
- **Dashboard**: KPIs (totais, estoque baixo, Curva ABC por valor, pendências), alertas de validade de lotes (7-90 dias e vencidos).
- **Solicitação de Materiais**: ciclo de 6 etapas (`solicitado → aprovado → separado → conferido → entregue → finalizado`), com validação de disponibilidade e consumo de estoque via Kardex na entrega.
- **Inventário**: 6 modalidades (`general`, `partial`, `rotating`, `by_category`, `by_location`, `by_lot`), contagem item a item (book × counted → diferença) e **ajuste automático** de estoque no finalize (gera `MovementType::ADJUST`).
- **Lotes / Serial Number**: modelos + helpers de validade (alertas 7-90d).

Módulos previstos para Fase 3+: Transferências (UI/fluxo), Ajustes (6 motivos), Reservas, Relatórios (13) + PDF/Excel, Notificações (Reverb/email/WhatsApp), Frontend Angular 21.

### Fase 2b — Transferências e Ajustes no backend (9 testes verdes)
- **Transferências** entre almoxarifados: modelo `Transfer`+`TransferItem`, enum `TransferStatus` (`pending → in_transit → received`, `cancelled` terminal), fluxo de 2 pernas sobre o `StockService` (single source of truth do Kardex):
  - `ship` (pending→in_transit): aplica `MovementType::TRANSFER_OUT` na **origem** (sem crédito antecipado no destino).
  - `receive` (in_transit→received): aplica `MovementType::TRANSFER_IN` no **destino** pela quantidade recebida (suporta parcial). Fecha a transferência.
  - Regra: origem ≠ destino; validação de disponibilidade na origem; código `TR-AAMMDD-XXXX`.
- **Ajustes** de estoque (6 motivos: `erro`, `quebra`, `perda`, `roubo`, `vencimento`, `correcao`): modelo `Adjustment` (Auditable+SoftDeletes), enum `AdjustmentReason`. Criação aplica `MovementType::ADJUST` via `StockService` (direção `neutral` → usa o sinal do valor: perda negativa, ganho positivo) e registra `balance_before`/`balance_after` + `movement_id` para auditoria. Permissão `stock.adjust`; código `AD-AAMMDD-XXXX`.
- Total backend: **30 testes verdes / 91 assertions**, 73 rotas `/api/v1`, Swagger 27 paths.

### Fase 3 — Frontend Angular 21 (em andamento)
- Scaffold em `frontend/` (standalone components, signals, Material, dark mode).
- Fundação: interceptors de auth/CSRF (cookie Sanctum), env tipado, modelos da API.
- Módulos: Auth (login SPA), Dashboard (ApexCharts + Curva ABC + alertas), Solicitação de Materiais (6 etapas), Inventário (contagem), Transferências, Ajustes.
