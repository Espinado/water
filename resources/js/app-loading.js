const overlayId = 'app-page-loading';
const MIN_VISIBLE_MS = 600;
const SETTLE_MS = 800;
const MAX_WAIT_MS = 15000;

let shownAt = 0;
let hideToken = 0;
let pendingLivewireMessages = 0;
let livewireHooksReady = false;

const overlay = () => document.getElementById(overlayId);

const showPageLoading = () => {
    const el = overlay();
    if (! el) {
        return;
    }

    shownAt = Date.now();
    el.classList.remove('hidden');
    el.classList.add('flex');
};

const hidePageLoading = () => {
    const el = overlay();
    if (! el) {
        return;
    }

    el.classList.add('hidden');
    el.classList.remove('flex');
};

const nextPaint = () => new Promise((resolve) => {
    requestAnimationFrame(() => requestAnimationFrame(resolve));
});

const waitForFonts = async () => {
    try {
        await document.fonts?.ready;
    } catch {
        // ignore
    }
};

const waitMinVisible = async () => {
    const remaining = MIN_VISIBLE_MS - (Date.now() - shownAt);
    if (remaining > 0) {
        await new Promise((resolve) => setTimeout(resolve, remaining));
    }
};

const registerLivewireHooks = () => {
    if (livewireHooksReady || ! window.Livewire?.hook) {
        return;
    }

    livewireHooksReady = true;

    window.Livewire.hook('message.sent', () => {
        pendingLivewireMessages += 1;
    });

    window.Livewire.hook('message.processed', () => {
        pendingLivewireMessages = Math.max(0, pendingLivewireMessages - 1);
    });
};

const waitForLivewireIdle = () => new Promise((resolve) => {
    registerLivewireHooks();

    const started = Date.now();

    const tick = () => {
        const elapsed = Date.now() - started;

        if (pendingLivewireMessages === 0 && elapsed >= SETTLE_MS) {
            resolve();

            return;
        }

        if (elapsed >= MAX_WAIT_MS) {
            resolve();

            return;
        }

        setTimeout(tick, 100);
    };

    tick();
});

const waitUntilPageReady = async (token) => {
    await waitForFonts();
    await nextPaint();
    await waitMinVisible();

    if (token !== hideToken) {
        return;
    }

    await waitForLivewireIdle();

    if (token !== hideToken) {
        return;
    }

    await nextPaint();
    hidePageLoading();
};

const scheduleHide = () => {
    const token = ++hideToken;
    waitUntilPageReady(token);
};

const bootInitialLoad = async () => {
    if (! overlay()) {
        return;
    }

    shownAt = Date.now();

    await Promise.all([
        new Promise((resolve) => {
            if (document.readyState === 'complete') {
                resolve();

                return;
            }

            window.addEventListener('load', resolve, { once: true });
        }),
        new Promise((resolve) => {
            if (window.Livewire) {
                registerLivewireHooks();
                resolve();

                return;
            }

            document.addEventListener('livewire:init', () => {
                registerLivewireHooks();
                resolve();
            }, { once: true });
        }),
    ]);

    scheduleHide();
};

document.addEventListener('livewire:navigate', () => {
    hideToken += 1;
    showPageLoading();
});

document.addEventListener('livewire:navigated', scheduleHide);

bootInitialLoad();

export { hidePageLoading, showPageLoading };
