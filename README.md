# Fiva Sales App — Backend

> Backend API and Admin Dashboard for Fiva Food's sales operations.

Fiva Sales App is a sales operations backend built to support field sales activities, store visits, reporting, product information, and operational monitoring.

The system provides a **REST API consumed by the Fiva Sales mobile application** and a **web-based Admin Dashboard** for monitoring and managing sales activities.

---

## Overview

The platform is designed to centralize sales operations between field sales representatives, administrators, internal databases, and Odoo.

The backend handles the core business logic for:

* Sales authentication and authorization
* Store visit management
* Store claiming and visit tracking
* GPS-based visit verification
* Store radius validation
* Visit reports
* Visit history
* Product catalog
* Customer retention data
* Sales performance monitoring
* Admin dashboard
* User management
* Operational reporting
* Excel exports
* Odoo integration
* Retention data processing

---

## Architecture

```text
                    ┌──────────────────────┐
                    │   Fiva Sales App     │
                    │   Flutter Mobile     │
                    └──────────┬───────────┘
                               │
                               │ REST API
                               ▼
              ┌───────────────────────────────┐
              │        Laravel Backend        │
              │                               │
              │  Authentication               │
              │  Store Visits                 │
              │  Visit Reports                │
              │  Product Catalog              │
              │  Retention                    │
              │  User Management              │
              │  Business Logic               │
              └───────────────┬───────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
                ▼             ▼             ▼
          ┌──────────┐  ┌──────────┐  ┌────────────┐
          │  MySQL   │  │   Odoo   │  │ Cloudinary │
          │ Database │  │   ERP    │  │   Storage  │
          └──────────┘  └──────────┘  └────────────┘
                             
                              │
                              ▼
                    ┌──────────────────┐
                    │ Python Data      │
                    │ Processing       │
                    │ Pipeline         │
                    └──────────────────┘

              ┌───────────────────────────────┐
              │       Admin Dashboard        │
              │     Inertia.js + Vue.js      │
              └───────────────────────────────┘
```

---

## Key Features

### 🔐 Authentication & Authorization

The backend provides authentication for both the mobile API and Admin Dashboard.

Features include:

* User login
* Logout
* Password reset
* Password change
* Username management
* Profile management
* Profile photo upload
* Active/inactive account status
* Role-based authorization
* Laravel Sanctum authentication

Supported roles include:

* `admin`
* `superadmin`
* `sales`

---

### 🏪 Store Visit Management

Store visits are one of the core workflows of the application.

Sales representatives can:

1. Select a store
2. Claim the store
3. Start a visit
4. Submit a visit report
5. Complete the visit
6. Cancel a visit
7. View visit history
8. Check their active visit

The backend applies business rules to prevent conflicting visits.

For example:

* A sales representative cannot have multiple active visits.
* A store cannot be actively visited by multiple sales representatives.
* A store that has already been visited during the current week can be restricted from being visited again.
* Visit reports can only be submitted by the sales representative who owns the visit.

Critical operations are protected using database transactions and row-level locking.

---

### 📍 GPS & Radius Verification

Visit reports require location data from the sales representative.

The backend validates:

* Latitude
* Longitude
* GPS accuracy
* Store coordinates
* Distance between the sales representative and the store

Store coordinates are retrieved from Odoo.

The system calculates the distance between the sales representative and the store and records whether the visit was performed outside the configured radius.

This allows administrators to identify and review visit exceptions.

---

### 📊 Admin Dashboard

The Admin Dashboard provides operational visibility into sales activities.

Dashboard metrics include:

* Total check-ins
* Active sales representatives
* Visit targets
* Average visit duration
* Ordered stores
* Order events
* Store coverage
* Sales performance
* Area performance
* Visit radius exceptions

The dashboard is built using:

* Laravel
* Inertia.js
* Vue.js
* Tailwind CSS

---

### 👥 User Management

Administrators can manage sales accounts and internal users.

The system supports:

* User creation
* User updates
* Role assignment
* Account status
* User search
* User filtering
* Profile management

---

### 📦 Product Catalog

The backend provides product catalog functionality for the sales application and administration interface.

Product information can be retrieved from the integrated Odoo system and exposed through the application's API.

---

### 🔄 Customer Retention

The application includes a retention data workflow for monitoring customer activity.

Retention data is processed through a dedicated Python pipeline before being consumed by the Laravel application.

