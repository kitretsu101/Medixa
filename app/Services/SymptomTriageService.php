<?php

namespace App\Services;

/**
 * Keyword-based department navigation; existing medical-safety checks retain priority.
 */
class SymptomTriageService
{
    public const DISCLAIMER = 'This is not a medical diagnosis. Please consult a doctor for proper evaluation. If this is a medical emergency, use the Emergency Request button on the homepage.';

    /** @var array<string, list<string>> */
    private const DEPARTMENT_KEYWORDS = [
        'Cardiology' => ['chest pain', 'palpitation', 'palpitations', 'heart racing', 'heart is racing', 'racing heart', 'irregular heartbeat', 'বুকে ব্যথা', 'বুক ধড়ফড়', 'বুক ধড়ফড়'],
        'Neurology' => ['headache', 'headaches', 'head ache', 'tingling', 'numbness', 'tremor', 'মাথাব্যথা', 'মাথা ব্যথা', 'অবশ', 'ঝিনঝিন'],
        'Orthopedics' => ['joint pain', 'joint stiffness', 'knee pain', 'back pain', 'shoulder pain', 'bone pain', 'neck pain', 'হাঁটু ব্যথা', 'কোমর ব্যথা', 'হাড়ে ব্যথা', 'জয়েন্টে ব্যথা'],
    ];

    /** @var list<string> */
    private const OTHER_SYMPTOMS = [
        'fever', 'cough', 'coughing', 'sore throat', 'skin rash', 'rash', 'itching', 'itchy',
        'stomach pain', 'pain', 'hurts', 'unwell', 'feel sick', 'feeling sick', 'symptoms',
        'জ্বর', 'কাশি', 'গলা ব্যথা', 'চুলকানি', 'অসুস্থ', 'লক্ষণ',
    ];

    /** @return list<string> */
    public function departmentsFor(string $message): array
    {
        $text = $this->normalize($message);
        $matched = [];

        foreach (self::DEPARTMENT_KEYWORDS as $department => $keywords) {
            if ($this->matches($text, $keywords)) {
                $matched[] = $department;
            }
        }

        if ($this->matches($text, ['child', 'baby', 'infant', 'toddler', 'teenager', 'শিশু', 'শিশুর', 'বাচ্চা', 'বাচ্চার'])
            && ($matched !== [] || $this->matches($text, self::OTHER_SYMPTOMS))) {
            return ['Pediatrics'];
        }

        return $matched;
    }

    public function hasSymptoms(string $message): bool
    {
        return $this->departmentsFor($message) !== [] || $this->matches($this->normalize($message), self::OTHER_SYMPTOMS);
    }

    public function requestsDepartment(string $message): bool
    {
        return $this->matches($this->normalize($message), [
            'department', 'specialty', 'specialist', 'where should i go', 'which doctor',
            'বিভাগ', 'কোন ডাক্তার', 'কোথায় যাব',
        ]);
    }

    public function requestsDiagnosis(string $message): bool
    {
        $text = $this->normalize($message);

        if (preg_match('/\b(?:(?:what|which) (?:medical )?(?:disease|illness|condition)|diagnose (?:me|my)|(?:give me|i need|i want) (?:a )?diagnosis|what is my diagnosis|what do i have|what have i got|predict (?:my|the) (?:disease|diagnosis)|(?:do|could|might|may) i have (?!an? appointment|a booking|an? account|a slot))\b/u', $text) === 1
            || $this->matches($text, ['আমার কি রোগ', 'আমার কী রোগ', 'কোন রোগ হয়েছে', 'রোগ নির্ণয় করুন', 'রোগের সম্ভাবনা', 'কত শতাংশ'])) {
            return true;
        }

        $probability = preg_match('/\b(?:probability|likelihood|odds|chances?|risk|percent|percentage|how likely|how probable|am i likely|could this be|might this be|what is wrong with me|what s wrong with me)\b|%/u', $text) === 1;
        $bookingOnly = $this->matches($text, ['appointment', 'booking', 'slot'])
            && ! $this->hasSymptoms($message)
            && ! $this->matches($text, ['disease', 'illness', 'condition', 'diagnosis']);

        return $probability && ! $bookingOnly;
    }

    public function containsDiagnosticClaim(string $reply): bool
    {
        $text = $this->normalize($reply);

        return preg_match('/\p{N}+(?:[.,]\p{N}+)?\s*(?:%|percent)|\b(?:probability|likelihood|odds|chances?|percentage)\b|\byou (?:(?:may|might|could|probably|possibly|likely) (?:have|suffer|be suffering)|(?:are|have) (?:probably|likely))\b|\b(?:likely|probably|possibly) you (?:have|are)\b|\b(?:this|it) is (?:likely|probably|possibly)\b|\b(?:possible|probable|likely) diagnosis\b|\b(?:your )?symptoms (?:suggest|indicate|mean|point to)\b/u', $text) === 1
            || $this->matches($text, ['আপনার সম্ভবত', 'আপনার হয়তো', 'আপনার হয়তো', 'শতাংশ সম্ভাবনা', 'শতকরা']);
    }

    public function refusal(string $locale): string
    {
        $reply = $locale === 'bn'
            ? 'আমি রোগ নির্ণয় বা কোনো রোগ হওয়ার সম্ভাবনা বলতে পারি না। বিভাগ সম্পর্কে পরামর্শের জন্য আপনার লক্ষণ বর্ণনা করুন, অথবা অ্যাপয়েন্টমেন্ট নিতে বলুন।'
            : 'I cannot diagnose a disease or estimate the chance of having a condition. Describe your symptoms for a department suggestion, or ask to book an appointment.';

        return $this->withDisclaimer($reply);
    }

    public function suggestion(?string $department, string $locale): string
    {
        if ($department === null) {
            $reply = $locale === 'bn'
                ? 'উপলব্ধ বিভাগগুলোর মধ্যে একটি স্পষ্ট বিভাগ বেছে নিতে পারছি না। কোন লক্ষণটি আপনাকে সবচেয়ে বেশি বিরক্ত করছে?'
                : 'I cannot choose a clear department from the available departments. Which symptom is bothering you most?';
        } else {
            $reply = $locale === 'bn'
                ? 'লক্ষণের শব্দগুলোর ভিত্তিতে মূল্যায়নের জন্য '.$department.' বিভাগে যোগাযোগ করতে পারেন।'
                : 'Based on the symptom keywords, you can start with the '.$department.' department for evaluation.';
        }

        return $this->withDisclaimer($reply);
    }

    public function withDisclaimer(string $reply): string
    {
        return str_contains($reply, self::DISCLAIMER) ? $reply : trim($reply)."\n\n".self::DISCLAIMER;
    }

    /** @param list<string> $phrases */
    private function matches(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (preg_match('/(?<![\p{L}\p{M}\p{N}])'.preg_quote($phrase, '/').'(?![\p{L}\p{M}\p{N}])/u', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/[^\p{L}\p{M}\p{N}%]+/u', ' ', mb_strtolower($text)));
    }
}
