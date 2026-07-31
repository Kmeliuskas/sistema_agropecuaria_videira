# 10 — Front-end (Blade + Turbo + Alpine + Tailwind v4)

Este documento descreve a **interface web** do WMS: como ela é montada, como
funciona a navegação sem refresh, o sistema de estilos e — o mais importante —
os **macetes (gotchas)** que você precisa saber para criar ou editar telas sem
quebrar nada.

> Stack: Laravel Blade (server-rendered) + **Tailwind CSS v4** (CSS-first,
> `@theme`/`@utility`) + **Alpine.js** (interações) + **Turbo / Hotwire**
> (navegação sem refresh) + Vite.

---

## 1. Arquitetura da interface

```
resources/
  layouts/
    app.blade.php     ← shell da aplicação (sidebar + header + <main>)
    auth.blade.php    ← shell do login
  views/
    dashboard.blade.php
    products/   (index, show, form)
    sectors/    (index, form)
    warehouses/ (index)
    stock/      (index)
    movements/  (index)
    material_requests/ (index, create, show)
    catalogs/   (index — usado para os 6 catálogos)
    auth/       (login)
  css/app.css            ← design tokens + componentes (Tailwind v4)
  js/app.js              ← entry: tema dark, Alpine, Turbo
public/build/            ← assets compilados (não editar à mão)
```

Cada tela de módulo **estende `layouts.app`** e preenche as seções:

```blade
@extends('layouts.app')

@section('title', 'Produtos — WMS')      {{-- <title> da aba --}}
@section('page_title', 'Produtos')        {{-- título no header --}}

@section('content')
  {{-- corpo da tela --}}
@endsection

@push('scripts')                          {{-- JS opcional no fim --}}
  <script> ... </script>
@endpush
```

O `layouts.app` monta: `<aside>` (sidebar com menu), `<header>` (título +
botão hamburguer no mobile) e `<main>` (onde entra o `@yield('content')`).

---

## 2. Navegação sem refresh (Turbo) — O MACETE CENTRAL

O sistema usa **Turbo (Hotwire)** para trocar o conteúdo da página sem dar
refresh completo. Instalado via `hotwired-laravel/turbo-laravel` (composer) +
`@hotwired/turbo` (npm), inicializado em `resources/js/app.js`.

### Como funciona
- O Turbo intercepta cliques em `<a href>` e submits de `<form>` dentro da app.
- Em vez de recarregar, ele faz um `fetch` e troca o `<body>` (ação `advance`).
- A sidebar e o tema escuro **permanecem** (não recriam do zero de forma
  visível). O `animate-fade-in` no `<main>` dá a transição.

### ✅ O que FAZER
- **Deixe os links como `<a href="{{ route(...) }}">` normais.** O Turbo cuida
  do resto. Não precisa de `fetch`, `axios` ou `data-turbo` na maioria dos casos.
- **Forms POST/PUT/DELETE normais funcionam.** O Turbo envia e segue o
  `redirect()` do controller automaticamente (troca o conteúdo pela página
  destino). Ex.: aprovar solicitação, entregar, excluir setor.
- Para navegar via JS: `Turbo.visit('/produtos')`.

### ❌ O que NÃO FAZER (causa bug)
- **NUNCA use `Turbo.visit(url, { action: 'replace' })`** — o `action: 'replace'`
  corrompe o DOM e faz a sidebar sumir. Use sempre a navegação por clique ou
  `Turbo.visit(url)` puro. (Comprovado em teste: com `replace` o `<aside>`
  desaparece do DOM.)
- **Não coloque `x-data` no `<body>`.** O Turbo troca o `<body>`; um `x-data`
  ali conflita com o Alpine e gera estado inconsistente. O estado do drawer
  mobile (`sidebarOpen`) está no `<body>` **apenas porque o Turbo recria o body
  a cada navegação e ele volta a `false`** — funciona, mas prefira mover estado
  Alpine para `<div>`/componentes específicos se criar novos.
