💸Expense Management System

A full-stack personal finance web application built with PHP and MySQL. Track your daily expenses, set monthly budgets, visualise spending patterns with charts, and manage users —> all from a clean, responsive dashboard.

📸Features

|Feature         |Description                                                                                 |

|📊 Dashboard     |Live stat cards, pie chart (by category), line chart (daily trend), filterable expense table|
|➕ Add Expense   |Log expenses with amount, category, date, and description                                   |
|🎯 Budget Tracker|Set a monthly budget; colour-coded progress bar (green → warning → over-budget)             |
|📈 Reports       |Annual bar chart + category breakdown; one-click printable reports                          |
|👤 Profile       |Update name/email and change password                                                       |
|👑 Admin Panel   |View all users, add/edit/delete users, toggle active status                                 |
|🔒 Security      |Session-based auth, bcrypt password hashing, CSRF token protection, `.htaccess` access rules|


🛠️Tech Stack

- Backend: PHP 8 with PDO (prepared statements)
- Database: MySQL 5.7+ / MariaDB
- Frontend: HTML5, CSS3 (custom CSS variables), vanilla JavaScript
- Charts: [Chart.js 4.4](https://www.chartjs.org/)
- Fonts: Google Fonts — Playfair Display + DM Sans
- Server: Apache (XAMPP / WAMP / Laragon or any PHP host)


📁Project Structure

watchmywallet/
├── index.php               # Login page
├── signup.php              # User registration
├── dashboard.php           # Main dashboard
├── add_expense.php         # Add new expense
├── budget.php              # Budget management
├── reports.php             # Annual reports
├── profile.php             # User profile & password change
├── logout.php              # Session destroy
│
├── admin/
│   ├── users.php           # Admin: user management
│   └── all_expenses.php    # Admin: view all expenses
│
├── includes/
│   ├── config.php          # DB config & PDO connection
│   ├── auth.php            # Login, session, CSRF helpers
│   ├── expenses.php        # All expense/budget query functions
│   └── sidebar.php         # Shared navigation sidebar
│
├── assets/
│   └── css/
│       └── style.css       # Full stylesheet with CSS variables
│
└── watchmywallet.sql       # Database schema + seed data


⚙️Installation

Prerequisites

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Apache with `mod_rewrite` enabled (XAMPP, WAMP, or Laragon recommended)

Steps

1. Clone the repository

```bash
git clone https://github.com/your-username/expense-management-system.git
```

2. Move to your server’s web root

```bash
cp -r expense-management-system /xampp/htdocs/watchmywallet
```

3. Create the database

Open phpMyAdmin (or your MySQL client) and import the SQL file:

watchmywallet.sql

This will create the `watchmywallet` database, all 4 tables, and seed the default expense categories.

4. Configure the database connection

Edit `includes/config.php`:

```php
define('DB_HOST',  'localhost');
define('DB_USER',  'root');
define('DB_PASS',  '');
define('DB_NAME',  'watchmywallet');
define('APP_URL',  'http://localhost/watchmywallet');
```

5. Run the app

Open your browser and go to:

```
http://localhost/watchmywallet
```

🔑Default Admin Credentials

After importing the SQL file, an admin account is created automatically:

|Field   |Value     |

|Username|`admin`   |
|Password|`password`|


⚠️Change the admin password immediately after your first login.

🗄️Database Schema

users        — id, full_name, email, username, password (bcrypt), role, monthly_budget, is_active
categories   — id, name, icon (emoji), color (hex)
expenses     — id, user_id (FK), category_id (FK), amount, description, expense_date
budgets      — id, user_id (FK), category_id (FK), month_year, budget_amount

Default Expense Categories

🍽️ Food & Dining · 🚗 Transport · 🛍️ Shopping · 🎬 Entertainment · 🏥 Health & Medical · 💡 Utilities · 📚 Education · ✈️ Travel · 🏠 Rent/Housing · 💼 Other

🔐Security Notes

- Passwords are hashed using "bcrypt" (`PASSWORD_BCRYPT`, cost factor 12)
- All database queries use "PDO prepared statements" —> no raw SQL interpolation
- "CSRF tokens" are validated on every POST request
- The `includes/` directory is protected by `.htaccess` to block direct browser access
- Sessions are regenerated on login to prevent session fixation attacks


📊Dashboard Filters

The dashboard supports flexible filtering:

- Month Preset — quick-select any of the last 12 months
- Date Range — custom from/to date picker
- Search — real-time search across expense descriptions

All filters affect the stat cards, charts, and expense table simultaneously.


🖨️Print Reports

Any page can be printed cleanly. The sidebar, filter bar, and action buttons are hidden via `@media print` CSS rules, leaving only the data tables and charts.


🚀Possible Improvements

- [ ] Export expenses to CSV / Excel
- [ ] Email notifications when approaching budget limit
- [ ] Multi-currency support
- [ ] Dark mode toggle
- [ ] REST API for mobile app integration
- [ ] Recurring expense tracking


📄License

This project was developed for academic and educational purposes as part of a postgraduate diploma program.

You are free to view, learn from, and modify the source code for personal or educational use. Proper credit to the original author is appreciated.

🙋‍♂️Author

Jwalant Rohit.

Developer of "Watch My Wallet – Expense Management System"

This project was designed and implemented as an academic mini-project to help users track expenses, manage budgets, and analyze spending patterns.

Suggestions, feedback, and improvements are welcome.