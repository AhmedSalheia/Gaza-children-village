<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | The private disk used for all attachment storage. Must be configured in
    | config/filesystems.php. Never the 'public' disk.
    |
    */
    'disk' => env('ATTACHMENTS_DISK', 'attachments'),

    /*
    |--------------------------------------------------------------------------
    | Maximum upload size
    |--------------------------------------------------------------------------
    |
    | Maximum allowed file size in bytes. Default 10 MB.
    |
    */
    'max_size_bytes' => (int) env('ATTACHMENTS_MAX_SIZE_BYTES', 10 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME types per purpose
    |--------------------------------------------------------------------------
    |
    | Keys are purpose codes used when calling SecureAttachmentService::store().
    | The service validates MIME type detected by finfo (never trusts the client-
    | supplied Content-Type). Add new purposes here when new request types need
    | different format allowlists.
    |
    */
    'allowed_mime_types' => [
        // Evidence attached to guardian correction requests and formal institution requests
        'evidence' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed file extensions per purpose
    |--------------------------------------------------------------------------
    |
    | Extensions are cross-checked with detected MIME. A mismatch between
    | extension and MIME is treated as a rejected upload.
    |
    */
    'allowed_extensions' => [
        'evidence' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocked extensions (executables and scripts)
    |--------------------------------------------------------------------------
    |
    | Regardless of purpose or MIME, files with these extensions are always
    | rejected. Defense-in-depth against polyglot or disguised executables.
    |
    */
    'blocked_extensions' => [
        'exe', 'dll', 'bat', 'cmd', 'sh', 'bash', 'zsh', 'ps1', 'vbs', 'js',
        'mjs', 'ts', 'jsx', 'tsx', 'php', 'php3', 'php4', 'php5', 'phtml',
        'phar', 'asp', 'aspx', 'jsp', 'cfm', 'cgi', 'pl', 'py', 'rb', 'go',
        'html', 'htm', 'xhtml', 'svg', 'xml', 'xsl', 'xslt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Virus scanner binding
    |--------------------------------------------------------------------------
    |
    | When set, the container will resolve this class as the VirusScannerContract
    | implementation. When null, no scanning is performed and uploaded files are
    | detected → the record is persisted as 'rejected' (forensic trail) and the blob purged.
    |
    | Example: 'scanner' => \App\Services\ClamAvScanner::class
    |
    */
    'scanner' => null,
];
