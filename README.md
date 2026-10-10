# Medixa — Outpatient Department (OPD) Healthcare Management System

Medixa is an enterprise-grade Outpatient Department (OPD) hospital management system built with Laravel 11, Tailwind CSS, Alpine.js, and Laravel Reverb. The system is engineered around a zero-trust, patient-centric consent model, real-time consultation queue broadcasting, AI clinical triage assistance, and immutable audit logging.

---

## Architecture & Security: Consent & Audit System

> **Consent & Audit Architecture Overview:**  
> Medixa enforces a strict patient-first privacy boundary where medical records (prescriptions, clinical notes, and lab reports) are completely sealed by default. Patient records can only be accessed by healthcare practitioners who hold an active, non-revoked `ConsentToken` issued with an explicit cryptographic time-to-live (TTL). Every incoming request to a medical resource is intercepted by the `EnsureConsent` middleware: if the authenticated user is the patient themselves, access is automatically permitted; otherwise, the middleware queries the token repository, rejects expired or revoked authorizations with an immediate `403 Forbidden`, and silently generates an append-only, tamper-evident entry in the `audit_log` capturing actor ID, timestamp, IP address, and resource metadata. Hospital administrators are provided with an aggregate-only oversight interface that computes analytical patterns (e.g., weekly query volume per doctor and anomaly detection) without ever exposing individual patient record contents, diagnoses, or personal health information.

---

## Project structure

Medixa is one Laravel app. Each portal is a controller, a set of Blade pages, and a route group. Shared rules live in services, not in the pages.

```text
app/Http/Controllers/     patient, doctor, admin, lab, reception, public
app/Http/Middleware/      role check and consent check
app/Services/             booking, queue ranking, notifications, analytics
app/Models/               appointments, live queue, lab reports, prescriptions
resources/views/          one folder per portal
routes/web.php            routes grouped by role
storage/app/private/      lab files
docs/                     overview, architecture, queue algorithm, API
tests/                    feature and unit tests
```

The queue brain is `QueueOptimizationService` (who is next, and how long the wait is) and `QueueService` (the locked update and the event log). Booking is `AppointmentService`.

| Folder | What lives there |
| --- | --- |
| `app/Http/Controllers/PatientPortalController.php` | Patient chart, booking, check-in, timeline, feedback |
| `app/Http/Controllers/DoctorPortalController.php` | Queue-linked patients, prescriptions, lab review |
| `app/Http/Controllers/LiveQueueController.php` | Call next, delay, pause, presence |
| `app/Http/Controllers/LabAssistantController.php` | Report reference lookup and upload only |
| `app/Http/Controllers/ReceptionistController.php` | Today's desk and walk-ins |
| `app/Http/Controllers/AdminController.php` | Departments, doctors, analytics, audit counts |
| `app/Services/QueueOptimizationService.php` | Priority score, wait estimate, FIFO vs priority simulation |
| `app/Services/QueueService.php` | Locked queue updates and queue events |
| `app/Services/AppointmentService.php` | Daily cap, emergency buffer, no double booking |
| `app/Services/OpdAnalyticsService.php` | Measured waits, load, no-show rate, feedback |
| `resources/views/patient/` | Phone-oriented patient pages, including the live serial |
| `resources/views/doctor/` `admin/` `lab/` `receptionist/` | The other portals |

## How a visit works

1. The patient books online, or reception books a walk-in. The server assigns the next serial under a lock. Routine booking stops at 18. Two of the 20 daily slots stay open for emergencies.
2. On the day, check-in changes the visit from booked to waiting. A booking alone does not put someone in the live queue.
3. Call Next ranks checked-in patients. Emergency is always first. Senior, follow-up, and normal then compete, and a long wait adds points so a normal patient is not stuck forever.
4. The patient sees their serial, how many people are ahead, an estimated wait, and a suggested arrival time. The wait is people ahead times the doctor's average consultation. Until five completed visits exist, that average is a stated 12 minutes. A delay appears only if the doctor typed the minutes.
5. The doctor can order a lab test. The lab assistant uploads by the report reference and does not see the chart. The doctor records the outcome. The patient gets a link, not the file.

