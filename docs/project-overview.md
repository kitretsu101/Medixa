# Medixa project overview

Medixa is a Laravel outpatient system for one hospital. It keeps the ideas in *A Review on Smart Healthcare Integration System for Optimizing OPD Queues* and implements the ones that change a real visit: who is served next, when the patient should arrive, who may see a record, and how a lab report gets back to the doctor.

## Project structure

```text
medixa-project/
├── app/
│   ├── Http/Controllers/     Portals: public, patient, doctor, admin, lab, reception
│   ├── Http/Middleware/      EnsureRole, EnsureConsent
│   ├── Models/               User, Appointment, LiveQueue, LabReport, Prescription, ...
│   ├── Services/             Booking, queue, consent, notifications, analytics, AI
│   ├── Events/QueueUpdated.php
│   ├── Notifications/MedixaAlert.php
│   └── Support/DoctorName.php
├── database/
│   ├── migrations/           Schema, including queue and lab columns
│   ├── seeders/MedixaSeeder.php
│   └── factories/
├── resources/views/          Blade pages, one folder per portal
├── routes/web.php            All browser routes, grouped by role
├── tests/Feature and tests/Unit
├── docs/                     This file, architecture, queue algorithm, API
└── storage/app/private/      Laboratory files, not public uploads
```

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

1. The patient books a doctor and a date, or reception books a walk-in. The server assigns the next serial inside a lock so two bookings cannot take the same number. Routine bookings stop at 18. Two slots stay open for emergencies.
2. On the day, the patient or reception checks in. Status changes from booked to `checked_in`. That is the moment the person joins the live queue.
3. The doctor presses Call Next. If anyone is checked in, `QueueOptimizationService` ranks them. Emergency is always first. After that, senior, follow-up, and normal compete, and a long wait adds points so a normal patient is not stuck forever. The chamber serial becomes that patient's serial.
4. The patient dashboard shows the serial, patients ahead, an estimated wait, and a recommended arrival time. The wait is patients ahead times the doctor's average consultation, plus a delay only if the doctor entered one. Until five completed visits exist, the average is a stated 12 minutes.
5. The doctor writes a prescription and can order a lab test. The order is a pending report with a reference such as `LAB-2026-00008`.
6. The lab assistant types that reference, sees the test name and a patient reference such as `PAT-00024`, and uploads a PDF or image. The file stays on the private disk. The patient and the ordering doctor get a link, not the file.
7. The doctor records the outcome: routine follow-up, needs attention, urgent review, or a physical visit. The doctor makes that decision. The chatbot does not.
8. After the visit, the patient can rate waiting time, the doctor, and the system. Admin analytics read those ratings in aggregate.

Queue changes are saved in `queue_events` with a short reason, then `QueueUpdated` is broadcast. The patient page also polls every few seconds, so the serial still moves if WebSockets are off.

## Roles

| Role | Demo login | Password |
| --- | --- | --- |
| Admin | `admin@medixa.test` | `password` |
| Doctor | `arman.hossain@medixa.test` | `password` |
| Patient | `rahima.khatun@patient.test` | `password` |
| Lab assistant | `lab.assistant@medixa.test` | `password` |
| Reception | `reception@medixa.test` | `password` |

Login sends each role to its own dashboard. Opening another portal by URL returns 403.

## Features

**Queue.** Priority categories, aging, emergency buffer, check-in, doctor delay and pause, estimated wait, recommended arrival, explainable priority, and a queue event log.

**Appointments.** Online booking and walk-in booking, serial numbers, daily capacity, and protection against a double booking of the same doctor and date.

**Clinical records.** Patient timeline, digital prescriptions, assistance requests, and consent-scoped doctor access. Phone, national ID, emergency contact, and prescription instructions are encrypted.

**Laboratory.** Doctor orders a test. Lab assistant uploads by reference without seeing the chart. Doctor reviews and records an outcome. Patient downloads only their own file through a short-lived signed link.

**Analytics.** Today's volume, completed, cancelled, and no-show counts, department load, doctor workload, feedback averages, and a computed comparison of FIFO, priority, and priority plus aging. The comparison is labeled as a simulation, not as a measured hospital improvement.

**Notifications.** Info, warning, and urgent. Queue notices fire at 10, 5, 3, and 1 patient ahead, and when the doctor delays or pauses. They do not fire on every poll.

## Comparison with the base paper

The paper reviews a smart OPD design: priority queues, waiting-time estimates, schedules, dashboards, a chatbot, notifications, a receptionist, and later billing or inventory. Medixa keeps that clinical scope and is stricter about privacy and measurement.

| Paper idea | In Medixa |
| --- | --- |
| Priority queue: emergency, senior, normal | Same categories, plus follow-up. Aging stops a normal patient from waiting forever. Emergency cannot be overtaken by aging. |
| Estimated waiting time | Calculated from patients ahead and the doctor's recorded consultation length, with a 12-minute baseline when history is thin. |
| Appointment scheduling | Daily cap, emergency buffer, row lock against double booking, and a separate check-in so a booking is not treated as an arrival. |
| Doctor delay | The doctor enters the minutes. The patient sees that number. The system does not invent a delay. |
| EHR and dashboards | Patient timeline, doctor chart for linked patients only, admin analytics. |
| Predictive analytics | No-show rate and workload are counted from stored visits. The algorithm table is a live simulation on a fixed sample, not a hardcoded claim. |
| AI chatbot | Helps with navigation and booking. It does not diagnose, prescribe, or mark a report urgent. |
| Receptionist | Today's queue, walk-in token, check-in. No prescriptions and no lab files. |
| Notifications | Sent on meaningful queue steps, with info, warning, and urgent. |
| Resource management | Doctor and department load on the analytics page. There is no inventory or billing module. |

Medixa also does work the paper leaves out of the queue discussion:

- Consent tokens before a doctor opens a record, plus an audit log.
- A lab assistant who can upload a report and cannot open the chart.
- Private report storage and signed downloads.
- Remote lab review whose outcome is stored as the doctor's decision.
- Patient feedback that is aggregated and not published per person.
- An explainable queue decision and a FIFO versus priority comparison that is recalculated, not pasted in.

## What this project deliberately leaves out

Full billing, pharmacy stock, insurance, multi-hospital exchange, and GPS-based travel time are outside this build. The chatbot is an assistant for booking and hospital questions. Clinical urgency stays with the doctor.
