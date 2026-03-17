<?php

declare(strict_types=1);

namespace Temant\Archiver\Tests\Unit\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Temant\Archiver\Enum\CompressionLevel;

final class CompressionLevelTest extends TestCase
{
    #[Test]
    public function it_has_correct_integer_values(): void
    {
        $this->assertSame(0, CompressionLevel::None->value);
        $this->assertSame(1, CompressionLevel::Fastest->value);
        $this->assertSame(3, CompressionLevel::Fast->value);
        $this->assertSame(5, CompressionLevel::Normal->value);
        $this->assertSame(7, CompressionLevel::Good->value);
        $this->assertSame(9, CompressionLevel::Best->value);
    }

    #[Test]
    public function it_can_be_created_from_value(): void
    {
        $this->assertSame(CompressionLevel::Normal, CompressionLevel::from(5));
        $this->assertSame(CompressionLevel::Best, CompressionLevel::from(9));
    }

    #[Test]
    public function it_returns_null_for_invalid_value(): void
    {
        $result4 = CompressionLevel::tryFrom(4);
        $resultNeg = CompressionLevel::tryFrom(-1);

        $this->assertNull($result4);
        $this->assertNull($resultNeg);
    }

    #[Test]
    public function it_has_six_levels(): void
    {
        $this->assertCount(6, CompressionLevel::cases());
    }
}
