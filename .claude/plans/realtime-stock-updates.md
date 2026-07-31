# Tempo real: atualização de estoque ao vivo

## Objetivo
Quando o estoque de um produto muda (ex.: ao **entregar** uma solicitação), toda tela aberta que mostra aquele produto reflete o novo valor **sem refresh** — resolvendo o caso das 2 abas (uma aberta antes, outra fazendo o fluxo).

## Por que é viável com pouco código
O projeto **já tem a tubulação de tempo real montada**, só desconectada:
- **Reverb** (websockets) roda como container no Dokploy (`wms_reverb`) e está no `.env` local (`REVERB_*`).
- O pacote **Hotwired Turbo** (`hotwired-laravel/turbo-laravel`) traz o trait `Broadcasts` — um model com esse trait **auto-dispara um broadcast via websocket sempre que é salvo** (update/insert), usando `TurboStreamBroadcast` (que o `turbo-echo-stream-source` já escuta no JS).
- Existe o custom element **`<turbo-echo-stream-source>`** em `resources/js/elements/turbo-echo-stream-tag.js` que, dado um canal, recebe o stream e aplica o `<turbo-stream action="replace" target="...">` na DOM. **Ninguém o importa hoje**, e o `window.Echo` **nunca é inicializado**.

Então o trabalho é *ligar* o que já existe, não escrever websocket do zero.

## Escopo (confirmado com o usuário)
- **Todas as telas** que mostram estoque: `/solicitacoes` (index + show), `/estoque`, detalhe de `/produtos/{id}`, e o card de estoque do Dashboard.
- **Canal privado por papel**: `private-stock.{productId}` com `authorize()` checando permissão (`stock.view` ou `requests.view`).

## Plano de implementação

### 1. Model `Product` — auto-broadcast ao salvar
`app/Models/Product.php`:
- `use HotwiredLaravel\TurboLaravel\Models\Broadcasts;`
- `use Broadcasts;` no model.
- Propriedade: `protected $broadcasts = ['update' => 'private-stock'];`
  - O Turbo converte `private-stock` (model) em `private-stock.{id}` automaticamente via `broadcastChannel()`/`toChannels()`.
- Isso faz o `Product::save()` (que o `deliver` já chama em `recalcAvailable()` / `current_stock`) disparar um `TurboStreamBroadcast` com `action=update`, `target=dom_id($product)` e o HTML do modelo.

### 2. Canal privado autorizado — `routes/channels.php` (criar)
```php
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('private-stock.{productId}', function (User $user, int $productId) {
    return $user->hasPermissionTo('stock.view')
        || $user->hasPermissionTo('requests.view')
        || $user->isAdministrator();
});
```
(O `isAdministrator()` já existe em `User` conforme padrão dos policies.)

### 3. Inicializar o Echo no JS — `resources/js/app.js`
- Importar e inicializar `window.Echo` com Reverb usando as vars do Vite (`import.meta.env.VITE_REVERB_*`):
```js
import Echo from 'laravel-echo';
import axios from 'axios'; // já deve existir via Sanctum
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```
- Importar o elemento: `import './elements/turbo-echo-stream-tag';` (logo após os imports existentes).

### 4. Marcar os nós de estoque com `id` (para o Turbo fazer `replace`)
O `dom_id($product)` do Turbo gera `id` no formato `product_{id}` (ex.: `product_123`). Preciso que cada lugar que mostra o estoque do produto X tenha um elemento com esse `id` (ou um container pai com `id="product_{id}"`) para o stream `update` substituir.

- **`resources/views/products/show.blade.php`** (linha ~38): envolver o bloco "Estoque atual" num `<div id="product_{{$product->id}}">…</div>` (ou adicionar `id` ao `<dl>`/container existente).
- **`resources/views/stock/index.blade.php`**: cada `<tr>` de saldo tem um produto — adicionar `id="product_{{$sb->product_id}}"` na `<tr>` (ou na `<td>` do produto).
- **`resources/views/material_requests/index.blade.php`** e **`show.blade.php`**: os itens referenciam `$item->product`. Adicionar `id="product_{{$item->product_id}}"` no container de cada produto.
- **`resources/views/dashboard.blade.php`**: o card de "itens em estoque" total é agregado (não por produto). Para ele, em vez de `replace` por produto, usaremos um **stream próprio** (ver item 5).

