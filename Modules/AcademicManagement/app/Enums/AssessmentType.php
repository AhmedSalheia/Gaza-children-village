<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

enum AssessmentType: string
{
    case Classwork = 'classwork';
    case Homework = 'homework';
    case Quiz = 'quiz';
    case Project = 'project';
    case Midterm = 'midterm';
    case Final = 'final';
    case Participation = 'participation';
    case Other = 'other';

    public function labelAr(): string
    {
        return match ($this) {
            self::Classwork => 'عمل صفي',
            self::Homework => 'واجب منزلي',
            self::Quiz => 'اختبار قصير',
            self::Project => 'مشروع',
            self::Midterm => 'اختبار منتصف الفصل',
            self::Final => 'اختبار نهائي',
            self::Participation => 'مشاركة',
            self::Other => 'أخرى',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Classwork => 'Classwork',
            self::Homework => 'Homework',
            self::Quiz => 'Quiz',
            self::Project => 'Project',
            self::Midterm => 'Midterm',
            self::Final => 'Final',
            self::Participation => 'Participation',
            self::Other => 'Other',
        };
    }

    /** @return list<array{value: string, labelAr: string, labelEn: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $t) => ['value' => $t->value, 'labelAr' => $t->labelAr(), 'labelEn' => $t->labelEn()],
            self::cases()
        );
    }
}
