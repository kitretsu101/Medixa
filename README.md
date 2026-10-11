# Medixa — Outpatient department system

Medixa is a Laravel application for an outpatient department: patient booking, a live serial, doctor and reception desks, lab-report upload, consent-gated record access, and a hospital chatbot.

The chatbot shares general hospital information and, for a signed-in patient, can offer a routine visit. It is not a clinically validated triage tool, it is not a legal or regulatory compliance control, and this repository does not by itself make the system ready for real patients. A qualified clinical review, a privacy and security review, and an operational check are still required. See the checklist at the end.

## Architecture

Medixa is one Laravel app. Each portal is a controller, Blade pages, and a route group. Shared rules live in services.

```text
app/Http/Controllers/     patient, doctor, admin, lab, reception, public, chatbot
app/Http/Middleware/      role check and consent check
app/Services/             booking, queue, chat, safety, knowledge, notifications
app/Services/Ai/          replaceable completion provider
app/Models/               appointments, queue, lab reports, chat sessions, knowledge
resources/views/          one folder per portal, plus the chat panel
routes/web.php            routes grouped by role
storage/app/private/      lab files, not public
docs/                     queue, API, chat privacy, safety limits
```

| Piece | Role |
| --- | --- |
| `AppointmentService` | Daily cap of 20, two places held for emergencies, no second active visit with the same doctor on the same date |
| `QueueOptimizationService` | Who is called next, and the wait estimate |
| `QueueService` | Locked queue update and the broadcast event |
| `ChatService` | One chat turn: safety, knowledge, provider, then optional booking |
| `MedicalSafetyService` | Phrase checks before an ordinary answer and again on the model reply |
| `MedicalKnowledgeService` | Retrieves only approved, active, in-date articles |
| `ChatSessionService` | Server-issued session id and ownership check |
| `AiCompletionProvider` | OpenAI when a key is set; otherwise a local reply |

A chat turn runs in this order:

1. Emergency phrases stop the turn. The reply tells the person to get emergency care. No visit is offered or created.
2. A confirmation, when the message is not an emergency, is sent to `AppointmentService::book()` with priority `normal`.
3. Prescription and dose requests get a fixed referral. They do not call the model.
4. A signed-in patient who asks to book sees up to six routine choices. Guests are told to log in and do not see the date list.
5. A medical question uses an approved article when one matches. Otherwise the reply says the knowledge base does not cover it.
6. Other hospital questions go to the configured provider, or to a local department and doctor list when no key is set.

The model cannot create an appointment. A `[BOOKING_INTENT]` marker in a model reply is removed and ignored.

## How a visit works

1. The patient books online, reception books a walk-in, or a signed-in patient confirms a routine visit in the chat. The server assigns the next serial under a lock. Routine booking stops at 18. Two of the 20 daily places stay open for emergencies. Chat confirmation cannot use those two places.
2. On the day, check-in changes the visit from booked to waiting. A booking alone does not put someone in the live queue.
3. Call Next ranks checked-in patients. Emergency is first. Senior, follow-up, and normal then compete, and a long wait adds points so a normal patient is not stuck forever.
4. The patient sees their serial, how many people are ahead, an estimated wait, and a suggested arrival time. Until five completed visits exist, the average used for the estimate is a stated 12 minutes. A delay appears only if the doctor entered the minutes.
5. The doctor can order a lab test. The lab assistant uploads by the report reference and does not see the chart. The patient gets a link, not a public file.

Queue changes are saved in `queue_events`, then `QueueUpdated` is broadcast. With `QUEUE_CONNECTION=database`, that broadcast is queued, so a queue worker is required for the socket update. The patient page also polls, so the serial still moves if the socket is down.

## Local setup

Requirements from `composer.json`: PHP 8.3 or newer, Composer 2, Node.js 18 or newer, and MySQL.

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Create the database named in `.env`, then:

```bash
php artisan migrate --seed
php artisan serve
```

`php artisan migrate:fresh --seed` drops every table and reloads demo data. Use it only on a local database you can afford to wipe.

Demo accounts use the password `password`. Change or remove them before any shared server. They are listed at the end of this file.

The patient page polls `/patient/queue/status`. For the WebSocket path, set `BROADCAST_CONNECTION=reverb` and run `php artisan reverb:start` in a second terminal. Replace the local Reverb placeholders in `.env` before that server is reachable by anyone else.

## Migrations and seeds

`php artisan migrate` creates the hospital tables, the database cache, session, and job tables, and these chatbot tables:

| Migration | What it adds |
| --- | --- |
| `2026_10_10_073553_create_medical_knowledge_articles_table` | Knowledge articles, one row per topic and language |
| `2026_10_11_191430_create_chat_sessions_table` | Chat session id, optional user id, expiry |
| `2026_10_11_192404_change_chat_session_expiry_to_datetime` | Keeps expiry from being reset on update |

