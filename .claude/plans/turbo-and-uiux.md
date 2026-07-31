# Plano: Navegação sem refresh (Turbo) + ajustes de UI/UX (skill ui-ux-pro-max)

## Contexto

Projeto **Laravel 13 + Blade + Tailwind v4 (CSS-first) + Alpine.js**. Hoje cada
`<a href>` dispara GET ao servidor e devolve HTML novo → refresh completo da página.
O objetivo é (1) navegar entre rotas **sem refresh** da página inteira e (2) aplicar
melhorias de layout conforme as regras da skill `ui-ux-pro-max` (acessibilidade,
responsividade, animação, tipografia/espaçamento).

Todos os controllers Web retornam `view()` (full-page Blade) e os POSTs fazem
`redirect()`. Isso é **compatível nativamente com Turbo** — nenhuma mudança em
controllers é necessária.

## Decisões

- **Navegação:** Turbo (Hotwire) — interferon de cliques, troca só `<main>`.
  Mantém Tailwind + Alpine. É o padrão Laravel atual e a menor mudança sobre o
  Blade existente.
- **CSS:** aplicar regras da skill `ui-ux-pro-max` (prioridades 1, 5, 6, 7).
- O Alpine.js convive com Turbo: a sidebar fica **fora** da área trocada, então o
  `x-data` dos menus persiste. Precisamos só re-iniciar componentes Alpine que
  nasçam DENTRO do `<main>` (o `@app.js` já faz `Alpine.start()`; Turbo dispara
  `turbo:render` e o Alpine reapplica `x-data` automaticamente em v4+).

## Parte A — Turbo (sem refresh)

### A1. Instalar dependência
- `composer require hotwired/turbo-laravel`
- Publicar config (opcional): `php artisan vendor:publish --provider="Hotwired\TurboLaravel\TurboServiceProvider"` — não obrigatório para o básico.

### A2. Injetar o Turbo (layout)
Em `resources/views/layouts/app.blade.php`:
- Envolver o conteúdo da `<main>` num elemento com `id` estável para o Turbo
  trocar: o `<main>` já existe; adicionar `data-turbo-frame` não é necessário —
  Turbo por padrão troca o `<body>` inteiro, mas queremos preservar a sidebar.
  Para preservar sidebar + header, marcamos a `<main>` como o alvo usando
  **Turbo Frames** ou simplesmente deixamos o Turbo trocar o body e garantimos
  que a sidebar seja re-renderizada igual (ela é estática, sem estado perdido
  além do `x-data` dos menus — que reinit).

  Abordagem escolhida (mais simples e robusta): **Turbo padrão (troca o body)**.
  - Vantagem: zero mudança estrutural; o `animate-fade-in` no `<main>` vira a
    transição de página.
  - Para manter o menu da sidebar no estado aberto entre navegações, o `x-data`
    já usa `request()->routeIs(...)` (server-side) → reabre o grupo correto sozinho.
  - Adicionar `@vite` já está presente; o Turbo é carregado via blade directive
    `@turbo_stream`/script que o pacote injeta. O pacote `turbo-laravel` injeta
    automaticamente o `<meta name="turbo-root">` e o script via `@turbo` ou
    automaticamente no `<head>` quando usamos `@vite`. Na prática basta o
  `composer require` + o pacote auto-injecta `<script type="module">` do Turbo
  no `<head>` através do `TurboServiceProvider` (ele escuta `composing`/`compose`).
  Caso não injete, adicionamos manualmente o import no `resources/js/app.js`.

### A3. Ajustes no JS (`resources/js/app.js`)
- Importar Turbo: `import * as Turbo from '@hotwired/turbo';` (ou usar o auto-inject).
- Re-aplicar foco/tema após `turbo:render` se necessário (tema já é aplicado
  antes da pintura via IIFE; não precisa re-rodar).
- Garantir que cliques em `<a>` com `data-turbo="false"` (ex.: logout POST, links
  externos) não sejam interceptados — o logout é `method="POST"` via `<form>`,
  Turbo trata formulários POST normalmente e segue o redirect.

### A4. Exceções (não usar Turbo)
- Logout: `<form method="POST" action="{{ route('logout') }}">` — Turbo envia e
  segue redirect para `/login`. OK. Se quiser garantir reload, `data-turbo="false"`.
- Links de download/externos: adicionar `data-turbo="false"` onde aplicável.

## Parte B — Ajustes de CSS (skill ui-ux-pro-max)

### B1. Acessibilidade (prioridade 1)
- `resources/css/app.css` base layer: adicionar `:focus-visible` global com ring
  visível (2–4px) em todos os links/botões interativos.
- Botões de ícone (tema/sair) já têm `aria-label`/title. Garantir foco ring no
  botão de tema (já tem `focus-visible` via `.btn`? não — é botão custom). Adicionar
  `focus-visible:ring-2` ao botão de toggle de tema no layout.
- Garantir contraste 4.5:1: `--color-muted-foreground` claro = oklch(52%) e escuro
  = oklch(68%) — ambos passam em superfície clara/escura. Verificar `text-muted-foreground/70`
  (usado em empty states) — reduzir opacidade só em texto não essencial.

### B2. Responsividade/scroll (prioridade 5)
- Sidebar: tornar colapsável no mobile. Adicionar `lg:` fixa e esconder abaixo;
  ou usar drawer com Alpine (`x-data` no `<aside>` + botão hamburguer no header).
  - Adicionar botão hamburguer no `<header>` visível só em `<lg`.
  - `min-h-screen` → `min-h-dvh` (mobile, evita barra inferior).
  - `overflow-x-auto` nas tabelas (já existe) evita scroll horizontal do body.
  - Container de conteúdo: respeitar `max-w` em telas grandes onde fizer sentido.

### B3. Animações/transição (prioridade 7)
- `animate-fade-in` já 0.25s (dentro de 150–300ms) ✓.
- Adicionar `@media (prefers-reduced-motion: reduce)` desligando animações.
- Transição de página Turbo: o `animate-fade-in` no `<main>` já serve; opção de
  crossfade via `turbo:before-render`. Manter simples: fade-in.
- Botões já têm `active:scale-[0.97]` (press feedback) ✓.

### B4. Tipografia/espaçamento (prioridade 6)
- Escala tipográfica: já usa tokens; garantir `line-height 1.5` no body (adicionar).
- Espaçamento 4/8pt: já coerente nos px/py.
- Números tabulares em colunas de estoque (já usa `number_format` BR; adicionar
  `tabular-nums` nas `<td>` de números para evitar shift).
- `font-size` base 16px (body já herda) ✓.

## Arquivos afetados
- `composer.json` / vendor (Turbo via composer)
- `resources/js/app.js` (import Turbo)
- `resources/views/layouts/app.blade.php` (sidebar drawer, hamburguer, foco no tema,
  `min-h-dvh`)
- `resources/css/app.css` (focus global, reduced-motion, line-height, tabular-nums)
- `resources/views/products/index.blade.php` (exemplo: `tabular-nums`, foco) — e
  aplicar padrão às outras views de tabela conforme necessário.

## Fora de escopo
- Reescrita para SPA Vue/React.
- Livewire.
- Mudanças em controllers (não necessárias para Turbo).

## Ordem de execução
1. A1 (composer require turbo-laravel) + verificar auto-inject no head.
2. A3 (app.js import Turbo) + A2 (layout).
3. B1→B4 no CSS.
4. B2 no layout (drawer/hamburguer) + B4 nas views de tabela.
5. Subir dev server (preview_start / npm run dev) e validar: navegar entre rotas
   sem refresh, dark mode, menu mobile, reduced-motion.
