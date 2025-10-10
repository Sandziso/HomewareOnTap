<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomewareOnTap - Reports & Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
            color: #444;
        }
        
        .navbar {
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 24px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            padding: 20px;
        }
        
        .chart-controls {
            padding: 15px 20px;
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .stats-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stats-card p {
            color: var(--secondary);
            margin-bottom: 0;
        }
        
        .date-filter {
            max-width: 250px;
        }
        
        .chart-type-selector {
            margin-bottom: 15px;
        }
        
        .btn-chart-type {
            border-radius: 20px;
            margin-right: 5px;
        }
        
        .export-btn {
            margin-left: 10px;
        }
        
        .tab-pane {
            padding: 20px 0;
        }
        
        .chart-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .chart-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .chart-type-selector {
                margin-bottom: 10px;
            }
            
            .date-filter {
                max-width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-bar me-2"></i>HomewareOnTap Reports
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line me-1"></i> Reports
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item active" href="#">Sales Reports</a></li>
                            <li><a class="dropdown-item" href="#">Product Analytics</a></li>
                            <li><a class="dropdown-item" href="#">Customer Insights</a></li>
                            <li><a class="dropdown-item" href="#">Revenue Analysis</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i> Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Sales Reports & Analytics</h1>
            <div>
                <button class="btn btn-sm btn-primary">
                    <i class="fas fa-download fa-sm"></i> Generate Report
                </button>
                <button class="btn btn-sm btn-outline-secondary export-btn" id="exportChart">
                    <i class="fas fa-image fa-sm"></i> Export as Image
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white stats-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>$24,582</h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white stats-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>1,248</h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white stats-card">
                    <i class="fas fa-users"></i>
                    <h3>892</h3>
                    <p>New Customers</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white stats-card">
                    <i class="fas fa-percentage"></i>
                    <h3>42.8%</h3>
                    <p>Conversion Rate</p>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="chartTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales Performance</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Product Analytics</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button" role="tab">Customer Insights</button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content" id="chartTabsContent">
                <!-- Sales Performance Tab -->
                <div class="tab-pane fade show active" id="sales" role="tabpanel">
                    <div class="chart-toolbar px-4 pt-3">
                        <div class="chart-type-selector">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary btn-chart-type" data-chart-type="line">Line</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="bar">Bar</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="area">Area</button>
                            </div>
                        </div>
                        <div class="d-flex">
                            <select class="form-select form-select-sm date-filter me-2">
                                <option selected>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>Last 90 Days</option>
                                <option>Year to Date</option>
                                <option>Custom Range</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar me-1"></i> Apply
                            </button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Product Analytics Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel">
                    <div class="chart-toolbar px-4 pt-3">
                        <div class="chart-type-selector">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary btn-chart-type" data-chart-type="doughnut">Doughnut</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="pie">Pie</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="polarArea">Polar</button>
                            </div>
                        </div>
                        <select class="form-select form-select-sm date-filter">
                            <option selected>All Categories</option>
                            <option>Furniture</option>
                            <option>Decor</option>
                            <option>Lighting</option>
                            <option>Kitchenware</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
                
                <!-- Customer Insights Tab -->
                <div class="tab-pane fade" id="customers" role="tabpanel">
                    <div class="chart-toolbar px-4 pt-3">
                        <div class="chart-type-selector">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary btn-chart-type" data-chart-type="bar">Bar</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="line">Line</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="radar">Radar</button>
                            </div>
                        </div>
                        <select class="form-select form-select-sm date-filter">
                            <option selected>By Region</option>
                            <option>By Age Group</option>
                            <option>By Acquisition Source</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="customersChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="chart-controls d-flex justify-content-between">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="dataLabelsToggle" checked>
                    <label class="form-check-label" for="dataLabelsToggle">Show Data Labels</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="gridLinesToggle" checked>
                    <label class="form-check-label" for="gridLinesToggle">Show Grid Lines</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="animationsToggle" checked>
                    <label class="form-check-label" for="animationsToggle">Enable Animations</label>
                </div>
            </div>
        </div>

        <!-- Additional Charts Row -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Revenue Sources</h6>
                    </div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="revenueSourcesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Sales by Category</h6>
                    </div>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart.js Configuration and Initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Register plugins
            Chart.register(ChartDataLabels);
            
            // Chart colors
            const chartColors = {
                primary: '#4e73df',
                success: '#1cc88a',
                info: '#36b9cc',
                warning: '#f6c23e',
                danger: '#e74a3b',
                secondary: '#858796',
                light: '#f8f9fc',
                dark: '#5a5c69'
            };
            
            // Global chart configuration
            Chart.defaults.font.family = 'Nunito, sans-serif';
            Chart.defaults.color = chartColors.secondary;
            Chart.defaults.scale.grid.color = 'rgba(134, 135, 150, 0.1)';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgb(255, 255, 255)';
            Chart.defaults.plugins.tooltip.bodyColor = chartColors.dark;
            Chart.defaults.plugins.tooltip.titleColor = chartColors.dark;
            Chart.defaults.plugins.tooltip.borderColor = '#dddfeb';
            Chart.defaults.plugins.tooltip.borderWidth = 1;
            Chart.defaults.plugins.tooltip.padding = 15;
            Chart.defaults.plugins.datalabels.color = chartColors.dark;
            Chart.defaults.plugins.datalabels.font = { weight: 'bold' };
            
            // Initialize charts
            const salesChart = initSalesChart();
            const productsChart = initProductsChart();
            const customersChart = initCustomersChart();
            const revenueSourcesChart = initRevenueSourcesChart();
            const categoryChart = initCategoryChart();
            
            // Store chart references
            const charts = {
                sales: salesChart,
                products: productsChart,
                customers: customersChart,
                revenue: revenueSourcesChart,
                category: categoryChart
            };
            
            // Set up event listeners
            setupEventListeners(charts);
            
            // Initialize chart
            function initSalesChart() {
                const ctx = document.getElementById('salesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Revenue',
                            data: [12500, 14000, 12800, 15500, 16800, 18200, 19500, 18700, 20100, 21400, 22500, 24582],
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: chartColors.primary,
                            pointRadius: 3,
                            pointBackgroundColor: chartColors.primary,
                            pointBorderColor: chartColors.primary,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: chartColors.primary,
                            pointHoverBorderColor: 'rgb(255, 255, 255)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                align: 'top',
                                formatter: function(value) {
                                    return '$' + (value/1000).toFixed(0) + 'k';
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: true,
                                    drawBorder: false
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return '$' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            }
            
            function initProductsChart() {
                const ctx = document.getElementById('productsChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Furniture', 'Decor', 'Lighting', 'Kitchenware', 'Bedding'],
                        datasets: [{
                            data: [35, 25, 15, 18, 7],
                            backgroundColor: [
                                chartColors.primary,
                                chartColors.success,
                                chartColors.info,
                                chartColors.warning,
                                chartColors.danger
                            ],
                            hoverBackgroundColor: [
                                '#2e59d9',
                                '#17a673',
                                '#2c9faf',
                                '#dda20a',
                                '#e02d1b'
                            ],
                            hoverBorderColor: 'rgba(234, 236, 244, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return value + '%';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            }
            
            function initCustomersChart() {
                const ctx = document.getElementById('customersChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['North', 'South', 'East', 'West', 'Central'],
                        datasets: [{
                            label: 'Customers',
                            data: [350, 410, 280, 390, 320],
                            backgroundColor: chartColors.info,
                            borderColor: chartColors.info,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Customers: ' + context.parsed.y;
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return value;
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)'
                                }
                            }
                        }
                    }
                });
            }
            
            function initRevenueSourcesChart() {
                const ctx = document.getElementById('revenueSourcesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Direct', 'Social Media', 'Email', 'Referral', 'Organic'],
                        datasets: [{
                            data: [55, 15, 10, 12, 8],
                            backgroundColor: [
                                chartColors.primary,
                                chartColors.success,
                                chartColors.info,
                                chartColors.warning,
                                chartColors.secondary
                            ],
                            hoverBackgroundColor: [
                                '#2e59d9',
                                '#17a673',
                                '#2c9faf',
                                '#dda20a',
                                '#6b6d7c'
                            ],
                            hoverBorderColor: 'rgba(234, 236, 244, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return value + '%';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 10
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
            
            function initCategoryChart() {
                const ctx = document.getElementById('categoryChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Furniture', 'Decor', 'Lighting', 'Kitchen', 'Bedding', 'Bath'],
                        datasets: [{
                            label: 'Sales ($)',
                            data: [12500, 7500, 4800, 6200, 3800, 4200],
                            backgroundColor: [
                                'rgba(78, 115, 223, 0.7)',
                                'rgba(28, 200, 138, 0.7)',
                                'rgba(54, 185, 204, 0.7)',
                                'rgba(246, 194, 62, 0.7)',
                                'rgba(231, 74, 59, 0.7)',
                                'rgba(133, 135, 150, 0.7)'
                            ],
                            borderColor: [
                                chartColors.primary,
                                chartColors.success,
                                chartColors.info,
                                chartColors.warning,
                                chartColors.danger,
                                chartColors.secondary
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return '$' + (value/1000).toFixed(1) + 'k';
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return '$' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Set up event listeners for chart controls
            function setupEventListeners(charts) {
                // Chart type switching
                document.querySelectorAll('.btn-chart-type').forEach(button => {
                    button.addEventListener('click', function() {
                        const chartType = this.getAttribute('data-chart-type');
                        const tabPane = this.closest('.tab-pane');
                        const chartId = tabPane.querySelector('canvas').id;
                        
                        // Update button states
                        this.closest('.btn-group').querySelectorAll('.btn').forEach(btn => {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-primary');
                        });
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                        
                        // Change chart type
                        let chart;
                        if (chartId === 'salesChart') chart = charts.sales;
                        else if (chartId === 'productsChart') chart = charts.products;
                        else if (chartId === 'customersChart') chart = charts.customers;
                        
                        if (chart) {
                            chart.destroy();
                            
                            if (chartId === 'salesChart') {
                                if (chartType === 'line') charts.sales = initSalesChart();
                                else if (chartType === 'bar') charts.sales = initSalesBarChart();
                                else if (chartType === 'area') charts.sales = initSalesAreaChart();
                            } else if (chartId === 'productsChart') {
                                if (chartType === 'doughnut') charts.products = initProductsChart();
                                else if (chartType === 'pie') charts.products = initProductsPieChart();
                                else if (chartType === 'polarArea') charts.products = initProductsPolarChart();
                            } else if (chartId === 'customersChart') {
                                if (chartType === 'bar') charts.customers = initCustomersChart();
                                else if (chartType === 'line') charts.customers = initCustomersLineChart();
                                else if (chartType === 'radar') charts.customers = initCustomersRadarChart();
                            }
                        }
                    });
                });
                
                // Toggle data labels
                document.getElementById('dataLabelsToggle').addEventListener('change', function() {
                    const isVisible = this.checked;
                    Object.values(charts).forEach(chart => {
                        chart.options.plugins.datalabels.display = isVisible;
                        chart.update();
                    });
                });
                
                // Toggle grid lines
                document.getElementById('gridLinesToggle').addEventListener('change', function() {
                    const isVisible = this.checked;
                    Object.values(charts).forEach(chart => {
                        if (chart.options.scales) {
                            if (chart.options.scales.x) chart.options.scales.x.grid.display = isVisible;
                            if (chart.options.scales.y) chart.options.scales.y.grid.display = isVisible;
                        }
                        chart.update();
                    });
                });
                
                // Toggle animations
                document.getElementById('animationsToggle').addEventListener('change', function() {
                    const isEnabled = this.checked;
                    Object.values(charts).forEach(chart => {
                        chart.options.animation = isEnabled;
                        chart.update();
                    });
                });
                
                // Export chart as image
                document.getElementById('exportChart').addEventListener('click', function() {
                    const activeTab = document.querySelector('.tab-pane.active');
                    const canvas = activeTab.querySelector('canvas');
                    const imageLink = document.createElement('a');
                    const filename = 'homewareontap_chart_' + new Date().toISOString().slice(0, 10) + '.png';
                    
                    imageLink.href = canvas.toDataURL('image/png');
                    imageLink.download = filename;
                    document.body.appendChild(imageLink);
                    imageLink.click();
                    document.body.removeChild(imageLink);
                });
            }
            
            // Alternative chart types for demonstration
            function initSalesBarChart() {
                const ctx = document.getElementById('salesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Revenue',
                            data: [12500, 14000, 12800, 15500, 16800, 18200, 19500, 18700, 20100, 21400, 22500, 24582],
                            backgroundColor: 'rgba(78, 115, 223, 0.7)',
                            borderColor: chartColors.primary,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return '$' + (value/1000).toFixed(0) + 'k';
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return '$' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)'
                                }
                            }
                        }
                    }
                });
            }
            
            function initSalesAreaChart() {
                const ctx = document.getElementById('salesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Revenue',
                            data: [12500, 14000, 12800, 15500, 16800, 18200, 19500, 18700, 20100, 21400, 22500, 24582],
                            backgroundColor: 'rgba(78, 115, 223, 0.3)',
                            borderColor: chartColors.primary,
                            pointRadius: 3,
                            pointBackgroundColor: chartColors.primary,
                            pointBorderColor: chartColors.primary,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: chartColors.primary,
                            pointHoverBorderColor: 'rgb(255, 255, 255)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                align: 'top',
                                formatter: function(value) {
                                    return '$' + (value/1000).toFixed(0) + 'k';
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: true,
                                    drawBorder: false
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return '$' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });
            }
            
            function initProductsPieChart() {
                const ctx = document.getElementById('productsChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Furniture', 'Decor', 'Lighting', 'Kitchenware', 'Bedding'],
                        datasets: [{
                            data: [35, 25, 15, 18, 7],
                            backgroundColor: [
                                chartColors.primary,
                                chartColors.success,
                                chartColors.info,
                                chartColors.warning,
                                chartColors.danger
                            ],
                            hoverBackgroundColor: [
                                '#2e59d9',
                                '#17a673',
                                '#2c9faf',
                                '#dda20a',
                                '#e02d1b'
                            ],
                            hoverBorderColor: 'rgba(234, 236, 244, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return value + '%';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                }
                            }
                        }
                    }
                });
            }
            
            function initProductsPolarChart() {
                const ctx = document.getElementById('productsChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'polarArea',
                    data: {
                        labels: ['Furniture', 'Decor', 'Lighting', 'Kitchenware', 'Bedding'],
                        datasets: [{
                            data: [35, 25, 15, 18, 7],
                            backgroundColor: [
                                'rgba(78, 115, 223, 0.7)',
                                'rgba(28, 200, 138, 0.7)',
                                'rgba(54, 185, 204, 0.7)',
                                'rgba(246, 194, 62, 0.7)',
                                'rgba(231, 74, 59, 0.7)'
                            ],
                            borderColor: [
                                chartColors.primary,
                                chartColors.success,
                                chartColors.info,
                                chartColors.warning,
                                chartColors.danger
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return value + '%';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 10
                                }
                            }
                        }
                    }
                });
            }
            
            function initCustomersLineChart() {
                const ctx = document.getElementById('customersChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['North', 'South', 'East', 'West', 'Central'],
                        datasets: [{
                            label: 'Customers',
                            data: [350, 410, 280, 390, 320],
                            backgroundColor: 'rgba(54, 185, 204, 0.1)',
                            borderColor: chartColors.info,
                            pointRadius: 4,
                            pointBackgroundColor: chartColors.info,
                            pointBorderColor: chartColors.info,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: chartColors.info,
                            pointHoverBorderColor: 'rgb(255, 255, 255)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Customers: ' + context.parsed.y;
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return value;
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)'
                                }
                            }
                        }
                    }
                });
            }
            
            function initCustomersRadarChart() {
                const ctx = document.getElementById('customersChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: ['North', 'South', 'East', 'West', 'Central'],
                        datasets: [{
                            label: 'Customers',
                            data: [350, 410, 280, 390, 320],
                            backgroundColor: 'rgba(54, 185, 204, 0.2)',
                            borderColor: chartColors.info,
                            pointRadius: 4,
                            pointBackgroundColor: chartColors.info,
                            pointBorderColor: chartColors.info,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: chartColors.info,
                            pointHoverBorderColor: 'rgb(255, 255, 255)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Customers: ' + context.parsed.y;
                                    }
                                }
                            },
                            datalabels: {
                                display: false
                            }
                        },
                        scales: {
                            r: {
                                ticks: {
                                    display: false
                                },
                                grid: {
                                    color: 'rgba(134, 135, 150, 0.1)'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>