`php artisan db:seed` runs `MedixaSeeder` and `MedicalKnowledgeSeeder`.

`MedicalKnowledgeSeeder` inserts candidate articles with `firstOrCreate` on topic and language. Every seeded article is `pending`, with no reviewer. Running the seeder again does not overwrite a row that already exists, including one a reviewer has approved. It also does not approve anything.

To reload only the knowledge candidates on an existing database:

```bash
php artisan db:seed --class=MedicalKnowledgeSeeder
```

Chat session rows expire after `CHAT_SESSION_RETENTION_DAYS` (default 7), counted from creation. The scheduler runs `php artisan chat-sessions:prune` daily. You can run that command by hand.

## Environment variables

Copy `.env.example`. Do not commit a filled `.env`. Values below are names and safe defaults, not secrets.

| Variable | Purpose | Local example |
| --- | --- | --- |
| `APP_NAME` | Application name | `Medixa` |
| `APP_ENV` | `local` or `production` | `local` |
| `APP_KEY` | Encryption key from `php artisan key:generate` | empty until generated |
| `APP_DEBUG` | Error pages and debug output | `true` locally. **`false` for any shared server** |
| `APP_URL` | Canonical URL | `http://localhost:8000` |
| `LOG_LEVEL` | Log verbosity | `debug` locally. **`warning` for any shared server** |
| `DB_*` | MySQL connection | host `127.0.0.1`, database `medixa_db` |
| `SESSION_DRIVER` | Login sessions | `database` |
| `CACHE_STORE` | Rate limits and framework cache | `database` |
| `QUEUE_CONNECTION` | Broadcast jobs | `database` |
| `FILESYSTEM_DISK` | Default disk. Lab files use the private disk | `local` |
| `BROADCAST_CONNECTION` | Queue socket. `log` disables it | `log` in the example |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Reverb, only if you enable it | local placeholders. Replace them |
| `AI_PROVIDER` | `openai`, or any other name for the local reply | `openai` |
| `AI_API_KEY` | Provider credential. `OPENAI_API_KEY` is the fallback | empty |
| `AI_MODEL` | Model name | `gpt-4o-mini` |
| `AI_TIMEOUT` | Response timeout, seconds | `20` |
| `AI_CONNECT_TIMEOUT` | Connect timeout, seconds | `5` |
| `KNOWLEDGE_REVIEW_VALID_DAYS` | How long an approval stays retrievable | `365` |
| `MEDIXA_CHILD_AGE_YEARS` | Age under which a child phrase is high-risk | `16` |
| `MEDIXA_EMERGENCY_NUMBER` | Only number the emergency reply may state | `999` |
| `MEDIXA_EMERGENCY_SERVICE_NAME` | Label next to that number | `National Emergency Service` |
| `MEDIXA_EMERGENCY_SOURCE_NAME` | Source shown with the reply | `Bangladesh Police` |
| `MEDIXA_EMERGENCY_SOURCE_URL` | HTTPS source link | Bangladesh Police page in `.env.example` |
| `CHAT_SESSION_RETENTION_DAYS` | Session-row lifetime | `7` |

`VITE_REVERB_*` is public by design because the browser opens the socket. Do not put `AI_API_KEY`, `APP_KEY`, or database passwords in a `VITE_` variable.

## AI provider

`AppServiceProvider` binds `AiCompletionProvider`. `AI_PROVIDER=openai` uses `OpenAiCompletionProvider`, which reads `AI_API_KEY` and then `OPENAI_API_KEY`. Any other provider name, or an empty key, uses `UnconfiguredAiProvider` and a local reply. No request is sent.

The outbound call is `POST https://api.openai.com/v1/chat/completions`. The body has the system prompt, the user message, and a correlation id `session:` plus 32 hex characters. That id is not the session UUID and not the user id. The key is sent only as the bearer token.

To check connectivity without a symptom and without a paid call:

1. Leave both key variables empty.
2. Open the site and ask `Which departments are open?`
3. The reply is the local department list. `storage/logs/laravel.log` should not contain that message.

To check a configured key:

1. Put the key only in the server environment.
2. Ask the same department question, not a symptom and not a medicine question.
3. A provider HTTP failure returns a fixed “could not answer” sentence with `error: true` and `retryable: true`. The log line is `AI provider request failed.` with either `reason: connection` or a status code.
4. Do not paste `php artisan config:show services.ai` into a ticket. That output can include the key.

The automated suite mocks the provider. `php artisan test --compact tests/Feature/ChatbotTest.php` does not call OpenAI. `phpunit.xml` forces an empty key.

