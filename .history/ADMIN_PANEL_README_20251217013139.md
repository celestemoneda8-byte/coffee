## EXpresso Admin Panel - Access Instructions

### How to Access the Admin Panel

1. **Start XAMPP** and ensure Apache and MySQL are running
2. **Access the login page** via your browser:
   ```
   http://localhost/EXpresso/coffee/AdminSide/includes/admin-login.php
   ```

3. **Login credentials:**
   - Username: `admin`
   - Password: `admin123`

### Available Pages After Login

After successful login, you can access:

- **Dashboard** - Overview and statistics
- **Orders** - Manage customer orders
- **Menu** - Manage products and categories
- **Delivery** - Track delivery orders
- **Users** - Manage admin users and customers
- **Bank Info** - Payment methods management
- **Sales Report** - Sales analytics and reports
- **Settings** - Application configuration and theme settings

### Directory Structure

```
AdminSide/
├── includes/
│   └── admin-login.php      # Login page
├── pages/
│   ├── dashboard.php        # Dashboard
│   ├── orders.php           # Orders management
│   ├── menu.php             # Menu management
│   ├── delivery.php         # Delivery tracking
│   ├── users.php            # User management
│   ├── bankinfo.php         # Payment methods
│   ├── sales_report.php     # Sales reports
│   ├── settings.php         # Settings
│   ├── sidebar.php          # Navigation sidebar
│   ├── header.php           # Header component
│   ├── footer.php           # Footer component
│   ├── logout.php           # Logout handler
│   ├── menu_actions.php     # Menu CRUD actions
│   └── order_actions.php    # Order CRUD actions
├── config/
│   ├── config.php           # Configuration & constants
│   └── db_connect.php       # Database connection
├── css/
│   ├── admin-sidebar-layout.css
│   ├── dashboard.css
│   └── style.css
├── js/
│   └── [JavaScript files]
└── api/
    └── [API endpoints]
```

### Important Notes

- All pages require login to access (protected by session checks)
- Redirects are automatically generated based on your domain/localhost
- Database connection is configured in `config/db_connect.php`
- All pages include sidebar, header, and footer for consistent navigation
- Styling is applied using Bootstrap 5.3.0 and custom admin stylesheet

### Troubleshooting

**If you get a 404 error:**
- Make sure you're accessing the correct URL path
- Verify that Apache is serving files from `htdocs` directory
- Check that all files exist in the AdminSide folder

**If login doesn't work:**
- Try with default credentials: `admin` / `admin123`
- Check that sessions are enabled in your PHP configuration
- Verify cookies are allowed in your browser

**If pages don't load after login:**
- Clear your browser cache
- Check that database is running (if using database authentication)
- Verify the sidebar and header includes are working correctly
