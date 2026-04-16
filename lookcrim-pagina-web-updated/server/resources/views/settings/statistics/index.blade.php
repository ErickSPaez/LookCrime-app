@extends('layouts.legacy')

@section('titulo_browser', __('pages.statistics_title') . ' - LookCrim')

@section('pagestyles')
    <style>
        .lc-stats-page .lc-stat-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 18px;
            height: 100%;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }
        .lc-stats-page .lc-stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
        }
        .lc-stats-page .lc-stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #7a1e1e;
            margin-top: 6px;
        }
        .lc-stats-page .lc-stat-sub {
            margin-top: 6px;
            font-size: 0.9rem;
            color: #4b5563;
        }
        .lc-stats-page .lc-stat-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }
        .lc-stats-page .lc-stat-heading {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #111827;
        }
        .lc-stats-page .lc-stat-empty {
            color: #6b7280;
            font-size: 0.95rem;
            margin: 0;
        }
        .lc-stats-page .lc-chart-wrap {
            min-height: 260px;
        }
        .lc-stats-page .lc-inline-table {
            margin-top: 12px;
        }
    </style>
@endsection

@section('conteudo')
    <div class="main-website-interior user-management-panel lc-stats-page">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.statistics_title') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">

        <div class="row" style="margin-bottom:18px;">
            <div class="col-md-4" style="margin-bottom:12px;">
                <div class="lc-stat-card">
                    <div class="lc-stat-label">{{ __('pages.total_registers') }}</div>
                    <div class="lc-stat-value">{{ number_format($totalRegisters) }}</div>
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:12px;">
                <div class="lc-stat-card">
                    <div class="lc-stat-label">{{ __('pages.top_category') }}</div>
                    <div class="lc-stat-value">{{ $topCategoryLabel ?? __('pages.no_data') }}</div>
                    @if($topCategory)
                        <div class="lc-stat-sub">{{ __('pages.total') }}: {{ number_format($topCategory->total) }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-4" style="margin-bottom:12px;">
                <div class="lc-stat-card">
                    <div class="lc-stat-label">{{ __('pages.top_city') }}</div>
                    <div class="lc-stat-value">{{ $topCity?->name ?? __('pages.no_data') }}</div>
                    @if($topCity)
                        <div class="lc-stat-sub">{{ __('pages.total') }}: {{ number_format($topCity->total) }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="lc-stat-panel">
                    <div class="lc-stat-heading">{{ __('pages.registers_by_category') }}</div>
                    @if($categoryChart['labels']->count() > 0)
                        <div class="lc-chart-wrap">
                            <canvas id="lc-category-chart" height="260"></canvas>
                        </div>
                    @else
                        <p class="lc-stat-empty">{{ __('pages.no_data') }}</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lc-stat-panel">
                    <div class="lc-stat-heading">{{ __('pages.users_per_city') }}</div>
                    @if($usersCityChart['labels']->count() > 0)
                        <div class="lc-chart-wrap">
                            <canvas id="lc-users-city-chart" height="260"></canvas>
                        </div>
                    @else
                        <p class="lc-stat-empty">{{ __('pages.no_data') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="lc-stat-panel">
                    <div class="lc-stat-heading">{{ __('pages.top_users') }}</div>
                    @if($topUsers->count() > 0)
                        <table class="table table-wrapper lc-inline-table">
                            <thead>
                                <tr>
                                    <th>{{ __('pages.user') }}</th>
                                    <th>{{ __('pages.email') }}</th>
                                    <th>{{ __('pages.total_registers') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topUsers as $row)
                                    <tr>
                                        <td>{{ $row->name ?? '-' }}</td>
                                        <td>{{ $row->email ?? '-' }}</td>
                                        <td>{{ number_format($row->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="lc-stat-empty">{{ __('pages.no_data') }}</p>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lc-stat-panel">
                    <div class="lc-stat-heading">{{ __('pages.users_per_city') }}</div>
                    <table class="table table-wrapper lc-inline-table">
                        <thead>
                            <tr>
                                <th>{{ __('pages.city') }}</th>
                                <th>{{ __('pages.total_users') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usersPerCity as $row)
                                <tr>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ number_format($row->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">{{ __('pages.no_data') }}</td>
                                </tr>
                            @endforelse
                            @if($usersWithoutCity > 0)
                                <tr>
                                    <td>{{ __('pages.users_without_city') }}</td>
                                    <td>{{ number_format($usersWithoutCity) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('pagescripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const categoryLabels = @json($categoryChart['labels']);
            const categoryData = @json($categoryChart['data']);
            const usersCityLabels = @json($usersCityChart['labels']);
            const usersCityData = @json($usersCityChart['data']);

            if (categoryLabels.length && document.getElementById('lc-category-chart')) {
                new Chart(document.getElementById('lc-category-chart'), {
                    type: 'doughnut',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryData,
                            backgroundColor: ['#7a1e1e', '#b91c1c', '#f59e0b', '#0284c7', '#16a34a', '#6b7280'],
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            if (usersCityLabels.length && document.getElementById('lc-users-city-chart')) {
                new Chart(document.getElementById('lc-users-city-chart'), {
                    type: 'bar',
                    data: {
                        labels: usersCityLabels,
                        datasets: [{
                            label: @json(__('pages.total_users')),
                            data: usersCityData,
                            backgroundColor: '#7a1e1e',
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
