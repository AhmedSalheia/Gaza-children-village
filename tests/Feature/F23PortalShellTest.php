<?php

declare(strict_types=1);

/**
 * F23 — Portal shell and component library tests.
 *
 * Verifies that Blade layout files, partials, and component CSS:
 *  - Declare correct html[lang] and html[dir] for Arabic (default) and English.
 *  - Include the locale switcher partial.
 *  - Have all three portal layouts (admin, staff, guardian).
 *  - Component CSS library covers required element types.
 *  - RTL and LTR look intentional (logical CSS properties used).
 *  - Skip link is present (WCAG 2.4.1).
 *  - CSRF token in meta (WCAG / security).
 */

// ---------------------------------------------------------------------------
// Blade layout structure
// ---------------------------------------------------------------------------

describe('portal Blade layouts', function (): void {

    it('admin layout has correct html[lang] dynamic attribute', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        expect($blade)->toContain("lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\"");
    });

    it('admin layout has html[dir] conditional on locale', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        expect($blade)->toContain("dir=\"{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}\"");
    });

    it('staff layout has correct html[lang] and html[dir]', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/staff.blade.php'));
        expect($blade)->toContain("lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\"");
        expect($blade)->toContain("dir=\"{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}\"");
    });

    it('guardian layout has correct html[lang] and html[dir]', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/guardian.blade.php'));
        expect($blade)->toContain("lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\"");
        expect($blade)->toContain("dir=\"{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}\"");
    });

    it('all three layouts include locale-switcher partial', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('locale-switcher');
        }
    });

    it('all three layouts include skip-link for WCAG 2.4.1', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('skip-link');
            expect($blade)->toContain('#main-content');
        }
    });

    it('all three layouts include CSRF meta tag', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('csrf-token');
            expect($blade)->toContain('csrf_token()');
        }
    });

    it('all three layouts include main#main-content with tabindex', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('id="main-content"');
            expect($blade)->toContain('tabindex="-1"');
        }
    });

    it('all three layouts include @vite directive', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('@vite');
            expect($blade)->toContain('resources/css/app.css');
        }
    });

    it('all layouts include GCV brand mark', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('brand-mark__name');
        }
    });

    it('all layouts include confirm-dialog partial', function (): void {
        foreach (['admin', 'staff', 'guardian'] as $portal) {
            $blade = file_get_contents(resource_path("views/layouts/{$portal}.blade.php"));
            expect($blade)->toContain('confirm-dialog');
        }
    });

});

// ---------------------------------------------------------------------------
// Locale switcher partial
// ---------------------------------------------------------------------------

describe('locale-switcher partial', function (): void {

    it('references both ar and en locale codes', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/locale-switcher.blade.php'));
        expect($blade)->toContain("'ar'");
        expect($blade)->toContain("'en'");
    });

    it('uses POST to the locale.switch route', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/locale-switcher.blade.php'));
        expect($blade)->toContain('locale.switch');
        expect($blade)->toContain('@csrf');
    });

    it('includes aria-pressed attribute for accessibility', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/locale-switcher.blade.php'));
        expect($blade)->toContain('aria-pressed');
    });

    it('sets dir attribute per locale on buttons', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/locale-switcher.blade.php'));
        expect($blade)->toContain('dir=');
    });

});

// ---------------------------------------------------------------------------
// Confirm dialog partial
// ---------------------------------------------------------------------------

describe('confirm-dialog partial', function (): void {

    it('uses native <dialog> element', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/confirm-dialog.blade.php'));
        expect($blade)->toContain('<dialog');
    });

    it('has aria-modal and aria-labelledby', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/confirm-dialog.blade.php'));
        expect($blade)->toContain('aria-modal="true"');
        expect($blade)->toContain('aria-labelledby');
    });

    it('has autofocus on cancel button (default safe action)', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/confirm-dialog.blade.php'));
        expect($blade)->toContain('autofocus');
    });

    it('handles keyboard Escape key', function (): void {
        $blade = file_get_contents(resource_path('views/layouts/partials/confirm-dialog.blade.php'));
        expect($blade)->toContain("'Escape'");
    });

});

// ---------------------------------------------------------------------------
// Component CSS library
// ---------------------------------------------------------------------------

describe('component CSS library', function (): void {

    it('defines button variants (primary, secondary, danger, ghost, outline)', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.btn--primary');
        expect($css)->toContain('.btn--secondary');
        expect($css)->toContain('.btn--danger');
        expect($css)->toContain('.btn--ghost');
        expect($css)->toContain('.btn--outline');
    });

    it('defines button size variants (sm, lg, full)', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.btn--sm');
        expect($css)->toContain('.btn--lg');
        expect($css)->toContain('.btn--full');
    });

    it('defines form control classes', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.form-group');
        expect($css)->toContain('.form-label');
        expect($css)->toContain('.form-control');
        expect($css)->toContain('.form-error');
        expect($css)->toContain('.form-hint');
    });

    it('defines alert variants', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.alert--success');
        expect($css)->toContain('.alert--danger');
        expect($css)->toContain('.alert--warning');
        expect($css)->toContain('.alert--info');
    });

    it('defines status badge variants matching AcademicStatus', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.badge--draft');
        expect($css)->toContain('.badge--open');
        expect($css)->toContain('.badge--closed');
        expect($css)->toContain('.badge--archived');
        expect($css)->toContain('.badge--pending');
        expect($css)->toContain('.badge--active');
    });

    it('defines data-table styles', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.data-table');
        expect($css)->toContain('.data-table-wrapper');
    });

    it('defines empty-state, error-state, loading-state components', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.empty-state');
        expect($css)->toContain('.error-state');
        expect($css)->toContain('.loading-state');
    });

    it('defines permission-denied state', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.permission-denied');
    });

    it('defines read-only indicator', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.read-only-indicator');
    });

    it('defines confirm-dialog CSS', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.confirm-dialog');
    });

    it('uses CSS logical properties for RTL/LTR support', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        // At least some logical property usage
        expect($css)->toContain('inline-size');
        expect($css)->toContain('block-size');
        expect($css)->toContain('padding-inline');
        expect($css)->toContain('margin-inline');
        expect($css)->toContain('inset-block');
    });

    it('defines focus-visible ring for WCAG 2.4.7', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain(':focus-visible');
        expect($css)->toContain('var(--focus-ring-color)');
    });

});

// ---------------------------------------------------------------------------
// Pagination partial existence
// ---------------------------------------------------------------------------

describe('CSS pagination component', function (): void {

    it('defines pagination CSS', function (): void {
        $css = file_get_contents(resource_path('css/app.css'));
        expect($css)->toContain('.pagination');
        expect($css)->toContain('.pagination__nav');
        expect($css)->toContain('.pagination__btn');
        expect($css)->toContain('.pagination__btn--active');
    });

});
