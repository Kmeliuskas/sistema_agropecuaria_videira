# 05 — Casos de Uso

## Catálogo (Fase 1 — implementado)
- **UC-01** Cadastrar Produto (admin/almoxarife): RF-01.
- **UC-02** Consultar/Listar Produtos com filtros e paginação (qualquer perfil com `products.view`).
- **UC-03** Atualizar Produto (admin/almoxarife) — auditoria de before/after.
- **UC-04** Inativar/Excluir Produto (soft delete + auditoria).
- **UC-05** Manter catálogos (unidades, categorias, marcas, fornecedores...).

## Estoque / Movimentação (Fase 1 — implementado)
- **UC-06** Registrar Entrada (compra/NF/produção/devolução/ajuste) → Kardex + saldo.
- **UC-07** Registrar Saída (consumo/produção/venda/quebra/perda/doação/baixa) → Kardex.
- **UC-08** Consultar saldos por almoxarifado (6 saldos).
- **UC-09** Transferir entre almoxarifados (TRANSFER_OUT/IN).

## RBAC (Fase 1 — implementado)
- **UC-10** Autenticar via SPA (Sanctum cookie + CSRF).
- **UC-11** Verificar permissão por ação (Gate/Policy `recurso.acao`).
- **UC-12** Auditar login/logout/cadastro/alteração/exclusão/movimentação.

## Próximas fases (esqueleto pronto)
- **UC-13** Solicitação de Materiais (6 etapas).
- **UC-14** Inventário (6 modalidades) + ajuste automático.
- **UC-15** Ajustes manuais (6 motivos).
- **UC-16** Lotes / Validade / Serial.
- **UC-17** Reservas de estoque.
- **UC-18** Relatórios (13) + Dashboard (KPIs, Curva ABC, ApexCharts).
- **UC-19** Notificações (estoque mínimo, vencimento, pendências).
