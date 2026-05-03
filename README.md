# Eastminster University Alumni API

This repository contains the backend API and client applications for the **Eastminster University Featured Alumnus System**.

The core of this project is a RESTful, **client-agnostic** PHP API built with **CodeIgniter 3**. It manages user authentication, alumni profile data, a secure blind bidding system for featured alumnus slots, and analytics endpoints for external clients. The project was developed in two stages:

- **Coursework 1:** Core REST API, authentication, profile management, blind bidding, cron resolution, and public featured alumnus retrieval.
- **Coursework 2:** Admin Analytics Dashboard client, AR app integration endpoint, scoped API key access control, and analytics reporting endpoints.

# 🌟 Key Features

## Coursework 1 Features
- **Client-Agnostic API:** Public and protected API endpoints return clean, structured JSON, independent of any frontend implementation.
- **Secure Authentication:** JWT-based registration, login, token verification, and protected endpoints.
- **Blind Bidding System:** Alumni can place hidden monetary bids for specific future dates to be featured.
- **Smart Monthly Limits:** Users are limited to 3 featured wins per month, or 4 if they attended a university event. The system automatically skips users who exceed the limit.
- **Automated Cron Resolution:** A cron controller resolves bids daily, selects the winning eligible alumnus, and sends email notifications via SMTP.
- **Featured Alumnus Endpoint:** A public API endpoint returns the alumnus selected for a given date.

## Coursework 2 Features
- **Admin Analytics Dashboard:** A separate admin client built with HTML, Bootstrap, Chart.js, and JavaScript.
- **Interactive Graphs and Reports:** Dashboard includes summary cards, charts, alumni filtering, CSV export, and PDF export.
- **AR App Integration Endpoint:** A dedicated endpoint returns the “Alumni of the Day” profile for AR-based applications.
- **Scoped API Key Security:** External clients are controlled through `x-api-key` headers and scope-based access checks.
- **Analytics API Endpoints:** New endpoints provide aggregated alumni insights such as certifications, job roles, employers, degree popularity, awarding bodies, and skills trends.
- **Dual-Layer Protection:** Sensitive analytics endpoints require both a valid API key scope and JWT authentication.

# 📁 Project Structure

```text
alumini_api_cw/
│
├── admin_client/                      # Coursework 2 admin dashboard frontend
│   ├── index.html
│   ├── login.html
│   └── js/
│       ├── auth.js
│       └── dashboard.js
│
├── api-docs/
│   └── swagger.yaml                   # OpenAPI / Swagger documentation
│
├── application/
│   ├── config/
│   │   └── routes.php
│   ├── controllers/
│   │   ├── Analytics.php
│   │   ├── AR_app.php
│   │   ├── Auth.php
│   │   ├── Bid.php
│   │   ├── Cron.php
│   │   ├── Featured.php
│   │   └── Profile.php
│   ├── core/
│   │   └── MY_Controller.php
│   └── models/
│       ├── Analytics_model.php
│       ├── API_client_model.php
│       ├── Bid_model.php
│       ├── Profile_model.php
│       └── User_model.php
│
├── .env.example
├── database.sql
└── README.md
```

# 🛠 Installation & Setup

## 1. Database Setup
1. Create a new MySQL database named `cw1_alumini_influencers`.
2. Import the provided `database.sql` file located in the root directory of this repository.
3. This will create the required tables, relationships, and sample data for both Coursework 1 and Coursework 2 features.

## 2. API Documentation (Swagger) 📖
Full API documentation is available in:

```text
api-docs/swagger.yaml
```

The Swagger file includes:
- endpoint descriptions
- request and response schemas
- Bearer token authentication
- API key security definitions
- Coursework 1 and Coursework 2 routes

## 3. Environment Configuration (.env)
This project uses environment variables for secure configuration. **Do not hardcode credentials in the CodeIgniter config files.**

1. Place the `alumini_api_cw` folder inside your local server web root (for example, `htdocs` in XAMPP).
2. Locate the `.env.example` file in the root directory.
3. Duplicate the file and rename the copy to `.env`.
4. Update the values to match your local environment.

Example:

