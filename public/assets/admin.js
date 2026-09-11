(() => {
    const body = document.body;
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close], [data-sidebar-overlay]');

    const closeSidebar = () => {
        body.classList.remove('sidebar-visible');
        openButton?.setAttribute('aria-expanded', 'false');
    };

    openButton?.addEventListener('click', () => {
        body.classList.add('sidebar-visible');
        openButton.setAttribute('aria-expanded', 'true');
    });
    closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        button.addEventListener('click', () => {
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!showing));
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    });

    document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-flash-message]')?.remove());
    });

    document.querySelectorAll('[data-language-tabs]').forEach((tabGroup) => {
        const tabs = [...tabGroup.querySelectorAll('[data-language-tab]')];
        const panels = [...tabGroup.querySelectorAll('[data-language-panel]')];

        const activate = (locale, focus = false) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.languageTab === locale;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                if (active && focus) tab.focus();
            });
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.languagePanel !== locale;
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab.dataset.languageTab));
            tab.addEventListener('keydown', (event) => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                let next = index;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                activate(tabs[next].dataset.languageTab, true);
            });
        });

        const firstError = tabs.find((tab) => tab.dataset.hasErrors === 'true');
        if (firstError) activate(firstError.dataset.languageTab);
    });

    document.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirmDelete || 'Delete this item?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-image-upload]').forEach((upload) => {
        const input = upload.querySelector('[data-image-input]');
        const preview = upload.querySelector('[data-image-preview]');
        const picker = upload.querySelector('.image-upload-picker');

        const showFile = (file) => {
            if (!file?.type?.startsWith('image/')) return;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                preview.classList.remove('image-upload-preview--empty');
                preview.innerHTML = '';
                const image = document.createElement('img');
                image.src = reader.result;
                image.alt = 'Selected image preview';
                const label = document.createElement('span');
                label.textContent = file.name;
                preview.append(image, label);
            });
            reader.readAsDataURL(file);
        };

        input?.addEventListener('change', () => showFile(input.files?.[0]));
        ['dragenter', 'dragover'].forEach((name) => picker?.addEventListener(name, (event) => {
            event.preventDefault();
            picker.classList.add('is-dragging');
        }));
        ['dragleave', 'drop'].forEach((name) => picker?.addEventListener(name, (event) => {
            event.preventDefault();
            picker.classList.remove('is-dragging');
        }));
        picker?.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file || !input || typeof DataTransfer === 'undefined') return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            showFile(file);
        });
    });

    const slugify = (value) => value.toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const englishTitle = document.getElementById('translations-en-title');
    const slugInput = document.querySelector('[data-slug-input]');
    let slugWasEdited = Boolean(slugInput?.value);
    slugInput?.addEventListener('input', () => { slugWasEdited = Boolean(slugInput.value); });
    englishTitle?.addEventListener('input', () => {
        if (slugInput && !slugWasEdited) slugInput.value = slugify(englishTitle.value);
    });

    document.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
        const row = toggle.closest('[data-section-row]');
        const label = row?.querySelector('[data-section-status-label]');
        const status = row?.querySelector('[data-section-status]');
        const update = () => {
            row?.classList.toggle('is-active', toggle.checked);
            row?.classList.toggle('is-hidden', !toggle.checked);
            status?.classList.toggle('badge--published', toggle.checked);
            status?.classList.toggle('badge--draft', !toggle.checked);
            if (label) label.textContent = toggle.checked ? 'Visible' : 'Hidden';
        };
        toggle.addEventListener('change', update);
        update();
    });

    document.querySelectorAll('[data-sortable-sections]').forEach((list) => {
        let dragged;
        const rows = () => [...list.querySelectorAll('[data-section-row]')];

        rows().forEach((row) => {
            row.draggable = true;
            row.addEventListener('dragstart', () => {
                dragged = row;
                row.style.opacity = '.45';
            });
            row.addEventListener('dragend', () => {
                row.style.opacity = '';
                dragged = null;
                rows().forEach((currentRow, index) => {
                    const order = currentRow.querySelector('[data-section-order]');
                    const number = currentRow.querySelector('.section-order-number');
                    if (order) order.value = (index + 1) * 10;
                    if (number) number.textContent = String(index + 1).padStart(2, '0');
                });
            });
            row.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!dragged || dragged === row) return;
                const bounds = row.getBoundingClientRect();
                list.insertBefore(dragged, event.clientY < bounds.top + bounds.height / 2 ? row : row.nextSibling);
            });
        });
    });

    document.querySelectorAll('form:not([data-confirm-delete])').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.style.opacity = '.7';
            }
        });
    });

    document.querySelector('[data-error-summary]')?.focus();
})();
