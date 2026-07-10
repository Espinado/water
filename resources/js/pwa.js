import Swal from 'sweetalert2';

const labels = window.__PWA_LABELS__ ?? {
    preparing: 'Подготовка…',
    confirm: 'Подтвердите установку в окне браузера',
    installing: 'Установка…',
    done: 'Приложение установлено',
    cancelled: 'Установка отменена',
    openApp: 'Открыть приложение',
    retry: 'Попробовать снова',
    unavailable: 'Автоустановка недоступна. Используйте меню браузера или откройте сайт по HTTPS.',
    needsHttps: 'Для установки нужен HTTPS.',
    alreadyInstalled: 'Приложение установлено',
    installBarMessage: 'Установите приложение — так входить удобнее',
    install: 'Установить',
    dismiss: 'Не сейчас',
    installBarWaiting: 'Подготовка установки…',
    installConfirmTitle: 'Установить приложение?',
    installConfirmText: 'Ярлык появится на рабочем столе или главном экране. Затем подтвердите установку в окне браузера.',
    unavailableTitle: 'Установка недоступна',
    ok: 'Понятно',
    installBarOpenPage: 'Подробная инструкция',
    installIosBarMessage: 'Добавьте приложение на главный экран',
    installIosHint: 'В Safari нажмите «Поделиться» внизу экрана, затем «На экран Домой».',
    installManagerHint: 'Это отдельное приложение «K16 — управляющий», не путайте с приложением жильца. В Chrome на этом сайте выберите в меню «Установить K16 — управляющий» или иконку установки в адресной строке.',
};

const swalThemeColor = () => window.__PWA_THEME__ ?? '#059669';

const askToInstall = async () => {
    const result = await Swal.fire({
        title: labels.installConfirmTitle,
        text: labels.installConfirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: labels.install,
        cancelButtonText: labels.dismiss,
        confirmButtonColor: swalThemeColor(),
        reverseButtons: true,
        focusCancel: true,
        heightAuto: false,
    });

    return result.isConfirmed;
};

const showInstallAlert = async ({ title, text, icon = 'info' }) => {
    await Swal.fire({
        title,
        text,
        icon,
        confirmButtonText: labels.ok,
        confirmButtonColor: swalThemeColor(),
        heightAuto: false,
    });
};

const showInstallHelp = async (message) => {
    const installUrl = installPageUrl();
    const result = await Swal.fire({
        title: labels.unavailableTitle,
        text: message,
        icon: 'info',
        confirmButtonText: labels.ok,
        denyButtonText: labels.installBarOpenPage,
        showDenyButton: Boolean(installUrl),
        confirmButtonColor: swalThemeColor(),
        heightAuto: false,
    });

    if (result.isDenied && installUrl) {
        window.location.assign(installUrl);
    }
};

const showInstallUnavailable = async (message) => {
    await showInstallHelp(message);
};

const showInstallCancelled = async () => {
    await Swal.fire({
        title: labels.cancelled,
        icon: 'info',
        confirmButtonText: labels.ok,
        confirmButtonColor: swalThemeColor(),
        heightAuto: false,
    });
};

const showInstallSuccess = async () => {
    await Swal.fire({
        title: labels.done,
        icon: 'success',
        confirmButtonText: labels.ok,
        confirmButtonColor: swalThemeColor(),
        timer: 2500,
        timerProgressBar: true,
        heightAuto: false,
    });
};

/** @type {BeforeInstallPromptEvent|null} */
let deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;

const isInstallPage = () => document.getElementById('pwa-install-button') !== null;
const hasInstallBar = () => document.getElementById('pwa-install-bar') !== null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.__PWA_DEFERRED_PROMPT__ = event;
    window.dispatchEvent(new CustomEvent('pwa:install-ready'));
});

const appKey = () => window.__PWA_APP__ ?? null;

const isManagerApp = () => appKey() === 'manager';

const expectedAppOrigin = () => window.__PWA_ORIGIN__ ?? window.location.origin;

const storageKey = (suffix) => {
    const app = appKey();

    return app ? `pwa-${suffix}-${app}` : null;
};

const markInstalled = () => {
    const key = storageKey('installed');

    if (key) {
        localStorage.setItem(key, '1');
    }
};

const clearInstalledMarker = () => {
    const key = storageKey('installed');

    if (key) {
        localStorage.removeItem(key);
    }
};

