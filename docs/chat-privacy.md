# Chat privacy and retention

This note describes what the chatbot stores. It is not a legal or regulatory compliance assessment.

## What is stored

A chat session row stores:

- a random UUID, created by the server
- the signed-in user id, when the request is authenticated
- creation, last use, and expiry timestamps

Message text is not stored. Prescriptions, lab reports, and other chart data are not copied into the session and are not added to the AI request.

The browser keeps the session id in `sessionStorage` so the next message can present it. The server accepts that id only when the request owns the row. A guest request owns only a guest row. A signed-in request owns only a row for that user. Anyone else receives 404, including an admin or a staff account.

## Session identifier

The browser must not invent the id. The first message omits it, and the response returns a UUID from `Str::uuid()`. Presenting another value does not create a session under that value.

A signed-in patient's session is not usable from a public request, even if the id is known. The public request has no user, and the row has a user id, so the ownership check fails.

## External AI

When an API key is configured, the message text and the hospital prompt are sent to that provider. The provider receives a one-way correlation id, not the session UUID and not the user id. When no key is configured, the message stays on the server and the local reply is used.

The chat panel tells the visitor this before they send a message.

## Deletion

`DELETE /chatbot/sessions/{session}` deletes the row when the request owns it. The chat panel's clear action calls that route and removes the id from the browser. Clearing the panel does not delete a medical record, because the chat has no copy of one.

## Retention

`CHAT_SESSION_RETENTION_DAYS` defaults to 7. The expiry is set when the session is created and does not move forward on later messages. `php artisan chat-sessions:prune` deletes rows whose expiry is in the past. The scheduler runs that command daily. A request that presents an expired id also deletes that row and returns 404.

## Appointment choices

A signed-in patient who asks to book can receive a short list of routine visits: doctor name, department, date, and how many routine places remain. That list is not stored on the chat session. It does not include another patient's name or serial. Guests and staff accounts do not receive the list. Nothing is booked until that patient confirms, and the confirmation goes through the same appointment service as the patient portal.

## Admin access

There is no admin page for chat sessions or transcripts. Admins do not receive a bypass on the session routes. Access to patient charts stays on the existing consent-scoped portal routes.

## Logging

Provider failures are logged with a status or the reason `connection`. The log line does not include the message text or the API key.
