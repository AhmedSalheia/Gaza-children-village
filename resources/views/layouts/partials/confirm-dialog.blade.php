{{--
    F23 — Global confirmation dialog.
    Minimal accessible implementation (no JavaScript framework dependency).
    Triggered by adding data-confirm="..." and data-form-id="..." to any element.
--}}
<dialog id="confirm-dialog" class="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-modal="true">
    <div class="confirm-dialog__inner">
        <h2 id="confirm-dialog-title" class="confirm-dialog__title">
            {{ __('ui.are_you_sure') }}
        </h2>
        <p id="confirm-dialog-body" class="confirm-dialog__body">
            {{ __('ui.cannot_undone') }}
        </p>
        <div class="confirm-dialog__actions">
            <button
                type="button"
                id="confirm-dialog-cancel"
                class="btn btn--secondary"
                autofocus
            >{{ __('ui.cancel') }}</button>
            <button
                type="button"
                id="confirm-dialog-confirm"
                class="btn btn--danger"
            >{{ __('ui.confirm') }}</button>
        </div>
    </div>
</dialog>

<script>
(function () {
    'use strict';
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog) return;

    const cancelBtn  = document.getElementById('confirm-dialog-cancel');
    const confirmBtn = document.getElementById('confirm-dialog-confirm');
    const bodyEl     = document.getElementById('confirm-dialog-body');
    let pendingForm  = null;

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;

        e.preventDefault();
        const msg = trigger.dataset.confirm;
        if (bodyEl && msg) bodyEl.textContent = msg;

        // Find associated form
        const formId = trigger.dataset.formId;
        pendingForm = formId
            ? document.getElementById(formId)
            : trigger.closest('form');

        dialog.showModal();
        cancelBtn.focus();
    });

    cancelBtn.addEventListener('click', function () {
        dialog.close();
        pendingForm = null;
    });

    confirmBtn.addEventListener('click', function () {
        dialog.close();
        if (pendingForm) pendingForm.submit();
        pendingForm = null;
    });

    dialog.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dialog.close();
            pendingForm = null;
        }
    });
})();
</script>
