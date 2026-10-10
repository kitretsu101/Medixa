# Queue algorithm

Medixa still shows a chamber serial. The serving order of patients who have checked in is no longer “lowest serial only.”

## Categories

| Priority | Base score | Who can set it |
| --- | --- | --- |
| Emergency | 100, on a separate lane | Reception or a walk-in booking |
| Senior citizen | 70 | Patient or reception |
| Follow-up | 50 | Patient or reception |
| Normal | 10 | Default |

Patients cannot book the emergency priority themselves. Two of the 20 daily slots are held back from routine bookings.

## Score

For a checked-in patient:

```
aging = min(80, minutes since check-in)
score = base + aging
```

Emergency uses `1000 + base + aging`, so a waiting emergency is always ahead of a non-emergency patient. Aging still orders emergencies against each other.

A normal patient who has waited 80 minutes scores 90. That is above a senior citizen who just checked in (70), so a normal patient is not stuck forever behind new arrivals. A senior who has also been waiting keeps their own aging points.

Ties use earlier check-in, then lower serial.

Before check-in, the estimate uses the gap between the chamber serial and the patient’s serial. After check-in, “patients ahead” is the patient’s place in this ranked waiting list.

## Waiting time

```
estimated minutes = patients ahead × average consultation + recorded doctor delay
```

Average consultation is the mean of the doctor’s last 30 visits that have both a start and an end time. If fewer than five exist, the figure is the 12-minute baseline and the screen treats it as an estimate.

“Recommended arrival” is that estimate plus a 15-minute buffer. It is not a guaranteed time.

## Call next

If at least one patient is `checked_in` or `waiting`, Call Next selects the highest score inside a database transaction and row lock. The previous `in_progress` visit for today is marked completed and given an end time. The chosen visit becomes `in_progress`, and the chamber serial becomes that patient’s serial.

If nobody has checked in, the serial still advances by one so the existing control keeps working.

Patients are notified when they are 10, 5, 3, or 1 ahead, and when the doctor records a delay or pauses the queue. Polling every few seconds does not send those notices.

## Comparison

`/admin/analytics` runs FIFO, priority-only, and priority-plus-aging on the same generated arrival list. Those minutes are calculated when the page loads. They are not stored as a claimed hospital improvement.
