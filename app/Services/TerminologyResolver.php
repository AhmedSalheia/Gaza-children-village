<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\App;

/**
 * Resolves institution-type-specific terminology for the current locale.
 *
 * Centralises label lookups that differ by institution type, locale, and
 * position definition. Falls back gracefully from type-specific → generic
 * → English → raw key.
 */
final class TerminologyResolver
{
    /**
     * Resolve a terminology key for a given institution type and locale.
     *
     * @param  string  $key  Dot-notation key within institutions.php
     * @param  string|null  $type  Institution type slug
     * @param  string|null  $locale  Override locale; null uses current App locale
     */
    public function term(string $key, ?string $type = null, ?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();

        // Try type-specific key first (e.g. positions.principal.school)
        if ($type !== null) {
            $typedKey = $key.'.'.$type;
            /** @var string|array<string> $typedTrans */
            $typedTrans = trans('institutions.'.$typedKey, [], $locale);
            if (is_string($typedTrans) && $typedTrans !== 'institutions.'.$typedKey) {
                return $typedTrans;
            }
        }

        // Generic key
        /** @var string|array<string> $genericTrans */
        $genericTrans = trans('institutions.'.$key, [], $locale);
        if (is_string($genericTrans) && $genericTrans !== 'institutions.'.$key) {
            return $genericTrans;
        }

        // Fallback: English
        if ($locale !== 'en') {
            /** @var string|array<string> $enTrans */
            $enTrans = trans('institutions.'.$key, [], 'en');
            if (is_string($enTrans) && $enTrans !== 'institutions.'.$key) {
                return $enTrans;
            }
        }

        return $key;
    }

    /**
     * Resolve the display label for an institution type.
     */
    public function institutionTypeLabel(string $type, ?string $locale = null): string
    {
        return $this->term('types.'.$type, null, $locale);
    }

    /**
     * Resolve the display label for a position definition.
     */
    public function positionLabel(string $positionDefinitionValue, ?string $locale = null): string
    {
        return $this->term('positions.'.$positionDefinitionValue, null, $locale);
    }
}
