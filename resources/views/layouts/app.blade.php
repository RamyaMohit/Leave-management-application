<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'HR Leave Management System') }}</title>

    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-bg: #f4f6f9;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --brand-primary: #4f46e5;
            --brand-secondary: #06b6d4;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--primary-bg);
            color: #1e293b;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #f8fafc;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar .brand {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
        }

        .sidebar .nav-link {
            color: #94a3b8;
            padding: 0.8rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: var(--sidebar-hover);
        }

        .sidebar .nav-link.active {
            background: var(--brand-primary);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }

        .top-navbar {
            background: #fff;
            padding: 1rem 2rem;
            margin-left: 260px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
        }

        .badge-status {
            padding: 0.4em 0.8em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-PENDING { background-color: #fef3c7; color: #d97706; }
        .badge-MANAGER_APPROVED { background-color: #e0f2fe; color: #0284c7; }
        .badge-HR_APPROVED { background-color: #e0e7ff; color: #4338ca; }
        .badge-APPROVED { background-color: #dcfce7; color: #15803d; }
        .badge-REJECTED { background-color: #fee2e2; color: #b91c1c; }
        .badge-CANCELLED { background-color: #f1f5f9; color: #64748b; }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .sidebar .nav-link span { display: none; }
            .main-content, .top-navbar { margin-left: 80px; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-briefcase me-2"></i>
            <span>HR Leave Approvals</span>
        </div>
        <div class="nav flex-column mt-3">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('leave-requests.index') }}" class="nav-link {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Leave Requests</span>
            </a>

            @if(auth()->user()->hasRole('HR', 'ADMIN'))
            <div class="px-3 mt-3 mb-1 text-uppercase text-muted text-xs fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Management</div>
            <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Employees</span>
            </a>
            <a href="{{ route('leave-types.index') }}" class="nav-link {{ request()->routeIs('leave-types.*') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders"></i>
                <span>Leave Types</span>
            </a>
            <a href="{{ route('holidays.index') }}" class="nav-link {{ request()->routeIs('holidays.*') ? 'active' : '' }}">
                <i class="fa-solid fa-umbrella-beach"></i>
                <span>Holidays</span>
            </a>
            <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Audit Logs</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <h5 class="m-0 fw-bold text-dark">@yield('page-title', 'HR Leave System')</h5>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end me-2 d-none d-sm-block">
                <div class="fw-bold text-dark fs-6">{{ auth()->user()->name }}</div>
                <span class="badge bg-indigo-100 text-indigo-700 border border-indigo-200 px-2 py-1" style="font-size: 0.7rem; background:#e0e7ff; color:#3730a3;">
                    {{ auth()->user()->role }}
                </span>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container">
        <div id="appToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Setup CSRF header for all jQuery AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        function showToast(message, isSuccess = true) {
            const toastEl = $('#appToast');
            toastEl.removeClass('bg-success bg-danger').addClass(isSuccess ? 'bg-success' : 'bg-danger');
            $('#toastMessage').text(message);
            const toast = new bootstrap.Toast(toastEl[0]);
            toast.show();
        }
    </script>
    @yield('scripts')
</body>
</html>