const isInstalledMarker = () => {
    const key = storageKey('installed');

    return key ? localStorage.getItem(key) === '1' : false;
};

const snoozeKey = () => storageKey('install-snooze');

const markSnoozed = () => {
    const key = snoozeKey();

    if (key) {
        localStorage.setItem(key, String(Date.now()));
    }
};

const clearSnooze = () => {
    const key = snoozeKey();

    if (key) {
        localStorage.removeItem(key);
    }
};

const isSnoozeActive = () => {
    const key = snoozeKey();

    if (! key) {
        return false;
    }

    const snoozedAt = Number(localStorage.getItem(key));

    if (! Number.isFinite(snoozedAt) || snoozedAt <= 0) {
        return false;
    }

    const intervalHours = Number(window.__PWA_PROMPT_INTERVAL_HOURS__ ?? 72);
    const intervalMs = intervalHours * 60 * 60 * 1000;

    return Date.now() - snoozedAt < intervalMs;
};

const isRunningStandalone = () => window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true;

const isRunningCurrentAppStandalone = () => {
    if (! isRunningStandalone()) {
        return false;
    }

    return window.location.origin === expectedAppOrigin();
};

const hideInstallSection = () => {
    document.getElementById('pwa-install-section')?.classList.add('hidden');
    document.getElementById('pwa-already-installed')?.classList.remove('hidden');
};

const normalizeManifestUrl = (url) => {
    try {
        const parsed = new URL(url, window.location.origin);

        return `${parsed.origin}${parsed.pathname.replace(/\/$/, '')}`;
    } catch {
        return url;
    }
};

const manifestId = () => window.__PWA_MANIFEST_ID__ ?? null;

const manifestUrl = () => {
    const configured = window.__PWA_MANIFEST_URL__;

    if (configured) {
        return normalizeManifestUrl(configured);
    }

    const manifestLink = document.querySelector('link[rel="manifest"]');

    return manifestLink?.href ? normalizeManifestUrl(manifestLink.href) : null;
};

const matchesCurrentManifest = (app) => {
    if (! app || app.platform !== 'webapp') {
        return false;
    }

    const expectedId = manifestId();
    const expectedUrl = manifestUrl();

    if (expectedId && app.id) {
        if (app.id === expectedId) {
            return true;
        }

        if (app.id.endsWith(expectedId) || expectedId.endsWith(app.id)) {
            return true;
        }
    }

    if (expectedUrl && app.url && normalizeManifestUrl(app.url) === expectedUrl) {
        return true;
    }

    return false;
};

const isInstalledRelatedApp = async () => {
    if (! ('getInstalledRelatedApps' in navigator)) {
        return false;
    }

    try {
        const related = await navigator.getInstalledRelatedApps();
        const expectedId = manifestId();
        const expectedUrl = manifestUrl();

        if (! expectedId && ! expectedUrl) {
            return false;
        }

        return related.some(matchesCurrentManifest);
    } catch {
        return false;
    }
};

const isAppAlreadyInstalled = async () => {
    if (isRunningCurrentAppStandalone()) {
        markInstalled();

        return true;
    }

    if (isInstalledMarker()) {
        return true;
    }

    if (await isInstalledRelatedApp()) {
        markInstalled();

        return true;
    }

    return false;
};

const syncInstalledMarker = isAppAlreadyInstalled;

const hideInstallBar = () => {
    const bar = document.getElementById('pwa-install-bar');

    if (! bar) {
        return;
    }

    bar.hidden = true;
    bar.classList.add('translate-y-full');
    bar.classList.remove('translate-y-0');
    document.documentElement.style.removeProperty('--pwa-install-bar-height');
};

const showInstallBar = () => {
    const bar = document.getElementById('pwa-install-bar');

    if (! bar) {
        return;
    }

    bar.hidden = false;
    bar.classList.remove('translate-y-full');
    bar.classList.add('translate-y-0');

    window.requestAnimationFrame(() => {
        document.documentElement.style.setProperty(
            '--pwa-install-bar-height',
            `${bar.offsetHeight}px`,
        );
    });
};

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.__PWA_DEFERRED_PROMPT__ = null;
    markInstalled();
    hideInstallSection();
    hideInstallBar();
    window.dispatchEvent(new CustomEvent('pwa:installed'));
});

const registerServiceWorker = () => {
    if (! ('serviceWorker' in navigator)) {
        return Promise.resolve(null);
    }

    return navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => null);
};