The pipeline handles:

```text
Sales Data
    │
    ▼
Extract
    │
    ▼
Transform
    │
    ▼
Retention Data
```

---

### 🔗 Odoo Integration

Odoo is used as an external business data source.

The backend contains a dedicated Odoo integration layer:

```text
app/Services/Odoo/
├── OdooClient.php
├── OdooProductService.php
├── OdooSchemaService.php
└── OdooStoreService.php
```

The integration is used for retrieving information such as:

* Store/customer information
* Partner information
* Store coordinates
* Product information
* Sales-related data

The Odoo integration is separated from the controllers through dedicated service classes.

---

### ☁️ Cloudinary

Cloudinary is used for profile image storage.

The integration is handled through:

```text
app/Services/CloudinaryService.php
```

This keeps media storage operations separated from the application's main business logic.

---

### 📤 Excel Reporting

The Admin Dashboard provides Excel export functionality for operational reporting.

Reports can include:

* Dashboard summaries
* Sales performance
* Area performance
* Visit information
* Radius exceptions

Excel files are generated using PHP spreadsheet libraries.

---

## Tech Stack

### Backend

| Technology        | Purpose                      |
| ----------------- | ---------------------------- |
| PHP 8.3+          | Backend language             |
| Laravel 13        | Application framework        |
| Laravel Sanctum   | API authentication           |
| Inertia.js        | Web application bridge       |
| Spatie Permission | Role & permission management |
| Laravel Reverb    | Real-time infrastructure     |

### Admin Dashboard

| Technology   | Purpose                    |
| ------------ | -------------------------- |
| Vue 3        | Frontend framework         |
| Inertia.js   | SPA-like application layer |
| Vite         | Frontend build tool        |
| Tailwind CSS | UI styling                 |
| Leaflet      | Map visualization          |
| Laravel Echo | Real-time client           |
| Pusher JS    | WebSocket client           |

### Data & External Services

| Technology     | Purpose                    |
| -------------- | -------------------------- |
| MySQL          | Primary database           |
| Odoo           | ERP / business data source |
| Cloudinary     | Image storage              |
| Python         | Data processing            |
| PhpSpreadsheet | Excel generation           |
| Fast Excel     | Spreadsheet export         |

---

## Project Structure

```text
api_sales_pro_app/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   └── ...
│   │   ├── Middleware/
│   │   └── Requests/
│   │
│   ├── Models/
│   │
│   └── Services/
│       ├── Odoo/
│       ├── CloudinaryService.php
│       └── OdooService.php
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── python_engine/
│   ├── aplikasi/
│   ├── archive/
│   └── run_pipeline.py
│
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   ├── Layouts/
│   │   └── Pages/
│   │
│   └── views/
│
├── routes/
│   ├── api.php
│   ├── auth.php
│   ├── channels.php
│   └── web.php
│
├── storage/
├── tests/
│
├── composer.json
├── package.json
├── vite.config.js
└── artisan
```

---

## API

The backend exposes a REST API consumed by the Fiva Sales mobile application.

API routes are defined in:

```text
routes/api.php
```

### Authentication

```text
POST /api/login
POST /api/forgot-password
POST /api/reset-password
POST /api/change-password
```

### User & Profile

```text
GET  /api/user
PUT  /api/user/username
POST /api/profile/photo
```

### Store Visits

```text
GET  /api/visits/active
POST /api/store-visits/claim
POST /api/store-visits/{visit}/submit-report
POST /api/store-visits/{visit}/cancel
GET  /api/store-visits/history
```

### Sales Data

```text
GET /api/stocks
GET /api/retensi
GET /api/contact-persons
```

Authenticated endpoints use Laravel Sanctum:

```text
Authorization: Bearer <token>
```

> The complete endpoint definitions can be found in `routes/api.php`.

---

## Store Visit Flow

The typical field sales workflow looks like this:

```text
┌───────────────┐
│ Select Store  │
└───────┬───────┘
        │
        ▼
┌───────────────┐
│  Claim Store  │
└───────┬───────┘
        │
        ▼
┌───────────────┐
│    IN_VISIT   │
└───────┬───────┘
        │
        ▼
┌─────────────────────┐
│ Capture GPS Location│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Submit Visit Report │
└──────────┬──────────┘
           │
           ▼
┌───────────────┐
│   COMPLETED   │
└───────────────┘
```

