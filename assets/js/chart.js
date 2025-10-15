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
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
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
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--secondary);
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
            
            .chart-container {
                height: 300px;
                padding: 10px;
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
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
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
                <button class="btn btn-sm btn-primary" id="generateReport">
                    <i class="fas fa-download fa-sm"></i> Generate Report
                </button>
                <button class="btn btn-sm btn-outline-secondary export-btn" id="exportChart">
                    <i class="fas fa-image fa-sm"></i> Export as Image
                </button>
                <button class="btn btn-sm btn-outline-info export-btn" id="refreshData">
                    <i class="fas fa-sync-alt fa-sm"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading chart data...</p>
        </div>

        <!-- Stats Overview -->
        <div class="row" id="statsOverview">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white stats-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3 id="totalRevenue">R0</h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white stats-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3 id="totalOrders">0</h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white stats-card">
                    <i class="fas fa-users"></i>
                    <h3 id="newCustomers">0</h3>
                    <p>New Customers</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white stats-card">
                    <i class="fas fa-percentage"></i>
                    <h3 id="conversionRate">0%</h3>
                    <p>Conversion Rate</p>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Date Range Filter</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-3">
                        <label for="presetRange" class="form-label">Preset Range</label>
                        <select class="form-select" id="presetRange">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="applyFilter">
                            <i class="fas fa-filter me-1"></i> Apply Filter
                        </button>
                    </div>
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
                            <select class="form-select form-select-sm date-filter me-2" id="salesGranularity">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                    <div class="no-data" id="salesNoData" style="display: none;">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>No sales data available for the selected period.</p>
                    </div>
                </div>
                
                <!-- Product Analytics Tab -->
                <div class="tab-pane fade" id="products" role="tabpanel">
                    <div class="chart-toolbar px-4 pt-3">
                        <div class="chart-type-selector">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary btn-chart-type" data-chart-type="doughnut">Doughnut</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="pie">Pie</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="bar">Bar</button>
                            </div>
                        </div>
                        <select class="form-select form-select-sm date-filter" id="productCategoryFilter">
                            <option value="all" selected>All Categories</option>
                            <option value="1">Kitchenware</option>
                            <option value="2">Home Decor</option>
                            <option value="4">Tableware</option>
                            <option value="12">Glassware</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="productsChart"></canvas>
                    </div>
                    <div class="no-data" id="productsNoData" style="display: none;">
                        <i class="fas fa-boxes fa-3x mb-3"></i>
                        <p>No product data available for the selected period.</p>
                    </div>
                </div>
                
                <!-- Customer Insights Tab -->
                <div class="tab-pane fade" id="customers" role="tabpanel">
                    <div class="chart-toolbar px-4 pt-3">
                        <div class="chart-type-selector">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary btn-chart-type" data-chart-type="bar">Bar</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="line">Line</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-chart-type" data-chart-type="doughnut">Doughnut</button>
                            </div>
                        </div>
                        <select class="form-select form-select-sm date-filter" id="customerMetric">
                            <option value="region" selected>By Region</option>
                            <option value="acquisition">By Acquisition Source</option>
                            <option value="lifetime">Customer Lifetime Value</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="customersChart"></canvas>
                    </div>
                    <div class="no-data" id="customersNoData" style="display: none;">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>No customer data available for the selected period.</p>
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
                    <div class="no-data" id="revenueNoData" style="display: none;">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                        <p>No revenue data available.</p>
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
                    <div class="no-data" id="categoryNoData" style="display: none;">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <p>No category data available.</p>
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
            
            // Store chart references
            let charts = {
                sales: null,
                products: null,
                customers: null,
                revenue: null,
                category: null
            };
            
            // Current date range
            let currentDateRange = {
                start: null,
                end: null,
                preset: '30'
            };
            
            // Initialize the dashboard
            initializeDashboard();
            
            // Set up event listeners
            setupEventListeners();
            
            // Initialize dashboard with default data
            function initializeDashboard() {
                // Set default date range (last 30 days)
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 30);
                
                document.getElementById('startDate').valueAsDate = startDate;
                document.getElementById('endDate').valueAsDate = endDate;
                
                currentDateRange.start = startDate;
                currentDateRange.end = endDate;
                
                // Load initial data
                loadDashboardData();
            }
            
            // Load all dashboard data
            function loadDashboardData() {
                showLoading(true);
                
                // In a real application, you would fetch this data from your server
                // For now, we'll use simulated data with a delay to mimic API calls
                setTimeout(() => {
                    // Update stats cards
                    updateStatsCards(generateStatsData());
                    
                    // Initialize charts with data
                    if (charts.sales) charts.sales.destroy();
                    charts.sales = initSalesChart(generateSalesData());
                    
                    if (charts.products) charts.products.destroy();
                    charts.products = initProductsChart(generateProductsData());
                    
                    if (charts.customers) charts.customers.destroy();
                    charts.customers = initCustomersChart(generateCustomersData());
                    
                    if (charts.revenue) charts.revenue.destroy();
                    charts.revenue = initRevenueSourcesChart(generateRevenueData());
                    
                    if (charts.category) charts.category.destroy();
                    charts.category = initCategoryChart(generateCategoryData());
                    
                    showLoading(false);
                }, 1500);
            }
            
            // Show/hide loading spinner
            function showLoading(show) {
                document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
                document.getElementById('statsOverview').style.display = show ? 'none' : 'block';
            }
            
            // Update stats cards with data
            function updateStatsCards(data) {
                document.getElementById('totalRevenue').textContent = 'R' + data.totalRevenue.toLocaleString();
                document.getElementById('totalOrders').textContent = data.totalOrders.toLocaleString();
                document.getElementById('newCustomers').textContent = data.newCustomers.toLocaleString();
                document.getElementById('conversionRate').textContent = data.conversionRate + '%';
            }
            
            // Set up event listeners for chart controls
            function setupEventListeners() {
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
                            const currentData = chart.data;
                            chart.destroy();
                            
                            if (chartId === 'salesChart') {
                                if (chartType === 'line') charts.sales = initSalesChart(currentData);
                                else if (chartType === 'bar') charts.sales = initSalesBarChart(currentData);
                                else if (chartType === 'area') charts.sales = initSalesAreaChart(currentData);
                            } else if (chartId === 'productsChart') {
                                if (chartType === 'doughnut') charts.products = initProductsChart(currentData);
                                else if (chartType === 'pie') charts.products = initProductsPieChart(currentData);
                                else if (chartType === 'bar') charts.products = initProductsBarChart(currentData);
                            } else if (chartId === 'customersChart') {
                                if (chartType === 'bar') charts.customers = initCustomersChart(currentData);
                                else if (chartType === 'line') charts.customers = initCustomersLineChart(currentData);
                                else if (chartType === 'doughnut') charts.customers = initCustomersDoughnutChart(currentData);
                            }
                        }
                    });
                });
                
                // Toggle data labels
                document.getElementById('dataLabelsToggle').addEventListener('change', function() {
                    const isVisible = this.checked;
                    Object.values(charts).forEach(chart => {
                        if (chart && chart.options.plugins.datalabels) {
                            chart.options.plugins.datalabels.display = isVisible;
                            chart.update();
                        }
                    });
                });
                
                // Toggle grid lines
                document.getElementById('gridLinesToggle').addEventListener('change', function() {
                    const isVisible = this.checked;
                    Object.values(charts).forEach(chart => {
                        if (chart && chart.options.scales) {
                            if (chart.options.scales.x) chart.options.scales.x.grid.display = isVisible;
                            if (chart.options.scales.y) chart.options.scales.y.grid.display = isVisible;
                        }
                        if (chart) chart.update();
                    });
                });
                
                // Toggle animations
                document.getElementById('animationsToggle').addEventListener('change', function() {
                    const isEnabled = this.checked;
                    Object.values(charts).forEach(chart => {
                        if (chart) {
                            chart.options.animation = isEnabled;
                            chart.update();
                        }
                    });
                });
                
                // Export chart as image
                document.getElementById('exportChart').addEventListener('click', function() {
                    const activeTab = document.querySelector('.tab-pane.active');
                    const canvas = activeTab.querySelector('canvas');
                    if (canvas) {
                        const imageLink = document.createElement('a');
                        const filename = 'homewareontap_chart_' + new Date().toISOString().slice(0, 10) + '.png';
                        
                        imageLink.href = canvas.toDataURL('image/png');
                        imageLink.download = filename;
                        document.body.appendChild(imageLink);
                        imageLink.click();
                        document.body.removeChild(imageLink);
                    }
                });
                
                // Generate report
                document.getElementById('generateReport').addEventListener('click', function() {
                    // In a real application, this would generate a PDF or CSV report
                    alert('Report generation would be implemented here. This would typically create a PDF or CSV file with all the current dashboard data.');
                });
                
                // Refresh data
                document.getElementById('refreshData').addEventListener('click', function() {
                    loadDashboardData();
                });
                
                // Apply filter
                document.getElementById('applyFilter').addEventListener('click', function() {
                    const startDate = new Date(document.getElementById('startDate').value);
                    const endDate = new Date(document.getElementById('endDate').value);
                    const preset = document.getElementById('presetRange').value;
                    
                    if (startDate && endDate && startDate <= endDate) {
                        currentDateRange.start = startDate;
                        currentDateRange.end = endDate;
                        currentDateRange.preset = preset;
                        loadDashboardData();
                    } else {
                        alert('Please select a valid date range.');
                    }
                });
                
                // Preset range change
                document.getElementById('presetRange').addEventListener('change', function() {
                    const preset = this.value;
                    if (preset !== 'custom') {
                        const endDate = new Date();
                        const startDate = new Date();
                        startDate.setDate(startDate.getDate() - parseInt(preset));
                        
                        document.getElementById('startDate').valueAsDate = startDate;
                        document.getElementById('endDate').valueAsDate = endDate;
                    }
                });
            }
            
            // Initialize charts with data
            function initSalesChart(data) {
                const ctx = document.getElementById('salesChart').getContext('2d');
                const noDataElement = document.getElementById('salesNoData');
                
                if (!data || data.labels.length === 0) {
                    noDataElement.style.display = 'block';
                    return null;
                }
                
                noDataElement.style.display = 'none';
                return new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                align: 'top',
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked,
                                    drawBorder: false
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return 'R' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    drawBorder: false,
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initProductsChart(data) {
                const ctx = document.getElementById('productsChart').getContext('2d');
                const noDataElement = document.getElementById('productsNoData');
                
                if (!data || data.labels.length === 0) {
                    noDataElement.style.display = 'block';
                    return null;
                }
                
                noDataElement.style.display = 'none';
                return new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
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
                                        return context.label + ': R' + context.parsed.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        cutout: '70%',
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initCustomersChart(data) {
                const ctx = document.getElementById('customersChart').getContext('2d');
                const noDataElement = document.getElementById('customersNoData');
                
                if (!data || data.labels.length === 0) {
                    noDataElement.style.display = 'block';
                    return null;
                }
                
                noDataElement.style.display = 'none';
                return new Chart(ctx, {
                    type: 'bar',
                    data: data,
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
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initRevenueSourcesChart(data) {
                const ctx = document.getElementById('revenueSourcesChart').getContext('2d');
                const noDataElement = document.getElementById('revenueNoData');
                
                if (!data || data.labels.length === 0) {
                    noDataElement.style.display = 'block';
                    return null;
                }
                
                noDataElement.style.display = 'none';
                return new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
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
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        cutout: '60%',
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initCategoryChart(data) {
                const ctx = document.getElementById('categoryChart').getContext('2d');
                const noDataElement = document.getElementById('categoryNoData');
                
                if (!data || data.labels.length === 0) {
                    noDataElement.style.display = 'block';
                    return null;
                }
                
                noDataElement.style.display = 'none';
                return new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(1) + 'k';
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return 'R' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            // Data generation functions (replace with actual API calls in production)
            function generateStatsData() {
                return {
                    totalRevenue: 24582,
                    totalOrders: 1248,
                    newCustomers: 892,
                    conversionRate: 42.8
                };
            }
            
            function generateSalesData() {
                return {
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
                };
            }
            
            function generateProductsData() {
                return {
                    labels: ['Glassware', 'Kitchenware', 'Home Decor', 'Tableware', 'Lighting'],
                    datasets: [{
                        data: [12500, 9800, 7500, 6200, 4800],
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
                };
            }
            
            function generateCustomersData() {
                return {
                    labels: ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State'],
                    datasets: [{
                        label: 'Customers',
                        data: [350, 410, 280, 190, 120],
                        backgroundColor: chartColors.info,
                        borderColor: chartColors.info,
                        borderWidth: 1
                    }]
                };
            }
            
            function generateRevenueData() {
                return {
                    labels: ['Online Store', 'Physical Store', 'Wholesale', 'Marketplace'],
                    datasets: [{
                        data: [55, 25, 15, 5],
                        backgroundColor: [
                            chartColors.primary,
                            chartColors.success,
                            chartColors.info,
                            chartColors.warning
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9',
                            '#17a673',
                            '#2c9faf',
                            '#dda20a'
                        ],
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                        borderWidth: 2
                    }]
                };
            }
            
            function generateCategoryData() {
                return {
                    labels: ['Glassware', 'Kitchenware', 'Home Decor', 'Tableware', 'Lighting', 'Storage'],
                    datasets: [{
                        label: 'Sales (R)',
                        data: [12500, 9800, 7500, 6200, 4800, 3200],
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
                };
            }
            
            // Alternative chart type initializers
            function initSalesBarChart(data) {
                const ctx = document.getElementById('salesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return 'R' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initSalesAreaChart(data) {
                const ctx = document.getElementById('salesChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                align: 'top',
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked,
                                    drawBorder: false
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return 'R' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    drawBorder: false,
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initProductsPieChart(data) {
                const ctx = document.getElementById('productsChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'pie',
                    data: data,
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
                                        return context.label + ': R' + context.parsed.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initProductsBarChart(data) {
                const ctx = document.getElementById('productsChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'R' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return 'R' + (value/1000).toFixed(0) + 'k';
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6,
                                    callback: function(value) {
                                        return 'R' + (value/1000).toFixed(0) + 'k';
                                    }
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initCustomersLineChart(data) {
                const ctx = document.getElementById('customersChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: data,
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
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    maxTicksLimit: 6
                                },
                                grid: {
                                    color: 'rgb(234, 236, 244)',
                                    display: document.getElementById('gridLinesToggle').checked
                                }
                            }
                        },
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
            
            function initCustomersDoughnutChart(data) {
                const ctx = document.getElementById('customersChart').getContext('2d');
                return new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
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
                                        return context.label + ': ' + context.parsed + ' customers';
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value) {
                                    return value;
                                },
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                display: document.getElementById('dataLabelsToggle').checked
                            }
                        },
                        cutout: '70%',
                        animation: document.getElementById('animationsToggle').checked
                    }
                });
            }
        });
    </script>
</body>
</html>