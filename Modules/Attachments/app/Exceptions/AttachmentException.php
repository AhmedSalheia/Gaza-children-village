<?php

declare(strict_types=1);

namespace Modules\Attachments\Exceptions;

use RuntimeException;

/**
 * Domain exception for attachment validation and storage failures.
 *
 * Thrown by SecureAttachmentService when an upload does not meet the
 * security requirements (size, MIME type, extension, duplicate, etc.).
 *
 * Callers should catch this exception and surface a user-visible error
 * message; it MUST NOT propagate as an unhandled 500.
 */
final class AttachmentException extends RuntimeException {}