- **Não espere que cliques dentro de `x-show` fechado naveguem:** o link de
  "Produtos" fica dentro do submenu "Cadastros" (`x-show`). Funciona com clique
  real do usuário; em testes automatizados pode ser necessário abrir o submenu
  antes.
- **Links que DEVEM recarregar de verdade:** adicione `data-turbo="false"`
  (ex.: download de arquivo, logout se quiser reload, links externos).

### Verificando se o Turbo está ativo
No console do navegador: `typeof window.Turbo` deve retornar `"object"`.

---

## 3. Sistema de estilos (Tailwind v4 CSS-first)

Toda a estilização vem de `resources/css/app.css` (Tailwind v4, modo
CSS-first — **não há `tailwind.config.js`**). Tudo é definido em `@theme`.

### Tokens semânticos (USE ESTES, não hex puro)
Nunca escreva `bg-slate-800` ou `#1e293b` nos componentes. Use os tokens:

| Token | Uso |
|-------|-----|
| `bg-background` / `text-foreground` | fundo e texto base |
| `bg-surface` | cards, header, sidebar |
| `text-muted-foreground` | texto secundário |
| `bg-muted` | hover de linhas/botões |
| `bg-primary` / `text-primary-foreground` | cor de destaque / botão primário |
| `border-border` | todas as bordas |
| `bg-success` / `bg-danger` | verde/vermelho semânticos |

As cores são em **OKLCH** (melhor percepção) e o dark mode é `@custom-variant
dark` + classe `.dark` no `<html>`.

### Utilitários de componente (já prontos)
Definidos com `@utility` no CSS — use nas views:

- `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`
- `.input`, `.label`
- `.card` (container com borda + sombra)
- `.badge`, `.badge-success`, `.badge-muted`, `.badge-danger`
- `.num` (tabular-nums — use em colunas numéricas para alinhar dígitos)

Exemplo:
```blade
<a href="{{ route('products.create') }}" class="btn-primary">+ Novo</a>
<input type="text" name="search" class="input">
<div class="card p-5">...</div>
```

### Animações e acessibilidade (regras da skill ui-ux-pro-max)
- `animate-fade-in` (0.25s) já aplicado no `<main>` — transição de página.
- `:focus-visible` global com ring de 2px (acessibilidade).
- Bloco `@media (prefers-reduced-motion: reduce)` desliga animações p/ quem
  pediu menos movimento.
- `line-height: 1.5` no body; botões têm `active:scale-[0.97]` (press feedback).

### Regras de ouro do CSS
1. **Só tokens semânticos** nos componentes — nunca cor hardcoded (ex.: evite
   `bg-green-100` solto; prefira `.badge-success`). Onde já existe `bg-green-100`
   em badges de status (movements/products), está ok mas prefira migrar.
2. **Nunca edite `public/build/`** — é gerado por `npm run build` / `npm run dev`.
3. Após editar `app.css` ou `app.js`, rode `npm run dev` (HMR) ou `npm run build`.

---

## 4. Alpine.js (interações)

Usado para: menu da sidebar (submenus `x-show`), drawer mobile, formulário
dinâmico de solicitação (`mrForm()`), toggle de tema.

### Padrões
- Submenu: `x-data="{ open: {{ request()->routeIs('products.*') ? 'true':'false' }} }"`
  — o estado inicial vem do **server** (`routeIs`), então o grupo certo já abre
  após navegação Turbo.
- Drawer mobile: `x-data="{ sidebarOpen: false }"` no `<body>`; botão hamburguer
  faz `sidebarOpen = true`; overlay fecha com `@click="sidebarOpen = false"`.
- Formulário dinâmico (solicitação): `x-data="mrForm()"` + `<script>` com a
  função; itens em `<template x-for>`. Os `name` dos inputs são montados como
  `items[${index}][product_id]` para o backend ler como array.

