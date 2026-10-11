<?php

namespace App\Services;

use App\Services\MedicalSafety\SafetyAssessment;
use App\Services\MedicalSafety\SafetyRules;

/**
 * Rule-based safety layer for the chatbot.
 *
 * This is not a validated clinical triage tool. Matching and output checks
 * are phrase rules. Limits are documented in docs/medical-safety-limitations.md.
 */
class MedicalSafetyService
{
    public function assess(string $message): SafetyAssessment
    {
        $text = $this->normalize($message);

        foreach ([
            'emergency' => SafetyRules::emergency(),
            'prescription' => SafetyRules::prescription(),
            'dose' => SafetyRules::dose(),
            'high_risk' => SafetyRules::highRisk(),
        ] as $level => $rules) {
            foreach ($rules as $rule) {
                foreach ($rule['phrases'] as $phrase) {
                    if ($this->containsPhrase($text, $this->normalize($phrase))) {
                        return new SafetyAssessment($level, $rule['id']);
                    }
                }
            }
        }

        if ($this->mentionsChildAge($text)) {
            return new SafetyAssessment('high_risk', 'child_age');
        }

        return new SafetyAssessment('routine', null);
    }

    /**
     * Return a violation id when a generated reply must not be shown.
     */
    public function outputViolation(string $reply): ?string
    {
        $text = $this->normalize($reply);

        foreach (SafetyRules::blockedReplyPhrases() as $rule) {
            foreach ($rule['phrases'] as $phrase) {
                if ($this->containsPhrase($text, $this->normalize($phrase))) {
                    return $rule['id'];
                }
            }
        }

        if ($this->containsDose($text)) {
            return 'invented_dose';
        }

        foreach (SafetyRules::blockedMedicineNames() as $name) {
            if ($this->containsPhrase($text, $this->normalize($name))) {
                return 'prescription_medicine';
            }
        }

        if ($this->containsDefinitiveDiagnosis($text)) {
            return 'definitive_diagnosis';
        }

        return null;
    }

    /**
     * @return array{reply: string, sources: list<array{title: string, source_name: string, source_url: string, topic: string}>}
     */
    public function emergencyReply(string $locale): array
    {
        $number = trim((string) config('medixa.safety.emergency.number'));
        $service = trim((string) config('medixa.safety.emergency.service_name'));
        $sourceName = trim((string) config('medixa.safety.emergency.source_name'));
        $sourceUrl = trim((string) config('medixa.safety.emergency.source_url'));

        $reply = $this->emergencyCopy($locale, $number, $service);
        $sources = [];

        if ($number !== '' && str_starts_with($sourceUrl, 'https://') && $sourceName !== '') {
            $sources[] = [
                'title' => $service !== '' ? $service : 'Emergency number',
                'source_name' => $sourceName,
                'source_url' => $sourceUrl,
                'topic' => 'emergency',
            ];
        }

        return [
            'reply' => $reply,
            'sources' => $sources,
        ];
    }

    public function highRiskReply(string $locale): string
    {
        $number = trim((string) config('medixa.safety.emergency.number'));

        if ($locale === 'bn') {
            $line = 'এটি চ্যাটের সাধারণ পরামর্শের বিষয় নয়। এই সহকারী বলতে পারে না যে আপনি নিরাপদ, এবং এটি রোগ নির্ণয় বা ওষুধ বেছে দেয় না। শিশু, গর্ভাবস্থা বা গুরুতর লক্ষণের জন্য দ্রুত একজন চিকিৎসকের সাথে যোগাযোগ করুন।';

            if ($number !== '') {
                $line .= ' খুব অসুস্থ লাগলে চ্যাট বন্ধ করে '.$number.'-এ জরুরি সেবায় যোগাযোগ করুন।';
            }

            return $line;
        }

        $line = 'This needs a clinician, not routine self-care from a chat. This assistant cannot tell you that you are safe, and it cannot diagnose or choose a medicine. For a child, pregnancy, or a potentially serious symptom, contact a doctor promptly.';

        if ($number !== '') {
            $line .= ' If the person is very unwell, stop using this chat and call '.$number.'.';
        }

        return $line;
    }

    public function doseReply(string $locale): string
    {
        if ($locale === 'bn') {
            return 'এই সহকারী ডোজ বলতে বা ওষুধ একসাথে নিরাপদ কি না বলতে পারে না। একজন ফার্মাসিস্ট বা প্রেসক্রিপশন দেওয়া চিকিৎসককে জিজ্ঞাসা করুন। এই চ্যাট থেকে কোনো ডোজ ব্যবহার করবেন না।';
        }

        return 'This assistant cannot give a dose or say whether medicines are safe together. There is no approved dosing reference for this question. Ask a pharmacist or the prescribing clinician, and do not use a dose from this chat.';
    }

