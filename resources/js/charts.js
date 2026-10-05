import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

/**
 * Validated categorical palette (light / dark steps of the same hues, fixed order).
 * Single-series charts use the gym's brand color instead.
 */
const SERIES = {
    light: ['#2a78d6', '#eb6834', '#1baf7a'],
    dark: ['#3987e5', '#d95926', '#199e70'],
};

function theme() {
    const dark = document.documentElement.classList.contains('dark');
    const brand = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#4f46e5';

    return {
        dark,
        brand,
        series: dark ? SERIES.dark : SERIES.light,
        grid: dark ? '#2e2e2b' : '#ecebe7',
        text: dark ? '#c3c2b7' : '#52514e',
        surface: dark ? '#18181b' : '#ffffff',
    };
}

function withAlpha(hex, alpha) {
    const value = hex.replace('#', '');
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(value.slice(i, i + 2), 16));

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Alpine component: <canvas x-data="chart({type, labels, series, format})">
 * type: 'bar' | 'line' | 'hbar'; series: [{label, data}]; format: 'money' | 'number'.
 */
export function chart(config) {
    return {
        instance: null,

        init() {
            this.render();
            this.observer = new MutationObserver(() => this.render());
            this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        destroy() {
            this.observer?.disconnect();
            this.instance?.destroy();
        },

        format(value) {
            const number = Number(value) || 0;

            if (config.format === 'money') {
                return (config.symbol ?? '$') + number.toLocaleString(undefined, { maximumFractionDigits: number >= 1000 ? 0 : 2 });
            }

            return number.toLocaleString();
        },

        render() {
            this.instance?.destroy();
            const t = theme();
            const horizontal = config.type === 'hbar';
            const isLine = config.type === 'line';
            const multi = config.series.length > 1;

            const datasets = config.series.map((series, index) => {
                const color = multi ? t.series[index % t.series.length] : t.brand;

                return isLine
                    ? {
                          label: series.label,
                          data: series.data,
                          borderColor: color,
                          backgroundColor: withAlpha(color.length === 7 ? color : '#4f46e5', 0.1),
                          fill: !multi,
                          borderWidth: 2,
                          tension: 0.3,
                          pointRadius: 0,
                          pointHoverRadius: 5,
                          pointHoverBorderWidth: 2,
                          pointHoverBorderColor: t.surface,
                          pointBackgroundColor: color,
                          borderCapStyle: 'round',
                          borderJoinStyle: 'round',
                      }
                    : {
                          label: series.label,
                          data: series.data,
                          backgroundColor: color,
                          hoverBackgroundColor: color,
                          borderRadius: 4,
                          borderSkipped: 'start',
                          maxBarThickness: 24,
                          categoryPercentage: multi ? 0.7 : 0.8,
                          barPercentage: multi ? 0.9 : 1,
                          borderColor: t.surface,
                          borderWidth: { top: 0, bottom: 0, left: multi ? 1 : 0, right: multi ? 1 : 0 },
                      };
            });

            const valueAxis = {
                beginAtZero: true,
                grid: { color: t.grid, drawTicks: false },
                border: { display: false },
                ticks: { color: t.text, padding: 8, maxTicksLimit: 6, callback: (value) => this.format(value) },
            };
            const categoryAxis = {
                grid: { display: false },
                border: { color: t.grid },
                ticks: { color: t.text, padding: 6, autoSkip: true, maxRotation: 0, maxTicksLimit: horizontal ? 20 : 12 },
            };

            this.instance = new Chart(this.$el, {
                type: isLine ? 'line' : 'bar',
                data: { labels: config.labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: horizontal ? 'y' : 'x',
                    animation: { duration: 400 },
                    interaction: { mode: 'index', intersect: false },
                    layout: { padding: { top: 4 } },
                    scales: horizontal ? { x: valueAxis, y: categoryAxis } : { x: categoryAxis, y: valueAxis },
                    plugins: {
                        legend: {
                            display: multi,
                            position: 'top',
                            align: 'end',
                            labels: { color: t.text, usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, boxHeight: 8, padding: 16 },
                        },
                        tooltip: {
                            backgroundColor: t.dark ? '#27272a' : '#18181b',
                            titleColor: '#ffffff',
                            bodyColor: '#e4e4e7',
                            padding: 10,
                            cornerRadius: 8,
                            boxPadding: 4,
                            usePointStyle: true,
                            callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${this.format(ctx.parsed[horizontal ? 'x' : 'y'])}` },
                        },
                    },
                },
            });
        },
    };
}
