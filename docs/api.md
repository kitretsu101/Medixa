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

## Chatbot

These routes use the web session and CSRF protection. They are not a separate API token. `POST /chatbot/message` accepts guests and signed-in users. `DELETE` accepts only the owner of that session id. Both are limited to 30 requests per minute. Message text is not stored.

| Method | URL | Who | Notes |
| --- | --- | --- | --- |
| POST | `/chatbot/message` | guest or signed-in user | `message` required, at most 2000 characters. Optional `locale` of `en` or `bn`. Optional `session_token`. Optional `confirm` object, described below. |
| DELETE | `/chatbot/sessions/{session}` | owner of that UUID | Anyone else receives 404, including an admin. A non-UUID URL is 404. |

A booking confirmation is ignored unless `confirm.confirmed` is accepted (`true`, `1`, `"1"`, `"yes"`, or `"on"`). The server books only through `AppointmentService` with priority `normal`. Extra fields such as `priority_type`, `serial_number`, and `time` are not used.

Example directory question:

```http
POST /chatbot/message
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: {token from the page meta tag}

{"message":"Which departments are open?","locale":"en"}
```

```json
{
  "reply": "We have the following departments: Cardiology.",
  "session_token": "00000000-0000-4000-8000-000000000000",
  "booking": null,
  "error": false,
  "retryable": false,
  "locale": "en",
  "sources": [],
  "offers": []
}
```

The session id above is a shape example, not a live id. Send it back as `session_token` on the next message from the same browser session.

Example confirmation, only after the patient has chosen a visit the server offered:

```json
{
  "message": "Confirm this routine appointment",
  "confirm": {
    "doctor_id": 1,
    "appointment_date": "2026-10-20",
    "confirmed": true
  }
}
```

A successful booking returns `booking.success` true, `booking.appointment_id`, and a reply that includes the server-assigned serial. A duplicate or a full day returns `booking.success` false and creates no second row. An emergency message returns `booking` null and `offers` as an empty list, even if `confirm` is present.

Validation failures return 422. The 31st message in a minute returns 429. A session id the request does not own returns 404.
