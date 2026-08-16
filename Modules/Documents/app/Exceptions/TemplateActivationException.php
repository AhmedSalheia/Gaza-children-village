<?php

declare(strict_types=1);

namespace Modules\Documents\Exceptions;

/**
 * Thrown when a template version cannot be activated.
 *
 * Common causes: version is already active or archived,
 * version is a draft with unknown placeholders,
 * or the actor lacks the template.activate permission.
 */
final class TemplateActivationException extends \RuntimeException {}
