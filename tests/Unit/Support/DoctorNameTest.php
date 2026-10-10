<?php

namespace Tests\Unit\Support;

use App\Support\DoctorName;
use PHPUnit\Framework\TestCase;

class DoctorNameTest extends TestCase
{
    public function test_titled_name_is_not_repeated(): void
    {
        $formatted = DoctorName::format('Dr. Arman Hossain');

        $this->assertSame('Dr. Arman Hossain', $formatted);
    }

    public function test_untitled_name_gains_a_title(): void
    {
        $formatted = DoctorName::format('Arman Hossain');

        $this->assertSame('Dr. Arman Hossain', $formatted);
    }

    public function test_blank_name_reads_as_doctor(): void
    {
        $formatted = DoctorName::format('  ');

        $this->assertSame('Doctor', $formatted);
    }
}
