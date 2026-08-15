<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Stubs;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Informative placeholder for admin sections not yet implemented in this release.
 *
 * Shows a clear message about what's coming rather than a blank or 404 page.
 */
final class ComingSoonPage extends Component
{
    use HasAdminAuth;

    public string $section = '';

    public function mount(string $section = 'full_admin'): void
    {
        $this->section = $section;
    }

    public function render(): View
    {
        return view('livewire.admin.stubs.coming-soon')->layout('layouts.admin');
    }
}
