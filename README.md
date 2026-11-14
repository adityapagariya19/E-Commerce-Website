# 👓 JB Optician — E-Commerce Web Platform
### 🎓 Final Year Diploma Capstone Project

[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.5+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Framework](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Payment Gateway](https://img.shields.io/badge/Payments-Razorpay-02042B?style=for-the-badge&logo=razorpay&logoColor=3395FF)](https://razorpay.com)
[![License](https://img.shields.io/badge/License-Academic%20Project-success?style=for-the-badge)](#)

---

## 📌 Project Overview
**JB Optician E-Commerce Platform** is a full-featured, production-ready web application developed as a **Final Year Diploma Engineering Project** by **Aditya Pagariya**. 

The platform modernizes an optical retail business into an end-to-end digital storefront, offering eyewear browsing (spectacles, prescription frames, sunglasses, contact lenses, accessories), interactive virtual try-on capability, dynamic cart management, coupons/discounts, secure payments, automated invoice/order tracking, and a comprehensive role-based administrative dashboard.

---

## 👨‍💻 Project Developer Information
- **Student Name:** Aditya Pagariya
- **Project Type:** Final Year Diploma Capstone Project (Computer Engineering / Information Technology)
- **Project Title:** JB Optician E-Commerce Web Platform
- **GitHub Repository:** [adityapagariya19/E-Commerce-Website](https://github.com/adityapagariya19/E-Commerce-Website)

---

## ✨ Key Features

### 🛍️ Customer & Storefront Experience
- **Interactive Product Catalog:** Dynamic categorization (Spectacles, Sunglasses, Lenses, Accessories, Men, Kids).
- **Advanced Filtering & Search:** Filter by frame shape, color, gender, price, and brand with real-time feedback.
- **👓 Virtual Try-On Support:** Integration hook for AR/virtual try-on preview for frames.
- **Cart & Dynamic Pricing:** Session-persistent cart, tax calculation, and free-shipping thresholds.
- **Discounts & Coupon Engine:** Fixed and percentage-based coupon support with product/category constraints.
- **💳 Secure Checkout with Razorpay:** Integrated with test-mode / live-mode Razorpay checkout and automated webhooks.
- **Order Management & Invoicing:** Real-time order placement, status timeline tracking, and PDF receipts.
- **Customer Portal:** Secure login, registration, profile management, and order history.

### 🛡️ Administrative Dashboard & Operations
- **Administrator Control Panel:**
  - Full management of products, eyewear catalog, categories, and inventory.
  - Live order processing, customer records, and invoices.
  - Coupon creation, sales reports, customer inquiries, and reviews.
  - Website content customization (hero banners, brand collages, FAQs, and store policies).
- **Inventory Management:** Stock tracking with low-stock alerts and automatic order release on payment expiry.
- **Order Processing:** Lifecycle tracking (`pending`, `paid`, `shipped`, `delivered`, `cancelled`, `refunded`).
- **Store Configuration:** Announcement ticker, social links, tax rates, shipping rates, and API keys.
- **Built-in DB Browser:** Instant local browser database viewer (`tools/db-browser.sh`).

---

## 🛠️ Technology Stack

| Layer | Technologies Used |
|---|---|
| **Backend Framework** | Laravel 11.x (PHP 8.2+ / PHP 8.5 compatible) |
| **Frontend / Templates** | Blade Templating Engine, Responsive CSS3, JavaScript |
| **Database** | MySQL 8.x / MariaDB / SQLite support |
| **Payment Gateway** | Razorpay SDK & Webhook verification |
| **Testing Suite** | PHPUnit (116 automated test suites passed) |
| **Integrations** | Razorpay, Omnisend (Marketing), Chatbase (AI Assistant), GA4 |

---

## 🚀 Quick Setup & Installation Guide

### Prerequisites
- **PHP** >= 8.2 (PHP 8.5 supported) with extensions: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `curl`
- **Composer** 2.x
- **MySQL** 8.x (or SQLite)

### 1. Clone Repository & Install Dependencies
```bash
git clone https://github.com/adityapagariya19/E-Commerce-Website.git
cd E-Commerce-Website
composer install --no-security-blocking
```

### 2. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Configure your database credentials in `.env`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jb_optician
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Database Migration & Store Setup
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS jb_optician CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed
php artisan storage:link
```

### 4. Run the Application
```bash
php artisan serve
```
- **Storefront:** [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Admin Dashboard:** [http://127.0.0.1:8000/admin/login](http://127.0.0.1:8000/admin/login)

---

## 🔐 Store Administrator Credentials

| Role | Username | Email | Password |
|---|---|---|---|
| **Admin** | `admin` | `admin@gmail.com` | `admin` |

*(Customer accounts are registered directly by genuine buyers through the store's customer registration portal).*

---

## 🔍 System Health Check & Diagnostics
The project includes a built-in doctor command to verify requirements, permissions, and symlinks:
```bash
php artisan jbo:doctor
```

To run the automated test suite:
```bash
php artisan test
```

---

## 🎓 Academic Declaration
This project is developed by **Aditya Pagariya** as part of the curriculum for the **Diploma in Engineering / Technology**. It demonstrates practical proficiency in modern web architecture, relational database design, secure payment handling, and enterprise software engineering principles.
