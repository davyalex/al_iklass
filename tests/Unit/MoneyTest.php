<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_whole_numbers_have_no_decimals(): void
    {
        $this->assertSame('1 500', Money::format(1500));
        $this->assertSame('1 500', Money::format(1500.00));
        $this->assertSame('0', Money::format(0));
    }

    public function test_non_zero_decimals_show_two_digits(): void
    {
        $this->assertSame('1 500,50', Money::format(1500.5));
        $this->assertSame('1 500,05', Money::format(1500.05));
    }

    public function test_rounds_to_two_decimals(): void
    {
        $this->assertSame('1 500,56', Money::format(1500.555));
    }

    public function test_accepts_null_as_zero(): void
    {
        $this->assertSame('0', Money::format(null));
    }
}