### 5. Card do Dashboard (agregado) — broadcast manual
O total do dashboard não é um produto único, então o auto-broadcast do model não o cobre. Estratégia leve:
- No `deliver` (e em `recalcAvailable`), após salvar, disparar um broadcast manual do total atualizado para um canal `private-dashboard` (ou simplesmente `Turbo::broadcastAction`/refresh). **Alternativa mais simples e robusta**: adicionar `<turbo-echo-stream-source type="private" channel="private-dashboard">` no dashboard e, no `deliver`, disparar `Turbo::broadcast()->to(new PrivateChannel('dashboard'))->refresh()` (um `turbo-stream action="refresh"` recarrega a página via Turbo). Isso re-renderiza o dashboard inteiro ao vivo.
- Criar canal `private-dashboard` em `routes/channels.php` com a mesma autorização.

> Nota: o `refresh` do Turbo faz um visit GET à página atual — no dashboard isso re-busca os totais. É o caminho mais simples e evita duplicar lógica de agregação no cliente.

### 6. `<turbo-echo-stream-source>` em cada tela
Adicionar, no topo do `@section('content')` de cada view afetada, o elemento apontando para o canal do produto. Mas o canal é **por produto** (`private-stock.{id}`), e uma tela lista vários produtos. Solução: o `update` do Turbo usa `target=dom_id($product)` = `product_{id}`. O elemento `<turbo-echo-stream-source>` escuta o **canal**; para vários produtos na mesma tela, basta o elemento escutar um canal "guarda" ou escutar `private-stock.*`. Reverb/Pusher suportam wildcard `private-stock.*`. Então:
```html
<turbo-echo-stream-source type="private" channel="private-stock.*"></turbo-echo-stream-source>
```
e o `authorize` do canal precisa aceitar o wildcard (no Laravel, `Broadcast::channel('private-stock.{productId}', ...)` já casa `private-stock.*` via regex do param). Confirmar que o Reverb aceita o `*` — se não, escutamos o canal por produto gerado via Alpine/Blade `x-for` sobre a lista de IDs. **Fallback seguro**: gerar um `<turbo-echo-stream-source>` por produto visível usando `@foreach` sobre os IDs da view (ex.: no estoque, um por `<tr>`).

> Decisão de implementação: usar o **fallback por-produto** (`@foreach` gerando um source por id) — é determinístico, funciona com a autorização por `{productId}`, e não depende de suporte a wildcard do Reverb.

### 7. Cuidado com o `deliver` (local vs produção)
O `deliver` web já chama `$product->save()` e `$product->recalcAvailable()` (que também salva). Com o trait `Broadcasts`, **cada save dispara um broadcast**. Para não disparar em cascata durante a própria requisição do usuário que entregou (ele já vê o resultado via redirect), usar `Product::withoutTurboStreamBroadcasts(fn () => ...)` envolvendo a gravação no `deliver`, e disparar **um único** broadcast explícito no fim (ou simplesmente deixar o broadcast acontecer — o usuario que entregou está num redirect, então o echo dele reconecta e recebe o update de qualquer forma, o que é o comportamento desejado). **Decisão**: deixar o broadcast natural acontecer (mais simples); o Turbo ignora streams para o próprio socket se usar `broadcastToOthers`, mas aqui queremos que *todas* as abas recebam, inclusive a do autor. Então sem `broadcastToOthers`.

## Arquivos a modificar/criar
1. `app/Models/Product.php` — trait `Broadcasts` + `$broadcasts`.
2. `routes/channels.php` — **criar** — `private-stock.{id}` e `private-dashboard`.
3. `resources/js/app.js` — init `window.Echo` (Reverb) + import do elemento.
4. `resources/views/products/show.blade.php` — `id="product_{id}"` no bloco de estoque.
5. `resources/views/stock/index.blade.php` — `id="product_{id}"` por `<tr>` + `<turbo-echo-stream-source>` por produto.
6. `resources/views/material_requests/index.blade.php` — `id` + source por produto.
7. `resources/views/material_requests/show.blade.php` — `id` + source por item/produto.
8. `resources/views/dashboard.blade.php` — source `private-dashboard` + `refresh` broadcast no `deliver`.

## Validação
- `php -l` em `Product.php` e `channels.php`.
- Subir `php artisan reverb:start` + `php artisan serve` localmente e usar o **preview do navegador** com 2 abas: entregar numa, confirmar que a outra atualiza o estoque/status sem refresh.
- Conferir no `preview_console_logs` que o Echo conecta (sem erro 401 no canal privado — auth de websocket via Sanctum/cookie já está no stack).
- `npm run build` para garantir que o `app.js` compila (Echo import).

## Fora de escopo
- Não mexer na API `/api/v1/*` (mantém 2 etapas).
- Não alterar regras de negócio de estoque/movimentação.
- Não implementar presença/"usuários online".
