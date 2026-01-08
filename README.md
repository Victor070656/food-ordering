# FoodSys - Home-Cooked Meal Delivery Management System

A comprehensive web-based management system for home-cooked meal delivery operations. Built with vanilla PHP, MySQL, and TailwindCSS.

## Features

### Customer Facing
- **Browse Menu** - View available meals with prices and descriptions
- **Place Orders** - Easy checkout with multiple payment options
- **Order Tracking** - View order history and current status
- **Multiple Payment Methods**:
  - Cash on Delivery
  - Bank Transfer (with receipt upload)
  - POS Payment (with receipt upload)

### Admin Panel
- **Dashboard** - Overview of today's orders, revenue, and statistics
- **Order Management** - Full order lifecycle management
  - View all orders with filtering (status, date, search)
  - Update order status (pending → preparing → out for delivery → delivered)
  - Assign riders to orders
  - Order details page with complete information
- **Menu Management** - Add, edit, delete menu items with images
- **Customer Management** - View customer profiles, order history, total spent
- **Rider Management** - Manage delivery riders
- **Payment Tracking** - Track payments and verify uploaded receipts
- **Notification System** - Send notifications to customers
- **Payment Settings** - Configure bank account details for transfers/POS

### Staff Panel
- **Kitchen Dashboard** - Real-time view of orders to prepare
- **Quick Status Updates** - One-click status changes
- **Rider Assignment** - Assign riders when orders are ready

## Tech Stack

- **Backend**: PHP 8.2+
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla JavaScript, TailwindCSS
- **Server**: XAMPP / LAMPP

## Installation

1. **Prerequisites**
   - XAMPP or LAMPP installed
   - PHP 8.2 or higher
   - MySQL/MariaDB

2. **Setup**
   ```bash
   # Clone/copy the project to htdocs
   cd /opt/lampp/htdocs/food-sys

   # Create database
   /opt/lampp/bin/mysql -u root -e "CREATE DATABASE foodsys;"

   # Import migrations (run in order)
   /opt/lampp/bin/mysql -u root foodsys < migrations/database.sql
   /opt/lampp/bin/mysql -u root foodsys < migrations/payment_settings.sql
   /opt/lampp/bin/mysql -u root foodsys < migrations/add_payment_screenshot.sql
   ```

3. **Configure**
   - Edit `config/config.php` if needed (default settings work for XAMPP)

4. **Access**
   - Customer site: `http://localhost/food-sys/`
   - Admin panel: `http://localhost/food-sys/admin/`
   - Staff panel: `http://localhost/food-sys/staff/`

## Default Login

**Admin**:
- Email: `admin@foodsys.com`
- Password: (check database or create new via registration)

**Staff**:
- Register via customer interface and assign staff role in database

## File Structure

```
food-sys/
├── admin/              # Admin panel pages
├── staff/              # Staff panel pages
├── api/                # API endpoints
├── classes/            # PHP classes (Business logic)
├── config/             # Configuration files
├── includes/           # Reusable components (header, footer)
├── migrations/         # Database migrations
├── public/             # Public assets (uploads)
└── uploads/            # File uploads directory
```

## Database Tables

- `users` - User accounts (admin, staff, customer)
- `customers` - Customer profiles
- `riders` - Delivery riders
- `menu_items` - Menu items
- `orders` - Orders
- `order_items` - Order line items
- `payments` - Payment records
- `payment_settings` - Payment configuration
- `notifications` - Customer notifications

## Usage

### Placing an Order (Customer)
1. Browse menu items
2. Add items to cart
3. Proceed to checkout
4. Select payment method (Cash/Bank Transfer/POS)
5. For Bank Transfer/POS, upload payment receipt
6. Confirm order

### Managing Orders (Staff)
1. View pending orders on dashboard
2. Click "Start Preparing" when begining order
3. Click "Mark Ready" and assign rider when complete
4. Rider sees delivery details

### Managing System (Admin)
1. **Dashboard** - View daily statistics and metrics
2. **Orders** - View, filter, update all orders
3. **Menu** - Add/edit menu items, prices, availability
4. **Customers** - View customer data and history
5. **Riders** - Manage delivery team
6. **Payments** - Track and reconcile payments
7. **Payment Settings** - Configure bank account details

## License

Proprietary - All rights reserved
