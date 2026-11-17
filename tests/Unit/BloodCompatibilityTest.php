<?php

namespace Tests\Unit;

use App\Services\BloodMatchingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BloodCompatibilityTest extends TestCase
{
    #[DataProvider('compatibility')]
    public function test_red_cell_discovery_matches_only_compatible_groups(string $recipient, array $expected): void
    {
        $this->assertSame($expected, (new BloodMatchingService)->compatibleDonorGroups($recipient));
    }

    public static function compatibility(): array
    {
        return [
            'O negative' => ['O-', ['O-']],
            'O positive' => ['O+', ['O-', 'O+']],
            'A negative' => ['A-', ['O-', 'A-']],
            'A positive' => ['A+', ['O-', 'O+', 'A-', 'A+']],
            'B negative' => ['B-', ['O-', 'B-']],
            'B positive' => ['B+', ['O-', 'O+', 'B-', 'B+']],
            'AB negative' => ['AB-', ['O-', 'A-', 'B-', 'AB-']],
            'AB positive' => ['AB+', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']],
            'unknown' => ['unknown', []],
        ];
    }
}