const setProgress = (panel, step, percent, message) => {
    if (! panel) {
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
    if (! actions) {
        return;
    }

    actions.innerHTML = html;
    actions.classList.remove('hidden');
};

const runDeferredInstall = async ({ button, panel, onUnavailable }) => {
    if (! deferredPrompt) {
        deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;
    }

    const isSecure = window.isSecureContext || window.__PWA_SECURE__ === true;

    if (! deferredPrompt) {
        const message = isSecure ? labels.unavailable : labels.needsHttps;
        await showInstallUnavailable(message);
        onUnavailable?.(message);

        return false;
    }

    const defaultLabel = button?.dataset.defaultLabel ?? button?.textContent ?? labels.install;

    if (button) {
        button.disabled = true;
        button.textContent = labels.preparing;
    }

    if (panel) {
        setProgress(panel, 'preparing', 15, labels.preparing);
    }

    const promptEvent = deferredPrompt;
    deferredPrompt = null;
    window.__PWA_DEFERRED_PROMPT__ = null;

    try {
        if (panel) {
            setProgress(panel, 'confirm', 40, labels.confirm);
        }

        if (! await askToInstall()) {
            if (panel) {
                setProgress(panel, 'cancelled', 100, labels.cancelled);
            }

            if (button) {
                button.disabled = false;
                button.textContent = defaultLabel;
            }

            return false;
        }

        await promptEvent.prompt();

        if (panel) {
            setProgress(panel, 'installing', 70, labels.installing);
        }

        const choice = await promptEvent.userChoice;

        if (choice.outcome === 'accepted') {
            if (panel) {
                setProgress(panel, 'installing', 90, labels.installing);
            }

            await showInstallSuccess();

            return true;
        }

        await showInstallCancelled();

        if (panel) {
            setProgress(panel, 'cancelled', 100, labels.cancelled);
            showProgressActions(
                panel,
                `<button type="button" data-pwa-retry class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800">${labels.retry}</button>`,
            );
            panel.querySelector('[data-pwa-retry]')?.addEventListener('click', () => {
                panel.hidden = true;
                if (button) {
                    button.disabled = false;
                    button.textContent = defaultLabel;
                }
            });
        } else if (button) {
            button.disabled = false;
            button.textContent = defaultLabel;
        }

        return false;
    } catch {
        if (panel) {
            setProgress(panel, 'cancelled', 100, labels.cancelled);
        }

        if (button) {
            button.disabled = false;
            button.textContent = defaultLabel;
        }

        return false;
    }
};

const setupInstallPrompt = async () => {
    const button = document.getElementById('pwa-install-button');
    const hint = document.getElementById('pwa-install-hint');
    const panel = document.getElementById('pwa-install-progress');
    const httpWarning = document.getElementById('pwa-http-warning');

    if (! button || ! panel) {
        return;
    }

    if (await isAppAlreadyInstalled()) {
        hideInstallSection();

        return;
    }

    const isSecure = window.isSecureContext || window.__PWA_SECURE__ === true;

    if (! isSecure && httpWarning) {
        httpWarning.classList.remove('hidden');
    }

    button.dataset.defaultLabel = button.textContent;

    const revealInstallButton = () => {
        button.hidden = false;
        hint?.classList.remove('hidden');
    };

    const resetButton = () => {
        button.hidden = ! deferredPrompt;
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
        await runDeferredInstall({
            button,
            panel,
            onUnavailable: showUnavailable,
        });
    };

    window.addEventListener('pwa:install-ready', revealInstallButton);
    window.addEventListener('pwa:installed', hideInstallSection);

    if (deferredPrompt) {
        revealInstallButton();
    } else if (isSecure) {
        window.setTimeout(async () => {
            deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;

            if (deferredPrompt) {
                revealInstallButton();

                return;
            }

            if (! isIosDevice() && await isAppAlreadyInstalled()) {
                hideInstallSection();
            }
        }, 2500);
    }

    button.addEventListener('click', runInstall);
};

const isIosDevice = () => /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const waitForDeferredPrompt = (timeoutMs = 5000) => new Promise((resolve) => {
    const existing = window.__PWA_DEFERRED_PROMPT__ ?? deferredPrompt;

    if (existing) {
        deferredPrompt = existing;
        resolve(existing);

        return;
    }

    const timer = window.setTimeout(() => {
        window.removeEventListener('pwa:install-ready', onReady);
        resolve(null);
    }, timeoutMs);

    const onReady = () => {
        window.clearTimeout(timer);
        window.removeEventListener('pwa:install-ready', onReady);
        deferredPrompt = window.__PWA_DEFERRED_PROMPT__ ?? null;
        resolve(deferredPrompt);
    };

    window.addEventListener('pwa:install-ready', onReady);
});

const setInstallBarMessage = (message) => {
    const messageEl = document.getElementById('pwa-install-bar-message');

    if (messageEl) {
        messageEl.textContent = message;
    }
};

const resetInstallBarMessage = () => {
    setInstallBarMessage(labels.installBarMessage);
};

const canTriggerInstallPrompt = () => {
    if (isIosDevice()) {
        return false;
    }

    return window.isSecureContext || window.__PWA_SECURE__ === true;
};

const hasDeferredInstallPrompt = () => Boolean(window.__PWA_DEFERRED_PROMPT__ ?? deferredPrompt);

const installPageUrl = () => window.__PWA_INSTALL_PAGE_URL__ ?? null;

const shouldOfferInstallBar = async () => {
    if (! hasInstallBar() || isInstallPage()) {
        return false;
    }

    if (isRunningCurrentAppStandalone()) {
        return false;
    }

    if (await isAppAlreadyInstalled()) {
        return false;
    }

    if (isSnoozeActive()) {
        return false;
    }

    if (isManagerApp()) {
        return isIosDevice() || canTriggerInstallPrompt();
    }

    if (isIosDevice()) {
        return true;
    }

    if (! canTriggerInstallPrompt()) {
        return false;
    }

    return hasDeferredInstallPrompt();
};

const refreshInstallBar = async () => {
    if (await shouldOfferInstallBar()) {
        if (isIosDevice()) {
            setInstallBarMessage(labels.installIosBarMessage);
        } else {
            resetInstallBarMessage();
        }

        showInstallBar();

        return;
    }

    hideInstallBar();
};

const setupInstallBar = async () => {
    const bar = document.getElementById('pwa-install-bar');
    const button = document.getElementById('pwa-install-bar-button');
    const dismiss = document.getElementById('pwa-install-bar-dismiss');

    if (! bar || ! button || ! dismiss) {
        return;
    }

    button.dataset.defaultLabel = button.textContent;

    button.addEventListener('click', async () => {
        if (isIosDevice()) {
            await showInstallHelp(labels.installIosHint);

            return;
        }

        if (! canTriggerInstallPrompt()) {
            await showInstallHelp(labels.needsHttps);
            setInstallBarMessage(labels.needsHttps);
            button.disabled = true;

            return;
        }

        button.disabled = true;
        button.textContent = labels.installBarWaiting;
        await registerServiceWorker();
        await waitForDeferredPrompt(5000);

        if (! hasDeferredInstallPrompt()) {
            button.disabled = false;
            button.textContent = button.dataset.defaultLabel ?? labels.install;
            await showInstallHelp(isManagerApp() ? labels.installManagerHint : labels.unavailable);
            setInstallBarMessage(isManagerApp() ? labels.installManagerHint : labels.unavailable);

            return;
        }

        const installed = await runDeferredInstall({ button });

        if (installed) {
            return;
        }

        button.disabled = false;
        button.textContent = button.dataset.defaultLabel ?? labels.install;

        if (! hasDeferredInstallPrompt()) {
            await showInstallHelp(isManagerApp() ? labels.installManagerHint : labels.unavailable);
            setInstallBarMessage(isManagerApp() ? labels.installManagerHint : labels.unavailable);
        }
    });

    dismiss.addEventListener('click', () => {
        markSnoozed();
        resetInstallBarMessage();
        hideInstallBar();
    });

    window.addEventListener('pwa:install-ready', () => {
        resetInstallBarMessage();
        refreshInstallBar();
    });

    window.addEventListener('pwa:installed', hideInstallBar);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshInstallBar();
        }
    });

    const delay = Number(window.__PWA_PROMPT_DELAY_MS__ ?? 2500);
    window.setTimeout(refreshInstallBar, delay);
};

if (isInstallPage()) {
    registerServiceWorker().finally(setupInstallPrompt);
} else if (appKey()) {
    registerServiceWorker().finally(setupInstallBar);
} else {
    syncInstalledMarker();
}

export {};
