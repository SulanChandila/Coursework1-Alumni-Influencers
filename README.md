# Eastminster University Alumni API

This repository contains the backend API for the Eastminster University Featured Alumnus System. 

The core of this project is a RESTful, **client-agnostic** PHP API built with CodeIgniter 3. It manages user authentication, profile data, and a secure "blind bidding" system where alumni can bid to be featured on specific dates.

# 🌟 Key Features
* **Client-Agnostic API:** The public API endpoints return clean, structured JSON, completely independent of the frontend implementation.
* **Secure Authentication:** JWT-based user registration, login, token verification, and role-based endpoint protection.
* **Advanced Blind Bidding System:** Alumni can place hidden monetary bids for specific future dates.
* **Smart Monthly Limits:** Users are capped at 3 featured wins per month (or 4 if they have attended a university event). The system automatically skips high bidders who have already reached their monthly cap.
* **Automated Cron Resolution:** A built-in cron controller resolves bids daily, automatically selecting eligible winners and sending email notifications via SMTP.

## 🛠 Installation & Setup

### 1. Database Setup
1. Create a new MySQL database named `cw1_alumini_influencers`.
2. Import the provided `database.sql` file located in the root directory of this repository to automatically generate the required tables, relationships, and dummy data.

### 2. API Documentation (Swagger) 📖
* **Full API documentation, including request bodies, security schemas (Bearer Auth), and endpoint descriptions, is available in the 
api-docs/swagger.yaml file included in this repository.**


### 3. Environment Configuration (.env)
This project uses environment variables for secure configuration. **Do not hardcode credentials in the CodeIgniter config files.**

1. Place the `alumini_api_cw` folder into your local server's web root (e.g., `htdocs` for XAMPP or `www` for WAMP).
2. Locate the `.env.example` file in the root directory.
3. Duplicate this file and rename the copy to `.env`.
4. Open the `.env` file and configure your local settings:

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

#Email Configuration (Mailtrap recommended for testing)
SMTP_HOST="sandbox.smtp.mailtrap.io"
SMTP_USER="your_mailtrap_username"
SMTP_PASS="your_mailtrap_password"





