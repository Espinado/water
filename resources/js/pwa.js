const labels = window.__PWA_LABELS__ ?? {
    preparing: 'Подготовка…',
    confirm: 'Подтвердите установку в окне браузера',
    installing: 'Установка…',
    done: 'Приложение установлено',
    cancelled: 'Установка отменена',
    openApp: 'Открыть приложение',
    retry: 'Попробовать снова',
    unavailable: 'Автоустановка недоступна. Используйте меню браузера или откройте сайт по HTTPS.',
    needsHttps: 'Для установки нужен HTTPS. Откройте https://water.test/app/manager',
};

/** @type {BeforeInstallPromptEvent|null} */
let deferredPrompt = null;

// Слушатель нужно повесить сразу — событие может прийти до регистрации SW.
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa:install-ready'));
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa:installed'));
});

const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return Promise.resolve(null);
    }

    return navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => null);
};

const setProgress = (panel, step, percent, message) => {
    if (!panel) {
        return;
    }

    panel.hidden = false;
    panel.dataset.step = step;

    const bar = panel.querySelector('[data-pwa-progress-bar]');
    const text = panel.querySelector('[data-pwa-progress-text]');

    if (bar) {
        bar.style.width = `${percent}%`;
    }

    if (text) {
        text.textContent = message;
    }
};

const showProgressActions = (panel, html) => {
    const actions = panel?.querySelector('[data-pwa-progress-actions]');
    if (!actions) {
        return;
    }

    actions.innerHTML = html;
    actions.classList.remove('hidden');
};

const setupInstallPrompt = () => {
    const button = document.getElementById('pwa-install-button');
    const hint = document.getElementById('pwa-install-hint');
    const panel = document.getElementById('pwa-install-progress');
    const httpWarning = document.getElementById('pwa-http-warning');

    if (!button || !panel) {
        return;
    }

    const isSecure = window.isSecureContext || window.__PWA_SECURE__ === true;

    if (!isSecure && httpWarning) {
        httpWarning.classList.remove('hidden');
    }

    button.dataset.defaultLabel = button.textContent;

    // Кнопка всегда видна: при клике либо запуск установки, либо понятная подсказка.
    button.hidden = false;

    const revealInstallButton = () => {
        hint?.classList.remove('hidden');
    };

    const resetButton = () => {
        button.hidden = !deferredPrompt;
        button.disabled = false;
        button.textContent = button.dataset.defaultLabel ?? button.textContent;
    };

    const showUnavailable = (message) => {
        setProgress(panel, 'unavailable', 100, message);
        showProgressActions(
            panel,
            `<p class="text-xs leading-relaxed text-slate-600">${labels.unavailable}</p>`,
        );
    };

    const runInstall = async () => {
        if (!deferredPrompt) {
            const message = isSecure ? labels.unavailable : labels.needsHttps;
            showUnavailable(message);

            return;
        }

        button.disabled = true;
        button.textContent = labels.preparing;
        setProgress(panel, 'preparing', 15, labels.preparing);

        const promptEvent = deferredPrompt;
        deferredPrompt = null;

        try {
            setProgress(panel, 'confirm', 40, labels.confirm);
            await promptEvent.prompt();

            setProgress(panel, 'installing', 70, labels.installing);
            const choice = await promptEvent.userChoice;

            if (choice.outcome === 'accepted') {
                setProgress(panel, 'installing', 90, labels.installing);
            } else {
                setProgress(panel, 'cancelled', 100, labels.cancelled);
                button.hidden = true;
                showProgressActions(
                    panel,
                    `<button type="button" data-pwa-retry class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800">${labels.retry}</button>`,
                );
                panel.querySelector('[data-pwa-retry]')?.addEventListener('click', () => {
                    panel.hidden = true;
                    resetButton();
                });
            }
        } catch {
            setProgress(panel, 'cancelled', 100, labels.cancelled);
            resetButton();
        }
    };

    window.addEventListener('pwa:install-ready', revealInstallButton);
    window.addEventListener('pwa:installed', () => {
        setProgress(panel, 'done', 100, labels.done);
        button.hidden = true;

        const openUrl = window.__PWA_OPEN_URL__;
        if (openUrl) {
            showProgressActions(
                panel,
                `<a href="${openUrl}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl px-4 py-2 text-sm font-bold text-white" style="background-color: var(--pwa-theme)">${labels.openApp}</a>`,
            );
        }
    });

    if (deferredPrompt) {
        revealInstallButton();
    }

    button.addEventListener('click', runInstall);
};

registerServiceWorker().finally(setupInstallPrompt);

export {};
