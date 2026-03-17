<?php

declare(strict_types=1);

namespace Temant\Archiver\Enum;

enum CompressionLevel: int
{
    case None = 0;
    case Fastest = 1;
    case Fast = 3;
    case Normal = 5;
    case Good = 7;
    case Best = 9;
}
