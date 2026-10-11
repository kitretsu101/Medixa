# Medixa architecture

## System

Laravel application with session authentication, role middleware, Blade portals, private file storage for lab reports, and a database-backed notification inbox. Live queue updates are broadcast on a public doctor channel and also polled by the patient dashboard.

## Roles

| Role | May do | May not do |
| --- | --- | --- |
| Patient | Own chart, own bookings, own reports, check-in, feedback | Other patients, admin, doctor tools |
| Doctor | Linked patients, own queue, prescriptions, lab review | Admin tools, another doctor’s queue |
| Admin | Departments, doctors, aggregate analytics, audit counts | Unconsented chart download |
| Lab assistant | Pending report metadata and file upload | Names, history, downloads, other portals |
| Receptionist | Today’s queue, walk-in, check-in | Prescriptions, lab files, admin |

## Request flow

1. Middleware checks authentication and role.
2. The controller loads only the records that role may see.
3. Queue changes go through `QueueService` inside a transaction, then `QueueUpdated` is dispatched.
4. Appointment creation locks the doctor row, then checks the daily cap and the emergency buffer.

## Data

- `appointments` stores priority, visit type, check-in, and consultation start and end.
- `live_queues` stores the chamber serial, presence, session status, and a delay the doctor entered.
- `queue_events` stores why the queue changed, without clinical notes.
- `appointment_feedback` stores ratings for the hospital team only.
- Lab reports keep the file on the private disk. `clinical_outcome` is set by the assigned doctor.

## Queue

See `docs/queue-algorithm.md`.

## Notifications

Each in-app notice has a level: info, warning, or urgent. Queue delay and pause are warnings. A doctor’s request for an in-person follow-up is urgent. The file itself is not attached.

## AI

The chatbot can help with navigation and booking through the existing appointment service. It does not set clinical urgency, it is not a validated triage tool, and the hospital portals keep working if the provider is down.

## Security

Role middleware blocks cross-portal URLs. Lab downloads still require a signature plus ownership or consent. Booking is rate-limited. Feedback and check-in check the appointment owner, or the receptionist role for desk check-in.
