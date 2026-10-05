# Medixa — Outpatient Department (OPD) Healthcare Management System

Medixa is an enterprise-grade Outpatient Department (OPD) hospital management system built with Laravel 11, Tailwind CSS, Alpine.js, and Laravel Reverb. The system is engineered around a zero-trust, patient-centric consent model, real-time consultation queue broadcasting, AI clinical triage assistance, and immutable audit logging.

---

## Architecture & Security: Consent & Audit System

> **Consent & Audit Architecture Overview:**  
> Medixa enforces a strict patient-first privacy boundary where medical records (prescriptions, clinical notes, and lab reports) are completely sealed by default. Patient records can only be accessed by healthcare practitioners who hold an active, non-revoked `ConsentToken` issued with an explicit cryptographic time-to-live (TTL). Every incoming request to a medical resource is intercepted by the `EnsureConsent` middleware: if the authenticated user is the patient themselves, access is automatically permitted; otherwise, the middleware queries the token repository, rejects expired or revoked authorizations with an immediate `403 Forbidden`, and silently generates an append-only, tamper-evident entry in the `audit_log` capturing actor ID, timestamp, IP address, and resource metadata. Hospital administrators are provided with an aggregate-only oversight interface that computes analytical patterns (e.g., weekly query volume per doctor and anomaly detection) without ever exposing individual patient record contents, diagnoses, or personal health information.

---

## Local Setup Steps

Follow these steps to configure and run Medixa in your local development environment:

### Prerequisites
- **PHP**: 8.2 or higher (with `pdo`, `mbstring`, `openssl`, `sodium` extensions)
- **Composer**: 2.x
- **Node.js**: 18.x or higher & **NPM**
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Cache / WebSockets**: Laravel Reverb (bundled)

### 1. Clone & Install Dependencies
```bash
git clone <repository-url>
cd medixa-project

# Install PHP dependencies
composer install

# Install and build frontend assets
npm install
npm run build
```

### 2. Environment Configuration
Copy the sample environment file and generate the application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database
Create a MySQL database (e.g., `medixa_db`). Then adjust your `.env` settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306         # or 4306 depending on your local MySQL setup
DB_DATABASE=medixa_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run Migrations & Demo Seed Data
Populate realistic hospital data (16+ patients, departments, doctors with queues, appointments in various states, expired/revoked consent tokens, and audit history):
```bash
php artisan migrate:fresh --seed
```

### 5. Launch the Application & Services
Open two terminal windows to run the web server and the real-time WebSocket queue server:
```bash
# Terminal 1: Web application server
php artisan serve

# Terminal 2: Real-time Reverb WebSocket server (for live queue updates)
php artisan reverb:start
```
The application will now be accessible at `http://localhost:8000`.

---

## Environment Variables Reference

| Variable | Description | Default / Example |
| :--- | :--- | :--- |
| `APP_NAME` | Name of the application | `Medixa` |
| `APP_ENV` | Environment type (`local`, `production`) | `local` |
| `APP_KEY` | AES-256 application encryption key | Generated via `php artisan key:generate` |
| `APP_URL` | Canonical local URL | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host address | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` (or `4306`) |
| `DB_DATABASE` | Database name | `medixa_db` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | `""` |
| `BROADCAST_CONNECTION` | Driver for live queue broadcasting | `reverb` |
| `REVERB_APP_ID` | Reverb application identifier | `medixa-local` |
| `REVERB_APP_KEY` | Reverb public key | `medixa-reverb-key` |
| `REVERB_APP_SECRET` | Reverb secret key | `medixa-reverb-secret` |
| `REVERB_HOST` | Reverb host | `localhost` |
| `REVERB_PORT` | Reverb server port | `8080` |
| `REVERB_SCHEME` | HTTP scheme for WebSocket handshake | `http` |
| `OPENAI_API_KEY` | *(Optional)* OpenAI API key for clinical AI chatbot | `sk-...` |
| `OPENAI_MODEL` | LLM model for clinical chatbot | `gpt-4o-mini` |

---

## Pre-seeded Demo Accounts

All pre-seeded demo accounts share the password: `password`

| Role | Name | Email | Initial View / Capabilities |
| :--- | :--- | :--- | :--- |
| **Admin** | Hospital Administrator | `admin@medixa.test` | Dept & Doctor CRUD, Doctor schedule & queue oversight, Aggregate-only audit compliance |
| **Doctor** | Dr. Arman Hossain (Cardiology) | `arman.hossain@medixa.test` | Live OPD queue controls, patient medical history via consent tokens, prescription builder |
| **Doctor** | Dr. Nadia Rahman (Cardiology) | `nadia.rahman@medixa.test` | Patient history review, pending report review inbox |
| **Doctor** | Dr. Farhan Karim (Neurology) | `farhan.karim@medixa.test` | Clinical reviews, active queue management |
| **Patient** | Rahima Khatun | `rahima.khatun@patient.test` | Medical timeline, consent center (token revoke), lab report upload, 2-min signed downloads |
| **Patient** | Mahbub Alam | `mahbub.alam@patient.test` | OPD assistance requests, health profile, consultation records |
| **Patient** | Shirin Sultana | `shirin.sultana@patient.test` | Medical timeline, consent token review |

---

## Security & Verification Summary

1. **Role Protection (`EnsureRole`)**:
   - `/patient/*` routes protected by `auth` and `role:patient`.
   - `/doctor/*` routes protected by `auth` and `role:doctor`.
   - `/admin/*` routes protected by `auth` and `role:admin`.
2. **Consent Interception (`EnsureConsent`)**:
   - Medical routes such as `/lab-reports/{labReport}` validate active patient-issued tokens; unauthorized doctors or admins are blocked with `403 Forbidden`.
   - Patients bypass checks on their own records.
3. **Signed Temporary URLs**:
   - Lab reports are never published to public storage disks. Downloads are served through short-lived signed URLs (`URL::signedRoute(..., now()->addMinutes(2))`) protected by Laravel's `signed` middleware.
4. **Data-at-Rest Field Encryption**:
   - Sensitive personal information (`nid`, `phone`, `emergency_contact`) and doctor consultation `instructions`/`notes` are cast as `encrypted` on their Eloquent models using Laravel's native AES-256 encryption.
5. **Rate Limiting**:
   - `POST /login`: `throttle:5,1` (prevents brute-force credential stuffing).
   - `POST /chatbot/message`: `throttle:30,1` (protects AI triage endpoint against abuse).