### Macetes Alpine + Turbo
- Componentes Alpine **dentro do `<main>`** são recriados a cada navegação Turbo
  (o body é trocado) — isso é o esperado, não precisa re-iniciar nada.
- O Alpine já está `window.Alpine.start()` no `app.js`; não chame de novo.
- Scripts de máscara (dinheiro/inteiro) em `@push('scripts')` rodam no carregamento
  da view — mas **após navegação Turbo, o `@push('scripts')` da nova view NÃO
  re-executa automaticamente** se for o mesmo `<script>` (Turbo cache). Para JS
  que deve rodar toda navegação, ouvir `document.addEventListener('turbo:render', ...)`
  ou usar Alpine `x-init` (recomendado para lógica de view).

---

## 5. Máscaras de input (produtos)

Em `products/form.blade.php` há máscaras feitas à mão (sem lib):
- **Dinheiro** (`data-money`): digita `100000` → `1.000,00`; no submit converte
  `1.000,00` → `1000.00` antes de enviar.
- **Inteiro** (`data-integer`): remove tudo que não é dígito (bloqueia vírgula).

Se criar outro formulário com valores monetários, reaproveite os mesmos
atributos `data-money` / `data-integer` e o `<script>` do form de produto.

---

## 6. Responsividade

- Sidebar: `fixed` + `translate-x` no mobile, `static` em `lg:` (≥1024px).
- Botão hamburguer: `lg:hidden` (some no desktop).
- Grids: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3/4`.
- Tabelas largas: wrapper `overflow-x-auto` (evita scroll horizontal do body).
- `min-h-dvh` no body (altura correta em mobile, barra inferior inclusa).
- **Teste sempre em viewport mobile (375px)** ao editar a sidebar/layout.

---

## 7. Checklist para CRIAR uma nova tela

1. Crie `resources/views/<modulo>/<nome>.blade.php` com `@extends('layouts.app')`
   e as seções `title` / `page_title` / `content`.
2. Adicione a rota em `routes/web.php` (grupo `auth`) — use `route('<nome>.index')`.
3. Crie/ajuste o controller em `app/Http/Controllers/Web/` retornando
   `view('<modulo>.<nome>', [...])`.
4. Adicione o link no menu da sidebar (`layouts/app.blade.php`) dentro do
   `x-data` de submenu correspondente, usando `request()->routeIs('<nome>.*')`
   para manter o grupo aberto.
5. Use os utilitários `.btn-*`, `.input`, `.card`, `.badge-*` e tokens semânticos.
6. Rode `npm run build` (ou `npm run dev`) para compilar.
7. Teste: navegação Turbo (sem refresh), dark mode, mobile (drawer), filtros.

## 8. Checklist para EDITAR uma tela existente

- **Não quebre o `@extends('layouts.app')`** nem remova as seções.
- **Não troque tokens semânticos por hex/slate** — mantenha a consistência.
- **Não adicione `data-turbo="false"` sem motivo** — quebra a navegação instantânea.
- **Forms:** mantenha `@csrf` e `@method('PUT')`/`@method('DELETE')` quando
  editar/excluir (o Turbo precisa do CSRF).
- **Exclusões perigosas:** botões de excluir usam `onsubmit="return confirm(...)"`
  — mantenha a confirmação.
- Após editar, **build + teste no browser** (desktop e mobile).

---

## 9. Como rodar o front localmente

```bash
# Build de produção (gera public/build/)
npm run build

# OU dev com hot-reload
npm run dev

# Backend (em outro terminal)
php artisan serve --host=127.0.0.1 --port=8000
```

> ⚠️ **Sessão no browser:** o cookie de sessão é por host exato. Se logar via
> `127.0.0.1:8000` e depois acessar `localhost:8000`, a sessão **não persiste**
> (vai cair no login). Use sempre o **mesmo host**. Isso é normal em dev e não
> é bug do Turbo.

---

## 10. Usuário de teste

| Email | Senha | Papel |
|-------|-------|-------|
| admin@wms.local | Admin@123456 | administrador |
