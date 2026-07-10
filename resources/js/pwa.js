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
    alreadyInstalled: 'Приложение установлено',
};

/** @type {BeforeInstallPromptEvent|null} */
let deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;

// Слушатель в <head> страницы установки ловит событие до загрузки Vite; дублируем на всякий случай.
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.__PWA_DEFERRED_PROMPT__ = event;
    window.dispatchEvent(new CustomEvent('pwa:install-ready'));
});

const installedStorageKey = () => {
    const app = window.__PWA_APP__;

    return app ? `pwa-installed-${app}` : null;
};

const markInstalled = () => {
    const key = installedStorageKey();

    if (key) {
        localStorage.setItem(key, '1');
    }
};

const isInstalledMarker = () => {
    const key = installedStorageKey();

    return key ? localStorage.getItem(key) === '1' : false;
};

const isRunningStandalone = () => window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true;

const hideInstallSection = () => {
    document.getElementById('pwa-install-section')?.classList.add('hidden');
    document.getElementById('pwa-already-installed')?.classList.remove('hidden');
};

const isInstalledRelatedApp = async () => {
    if (!('getInstalledRelatedApps' in navigator)) {
        return false;
    }

    try {
        const related = await navigator.getInstalledRelatedApps();
        const manifestLink = document.querySelector('link[rel="manifest"]');

        if (!manifestLink?.href) {
            return related.length > 0;
        }

        return related.some(
            (app) => app.platform === 'webapp' && app.url === manifestLink.href,
        );
    } catch {
        return false;
    }
};

const isAppAlreadyInstalled = async () => {
    if (isRunningStandalone() || isInstalledMarker()) {
        return true;
    }

    if (await isInstalledRelatedApp()) {
        markInstalled();

        return true;
    }

    return false;
};

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.__PWA_DEFERRED_PROMPT__ = null;
    markInstalled();
    hideInstallSection();
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

const setupInstallPrompt = async () => {
    const button = document.getElementById('pwa-install-button');
    const hint = document.getElementById('pwa-install-hint');
    const panel = document.getElementById('pwa-install-progress');
    const httpWarning = document.getElementById('pwa-http-warning');

    if (!button || !panel) {
        return;
    }

    if (await isAppAlreadyInstalled()) {
        hideInstallSection();

        return;
    }

    const isSecure = window.isSecureContext || window.__PWA_SECURE__ === true;

    if (!isSecure && httpWarning) {
        httpWarning.classList.remove('hidden');
    }

    button.dataset.defaultLabel = button.textContent;

    const revealInstallButton = () => {
        button.hidden = false;
        hint?.classList.remove('hidden');
    };

    const resetButton = () => {
        button.hidden = !deferredPrompt;
        button.disabled = false;
        button.textContent = button.dataset.defaultLabel ?? button.textContent;
    };

    const showUnavailable = (message) => {
        button.hidden = false;
        setProgress(panel, 'unavailable', 100, message);
        showProgressActions(
            panel,
            `<p class="text-xs leading-relaxed text-slate-600">${labels.unavailable}</p>`,
        );
    };

    const runInstall = async () => {
        if (!deferredPrompt) {
            deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;
        }

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
        window.__PWA_DEFERRED_PROMPT__ = null;

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
        hideInstallSection();
    });

    if (deferredPrompt) {
        revealInstallButton();
    } else if (isSecure) {
        window.setTimeout(() => {
            deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;
            if (deferredPrompt) {
                revealInstallButton();
            }
        }, 2500);
    }

    button.addEventListener('click', runInstall);
};

registerServiceWorker().finally(setupInstallPrompt);

export {};
