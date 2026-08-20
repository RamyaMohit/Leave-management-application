# Leave Management System

A Laravel-based HR Leave & Approval Management System to manage employee leave applications, approval workflows, and leave balances.

---

## Requirements

- PHP >= 8.1
- Composer
- MySQL or SQLite

---

## Setup & Installation

### 1. Clone Repository & Install Dependencies
```bash
git clone <repository-url>
cd LeaveManagementSystem

composer install
```

### 2. Environment Setup
Copy the example environment file and generate the app key:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Configuration
Open `.env` and set up your database credentials:

**For MySQL:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leave_management
DB_USERNAME=root
DB_PASSWORD=
```
Make sure to create the database in MySQL first:
```sql
CREATE DATABASE leave_management;
```

**For SQLite (Alternative):**
```env
DB_CONNECTION=sqlite
```
Create the SQLite database file if using SQLite:
```bash
touch database/database.sqlite
```

### 4. Run Migrations & Seeders
Run migrations to create tables and seed default users, leave types, and holidays:
```bash
php artisan migrate:fresh --seed
```

### 5. Start Application
```bash
php artisan serve
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Default Test Users

All default seeded accounts use the password: `password`

| Role | Email | Employee Code |
|------|-------|---------------|
| Admin | `admin@company.com` | EMP001 |
| HR | `hr@company.com` | EMP002 |
| Manager | `manager@company.com` | EMP003 |
| Employee | `employee@company.com` | EMP004 |

---

## Running Tests

To run the automated test suite:
```bash
php artisan test
```