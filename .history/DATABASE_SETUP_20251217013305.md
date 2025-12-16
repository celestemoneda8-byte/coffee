## Database Setup Instructions

### Step 1: Create the Database

You need to import the SQL file to create the database and tables.

#### Using phpMyAdmin (Easiest):

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on "Import" tab
3. Click "Choose File" and select: `C:\xampp\htdocs\EXpresso\coffee\expresso_caffe.sql`
4. Click "Import" button
5. The database `expresso_caffe` will be created with all tables

#### Using MySQL Command Line:

```bash
mysql -u root -p < C:\xampp\htdocs\EXpresso\coffee\expresso_caffe.sql
```

Or if you don't have a password:

```bash
mysql -u root < C:\xampp\htdocs\EXpresso\coffee\expresso_caffe.sql
```

#### Using XAMPP Control Panel:

1. Start MySQL from XAMPP Control Panel
2. Open a terminal and navigate to MySQL bin folder:
   ```bash
   cd C:\xampp\mysql\bin
   ```
3. Run:
   ```bash
   mysql -u root -p < "C:\xampp\htdocs\EXpresso\coffee\expresso_caffe.sql"
   ```

### Step 2: Verify Database Creation

After importing, verify the database exists:

1. In phpMyAdmin, you should see `expresso_caffe` in the left sidebar
2. It should contain tables like: orders, products, customers, categories, etc.

### Step 3: Access Admin Panel

Now you can access the admin panel:

```
http://localhost/EXpresso/coffee/AdminSide/includes/admin-login.php
```

Login with:
- Username: `admin`
- Password: `admin123`

### Troubleshooting

**If import fails with "Unknown database":**
- Make sure you selected the correct SQL file
- Ensure MySQL is running in XAMPP
- Check that you have proper permissions

**If you see "Access denied":**
- Verify your MySQL username (default: `root`)
- Check if there's a password set (default: none)
- Update credentials in `AdminSide/config/db_connect.php` if needed

**To check current database:**
```bash
mysql -u root -e "SHOW DATABASES;"
```

This will list all databases. You should see `expresso_caffe` in the list.