## Medical knowledge

Articles live in `medical_knowledge_articles`. The admin screens are `/admin/knowledge` and `/admin/knowledge/{article}`, limited to `role:admin`.

An article is retrieved only when it is `approved`, `is_active` is true, `reviewer_name` is set, and `review_date` is within `KNOWLEDGE_REVIEW_VALID_DAYS`. Pending, rejected, inactive, and expired articles are excluded. A missing article produces the insufficient-information reply and no source links.

| Action | How |
| --- | --- |
| Add | Add a pending row, or extend `MedicalKnowledgeSeeder` and run `php artisan db:seed --class=MedicalKnowledgeSeeder`. New seed rows stay pending. There is no “create article” screen. |
| Review | Sign in as an admin, open `/admin/knowledge`, and open the article. The page shows the stored text and source. An admin login does not approve it. |
| Approve | Set the decision to approved and enter the reviewer’s name. The review date is set to today. |
| Reject | Set the decision to rejected and enter the reviewer’s name. Rejected text is not retrieved. |
| Disable | Set Active to inactive and save. An inactive article is not retrieved even if it was approved. |
| Update the wording | The review form does not edit the title or body. Change the text in a controlled database update, then set the row back to `pending`, clear `reviewer_name` and `review_date`, and review it again. Leaving an old approval on edited text would keep serving the new wording without a new review. |

Seeded topics are general self-care notes drawn from named public pages (NHS symptom and hydration pages, and a WHO medicine-quality page). They are candidates, not an approved clinical library.

## Emergency rules, fallback, and limits

`MedicalSafetyService` checks the message before an ordinary answer, and checks the model text afterward. Details and the phrase limits are in `docs/medical-safety-limitations.md`.

- Emergency wording in English or Bangla returns a fixed reply. The only number it may include is `MEDIXA_EMERGENCY_NUMBER`. A blank number does not invent one. The footer text `+880 1800-MEDIXA` is not used.
- The emergency reply does not offer a routine visit, even if the same message asks to book or includes `confirm`.
- Children under the configured age, pregnancy, and a short serious-symptom list get a clinician referral and, for a signed-in patient, routine visit choices. That is not an emergency clearance.
- Dose, interaction, and antibiotic requests are refused. There is no approved dosing reference.
- If the model reply contains a diagnosis, a dose, a listed prescription medicine, or a clearance such as “you are safe”, the reply is replaced with a fixed withheld sentence and nothing is booked.
- If the provider fails, or no approved article matches, the reply says a missing answer does not mean the person is well.

The phrase lists do not understand negation, fiction, or most typos. No match does not mean the person is safe.

## Access control and privacy

`docs/chat-privacy.md` is the storage note. It is not a legal assessment.

- The chat panel is on the public layout and on the signed-in layout only when the user is a patient. Doctor, admin, lab, and reception pages do not include it.
- The server creates the session UUID. A client-supplied id does not create a row.
- Guests can use only guest sessions. A signed-in user can use only their own. Other patients and staff receive 404. There is no admin transcript page.
- Message text is not stored. Chart fields are not added to the provider prompt.
- The browser sends `X-CSRF-TOKEN`. Messages are limited to 2000 characters and 30 requests per minute.
- Clearing the chat deletes the session row. It does not delete a medical record.
- Routine visit choices are returned only to a signed-in patient. They contain doctor, department, date, and remaining routine places. They do not contain another patient’s name or serial.

Lab files stay on `storage/app/private`. A download needs a short-lived signed URL and either the patient who owns the report or a doctor with an unexpired, unrevoked consent token.

## Chat HTTP examples

Full tables are in `docs/api.md`. Requests need the session cookie and this header:

```http
X-CSRF-TOKEN: {value of the csrf-token meta tag}
Accept: application/json
Content-Type: application/json
```

Directory question:

