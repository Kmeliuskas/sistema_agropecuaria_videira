# 01 — Requisitos

## 1. Requisitos Funcionais (RF)

### RF-01 Cadastro de Produtos
O sistema deve permitir o cadastro de produtos com: código interno, código de barras,
QR Code, nome, descrição, categoria, subcategoria, marca, modelo, unidade de medida,
estoques (mínimo/máximo/atual/reservado/disponível), custos (último/custo médio/venda),
tributação (NCM/CFOP/CST), controles (lote/validade/serialização), localização
(almoxarifado/rua/corredor/pratelera/nível/posição), imagem e anexos (manual, ficha técnica).

### RF-02 Catálogos Auxiliares
Categorias, Subcategorias, Marcas, Fabricantes, Unidades de Medida, Fornecedores,
Centros de Custo, Setores, Funcionários, Clientes, Transportadoras.

### RF-03 Controle de Estoque
Manter 6 saldos por produto×almoxarifado: atual, reservado, disponível, bloqueado,
em conferência, em trânsito.

### RF-04 Entradas
Por Compra, Nota Fiscal, Transferência, Produção, Devolução, Ajuste, Importação XML.
Toda entrada gera movimentação automática (Kardex).

### RF-05 Saídas
Por Consumo, Produção, Venda, Transferência, Quebra, Perda, Doação, Baixa Técnica.
Registrar usuário, data, hora, centro de custo, funcionário, motivo, observações.

### RF-06 Solicitação de Materiais
Fluxo: Solicitação → Aprovação → Separação → Conferência → Entrega → Finalização.
Cada etapa registra usuário/data/hora.

### RF-07 Transferência entre Almoxarifados
Múltiplos almoxarifados (Central, Filial, Produção, Obras).

### RF-08 Inventário
Geral, Parcial, Rotativo, por Categoria, por Localização, por Lote. Ao finalizar,
gera ajustes automáticos pelas diferenças.

### RF-09 Ajustes
Por Erro, Quebra, Perda, Roubo, Vencimento, Correção. Todos geram auditoria.

### RF-10 Controle de Lotes / Validade / Serial
Lotes (número, fornecedor, qtd, fabricação, validade, status). Alertas de validade
(90/60/30/15/7 dias e vencidos). Serial único com histórico de passagem.

### RF-11 Reserva de Estoque
Para Ordens de Produção, Projetos, Obras, Clientes, Manutenções.

### RF-12 Movimentações (Kardex)
Tipo, origem, destino, produto, quantidade, saldo anterior/posterior, usuário, data,
hora, centro de custo, observação.

### RF-13 Relatórios
Estoque atual, Kardex, Curva ABC, Entradas, Saídas, Inventário, Vencidos, Vencendo,
Sem movimentação, Consumo por setor, Consumo por funcionário, Histórico, Valor
financeiro, Abaixo do mínimo.

### RF-14 Auditoria
Registrar login, logout, cadastro, exclusão, alteração, movimentação, inventário,
transferências, ajustes — com IP, usuário, data, hora, antes e depois.

### RF-15 Permissões (RBAC)
Níveis: Administrador, Supervisor, Almoxarife, Comprador, Solicitante, Consulta.
Permissões configuráveis (padrão `recurso.acao`).

### RF-16 Notificações
Estoque mínimo, produtos vencendo, solicitações pendentes, inventário pendente,
transferências pendentes.

### RF-17 Dashboard
Indicadores (KPIs), gráficos (ApexCharts), giro de estoque, Curva ABC, produtos mais
movimentados, sem movimentação, abaixo do mínimo, vencendo/vencidos, últimas movimentações.

### RF-18 Integrações Futuras
TOTVS Protheus, SAP, Senior, Oracle, Sankhya, leitores de código de barras, coletores
Android, impressoras Zebra, balanças, NF-e, XML, API WhatsApp, Power BI.

## 2. Requisitos Não-Funcionais (RNF)

| ID | Requisito | Meta |
|----|-----------|------|
| RNF-01 | Performance | Índices em FKs; cache Redis para saldos/KPIs; paginação obrigatória; eager-loading controlado |
| RNF-02 | Escalabilidade | API stateless; filas Redis para importação/movimentações pesadas; Reverb para tempo real; Docker multi-stage |
| RNF-03 | Segurança | Sanctum+CSRF, RBAC (Policies/Gates), Rate Limit por rota, criptografia (AES-256) de dados sensíveis, sanitização, auditoria imutável |
| RNF-04 | Disponibilidade | Logs estruturados, health-check `/up`, migrations replayable, soft-deletes recuperáveis |
| RNF-05 | Manutenibilidade | Clean Architecture, SOLID, versionamento `/api/v1`, DTOs, testes (Pest/PHPUnit) |
| RNF-06 | Conformidade | Auditoria append-only (sem update/delete), trilha completa de quem/quando/o-que |
| RNF-07 | Usabilidade | UI responsiva, dark mode, pesquisa/filtros/paginação/ordenação/exportação em todas as telas (fase 2) |
