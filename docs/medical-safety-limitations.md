# Medical safety limitations

This note describes the chatbot safety layer in `MedicalSafetyService`. It is not a validation report, and the layer is not a clinical triage tool.

A qualified medical professional must review the emergency rules, the configured emergency number, the knowledge-base articles, and these tests before the chatbot is used with real patients. An admin login does not supply that review. Seeded articles stay pending until a named reviewer approves them.

## What the layer does

- It checks the visitor's message with phrase lists before any ordinary answer or appointment suggestion.
- Emergency phrases take precedence. The reply tells the person to stop using the chat and contact emergency care. The only number it may state is `medixa.safety.emergency.number`.
- The default number is 999, the National Emergency Service published by Bangladesh Police: https://www.police.gov.bd/en/bangladesh_police
- The site footer line `+880 1800-MEDIXA` is not used. It is not a verified public emergency service.
- If the configured number is blank, the reply says to contact local emergency services and does not invent a number.
- Children (under the configured age, default 16), pregnancy, and a short list of other serious symptoms get a clinician referral instead of routine self-care.
- Dose, interaction, antibiotic, and other prescription-medicine requests are refused unless a future approved dosing reference exists. None exists now.
- After the model replies, a second phrase check withholds a diagnosis, a dose, a listed prescription medicine, or a claim that the person is safe. A withheld reply is not booked.
- A missing answer, an uncertain knowledge-base result, and a provider failure each say that the gap does not mean the person is well.

## What a missing match means

No matched phrase does not mean the person is safe, that an emergency is absent, or that self-care is appropriate. The reply must not say that they are safe or that the situation is not an emergency.

## Known limits of the phrase rules

- The matcher does not understand sentences. It looks for configured phrases in English and a small Bangla set.
- Negation is ignored. "I do not have chest pain" still matches chest pain.
- A question that merely contains a trigger word matches. "What are the warning signs of a stroke?" is handled as an emergency message.
- Fiction and role-play are not distinguished from a real event. "The character is unconscious" is handled as an emergency message.
- Spelling, romanized Bangla, mixed language, images, and voice are mostly invisible to the rules. A few obvious misspellings are included (`cant breath`). Others are not (`cheast pain`).
- Unicode variants of Bangla words are not fully covered.
- The child cutoff is an administrative setting, not a pediatric definition. Bangla age written only in numerals, without a child word, is not detected.
- Many serious problems are outside the lists, including most trauma, poisoning, obstetric emergencies beyond the pregnancy words, and neonatal illness.
- The self-harm phrases only select the same emergency-care reply. They are not a crisis-line protocol and they do not assess risk.
- "Collapsed", "stroke", and "is fitting" are broad and can match harmless wording.

## Known limits of the model check

- The model is not a safety classifier. The prompt asks it to avoid unsafe claims, and the phrase check can still miss wording that is not on the list.
- Advice without a listed medicine name or a numeric dose can pass. Example: a vague instruction to start "the usual capsule".
- A correct sentence that happens to contain a blocked phrase, including "you are safe" or a medicine name, is withheld.
- Provider failure returns a fixed unavailable sentence. It does not assess the medical situation.

## Required review before patient use

Review and, where needed, replace:

- every phrase in `app/Services/MedicalSafety/SafetyRules.php`
- the emergency number, service name, and source URL
- the child-age cutoff
- the withheld-reply and emergency copy in both languages
- the knowledge articles and their approval status
- the tests in `tests/Feature/MedicalSafetyTest.php`, including the cases that are expected false positives

Until that review is recorded by a qualified reviewer, this system must not be presented as safe for real-world patient use. Deployment notes in the README do not replace that review, and they do not make the chatbot clinically validated or legally compliant.
