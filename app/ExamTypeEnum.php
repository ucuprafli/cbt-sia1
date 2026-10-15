<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ExamTypeEnum: string implements HasLabel, HasColor
{
    case Quiz = 'quiz';
    case Uts = 'uts';
    case Uas = 'uas';
    case Kelulusan = 'kelulusan';
    case Lainnya = 'lainnya';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Quiz => 'Quiz',
            self::Uts => 'UTS',
            self::Uas => 'UAS',
            self::Kelulusan => 'Kelulusan',
            self::Lainnya => 'Lainnya',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Quiz => 'info',
            self::Uts => 'warning',
            self::Uas => 'danger',
            self::Kelulusan => 'success',
            self::Lainnya => 'gray',
        };
    }
}
