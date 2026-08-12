<?php

declare(strict_types=1);

function modulePhpFiles(): array
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path('Modules'), FilesystemIterator::SKIP_DOTS),
    );

    return collect(iterator_to_array($iterator))
        ->filter(fn (SplFileInfo $file) => $file->isFile() && $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file) => $file->getPathname())
        ->values()
        ->all();
}

function moduleNameFromPath(string $path): string
{
    preg_match('~[\\\\/]Modules[\\\\/]([^\\\\/]+)[\\\\/]~', $path, $matches);

    return $matches[1] ?? '';
}
