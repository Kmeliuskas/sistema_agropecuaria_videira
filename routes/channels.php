<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Canais de tempo real (Echo / Reverb).
 * stock.{id}: atualização de estoque e de dados ao vivo por produto.
 * products: atualizações da lista de produtos (criação, substituição, remoção).
 * dashboard: refresh do painel (totais de estoque).
 * Apenas usuários autorizados.
 */

Broadcast::channel('stock.{id}', function (User $user, int $id) {
    return $user->isAdministrator()
        || $user->hasPermissionTo('stock.view')
        || $user->hasPermissionTo('requests.view')
        || $user->hasPermissionTo('products.view');
});

Broadcast::channel('products', function (User $user) {
    return $user->isAdministrator()
        || $user->hasPermissionTo('stock.view')
        || $user->hasPermissionTo('products.view');
});

Broadcast::channel('dashboard', function (User $user) {
    return $user->isAdministrator()
        || $user->hasPermissionTo('dashboard.view')
        || $user->hasPermissionTo('stock.view')
        || $user->hasPermissionTo('requests.view');
});

Broadcast::channel('warehouses', function (User $user) {
    return $user->isAdministrator() || $user->hasPermissionTo('warehouses.view');
});

Broadcast::channel('warehouse.{id}', function (User $user, int $id) {
    return $user->isAdministrator() || $user->hasPermissionTo('warehouses.view');
});

Broadcast::channel('sectors', function (User $user) {
    return $user->isAdministrator() || $user->hasPermissionTo('sectors.view');
});

Broadcast::channel('sector.{id}', function (User $user, int $id) {
    return $user->isAdministrator() || $user->hasPermissionTo('sectors.view');
});

Broadcast::channel('stock-balances', function (User $user) {
    return $user->isAdministrator() || $user->hasPermissionTo('stock.view');
});

Broadcast::channel('material-requests', function (User $user) {
    return $user->isAdministrator() || $user->hasPermissionTo('requests.view');
});

Broadcast::channel('material-request.{id}', function (User $user, int $id) {
    return $user->isAdministrator() || $user->hasPermissionTo('requests.view');
});

Broadcast::channel('movements', function (User $user) {
    return $user->isAdministrator() || $user->hasPermissionTo('movements.view') || $user->hasPermissionTo('stock.view');
});