A visit can also be cancelled before completion.

---

## Dashboard Data Flow

The Admin Dashboard combines internal database information with data retrieved from Odoo.

```text
                  ┌──────────────┐
                  │    MySQL     │
                  │ Visit / User │
                  │    Data      │
                  └──────┬───────┘
                         │
                         ▼
                ┌─────────────────┐
                │ Laravel Backend │
                │ Business Logic  │
                └───────┬─────────┘
                        │
             ┌──────────┴──────────┐
             │                     │
             ▼                     ▼
      ┌──────────────┐      ┌──────────────┐
      │ Admin        │      │ Excel        │
      │ Dashboard    │      │ Export       │
      └──────────────┘      └──────────────┘

                        ▲
                        │
                 ┌──────┴──────┐
                 │     Odoo     │
                 │ External ERP │
                 └─────────────┘
```

---

## Installation

### Requirements

Make sure the development environment has:

* PHP `8.3+`
* Composer
* Node.js
* npm
* MySQL
* Python 3.x
* Git

Depending on the deployment environment, the following services may also be required:

* Odoo
* Cloudinary
* Redis
* Reverb / WebSocket infrastructure

---

### 1. Clone the repository

```bash
git clone https://github.com/Gamar6/api_sales_pro_app.git

cd api_sales_pro_app
```

---

### 2. Install PHP dependencies

```bash
composer install
```

---

### 3. Configure environment

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

---

### 4. Configure database

Create a MySQL database and configure `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sales_app
DB_USERNAME=root
DB_PASSWORD=
```

---

### 5. Run migrations

```bash
php artisan migrate
```

To also run the seeders:

```bash
php artisan migrate --seed
```

---

### 6. Install frontend dependencies

```bash
npm install
```

For development:

```bash
npm run dev
```

For production:

```bash
npm run build
```

---

### 7. Start Laravel

```bash
php artisan serve
```

The application will be available at:

```text
http://localhost:8000
```

---

## Development

For local development, the project provides a Composer development command:

```bash
composer run dev
```

This starts the required development processes for Laravel, queues, and Vite.

---

## Python Data Pipeline

Retention data processing is located under:

```text
python_engine/
```

The pipeline can be executed using:

```bash
python python_engine/run_pipeline.py
```

The runner executes two stages:

```text
extract_sales.py
        │
        ▼
transform_retensi.py
```

If one of the stages fails, the pipeline stops and reports the error.

---

## Testing

Run the Laravel test suite using:

```bash
php artisan test
```

or:

```bash
composer test
```

Tests are located under:

```text
tests/
├── Feature/
└── Unit/
```

---

## Environment Configuration

The application requires environment-specific configuration for:

* Application
* Database
* Odoo
* Cloudinary
* Mail
* Queue
* Cache
* Broadcasting
* Reverb

Example:

```env
APP_NAME="Fiva Sales App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sales_app
DB_USERNAME=root
DB_PASSWORD=
```

Additional service credentials should be configured through `.env`.

> **Never commit production credentials, API keys, access tokens, or other secrets to the repository.**

---

## Security & Data Integrity

The backend applies several mechanisms to protect business operations and data integrity.

### Authentication

Protected API routes use Laravel Sanctum.

### Authorization

Role-based middleware restricts administrative functionality.

### Request Validation

Important API operations use dedicated Laravel Form Request classes.

### Transaction Safety

Store claiming and visit operations use database transactions and row-level locking to prevent race conditions.

### Visit Ownership

Sales representatives can only submit reports for visits assigned to their own account.

### GPS Validation

The backend validates coordinate ranges and GPS accuracy before processing visit reports.

---

## Project Status

Fiva Sales App is an internal sales operations platform developed for Fiva Food.

The backend currently provides:

* REST API for the mobile sales application
* Admin Dashboard
* Sales authentication
* Store visit management
* GPS and radius verification
* Visit reporting
* Product catalog
* Customer retention processing
* Odoo integration
* Cloudinary integration
* Excel reporting
* User and role management
* Python data processing pipeline

---

## Author

Developed and maintained by **Gamar6**.

Repository:

**https://github.com/Gamar6/api_sales_pro_app**

---

## License

This project is currently maintained as an internal application.

Unless otherwise specified, the repository should not be considered an open-source project intended for redistribution or commercial reuse.
