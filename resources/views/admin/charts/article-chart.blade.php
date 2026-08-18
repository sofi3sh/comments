@php
    $data = \App\Services\Article\ArticleViewsService::getArticleChartData($entry);
@endphp

<canvas
        class="js-trend-chart js-trend-clickable"
        width="120"
        height="40"
        style="border:1px solid #ccc"
        data-values='@json($data)'
        data-start-date="{{ $entry->created_at->toIso8601String() }}"
></canvas>


<script>

    if (!window.chartJsInitialized) {

        window.chartJsInitialized = true;
        window.chartJsLoading = false;
        window.observer = null;
        window.bigChartInstance = null;
        window.modalInitialized = false;

        function loadChartJs(callback) {
            if (window.chartJsLoaded) return callback();
            if (window.chartJsLoading) return;

            window.chartJsLoading = true;

            const script = document.createElement('script');
            script.src = "https://cdn.jsdelivr.net/npm/chart.js";

            script.onload = () => {
                window.chartJsLoaded = true;
                callback();
            };

            document.head.appendChild(script);
        }

        function initCharts() {
            if (!window.Chart) return;

            if (!window.observer) {
                window.observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) return;

                        const canvas = entry.target;
                        if (canvas.dataset.rendered) return;

                        const data = JSON.parse(canvas.dataset.values || "{}");

                        new Chart(canvas, {
                            type: "line",
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    data: data.values,
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: false,
                                animation: false,
                                plugins: {legend: false, tooltip: false},
                                scales: {
                                    x: {display: false},
                                    y: {display: false, min: 0}
                                },
                                elements: {point: {radius: 0}}
                            }
                        });

                        canvas.dataset.rendered = true;
                        window.observer.unobserve(canvas);
                    });
                });
            }

            document.querySelectorAll('.js-trend-chart').forEach(canvas => {
                if (!canvas.dataset.observed) {
                    window.observer.observe(canvas);
                    canvas.dataset.observed = true;
                }
            });
        }

        loadChartJs(initCharts);

        document.addEventListener('DOMContentLoaded', function () {

            const tbody = document.querySelector('#crudTable tbody');

            if (tbody) {
                const observer = new MutationObserver((mutationsList) => {
                    setTimeout(() => {
                        initCharts();
                    }, 50);
                });

                observer.observe(tbody, {childList: true});
            }

        });

        window.getModal = function () {
            const modalEl = document.getElementById('chartModal');
            if (!modalEl) return null;

            if (!window.modalInitialized) {
                modalEl.addEventListener('hide.bs.modal', () => {
                    if (window.bigChartInstance) {
                        window.bigChartInstance.destroy();
                        window.bigChartInstance = null;
                    }
                    document.activeElement?.blur();
                });

                window.modalInitialized = true;
            }

            return bootstrap.Modal.getOrCreateInstance(modalEl);
        };

        window.groupByDays = function (values, startDateStr) {
            const days = [];
            const startDate = new Date(startDateStr);

            for (let i = 0; i < values.length; i += 24) {
                const daySum = values.slice(i, i + 24).reduce((sum, v) => sum + v, 0);
                days.push(daySum);
            }

            const labels = days.map((_, i) => {
                const day = new Date(startDate);
                day.setDate(startDate.getDate() + i);
                return `${String(day.getDate()).padStart(2, '0')}.${String(day.getMonth() + 1).padStart(2, '0')}`;
            });

            return {labels, values: days};
        };


        window.openBigChart = function (data) {
            if (!window.Chart) return;

            const ctx = document.getElementById('bigChart');
            if (!ctx) return;

            if (window.bigChartInstance) {
                window.bigChartInstance.destroy();
            }

            const daily = window.groupByDays(data.values, data.startDate);

            window.bigChartInstance = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: daily.labels,
                    datasets: [{
                        data: daily.values,
                        label: "По днях"
                    }]
                },
                options: {
                    responsive: true,
                    onClick(evt, elements) {
                        if (!elements.length) return;
                        window.openHourlyChart(data, elements[0].index);
                    }
                }
            });

            const modal = window.getModal();
            if (modal) modal.show();
        };

        window.openHourlyChart = function (data, dayIndex) {
            if (!window.Chart) return;

            const ctx = document.getElementById('bigChart');
            if (!ctx) return;

            if (window.bigChartInstance) {
                window.bigChartInstance.destroy();
            }

            const start = dayIndex * 24;
            const hourlyValues = data.values.slice(start, start + 24);
            const hourlyLabels = hourlyValues.map((_, i) => i + ':00');

            window.bigChartInstance = new Chart(ctx, {
                type: "line",
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        data: hourlyValues,
                        label: "По годинах",
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true
                }
            });
        };

        document.addEventListener('click', function (e) {
            const el = e.target.closest('.js-trend-clickable');
            if (!el) return;

            loadChartJs(() => {
                const data = JSON.parse(el.dataset.values || "{}");
                data.startDate = el.dataset.startDate;
                window.openBigChart(data);
            });
        });

        const tbody = document.querySelector('#crudTable tbody');
        if (tbody) {
            const observer = new MutationObserver((mutationsList) => {
                // можно добавить маленькую задержку, чтобы DOM успел обновиться
                setTimeout(() => {
                    initCharts();
                }, 50);
            });

            observer.observe(tbody, {childList: true});
        }
    }
</script>
