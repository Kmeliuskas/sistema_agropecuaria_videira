import Chart from 'chart.js/auto';

let chartInstance = null;
let refreshInterval = null;
let isInitialized = false;
let isDestroying = false;

/**
 * DashboardChart — gráfico de barras "Movimentações (30 dias)".
 * Busca dados do endpoint /api/dashboard/movements via fetch
 * e atualiza o Chart.js em tempo real.
 *
 * Proteções contra crescimento infinito:
 * - Guard `isDestroying` evita reentrância durante destruição.
 * - `destroyDashboardChart()` limpa intervalo e instância antes de criar nova.
 * - Canvas é clonado para obter um contexto 2D limpo (Chart.js requer isso).
 * - Listener `turbo:before-visit` garante limpeza antes de navegar.
 */
export function initDashboardChart() {
    // Evita múltiplas inicializações simultâneas (reentrância).
    if (isInitialized) return;

    const canvas = document.getElementById('movementsChart');
    if (!canvas) return;

    isInitialized = true;

    // Clona o canvas para obter um contexto 2D limpo.
    // Chart.js não permite reutilizar o mesmo canvas/contexto entre instâncias.
    const clone = canvas.cloneNode(true);
    clone.id = '';
    canvas.parentNode.replaceChild(clone, canvas);
    const ctx = clone.getContext('2d');

    function createChart(data) {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        // Dados por dia (arrays) ou totais (números) - compatível com ambos
        const dates = data.dates || [];
        const entries = data.entries || [];
        const exits = data.exits || [];
        const totalEntries = data.total_entries ?? (Array.isArray(entries) ? entries.reduce((a, b) => a + b, 0) : entries);
        const totalExits = data.total_exits ?? (Array.isArray(exits) ? exits.reduce((a, b) => a + b, 0) : exits);
        const period = data.period || null;
        const today = period?.to ?? new Date().toLocaleDateString('pt-BR');

        // Se temos dados por dia, mostra gráfico de barras por dia
        // Se não, mostra apenas totais
        const isDailyData = Array.isArray(entries) && entries.length > 0;
        const hasData = totalEntries + totalExits > 0;

        if (isDailyData) {
            // Gráfico de barras por dia
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Entradas',
                            data: entries,
                            backgroundColor: '#2e9e52',
                            borderRadius: 4,
                            borderSkipped: false,
                            barThickness: 8,
                        },
                        {
                            label: 'Saídas',
                            data: exits,
                            backgroundColor: '#d73337',
                            borderRadius: 4,
                            borderSkipped: false,
                            barThickness: 8,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 8, bottom: 4, left: 4, right: 4 },
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: '#64748b',
                                font: { size: 11 },
                                usePointStyle: true,
                                pointStyle: 'circle',
                            },
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1',
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                title: (context) => {
                                    return `Dia ${context[0].label}`;
                                },
                                label: (context) => {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: { size: 10 },
                            },
                            grid: { color: '#e2e8f0' },
                            border: { display: false },
                        },
                        x: {
                            ticks: {
                                color: '#64748b',
                                font: { size: 9 },
                                maxRotation: 45,
                                minRotation: 45,
                            },
                            grid: { display: false },
                            border: { display: false },
                        },
                    },
                    animation: {
                        duration: 500,
                        easing: 'easeOutQuart',
                    },
                },
            });
        } else {
            // Gráfico simples de totais (compatibilidade com dados antigos)
            const maxVal = Math.max(totalEntries, totalExits, 1);

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hasData ? ['Entradas', 'Saídas'] : ['Nenhuma movimentação'],
                    datasets: [{
                        label: 'Movimentações (30 dias)',
                        data: hasData ? [totalEntries, totalExits] : [0],
                        backgroundColor: hasData
                            ? ['#2e9e52', '#d73337']
                            : ['#6b7280', '#6b7280'],
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 40,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 8, bottom: 4, left: 4, right: 4 },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1',
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                title: (context) => {
                                    const label = context[0].label;
                                    return `${label} — ${today}`;
                                },
                                label: (context) => {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: hasData ? Math.ceil(maxVal * 1.2) : 10,
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: { size: 11 },
                            },
                            grid: { color: '#e2e8f0' },
                            border: { display: false },
                        },
                        x: {
                            ticks: { color: '#64748b', font: { size: 11 } },
                            grid: { display: false },
                            border: { display: false },
                        },
                    },
                    animation: {
                        duration: 500,
                        easing: 'easeOutQuart',
                    },
                },
            });
        }
    }

    function fetchAndRender() {
        fetch('/api/dashboard/movements')
            .then((r) => {
                if (!r.ok) throw new Error('Network error');
                return r.json();
            })
            .then((data) => {
                createChart(data);
            })
            .catch(() => {
                // Silently fail — dashboard still works without the chart
            });
    }

    // Primeira carga imediata
    fetchAndRender();

    // Atualização a cada 60 segundos (evita flood de requests)
    refreshInterval = setInterval(fetchAndRender, 60_000);
}

/**
 * Destroy the chart instance and stop the refresh interval.
 * Called on Turbo navigations to prevent memory leaks.
 *
 * Guard `isDestroying` previne reentrância: se destroy() for chamado
 * enquanto outra destruição está em andamento, retorna imediatamente.
 */
export function destroyDashboardChart() {
    if (isDestroying) return;
    isDestroying = true;

    isInitialized = false;

    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    isDestroying = false;
}
