# Endpoints added for queue optimization

All of these require a logged-in session. JSON queue actions also expect the CSRF token.

## Patient

| Method | URL | Role | Notes |
| --- | --- | --- | --- |
| POST | `/patient/appointments` | patient | `doctor_id`, `appointment_date`, optional `priority_type` of `normal`, `senior`, or `follow_up`. Rate limit 10 per minute. |
| POST | `/patient/appointments/{appointment}/check-in` | patient | Own appointment, today only. |
| POST | `/patient/appointments/{appointment}/feedback` | patient | Own completed visit. Ratings 1–5. |
| GET | `/patient/queue/status` | patient | Own next visit. Adds patients ahead, estimated minutes, delay message, and priority explanation. |

## Doctor

| Method | URL | Role | Notes |
| --- | --- | --- | --- |
| POST | `/doctor/queue/increment` | doctor | Calls the next checked-in patient, or advances the serial by one if the waiting list is empty. |
| POST | `/doctor/queue/delay` | doctor | JSON `minutes` from 0 to 180. |
| POST | `/doctor/queue/pause` | doctor | Toggles pause. |
| POST | `/doctor/appointments/{appointment}/no-show` | doctor | Own appointment that was never checked in. |
| POST | `/doctor/lab-reports/{labReport}/review` | doctor | Own uploaded report. Outcome is one of `routine_follow_up`, `needs_attention`, `urgent_clinical_review`, `physical_consultation`. |

## Reception

| Method | URL | Role | Notes |
| --- | --- | --- | --- |
| GET | `/receptionist/dashboard` | receptionist | Today’s serial, name, doctor, priority, and status. |
| POST | `/receptionist/walk-in` | receptionist | Existing patient email, doctor, priority including emergency, date. |
| POST | `/receptionist/appointments/{appointment}/check-in` | receptionist | Checks that visit in. |

## Admin

| Method | URL | Role | Notes |
| --- | --- | --- | --- |
| GET | `/admin/analytics` | admin | Recorded metrics plus a labeled simulation. |

Other roles receive 403 on these URLs. A patient cannot check in someone else’s appointment.