    public function prescriptionReply(string $locale): string
    {
        if ($locale === 'bn') {
            return 'এই সহকারী অ্যান্টিবায়োটিক বা প্রেসক্রিপশন-ওষুধ সুপারিশ করে না। সেই সিদ্ধান্তের জন্য ক্লিনিকাল মূল্যায়ন দরকার। একজন চিকিৎসক বা ফার্মাসিস্টের সাথে কথা বলুন।';
        }

        return 'This assistant cannot recommend an antibiotic or another prescription-only medicine. That decision needs a clinical assessment. Please speak with a doctor or pharmacist.';
    }

    public function withheldReply(string $locale): string
    {
        if ($locale === 'bn') {
            return 'উত্তরটি দেখানো হয়নি, কারণ তাতে রোগ নির্ণয়, ডোজ, প্রেসক্রিপশন-ওষুধ, বা নিরাপদ বলে দাবি ছিল। এই সহকারী সেই দাবি করতে পারে না। একজন চিকিৎসক বা ফার্মাসিস্টের সাথে কথা বলুন। গুরুতর অসুস্থতায় জরুরি সেবা নিন।';
        }

        return 'That answer was withheld because it included a diagnosis, a dose, a prescription medicine, or a clearance. This assistant cannot make those claims. Please speak with a doctor or pharmacist. If you might be seriously unwell, contact emergency services.';
    }

    private function emergencyCopy(string $locale, string $number, string $service): string
    {
        if ($locale === 'bn') {
            if ($number === '') {
                return 'এটি জরুরি হতে পারে। এই চ্যাট বন্ধ করে এখনই স্থানীয় জরুরি সেবায় যোগাযোগ করুন। এই সহকারীর জন্য অপেক্ষা করবেন না এবং নিয়মিত অ্যাপয়েন্টমেন্টের জন্যও অপেক্ষা করবেন না। এটি রোগ নির্ণয় নয়।';
            }

            $serviceLine = $service !== '' ? ' ('.$service.')' : '';

            return 'এটি জরুরি হতে পারে। এই চ্যাট বন্ধ করে এখনই জরুরি সেবা নিন। '.$number.$serviceLine.'-এ কল করুন। এই সহকারীর জন্য অপেক্ষা করবেন না এবং নিয়মিত অ্যাপয়েন্টমেন্টের জন্যও অপেক্ষা করবেন না। এটি রোগ নির্ণয় নয়। আপনি যেখানে আছেন সেখানে এই নম্বর জরুরি সেবা না হলে স্থানীয় জরুরি নম্বরে কল করুন।';
        }

        if ($number === '') {
            return 'This may be an emergency. Stop using this chat and contact your local emergency services now. Do not wait for this assistant, and do not wait for a routine appointment. This is not a diagnosis.';
        }

        $serviceLine = $service !== '' ? ' ('.$service.')' : '';

        return 'This may be an emergency. Stop using this chat and get emergency care now. Call '.$number.$serviceLine.'. Do not wait for this assistant, and do not wait for a routine appointment. This is not a diagnosis. If this number is not the emergency service where you are, use the local emergency number instead.';
    }

    private function mentionsChildAge(string $text): bool
    {
        if (preg_match('/\b(\d{1,2})\s*-?\s*(year|month|week|day)s?\s*-?\s*old\b/u', $text, $matches) !== 1) {
            return false;
        }

        $age = (int) $matches[1];
        $unit = $matches[2];

        if ($unit !== 'year') {
            return true;
        }

        return $age < (int) config('medixa.safety.child_age_years');
    }

    private function containsDose(string $text): bool
    {
        if (preg_match('/\b\d+(?:\.\d+)?\s*(?:mg|mcg|g|ml|iu)\b/u', $text) === 1) {
            return true;
        }

        if (preg_match('/\b(?:take|give)\s+\d+\b/u', $text) === 1) {
            return true;
        }

        return preg_match('/\b(?:one|two|three|four|\d+)\s+tablets?\b/u', $text) === 1;
    }

    private function containsDefinitiveDiagnosis(string $text): bool
    {
        if (preg_match('/\b(?:i diagnose|you are diagnosed|this is definitely)\b/u', $text) === 1) {
            return true;
        }

        if (preg_match('/\bthis is (?:a |an )?(?:heart attack|stroke|seizure|anaphylaxis)\b/u', $text) === 1) {
            return true;
        }

        return preg_match('/\byou have (?:a |an )?(?:pneumonia|covid-19|covid|cancer|stroke|heart attack|infection|migraine|flu|asthma|diabetes|sepsis|bronchitis)\b/u', $text) === 1;
    }

    private function containsPhrase(string $haystack, string $phrase): bool
    {
        if ($phrase === '') {
            return false;
        }

        if (preg_match('/\p{Bengali}/u', $phrase) === 1) {
            return str_contains($haystack, $phrase);
        }

        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($phrase, '/').'(?![\p{L}\p{N}])/u';

        return preg_match($pattern, $haystack) === 1;
    }

    private function normalize(string $value): string
    {
        $text = mb_strtolower($value);
        $text = str_replace(["\u{2019}", "\u{2018}", '`'], "'", $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
