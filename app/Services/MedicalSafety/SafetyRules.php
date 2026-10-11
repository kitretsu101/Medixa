<?php

namespace App\Services\MedicalSafety;

/**
 * Phrase rules for the safety layer.
 *
 * These are administrative matchers, not a clinical triage protocol.
 * Known limits are recorded in docs/medical-safety-limitations.md.
 */
final class SafetyRules
{
    /**
     * @return list<array{id: string, phrases: list<string>}>
     */
    public static function emergency(): array
    {
        return [
            [
                'id' => 'breathing_difficulty',
                'phrases' => [
                    "can't breathe",
                    'cannot breathe',
                    'cant breathe',
                    'cant breath',
                    'can not breathe',
                    'unable to breathe',
                    'not breathing',
                    'stopped breathing',
                    'difficulty breathing',
                    'hard to breathe',
                    'struggling to breathe',
                    'gasping for air',
                    'shortness of breath',
                    'short of breath',
                    'blue lips',
                    'lips turned blue',
                    'শ্বাস নিতে পারছি না',
                    'শ্বাস নিতে পারছে না',
                    'শ্বাসকষ্ট',
                    'নিশ্বাস নিতে কষ্ট',
                    'দম বন্ধ',
                ],
            ],
            [
                'id' => 'unconsciousness',
                'phrases' => [
                    'unconscious',
                    'unresponsive',
                    'not responding',
                    'not waking',
                    'will not wake',
                    "won't wake",
                    'passed out',
                    'collapsed',
                    'fainted',
                    'অজ্ঞান',
                    'জ্ঞান হারিয়েছে',
                    'জ্ঞান হারিয়েছে',
                    'সাড়া দিচ্ছে না',
                    'সাড়া দিচ্ছে না',
                ],
            ],
            [
                'id' => 'seizure',
                'phrases' => [
                    'seizure',
                    'seizing',
                    'convulsion',
                    'convulsing',
                    'having a fit',
                    'is fitting',
                    'খিঁচুনি',
                    'খিচুনি',
                ],
            ],
            [
                'id' => 'chest_pain',
                'phrases' => [
                    'chest pain',
                    'chest pains',
                    'chest hurts',
                    'pain in my chest',
                    'pain in the chest',
                    'pain in chest',
                    'pressure in my chest',
                    'pressure in the chest',
                    'crushing chest',
                    'বুকে ব্যথা',
                    'বুক ব্যথা',
                    'বুকে তীব্র ব্যথা',
                    'বুকে চাপ',
                ],
            ],
            [
                'id' => 'stroke_warning',
                'phrases' => [
                    'face droop',
                    'face is drooping',
                    'facial droop',
                    'slurred speech',
                    'speech is slurred',
                    'speech slurred',
                    'one side of my body',
                    'one side is weak',
                    'arm weakness',
                    "can't lift my arm",
                    'cannot lift my arm',
                    'sudden weakness on one side',
                    'signs of a stroke',
                    'having a stroke',
                    'stroke',
                    'মুখ বেঁকে',
                    'মুখ বাঁকা',
                    'কথা জড়িয়ে',
                    'কথা জড়িয়ে',
                    'এক পাশ অবশ',
                    'স্ট্রোক',
                ],
            ],
            [
                'id' => 'severe_allergy',
                'phrases' => [
                    'anaphylaxis',
                    'anaphylactic',
                    'severe allergic',
                    'throat is swelling',
                    'throat swelling',
                    'throat is closing',
                    'tongue is swelling',
                    'swollen tongue',
                    'অ্যানাফাইলাক্সিস',
                    'তীব্র অ্যালার্জি',
                    'গলা ফুলে',
                    'জিভ ফুলে',
                ],
            ],
            [
                'id' => 'serious_bleeding',
                'phrases' => [
                    'vomiting blood',
                    'coughing up blood',
                    'coughing blood',
                    'blood in my vomit',
                    'blood in vomit',
                    'heavy bleeding',
                    'বমি করে রক্ত',
                    'কাশিতে রক্ত',
                ],
            ],
            [
                'id' => 'self_harm',
                'phrases' => [
                    'kill myself',
                    'suicide',
                    'want to die',
                    'end my life',
                    'আত্মহত্যা',
                    'মরে যেতে চাই',
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, phrases: list<string>}>
     */
    public static function prescription(): array
    {
        return [
            [
                'id' => 'antibiotic_or_prescription',
                'phrases' => [
                    'antibiotic',
                    'antibiotics',
                    'amoxicillin',
                    'azithromycin',
                    'ciprofloxacin',
                    'doxycycline',
                    'metronidazole',
                    'penicillin',
                    'co-amoxiclav',
                    'augmentin',
                    'prescribe me',
                    'অ্যান্টিবায়োটিক',
                    'অ্যামোক্সিসিলিন',
                    'অ্যাজিথ্রোমাইসিন',
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, phrases: list<string>}>
     */
    public static function dose(): array
    {
        return [
            [
                'id' => 'dose_or_interaction',
                'phrases' => [
                    'what dose',
                    'which dose',
                    'how much should i take',
                    'how much should i give',
                    'how many mg',
                    'how many tablets',
                    'dosage',
                    'drug interaction',
                    'medicine interaction',
                    'interact with',
                    'take together',
                    'ডোজ',
                    'কতটা খাব',
                    'কত মিলিগ্রাম',
                    'একসাথে খাওয়া যাবে',
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, phrases: list<string>}>
     */
    public static function highRisk(): array
    {
        return [
            [
                'id' => 'child',
                'phrases' => [
                    'my baby',
                    'my child',
                    'my infant',
                    'my toddler',
                    'my newborn',
                    'the baby',
                    'infant',
                    'toddler',
                    'newborn',
                    'শিশু',
                    'বাচ্চা',
                    'নবজাতক',
                ],
            ],
            [
                'id' => 'pregnancy',
                'phrases' => [
                    'pregnant',
                    'pregnancy',
                    'গর্ভবতী',
                    'গর্ভাবস্থা',
                ],
            ],
            [
                'id' => 'serious_symptom',
                'phrases' => [
                    'worst headache',
                    'sudden severe headache',
                    'stiff neck',
                    'feeling faint',
                    'suddenly confused',
                    'new confusion',
                    'তীব্র মাথাব্যথা',
                    'ঘাড় শক্ত',
                ],
            ],
        ];
    }

    /**
     * Phrases in a generated reply that this assistant must not return.
     *
     * @return list<array{id: string, phrases: list<string>}>
     */
    public static function blockedReplyPhrases(): array
    {
        return [
            [
                'id' => 'safety_clearance',
                'phrases' => [
                    'you are safe',
                    "you're safe",
                    'you are fine',
                    "you're fine",
                    'nothing to worry',
                    'not an emergency',
                    'no emergency',
                    'not serious',
                    'you are not in danger',
                    'আপনি নিরাপদ',
                    'চিন্তার কিছু নেই',
                    'জরুরি নয়',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function blockedMedicineNames(): array
    {
        return [
            'amoxicillin',
            'azithromycin',
            'ciprofloxacin',
            'doxycycline',
            'metronidazole',
            'penicillin',
            'co-amoxiclav',
            'augmentin',
            'antibiotic',
            'antibiotics',
            'অ্যান্টিবায়োটিক',
            'অ্যামোক্সিসিলিন',
            'অ্যাজিথ্রোমাইসিন',
        ];
    }
}