```json
{ "message": "Which departments are open?", "locale": "en" }
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

Patient confirmation:

```json
{
  "message": "Confirm this routine appointment",
  "session_token": "00000000-0000-4000-8000-000000000000",
  "confirm": {
    "doctor_id": 1,
    "appointment_date": "2026-10-20",
    "confirmed": true
  }
}
```

`priority_type`, `serial_number`, and `time` in that object are ignored. The serial is assigned by the server. A duplicate or a full routine day returns `booking.success` false. A missing `confirmed` flag returns 422 and creates nothing.

`DELETE /chatbot/sessions/{session}` returns `{ "deleted": true }` for the owner and 404 otherwise.

## Deployment

This is the configuration the app expects. It is not a statement that a particular host has been verified.

| Concern | What to set |
| --- | --- |
| Debug | `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`. `config/app.php` defaults debug to false only when `APP_DEBUG` is absent. The example file sets it true for local work. |
| Config cache | `php artisan config:cache`, `route:cache`, and `view:cache` after the environment is in place. Change a variable only by updating the environment and caching again. |
| Dependencies | `composer install --no-dev` and `npm run build`. Dev tools such as Pail, Pint, and PHPUnit are in `require-dev`. |
| Database | `php artisan migrate --force` on the production database. Do not run `migrate:fresh` there. |
| Cache | `CACHE_STORE=database` uses the `cache` table from migrations, including the chat rate limit. |
| Queue | `QUEUE_CONNECTION=database` uses the `jobs` table. Run `php artisan queue:work` so `QueueUpdated` broadcasts leave the queue. Chat alerts are synchronous database notifications and do not depend on that worker. |
| Scheduler | Cron `* * * * * php /path/to/artisan schedule:run`. That runs `chat-sessions:prune` daily. |
| Storage | Lab files use `storage/app/private`. Do not expose that directory and do not copy those files onto the public disk. `storage/logs` is gitignored. |
| Sessions | `SESSION_DRIVER=database`. The example leaves `SESSION_ENCRYPT=false`. Turn encryption on only as a reviewed operational change. |
| Realtime | Leave `BROADCAST_CONNECTION=log` until Reverb is configured with new credentials. The poll still updates the serial. |
| Demo data | Do not seed demo accounts onto a shared server. |

Provider failure logs must stay as they are: status or `connection`, never the message and never the API key. `APP_DEBUG=true` would add framework stack traces to error pages. Keep it off outside local development.

## Tests

```bash
php artisan test --compact
```

Chat coverage is in `tests/Feature/ChatbotTest.php`, `ChatPrivacyTest.php`, `ChatAppointmentTest.php`, `MedicalKnowledgeTest.php`, and `MedicalSafetyTest.php`. Portal, queue, consent, and lab-file tests are in the same suite. A passing run is not a clinical validation.

## Pre-deployment checklist

Do not treat a completed engineering pass as approval to put this in front of patients.

**Security**

- [ ] `APP_DEBUG` is false and `LOG_LEVEL` is warning or quieter.
- [ ] `APP_KEY` was generated for this environment and is not in git.
- [ ] `AI_API_KEY` and `OPENAI_API_KEY` exist only on the server. They are absent from git, Blade, and built JavaScript.
- [ ] Reverb and database passwords are not the example placeholders.
- [ ] Demo users are absent or their passwords have been replaced.
- [ ] `composer install --no-dev` was used.
- [ ] HTTPS is terminated in front of the app, and `APP_URL` matches it.
- [ ] The private lab disk is not web-accessible.
- [ ] Chat and login rate limits are still in `routes/web.php`.

**Privacy**

- [ ] Someone other than the owner of a chat session receives 404, including an admin.
- [ ] Message text is still not written to `chat_sessions` or to provider failure logs.
- [ ] The scheduler is running `chat-sessions:prune`.
- [ ] The public chat notice still says messages may be sent to the configured provider.
- [ ] A privacy review has decided whether sending message text to that provider is acceptable. This repository does not make that decision.

**Clinical review**

- [ ] A qualified reviewer has read `app/Services/MedicalSafety/SafetyRules.php`, both languages of the emergency copy, and `docs/medical-safety-limitations.md`.
- [ ] The emergency number, service name, and source URL have been confirmed for the place the app will be used.
- [ ] Each knowledge article a patient might see has a named reviewer, or it is still pending and therefore not retrieved.
- [ ] No one has described the chatbot as clinically validated.

**Operations**

- [ ] Migrations have been applied with `php artisan migrate --force`.
- [ ] A queue worker is running if broadcasts should leave the database queue.
- [ ] A non-medical department question has been tried with the key empty, and again with the key set if a live provider will be used.
- [ ] `php artisan test --compact` was run on a release candidate and the result was kept.

## Demo accounts

Local seed only. Password: `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@medixa.test` |
| Doctor | `arman.hossain@medixa.test` |
| Doctor | `nadia.rahman@medixa.test` |
| Doctor | `farhan.karim@medixa.test` |
| Patient | `rahima.khatun@patient.test` |
| Patient | `mahbub.alam@patient.test` |
| Patient | `shirin.sultana@patient.test` |
| Lab assistant | `lab.assistant@medixa.test` |
| Reception | `reception@medixa.test` |

## Further notes

- `docs/architecture.md`
- `docs/project-overview.md`
- `docs/queue-algorithm.md`
- `docs/api.md`
- `docs/chat-privacy.md`
- `docs/medical-safety-limitations.md`
