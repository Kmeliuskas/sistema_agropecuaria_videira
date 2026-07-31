# 06 — Regras de Negócio

## Estoque (invariantes)

1. **Saldo disponível** = `current_stock − reserved_stock` (sempre ≥ 0).
   Garantido no `Product::booted` (saving) e em `StockBalance::recalcAvailable()`.
2. **Saldo por almoxarifado** (6 saldos): `available = current − reserved − blocked
   − in_conferencia − in_transit` (≥ 0).
3. **Saldo nunca negativo**: `max(0, saldo + delta)` — protege contra saídas/acertos
   que ultrapassem o disponível (alerta/log, não quebra).
4. **Custo médio ponderado** em entradas: `(saldoAnterior × custoMedio + qtd × custoUnit)
   / (saldoAnterior + qtd)`. Atualiza `average_cost` e `last_cost`.
5. **Kardex imutável**: cada `Movement` armazena `balance_before`/`balance_after`.
   Movimentações não são editadas — correções são novas movimentações (ajuste).

## Movimentação (MovementType)
- `ENTRY`/`TRANSFER_IN`/`RELEASE` → direção `in` (+saldo).
- `EXIT`/`TRANSFER_OUT`/`RESERVE` → direção `out` (−saldo).
- `ADJUST` → neutro, sinal do próprio valor (ganho + / perda −).

## Produto
- `internal_code` único. `barcode` indexado (leitura coletor).
- `control_batch`/`control_expiry`/`serialized` habilitam módulos respectivos.
- Exclusão é **soft delete** (recuperável); auditoria é append-only.

## Auditoria
- Toda mutação de model `Auditable` gera `AuditLog` com IP, user-agent, before/after.
- `AuditLog` nunca é atualizado/excluído (conformidade).

## Permissões (RBAC)
- 6 papéis; permissões no padrão `recurso.acao` (ex.: `products.create`, `stock.move`).
- `administrador` recebe wildcard `*` (todas). Demais recebem subconjuntos.
- `AuthController`/`EnsureUserIsActive` bloqueiam usuários inativos mesmo com token.

## Rate Limit / Segurança
- 120 req/min por usuário (ou IP) no grupo da API.
- Respostas sempre JSON com headers `X-Content-Type-Options`, `X-Frame-Options`.
- CSRF ativo para SPA (cookie `XSRF-TOKEN` enviado ao `/sanctum/csrf-cookie`).
