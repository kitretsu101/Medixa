<?php

namespace App\Services;

use App\Models\MedicalKnowledgeArticle;
use Illuminate\Support\Collection;

class MedicalKnowledgeService
{
    /**
     * Topic keys matched by ordinary phrase search. Longer phrases are checked first.
     *
     * @var array<string, list<string>>
     */
    private const TOPICS = [
        'fever' => ['fever', 'temperature', 'febrile', 'জ্বর'],
        'hydration' => ['hydration', 'stay hydrated', 'পানি পান'],
        'cough' => ['cough', 'coughing', 'কাশি'],
        'sore_throat' => ['sore throat', 'throat pain', 'গলা ব্যথা'],
        'headache' => ['headache', 'head ache', 'মাথাব্যথা', 'মাথা ব্যথা'],
        'medicine_safety' => ['medicine safety', 'medication safety', 'falsified medicine', 'ওষুধের নিরাপত্তা'],
        'dehydration' => ['dehydration', 'dehydrated', 'পানিশূন্য'],
        'warning_signs' => ['warning sign', 'warning signs', 'red flag', 'when to call 999', 'সতর্কতা'],
    ];

    public function isMedicalQuestion(string $message): bool
    {
        return $this->topicsIn($message) !== [];
    }

    /**
     * @return Collection<int, MedicalKnowledgeArticle>
     */
    public function retrieve(string $message, string $locale): Collection
    {
        $topics = $this->topicsIn($message);

        if ($topics === []) {
            return collect();
        }

        return MedicalKnowledgeArticle::query()
            ->retrievable()
            ->where('language', $locale === 'bn' ? 'bn' : 'en')
            ->whereIn('topic', $topics)
            ->orderBy('topic')
            ->limit(3)
            ->get();
    }

    /**
     * @return list<string>
     */
    private function topicsIn(string $message): array
    {
        $haystack = mb_strtolower($message);
        $matched = [];

        foreach (self::TOPICS as $topic => $aliases) {
            $ordered = $aliases;
            usort($ordered, fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

            foreach ($ordered as $alias) {
                if (str_contains($haystack, mb_strtolower($alias))) {
                    $matched[] = $topic;
                    break;
                }
            }
        }

        return $matched;
    }
}
