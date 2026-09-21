(() => {
    'use strict';

    document.querySelectorAll('[data-registration-form]').forEach((form) => {
        const steps = [...form.querySelectorAll('[data-form-step]')];
        const progressLinks = [...document.querySelectorAll('[data-form-step-link]')];
        const progressBar = document.querySelector('[data-form-progress-bar]');
        const announcement = form.querySelector('[data-form-announcement]');
        const errorSummary = document.querySelector('[data-error-summary]');

        if (!steps.length) {
            return;
        }

        const requestedStep = Number.parseInt(form.dataset.initialStep || '0', 10);
        let currentStep = Number.isNaN(requestedStep) ? 0 : Math.min(Math.max(requestedStep, 0), steps.length - 1);
        let furthestStep = currentStep;

        form.classList.add('is-enhanced');
        form.noValidate = true;

        const controlsFor = (step) => [...step.querySelectorAll('input, select, textarea')]
            .filter((control) => !control.disabled);

        const showStep = (index, { focus = true } = {}) => {
            currentStep = Math.min(Math.max(index, 0), steps.length - 1);
            furthestStep = Math.max(furthestStep, currentStep);

            steps.forEach((step, stepIndex) => {
                const active = stepIndex === currentStep;
                step.hidden = !active;
                step.setAttribute('aria-hidden', String(!active));
            });

            progressLinks.forEach((link, linkIndex) => {
                if (linkIndex === currentStep) {
                    link.setAttribute('aria-current', 'step');
                } else {
                    link.removeAttribute('aria-current');
                }

                link.classList.toggle('is-complete', linkIndex < currentStep);
            });

            if (progressBar) {
                progressBar.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
            }

            const legend = steps[currentStep].querySelector('legend');
            const stepName = legend?.textContent.trim() || `Section ${currentStep + 1}`;

            if (announcement) {
                announcement.textContent = `${stepName}. Step ${currentStep + 1} of ${steps.length}.`;
            }

            if (focus && legend) {
                legend.setAttribute('tabindex', '-1');
                legend.focus({ preventScroll: true });
                form.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            }
        };

        const validateStep = (stepIndex) => {
            const invalidControl = controlsFor(steps[stepIndex]).find((control) => !control.checkValidity());

            if (!invalidControl) {
                return true;
            }

            showStep(stepIndex, { focus: false });
            invalidControl.reportValidity();
            invalidControl.focus({ preventScroll: false });

            if (announcement) {
                announcement.textContent = `Please complete ${invalidControl.labels?.[0]?.textContent.trim() || 'the highlighted field'} before continuing.`;
            }

            return false;
        };

        form.querySelectorAll('[data-step-next]').forEach((button) => {
            button.addEventListener('click', () => {
                if (validateStep(currentStep)) {
                    showStep(currentStep + 1);
                }
            });
        });

        form.querySelectorAll('[data-step-previous]').forEach((button) => {
            button.addEventListener('click', () => showStep(currentStep - 1));
        });

        progressLinks.forEach((link, targetStep) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();

                if (targetStep <= currentStep || targetStep <= furthestStep) {
                    showStep(targetStep);
                    return;
                }

                for (let stepIndex = currentStep; stepIndex < targetStep; stepIndex += 1) {
                    if (!validateStep(stepIndex)) {
                        return;
                    }
                }

                showStep(targetStep);
            });
        });

        const arrivalDate = form.querySelector('[data-arrival-date]');
        const departureDate = form.querySelector('[data-departure-date]');
        const passportExpiryDate = form.querySelector('[name="passport_expiry_date"]');
        const syncTravelDates = () => {
            if (!departureDate) {
                return;
            }

            departureDate.min = arrivalDate?.value || '';

            if (arrivalDate?.value && departureDate.value && departureDate.value < arrivalDate.value) {
                departureDate.setCustomValidity('Departure Date must be on or after Arrival Date.');
            } else {
                departureDate.setCustomValidity('');
            }

            if (passportExpiryDate?.value && departureDate.value && passportExpiryDate.value < departureDate.value) {
                passportExpiryDate.setCustomValidity('The passport must remain valid through the Departure Date.');
            } else {
                passportExpiryDate?.setCustomValidity('');
            }
        };

        arrivalDate?.addEventListener('change', syncTravelDates);
        departureDate?.addEventListener('change', syncTravelDates);
        passportExpiryDate?.addEventListener('change', syncTravelDates);
        syncTravelDates();

        const dietaryRequirements = form.querySelector('[data-dietary-requirements]');
        const otherDietaryNeeds = form.querySelector('[data-other-dietary-needs]');
        const dietaryRequiredMarker = form.querySelector('[data-dietary-required]');
        const syncDietaryRequirement = () => {
            const isOther = dietaryRequirements?.value === 'Other';

            if (otherDietaryNeeds) {
                otherDietaryNeeds.required = isOther;
                otherDietaryNeeds.setAttribute('aria-required', String(isOther));
            }

            if (dietaryRequiredMarker) {
                dietaryRequiredMarker.hidden = !isOther;
            }
        };

        dietaryRequirements?.addEventListener('change', syncDietaryRequirement);
        syncDietaryRequirement();

        form.addEventListener('submit', (event) => {
            syncTravelDates();
            const allControls = steps.flatMap(controlsFor);
            const invalidControl = allControls.find((control) => !control.checkValidity());

            if (invalidControl) {
                event.preventDefault();
                const invalidStep = steps.indexOf(invalidControl.closest('[data-form-step]'));
                showStep(Math.max(0, invalidStep), { focus: false });
                window.requestAnimationFrame(() => {
                    invalidControl.reportValidity();
                    invalidControl.focus();
                });
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            submitButton?.setAttribute('aria-disabled', 'true');
            if (submitButton) {
                submitButton.disabled = true;
            }
            form.setAttribute('aria-busy', 'true');
        });

        showStep(currentStep, { focus: false });

        if (errorSummary) {
            window.requestAnimationFrame(() => errorSummary.focus());
        }
    });

    const confirmationDialog = document.querySelector('[data-registration-dialog]');
    const confirmationOpenButton = document.querySelector('[data-confirmation-open]');

    if (confirmationDialog) {
        const canShowModal = typeof confirmationDialog.showModal === 'function';

        const openConfirmation = () => {
            if (!canShowModal) {
                confirmationDialog.setAttribute('open', '');
                return;
            }

            if (confirmationDialog.open) {
                confirmationDialog.close();
            }

            confirmationDialog.showModal();
        };

        confirmationOpenButton?.addEventListener('click', openConfirmation);
        confirmationDialog.querySelector('[data-confirmation-close]')?.addEventListener('click', (event) => {
            if (canShowModal) {
                event.preventDefault();
                confirmationDialog.close();
            }
        });
        confirmationDialog.addEventListener('click', (event) => {
            if (event.target === confirmationDialog && canShowModal) {
                confirmationDialog.close();
            }
        });
        confirmationDialog.addEventListener('close', () => confirmationOpenButton?.focus());

        window.requestAnimationFrame(openConfirmation);
    }

    document.querySelectorAll('[data-print-registration]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
})();
