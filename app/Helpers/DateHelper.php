<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class DateHelper
{
    public const DATE_DEFAULT = 'd.m.Y';

    public const DATE_TIME = 'H:i';

    public const DATE_DATETIME = 'd.m.Y H:i';

    public const DATE_DATETIME_SLASH = 'H:i / d.m.Y';

    public const DATE_LOCALE_DATETIME = 'D MMMM YYYY, HH:mm';

    public static function format(DateTimeInterface $date, string $format = self::DATE_DEFAULT): string
    {
        return $date->format($format);
    }

    /**
     * Format date using Carbon's locale-aware isoFormat.
     */
    public static function localeFormat(DateTimeInterface $date, string $format = self::DATE_LOCALE_DATETIME, ?string $locale = null): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::instance($date);

        if ($locale !== null) {
            $carbon = $carbon->locale($locale);
        }

        return $carbon->isoFormat($format);
    }
}
