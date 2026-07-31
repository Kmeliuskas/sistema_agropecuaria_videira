<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Attributes as OAT;

#[OAT\Info(
    version: '1.0.0',
    title: 'WMS — Warehouse Management System API',
    description: 'API REST do sistema de Gestão de Almoxarifado (Laravel 13 + Sanctum SPA).',
    contact: new OAT\Contact(name: 'WMS', email: 'suporte@wms.local')
)]
#[OAT\Server(url: 'http://localhost:8000', description: 'Servidor local (Docker)')]
#[OAT\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'session'
)]
class OpenApiSpec {}
