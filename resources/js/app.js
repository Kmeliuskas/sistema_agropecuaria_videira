// Aplicação WMS — frontend Blade + Tailwind + Alpine.js + Turbo (Hotwire).

// ---- Dark mode ----
// Aplica o tema salvo antes da pintura para evitar flash.
(function () {
    const saved = localStorage.getItem('wms-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.classList.add('dark');
    }

    window.toggleTheme = function () {
        const root = document.documentElement;
        const isDark = root.classList.toggle('dark');
        localStorage.setItem('wms-theme', isDark ? 'dark' : 'light');
    };
})();

import Alpine from 'alpinejs';
import '@alpinejs/focus';

// Turbo (Hotwire): intercepta cliques em <a> e submits de <form>, trocando só
// o conteúdo da página sem refresh completo. Converte para ESM.
import * as Turbo from '@hotwired/turbo';

// Echo (Reverb/websockets): habilita atualização de estoque em tempo real.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // Necessário para o connector do Reverb funcionar
import './elements/turbo-echo-stream-tag';
import { initDashboardChart, destroyDashboardChart } from './elements/dashboard-chart';

window.Alpine = Alpine;
window.Turbo = Turbo;

// ---- Permissões Globais do Usuário Logado ----
// Injetado via meta tags no layout principal (app.blade.php)
window.UserPermissions = {
    canUpdateProduct: document.querySelector('meta[name="can-products-update"]')?.content === 'true',
    canDeleteProduct: document.querySelector('meta[name="can-products-delete"]')?.content === 'true',
    canUpdateSector: document.querySelector('meta[name="can-sectors-update"]')?.content === 'true',
    canDeleteSector: document.querySelector('meta[name="can-sectors-delete"]')?.content === 'true',
    canUpdateWarehouse: document.querySelector('meta[name="can-warehouses-update"]')?.content === 'true',
    canDeleteWarehouse: document.querySelector('meta[name="can-warehouses-delete"]')?.content === 'true',
};

// ---- Alpine Components ----

/**
 * Componente para renderizar ações (Editar/Excluir) nas linhas da tabela de produtos
 * baseado nas permissões do usuário atual (client-side).
 * Necessário porque o broadcast Turbo Stream renderiza no servidor com as permissões
 * do usuário que criou/editou o produto, não do usuário que recebe o broadcast.
 */
Alpine.data('productRowActions', (props) => ({
    productId: props.productId,
    editRoute: props.editRoute,
    deleteRoute: props.deleteRoute,

    init() {
        this.renderActions();
    },

    get canUpdate() {
        return window.UserPermissions?.canUpdateProduct ?? false;
    },

    get canDelete() {
        return window.UserPermissions?.canDeleteProduct ?? false;
    },

    renderActions() {
        const container = this.$refs.actions;
        if (!container) return;

        container.innerHTML = '';

        const flexContainer = document.createElement('div');
        flexContainer.className = 'flex justify-end gap-2';

        if (this.canUpdate) {
            const editLink = document.createElement('a');
            editLink.href = this.editRoute;
            editLink.className = 'action-btn-edit';
            editLink.textContent = 'Editar';
            flexContainer.appendChild(editLink);
        }

        if (this.canDelete) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.deleteRoute;
            form.onsubmit = () => confirm('Remover este produto?');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'submit';
            deleteBtn.className = 'action-btn-delete';
            deleteBtn.textContent = 'Remover';

            form.appendChild(deleteBtn);
            flexContainer.appendChild(form);
        }

        container.appendChild(flexContainer);
    }
}));

// Inicializa o cliente websocket apontando para o Reverb (credenciais via Vite env).
// Em try/catch para never quebrar o app se o websocket não conectar.
try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
        wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} catch (e) {
    console.error('Falha ao inicializar Echo/Reverb:', e);
    window.Echo = null;
}

/**
 * Componente para renderizar ações (Editar/Excluir) nos cards da tabela de setores
 */
Alpine.data('sectorRowActions', (props) => ({
    sectorId: props.sectorId,
    editRoute: props.editRoute,
    deleteRoute: props.deleteRoute,

    init() {
        this.renderActions();
    },

    get canUpdate() {
        return window.UserPermissions?.canUpdateSector ?? false;
    },

    get canDelete() {
        return window.UserPermissions?.canDeleteSector ?? false;
    },

    renderActions() {
        const container = this.$refs.actions;
        if (!container) return;

        container.innerHTML = '';

        const flexContainer = document.createElement('div');
        flexContainer.className = 'flex justify-end gap-2';

        if (this.canUpdate) {
            const editLink = document.createElement('a');
            editLink.href = this.editRoute;
            editLink.className = 'action-btn-edit';
            editLink.textContent = 'Editar';
            flexContainer.appendChild(editLink);
        }

        if (this.canDelete) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.deleteRoute;
            form.onsubmit = () => confirm('Remover este setor?');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'submit';
            deleteBtn.className = 'action-btn-delete';
            deleteBtn.textContent = 'Remover';

            form.appendChild(deleteBtn);
            flexContainer.appendChild(form);
        }

        container.appendChild(flexContainer);
    }
}));

/**
 * Componente para renderizar ações (Editar/Excluir) nos cards de almoxarifados
 */
Alpine.data('warehouseCardActions', (props) => ({
    warehouseId: props.warehouseId,
    editRoute: props.editRoute,
    deleteRoute: props.deleteRoute,

    init() {
        this.renderActions();
    },

    get canUpdate() {
        return window.UserPermissions?.canUpdateWarehouse ?? false;
    },

    get canDelete() {
        return window.UserPermissions?.canDeleteWarehouse ?? false;
    },

    renderActions() {
        const container = this.$refs.actions;
        if (!container) return;

        container.innerHTML = '';

        const flexContainer = document.createElement('div');
        flexContainer.className = 'flex justify-end gap-2';

        if (this.canUpdate) {
            const editLink = document.createElement('a');
            editLink.href = this.editRoute;
            editLink.className = 'action-btn-edit';
            editLink.textContent = 'Editar';
            flexContainer.appendChild(editLink);
        }

        if (this.canDelete) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.deleteRoute;
            form.onsubmit = () => confirm('Remover este almoxarifado?');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'submit';
            deleteBtn.className = 'action-btn-delete';
            deleteBtn.textContent = 'Remover';

            form.appendChild(deleteBtn);
            flexContainer.appendChild(form);
        }

        container.appendChild(flexContainer);
    }
}));

// Reaplica o foco no início do conteúdo trocado (acessibilidade / keyboard nav).
document.addEventListener('turbo:render', () => {
    // Garante que o Alpine processe componentes que nasceram dinamicamente via Turbo.
    if (window.Alpine) {
        Alpine.initTree(document.body);
    }
    initDashboardChart();
});

// Inicializa o gráfico assim que a página carrega.
document.addEventListener('DOMContentLoaded', () => {
    initDashboardChart();
});

// Destrói o gráfico antes de navegar away para evitar memory leaks e
// múltiplos intervals acumulados quando o usuário navega via Turbo.
document.addEventListener('turbo:before-visit', () => {
    destroyDashboardChart();
});

Alpine.start();