<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Support\Facades\DB;

/**
 * Runtime lookup service for the `document_type_catalogue` reference table.
 *
 * The catalogue is seeded by DocumentTypeSeeder and never mutated at runtime.
 * Results are cached in memory for the lifetime of the request.
 *
 * Public surface: this class is in the Services namespace (not public per
 * module conventions), so cross-module callers must use string-variable
 * resolution via app('Modules\\Documents\\Services\\DocumentTypeRegistry').
 */
final class DocumentTypeRegistry
{
    /** @var array<string, object>|null */
    private ?array $catalogue = null;

    /**
     * Look up a document type by its stable code.
     *
     * @throws \InvalidArgumentException When the code is not in the catalogue
     */
    public function get(string $code): object
    {
        $this->loadIfNeeded();

        if (! isset($this->catalogue[$code])) {
            throw new \InvalidArgumentException(
                "Document type '{$code}' is not registered in the catalogue."
            );
        }

        return $this->catalogue[$code];
    }

    /**
     * Return all registered document type entries ordered by display_order.
     *
     * @return object[]
     */
    public function all(): array
    {
        $this->loadIfNeeded();

        return array_values($this->catalogue ?? []);
    }

    public function exists(string $code): bool
    {
        $this->loadIfNeeded();

        return isset($this->catalogue[$code]);
    }

    /** @return string[] */
    public function codes(): array
    {
        $this->loadIfNeeded();

        return array_keys($this->catalogue ?? []);
    }

    private function loadIfNeeded(): void
    {
        if ($this->catalogue !== null) {
            return;
        }

        $rows = DB::table('document_type_catalogue')
            ->orderBy('display_order')
            ->get();

        $this->catalogue = [];

        foreach ($rows as $row) {
            // Decode JSON columns
            $row->required_context_keys = json_decode((string) $row->required_context_keys, true) ?? [];
            $row->completeness_checks = json_decode((string) ($row->completeness_checks ?? '[]'), true) ?? [];
            $row->allowed_requesters = json_decode((string) $row->allowed_requesters, true) ?? [];

            $this->catalogue[$row->code] = $row;
        }
    }

    /** Clear the in-memory cache (useful in tests). */
    public function flush(): void
    {
        $this->catalogue = null;
    }
}
