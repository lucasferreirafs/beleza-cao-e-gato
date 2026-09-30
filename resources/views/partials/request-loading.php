<dialog class="request-loading" data-request-loading aria-label="Solicitação em andamento" aria-modal="true">
    <div class="request-loading__trail" aria-hidden="true">
        <svg class="request-loading__symbols" xmlns="http://www.w3.org/2000/svg" focusable="false">
            <symbol id="request-loading-paw" viewBox="0 0 64 64">
                <ellipse cx="14" cy="20" rx="5" ry="7" transform="rotate(-24 14 20)" />
                <ellipse cx="26" cy="13" rx="5" ry="7" transform="rotate(-8 26 13)" />
                <ellipse cx="39" cy="13" rx="5" ry="7" transform="rotate(8 39 13)" />
                <ellipse cx="51" cy="20" rx="5" ry="7" transform="rotate(24 51 20)" />
                <path d="M32 30c-6 0-9 5-13 9-4 4-6 8-4 12 2 5 8 7 13 4 3-2 5-2 8 0 5 3 11 1 13-4 2-4 0-8-4-12-4-4-7-9-13-9Z" />
            </symbol>
        </svg>
        <?php for ($step = 1; $step <= 5; $step++): ?>
            <svg class="request-loading__step request-loading__step--<?= $step ?>" viewBox="0 0 64 64" focusable="false"><use href="#request-loading-paw" /></svg>
        <?php endfor; ?>
        <svg class="request-loading__center" viewBox="0 0 64 64" focusable="false"><use href="#request-loading-paw" /></svg>
    </div>
</dialog>
