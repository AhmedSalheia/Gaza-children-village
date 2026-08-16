<?php

declare(strict_types=1);

namespace Modules\Documents\Exceptions;

/**
 * Thrown when a template body contains a placeholder key that is not in
 * the approved DocumentDataContext catalogue.
 *
 * This prevents template authors from embedding arbitrary expressions or
 * accessing data paths that are not explicitly whitelisted.
 */
final class UnknownPlaceholderException extends \InvalidArgumentException
{
    /**
     * @param  string[]  $unknownKeys  The unrecognised placeholder keys found in the template
     */
    public function __construct(
        public readonly array $unknownKeys,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        if ($message === '') {
            $keys = implode(', ', array_map(static fn ($k) => "{{ {$k} }}", $unknownKeys));
            $message = "Template contains unknown placeholder(s): {$keys}. Only catalogue-approved keys are permitted.";
        }

        parent::__construct($message, $code, $previous);
    }
}
