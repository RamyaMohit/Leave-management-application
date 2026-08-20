<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            padding: 2.5rem 2rem;
            color: white;
            text-align: center;
        }
        .quick-role-btn {
            font-size: 0.8rem;
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="mb-2">
                <i class="fa-solid fa-briefcase fa-3x"></i>
            </div>
            <h4 class="fw-bold mb-1">HR Leave Management</h4>
            <p class="mb-0 text-white-50 fs-6">Sign in to your portal</p>
        </div>

        <div class="p-4 p-sm-5">
            @if ($errors->any())
                <div class="alert alert-danger rounded-3 py-2 fs-6 mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold fs-6">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-envelope"></i></span>
                        <input type="email" id="emailInput" name="email" class="form-control bg-light border-start-0" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary fw-semibold fs-6">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="passwordInput" name="password" class="form-control bg-light border-start-0" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold rounded-3 shadow-sm mb-4" style="background: #4f46e5; border: none;">
                    Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="border-top pt-3 text-center">
                <div class="text-muted fs-6 mb-2 fw-semibold">Quick Test Login Credentials:</div>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary quick-role-btn" onclick="fillCreds('employee@company.com')">Employee</button>
                    <button type="button" class="btn btn-outline-info quick-role-btn" onclick="fillCreds('manager@company.com')">Manager</button>
                    <button type="button" class="btn btn-outline-secondary quick-role-btn" onclick="fillCreds('hr@company.com')">HR</button>
                    <button type="button" class="btn btn-outline-dark quick-role-btn" onclick="fillCreds('admin@company.com')">Admin</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillCreds(email) {
            document.getElementById('emailInput').value = email;
            document.getElementById('passwordInput').value = 'password';
        }
    </script>
</body>
</html>
