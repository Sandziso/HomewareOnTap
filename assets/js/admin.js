<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - HomewareOnTap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }
        
        #wrapper {
            display: flex;
        }
        
        #content-wrapper {
            width: 100%;
            overflow-x: hidden;
        }
        
        #content {
            flex: 1 0 auto;
        }
        
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
            color: white;
        }
        
        .sidebar .nav-item {
            margin-bottom: 5px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
        }
        
        .sidebar .nav-link:hover {
            color: white;
        }
        
        .sidebar .nav-link.active {
            color: white;
            font-weight: bold;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .topbar {
            height: 70px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(180deg, var(--success) 10%, #13855c 100%);
        }
        
        .bg-gradient-info {
            background: linear-gradient(180deg, var(--info) 10%, #2a96a5 100%);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(180deg, var(--warning) 10%, #dda20a 100%);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #3a5fc8;
            border-color: #3a5fc8;
        }
        
        .sidebar-toggle {
            color: var(--dark);
        }
        
        .chart-area {
            position: relative;
            height: 250px;
        }
        
        .dataTables_wrapper {
            padding: 0;
        }
        
        .dataTables_filter {
            text-align: right;
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: var(--dark);
            color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            z-index: 1000;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--success);
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        
        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                z-index: 1000;
                display: none;
            }
            
            .sidebar.show {
                display: block;
            }
            
            body.sidebar-toggled #content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <h5 class="text-center mb-4">HomewareOnTap Admin</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-bag"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-users"></i>
                            Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-bar"></i>
                            Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-tags"></i>
                            Discounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="#">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggle" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Admin User</span>
                                <img class="img-profile rounded-circle" src="https://via.placeholder.com/40">
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                        </a>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Earnings (Monthly)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$40,000</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Earnings (Annual)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$215,000</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Orders</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">145</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Requests Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Pending Requests</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">18</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Area Chart -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Earnings Overview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="earningsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie Chart -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Revenue Sources</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-primary"></i> Direct
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-success"></i> Social
                                        </span>
                                        <span class="mr-2">
                                            <i class="fas fa-circle text-info"></i> Referral
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>ORD-1234</td>
                                                    <td>John Smith</td>
                                                    <td>2023-10-15</td>
                                                    <td>$245.99</td>
                                                    <td><span class="badge badge-success">Completed</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary">View</button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>ORD-1235</td>
                                                    <td>Jane Doe</td>
                                                    <td>2023-10-14</td>
                                                    <td>$149.50</td>
                                                    <td><span class="badge badge-warning">Processing</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary">View</button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>ORD-1236</td>
                                                    <td>Robert Johnson</td>
                                                    <td>2023-10-14</td>
                                                    <td>$89.99</td>
                                                    <td><span class="badge badge-danger">Cancelled</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary">View</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; HomewareOnTap 2023</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification">
        Operation completed successfully!
    </div>

    <script>
        // admin.js - Admin Panel Functionality for HomewareOnTap
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the admin panel
            const adminPanel = new AdminPanel();
            adminPanel.init();
        });

        class AdminPanel {
            constructor() {
                this.currentView = 'dashboard';
                this.charts = {};
                this.dataTables = {};
            }

            init() {
                this.setupEventListeners();
                this.initializeCharts();
                this.initializeDataTables();
                this.checkAuthStatus();
                this.loadDashboardData();
            }

            // Setup event listeners for the admin panel
            setupEventListeners() {
                // Sidebar toggle
                document.getElementById('sidebarToggle').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleSidebar();
                });

                // Navigation links
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const target = e.target.getAttribute('data-target') || e.target.hash;
                        if (target === '#logout') {
                            this.logout();
                        } else {
                            this.navigateTo(target);
                        }
                    });
                });

                // Responsive sidebar behavior
                window.addEventListener('resize', () => {
                    this.handleResize();
                });

                // Initialize tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // Initialize popovers
                $('[data-toggle="popover"]').popover();
            }

            // Toggle sidebar visibility
            toggleSidebar() {
                document.querySelector('.sidebar').classList.toggle('show');
                document.body.classList.toggle('sidebar-toggled');
            }

            // Handle window resize
            handleResize() {
                if (window.innerWidth < 768) {
                    document.querySelector('.sidebar').classList.remove('show');
                }
            }

            // Navigate to different admin sections
            navigateTo(view) {
                this.currentView = view;
                
                // Update active navigation link
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                document.querySelector(`[data-target="${view}"]`).classList.add('active');
                
                // Load content for the selected view
                this.loadViewContent(view);
            }

            // Load content for the selected view
            loadViewContent(view) {
                // In a real application, this would fetch content from the server
                // For this demo, we'll just show a notification
                this.showNotification(`Switched to ${view} view`);
                
                // Special initialization for different views
                switch(view) {
                    case 'products':
                        this.initializeProductManagement();
                        break;
                    case 'orders':
                        this.initializeOrderManagement();
                        break;
                    case 'customers':
                        this.initializeCustomerManagement();
                        break;
                    case 'analytics':
                        this.initializeAnalytics();
                        break;
                }
            }

            // Initialize charts for the dashboard
            initializeCharts() {
                // Earnings Chart
                const earningsCtx = document.getElementById('earningsChart');
                if (earningsCtx) {
                    this.charts.earnings = new Chart(earningsCtx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            datasets: [{
                                label: 'Earnings',
                                data: [0, 10000, 5000, 15000, 10000, 20000, 15000, 25000, 20000, 30000, 25000, 40000],
                                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                                borderColor: 'rgba(78, 115, 223, 1)',
                                pointRadius: 3,
                                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                                pointBorderColor: 'rgba(78, 115, 223, 1)',
                                pointHoverRadius: 3,
                                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                                pointHitRadius: 10,
                                pointBorderWidth: 2,
                                tension: 0.3
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    ticks: {
                                        maxTicksLimit: 5,
                                        callback: function(value) {
                                            return '$' + value;
                                        }
                                    },
                                    grid: {
                                        color: 'rgb(234, 236, 244)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgb(255, 255, 255)',
                                    bodyColor: '#858796',
                                    titleColor: '#6e707e',
                                    titleMarginBottom: 10,
                                    borderColor: '#dddfeb',
                                    borderWidth: 1,
                                    padding: 15,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return '$' + context.parsed.y;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart');
                if (revenueCtx) {
                    this.charts.revenue = new Chart(revenueCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Direct', 'Social', 'Referral'],
                            datasets: [{
                                data: [55, 30, 15],
                                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                                hoverBorderColor: 'rgba(234, 236, 244, 1)'
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgb(255, 255, 255)',
                                    bodyColor: '#858796',
                                    borderColor: '#dddfeb',
                                    borderWidth: 1,
                                    padding: 15,
                                    displayColors: false
                                }
                            }
                        }
                    });
                }
            }

            // Initialize DataTables
            initializeDataTables() {
                // Orders table
                if ($.fn.DataTable) {
                    this.dataTables.orders = $('#ordersTable').DataTable({
                        pageLength: 10,
                        responsive: true,
                        ordering: true,
                        order: [[2, 'desc']]
                    });
                }
            }

            // Check authentication status
            checkAuthStatus() {
                // In a real application, this would verify the user's session
                const isAuthenticated = localStorage.getItem('admin_authenticated') === 'true';
                if (!isAuthenticated) {
                    // Redirect to login page if not authenticated
                    window.location.href = '/admin/login.html';
                }
            }

            // Logout function
            logout() {
                localStorage.removeItem('admin_authenticated');
                window.location.href = '/admin/login.html';
            }

            // Load dashboard data
            loadDashboardData() {
                // In a real application, this would fetch data from the server
                // Simulate API call
                setTimeout(() => {
                    // Update dashboard metrics
                    this.updateDashboardMetrics({
                        monthlyEarnings: 40000,
                        annualEarnings: 215000,
                        orders: 145,
                        pendingRequests: 18
                    });
                }, 1000);
            }

            // Update dashboard metrics
            updateDashboardMetrics(data) {
                document.querySelector('.card-border-left-primary .h5').textContent = '$' + data.monthlyEarnings;
                document.querySelector('.card-border-left-success .h5').textContent = '$' + data.annualEarnings;
                document.querySelector('.card-border-left-info .h5').textContent = data.orders;
                document.querySelector('.card-border-left-warning .h5').textContent = data.pendingRequests;
            }

            // Initialize product management
            initializeProductManagement() {
                // In a real application, this would set up product management functionality
                console.log('Initializing product management');
            }

            // Initialize order management
            initializeOrderManagement() {
                // In a real application, this would set up order management functionality
                console.log('Initializing order management');
            }

            // Initialize customer management
            initializeCustomerManagement() {
                // In a real application, this would set up customer management functionality
                console.log('Initializing customer management');
            }

            // Initialize analytics
            initializeAnalytics() {
                // In a real application, this would set up analytics functionality
                console.log('Initializing analytics');
            }

            // Show notification
            showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                notification.textContent = message;
                
                // Set notification color based on type
                if (type === 'error') {
                    notification.style.backgroundColor = '#e74a3b';
                } else if (type === 'warning') {
                    notification.style.backgroundColor = '#f6c23e';
                } else {
                    notification.style.backgroundColor = '#1cc88a';
                }
                
                notification.classList.add('show');
                
                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }

            // API request helper
            async apiRequest(endpoint, options = {}) {
                try {
                    const response = await fetch(`/admin/api/${endpoint}`, {
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        ...options
                    });
                    
                    if (!response.ok) {
                        throw new Error(`API error: ${response.status}`);
                    }
                    
                    return await response.json();
                } catch (error) {
                    console.error('API request failed:', error);
                    this.showNotification('An error occurred while communicating with the server', 'error');
                    throw error;
                }
            }
        }

        // Additional admin functionality can be added as separate classes or modules
        class ProductManager {
            constructor() {
                this.products = [];
            }

            // Load products from server
            async loadProducts() {
                try {
                    const data = await adminPanel.apiRequest('products');
                    this.products = data.products;
                    this.renderProducts();
                } catch (error) {
                    console.error('Failed to load products:', error);
                }
            }

            // Render products in the UI
            renderProducts() {
                // Implementation for rendering products
            }

            // Add a new product
            async addProduct(productData) {
                try {
                    const result = await adminPanel.apiRequest('products', {
                        method: 'POST',
                        body: JSON.stringify(productData)
                    });
                    
                    adminPanel.showNotification('Product added successfully');
                    this.loadProducts(); // Reload the product list
                    
                    return result;
                } catch (error) {
                    console.error('Failed to add product:', error);
                    throw error;
                }
            }

            // Update an existing product
            async updateProduct(productId, productData) {
                try {
                    const result = await adminPanel.apiRequest(`products/${productId}`, {
                        method: 'PUT',
                        body: JSON.stringify(productData)
                    });
                    
                    adminPanel.showNotification('Product updated successfully');
                    this.loadProducts(); // Reload the product list
                    
                    return result;
                } catch (error) {
                    console.error('Failed to update product:', error);
                    throw error;
                }
            }

            // Delete a product
            async deleteProduct(productId) {
                try {
                    if (!confirm('Are you sure you want to delete this product?')) {
                        return;
                    }
                    
                    const result = await adminPanel.apiRequest(`products/${productId}`, {
                        method: 'DELETE'
                    });
                    
                    adminPanel.showNotification('Product deleted successfully');
                    this.loadProducts(); // Reload the product list
                    
                    return result;
                } catch (error) {
                    console.error('Failed to delete product:', error);
                    throw error;
                }
            }
        }

        // Initialize when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the admin panel
            const adminPanel = new AdminPanel();
            adminPanel.init();

            // Make adminPanel available globally for other scripts
            window.adminPanel = adminPanel;
        });
    </script>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
</body>
</html>