Queue changes are saved in `queue_events` with a short reason, then `QueueUpdated` is broadcast. The patient page also polls every few seconds, so the serial still moves if WebSockets are off.

## Comparison with the base paper

The paper *A Review on Smart Healthcare Integration System for Optimizing OPD Queues* describes priority queues, waiting-time estimates, schedules, dashboards, a chatbot, notifications, and a receptionist, and mentions billing and inventory as future work.

| Paper | Medixa |
| --- | --- |
| Emergency, senior, normal | Those, plus follow-up. Aging lets a long-waiting normal patient pass a newly arrived senior. Emergency cannot be overtaken. |
| Estimated wait | Calculated. Thin history uses a labeled 12-minute baseline. |
| Scheduling | Daily cap, emergency buffer, and a lock against double booking. Check-in is separate from booking. |
| Doctor delay | The doctor enters the minutes. The screen does not invent them. |
| Analytics | Real counts for volume, no-shows, and workload. The FIFO vs priority table is computed on a fixed sample and labeled as a simulation. |
| Chatbot | Booking and navigation help. It does not diagnose or mark a report urgent. |
| Receptionist | Today's queue, walk-in, check-in. No prescriptions and no lab files. |
| Billing and inventory | Not built. |

Medixa also adds consent before a doctor opens a record, an audit log, private lab storage, a lab assistant who can only upload, doctor-recorded review outcomes, and patient feedback shown only as averages.

## Features

- **Patient:** book, check in, live serial and wait estimate, timeline, prescriptions, own lab reports, assistance requests, feedback.
- **Doctor:** call next, delay, pause, linked charts, prescriptions, lab orders, review outcome.
- **Admin:** departments, doctors, schedule, audit counts, OPD analytics.
- **Lab assistant:** find a report by `LAB-…` or `PAT-…`, upload PDF or image, see own upload history.
- **Reception:** today's queue, check-in, walk-in including emergency.

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
| **Lab assistant** | Lab Assistant | `lab.assistant@medixa.test` | Report upload only. No patient chart |
| **Reception** | OPD Reception | `reception@medixa.test` | Today's queue, walk-in tokens, check-in. No charts or prescriptions |

---

## Security & Verification Summary

1. **Role Protection (`EnsureRole`)**:
   - `/patient/*` routes protected by `auth` and `role:patient`.
   - `/doctor/*` routes protected by `auth` and `role:doctor`.
   - `/admin/*` routes protected by `auth` and `role:admin`.
   - `/lab-assistant/*` routes protected by `auth` and `role:lab_assistant`.
   - `/receptionist/*` routes protected by `auth` and `role:receptionist`.
2. **Consent Interception (`EnsureConsent`)**:
   - Medical routes such as `/lab-reports/{labReport}` validate active patient-issued tokens; unauthorized doctors or admins are blocked with `403 Forbidden`.
   - Patients bypass checks on their own records.
3. **Signed Temporary URLs**:
   - Lab reports are never published to public storage disks. Downloads are served through short-lived signed URLs (`URL::signedRoute(..., now()->addMinutes(2))`) protected by Laravel's `signed` middleware.
4. **Data-at-Rest Field Encryption**:
   - Patient `phone`, `nid`, and `emergency_contact` are stored as encrypted text. Prescription `instructions` are encrypted. The patient dashboard also polls the queue every 8 seconds when Reverb is not running.
5. **Lab downloads**:
   - Files live on the private disk. A download requires a valid signature and either ownership or an unexpired, unrevoked consent token. Any logged-in doctor does not get access just by being a doctor.
5. **Rate Limiting**:
   - `POST /login`: `throttle:5,1` (prevents brute-force credential stuffing).
   - `POST /chatbot/message`: `throttle:30,1` (protects AI triage endpoint against abuse).
   - `POST /patient/appointments`: `throttle:10,1`.

---

## Further notes

Longer notes are in `docs/project-overview.md`, `docs/architecture.md`, `docs/queue-algorithm.md`, and `docs/api.md`.
