# 04 — Fluxos de Navegação

## Login (SPA)
`/login` (POST email+senha) → cookie de sessão + CSRF → `/me` (perfil+permissões)
→ telas protegidas por Guard. Logout revoga sessão e audita.

## Cadastro de Produto
Lista (`/products`) → Novo → Form (abaas: Geral, Controle, Custos, Tributação,
Localização, Anexos) → Validar → `POST /products` → Kardex inicial → Volta à lista.

## Movimentação (Kardex)
`POST /movements` { product_id, type, quantity, warehouse_id, unit_cost, reason,
cost_center_id, employee_id } → `StockService` (transação) → grava Movement +
atualiza saldos → dispara evento/notificação.

## Solicitação de Materiais (Fase 2)
```
Solicitação (usuário) → Aprovação (supervisor) → Separação (almoxarife)
→ Conferência (almoxarife) → Entrega (almoxarife) → Finalização
```
Cada transição: valida permissão, registra usuário/data/hora, gera movimentação
ao separar/entregar.

## Transferência entre Almoxarifados (Fase 2)
Origem → destino. TRANSFER_OUT no origem + TRANSFER_IN no destino (ou única
movimentação com warehouse_destination_id). Status: pending → in_transit →
received.

## Inventário (Fase 2)
Criar (tipo) → Contagem (book vs counted) → Diferença → Finalizar →
`StockService` gera ajustes automáticos (Movement type=adjust) por item com diff.

## Ajustes (Fase 2)
Motivo (erro/quebra/perda/roubo/vencimento/correção) → ±quantidade →
Movement(adjust) + `AuditLog` + `adjustments`.

## Navegação geral (todas as telas)
Pesquisa global · Filtros laterais · Paginação · Ordenação por coluna ·
Exportação Excel/PDF · Impressão · Dark Mode · Responsividade (fase 2 — Angular).