```env
#Application Settings
BASE_URL="http://localhost/alumini_api_cw/"
ENVIRONMENT="development"

#Database Configuration
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="cw1_alumini_influencers"

#JWT Authentication
JWT_SECRET_KEY="your_super_secret_jwt_key_here"

#Email Configuration
SMTP_HOST="sandbox.smtp.mailtrap.io"
SMTP_USER="your_mailtrap_username"
SMTP_PASS="your_mailtrap_password"

#Coursework 2 API Keys
DASHBOARD_API_KEY="dashboard_key_7A9F3B2C8E1D"
AR_API_KEY="ar_key_example_123456"
```

# 🔐 Authentication and Security

## JWT Authentication
Protected user endpoints require a Bearer token:

```http
Authorization: Bearer <your_token>
```

## API Key Authentication
Coursework 2 introduces scoped API keys for client applications:

```http
x-api-key: your_client_api_key
```

## Security Model
- **Coursework 1:** JWT-secured endpoints for user-based operations.
- **Coursework 2:** API key scope validation for external clients.
- **Analytics endpoints:** require both JWT and API key.
- **AR endpoint:** requires API key only.

# 📌 Main Endpoints

## Coursework 1
- `POST /auth/register` — Register a new user
- `POST /auth/login` — Login and receive JWT
- `GET /profile/me` — Get authenticated user profile
- `POST /bid/place` — Place a blind bid
- `GET /featured/{date}` — Get featured alumnus for a date
- `GET /cron/resolve_bids` — Resolve daily bids

## Coursework 2
- `GET /analytics/dashboard_data` — Get analytics datasets for charts
- `GET /analytics/alumni_list` — Get filtered alumni table data
- `GET /ar_app/get_alumni_of_day` — Get AR app alumnus of the day

# 📊 Admin Dashboard (Coursework 2)

The `admin_client` folder contains a browser-based analytics dashboard built for administrators.

### Features
- Admin login page
- Dashboard summary cards
- Interactive analytics charts
- Alumni filtering
- CSV export
- PDF export

### Access Flow
1. Login through `admin_client/login.html`
2. Store JWT token in session
3. Open `admin_client/index.html`
4. Dashboard calls analytics endpoints using:
   - `Authorization: Bearer <token>`
   - `x-api-key: dashboard_key_7A9F3B2C8E1D`

# 🧠 Analytics Data Provided

The analytics endpoints return insights such as:
- top certifications
- common job titles
- top employers
- popular degree programmes
- certification completion timeline
- top awarding bodies
- independent skills radar
- filtered alumni listing

# 🥽 AR App Integration

Coursework 2 also includes an AR-specific endpoint:

```text
GET /ar_app/get_alumni_of_day
```

This endpoint is designed for machine-to-machine access and returns the current featured alumnus profile for AR applications. It requires a valid API key with the correct scope.

# 🚀 Running the Project

## Backend
Run the CodeIgniter project through your local Apache/MySQL stack such as XAMPP or WAMP.

Example local URL:

```text
http://localhost/alumini_api_cw/index.php/
```

## Admin Dashboard
Open in browser:

```text
http://localhost/alumini_api_cw/admin_client/login.html
```

Make sure:
- backend server is running
- database is imported
- `.env` is configured
- JWT login works
- API keys are correctly inserted into the `api_clients` table

# 🧪 Suggested Test Flow

1. Register or log in as a user
2. Create or import alumni profile data
3. Place bids for future dates
4. Resolve bids using cron endpoint
5. Test `/featured/{date}`
6. Log in to the admin dashboard
7. Load graphs and alumni data
8. Test CSV and PDF export
9. Test AR endpoint with API key

# 👨‍💻 Technologies Used

- **Backend:** PHP, CodeIgniter 3
- **Database:** MySQL
- **Authentication:** JWT
- **API Docs:** Swagger / OpenAPI 3.0
- **Frontend (CW2):** HTML, Bootstrap 5, JavaScript
- **Charts:** Chart.js
- **PDF Export:** jsPDF, html2canvas

# 📖 Notes

- This repository combines both Coursework 1 and Coursework 2 deliverables.
- Some files were introduced in Coursework 2 specifically for analytics, AR integration, and API key scope validation.
- Only the `require_scope()` addition in `MY_Controller.php` is considered part of Coursework 2 enhancement work.
