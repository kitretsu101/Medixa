<?php

namespace Tests\Unit;

use App\Services\SymptomTriageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SymptomTriageServiceTest extends TestCase
{
    #[DataProvider('departmentExamples')]
    public function test_keywords_map_only_to_seeded_departments(string $message, array $expected): void
    {
        $this->assertSame($expected, (new SymptomTriageService)->departmentsFor($message));
    }

    public static function departmentExamples(): array
    {
        return [
            'heart symptoms' => ['I have palpitations', ['Cardiology']],
            'case and punctuation' => ['My HEART is RACING!', ['Cardiology']],
            'emergency keyword map only' => ['chest pain', ['Cardiology']],
            'headache' => ['Recurring headaches', ['Neurology']],
            'numbness' => ['I have tingling and numbness', ['Neurology']],
            'joint pain' => ['I have joint-pain', ['Orthopedics']],
            'knee' => ['My knee pain is bothering me', ['Orthopedics']],
            'child symptoms' => ['My child has fever', ['Pediatrics']],
            'child context wins' => ['My child has knee pain', ['Pediatrics']],
            'bangla heart' => ['আমার বুক ধড়ফড় করছে', ['Cardiology']],
            'bangla headache' => ['আমার মাথাব্যথা', ['Neurology']],
            'bangla knee' => ['আমার হাঁটু ব্যথা', ['Orthopedics']],
            'bangla child' => ['শিশুর জ্বর', ['Pediatrics']],
            'ambiguous' => ['I have headaches and joint pain', ['Neurology', 'Orthopedics']],
            'no seeded general department' => ['fever, cough and sore throat', []],
            'no seeded skin department' => ['skin rash and itching', []],
            'no substring match' => ['I visited Back Painter Street', []],
            'not symptom language' => ['Hello, what departments are open?', []],
        ];
    }

    #[DataProvider('diagnosisRequests')]
    public function test_direct_diagnosis_and_probability_requests_are_detected(string $message): void
    {
        $this->assertTrue((new SymptomTriageService)->requestsDiagnosis($message));
    }

    public static function diagnosisRequests(): array
    {
        return [
            ['What disease do I have?'],
            ['Which illness is this?'],
            ['Diagnose me from my symptoms'],
            ['Give me a diagnosis'],
            ['Do I have diabetes?'],
            ['Could I have pneumonia?'],
            ['What is my diagnosis?'],
            ['Which medical condition do I have?'],
            ['What do I have?'],
            ['Predict my disease'],
            ['How likely am I to have a condition?'],
            ['What is wrong with me?'],
            ["What's wrong with me?"],
            ['Could this be a disease?'],
            ['Give a percentage for having a condition'],
            ['What is the probability of diabetes?'],
            ['আমার কী রোগ হয়েছে?'],
            ['আমার রোগের সম্ভাবনা কত শতাংশ?'],
        ];
    }

    #[DataProvider('unsafeReplies')]
    public function test_model_disease_likelihood_claims_are_rejected(string $reply): void
    {
        $this->assertTrue((new SymptomTriageService)->containsDiagnosticClaim($reply));
    }

    public static function unsafeReplies(): array
    {
        return [
            ['You may have Condition-Secret-991.'],
            ['You might have Condition-Secret-991.'],
            ['You could be suffering from Condition-Secret-991.'],
            ['You likely have Condition-Secret-991.'],
            ['Most likely, you have Condition-Secret-991.'],
            ['This is probably Condition-Secret-991.'],
            ['There is a 75% chance of Condition-Secret-991.'],
            ['Probability of Condition-Secret-991: 0.8.'],
            ['Your symptoms indicate Condition-Secret-991.'],
            ['আপনার সম্ভবত Condition-Secret-991 হয়েছে।'],
            ['Condition-Secret-991 হওয়ার সম্ভাবনা ৭৫%।'],
        ];
    }

    public function test_logistics_are_not_diagnosis_requests_or_claims(): void
    {
        $service = new SymptomTriageService;

        $this->assertFalse($service->requestsDiagnosis('Do I have an appointment?'));
        $this->assertFalse($service->requestsDiagnosis('What are the chances of booking a slot?'));
        $this->assertFalse($service->containsDiagnosticClaim('General hospital information.'));
        $this->assertFalse($service->hasSymptoms('What departments are open?'));
        $this->assertTrue($service->hasSymptoms('I feel unwell'));
    }

    public function test_suggestions_refusals_and_clarifications_always_include_the_exact_disclaimer(): void
    {
        $service = new SymptomTriageService;

        foreach (['en', 'bn'] as $locale) {
            foreach (['Cardiology', 'Neurology', 'Orthopedics', 'Pediatrics', null] as $department) {
                $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $service->suggestion($department, $locale));
            }
            $this->assertStringContainsString(SymptomTriageService::DISCLAIMER, $service->refusal($locale));
        }
        $this->assertSame(1, substr_count($service->withDisclaimer($service->suggestion(null, 'en')), SymptomTriageService::DISCLAIMER));
    }
}
