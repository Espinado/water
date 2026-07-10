const overlayId = 'app-page-loading';
const MIN_VISIBLE_MS = 600;
const SETTLE_MS = 400;
const MAX_WAIT_MS = 15000;

let shownAt = 0;
let hideToken = 0;
let pendingActions = 0;
let pendingUploads = 0;
let navigationPending = false;
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

const hasPendingWork = () => navigationPending || pendingActions > 0 || pendingUploads > 0;

const tryShow = () => {
    if (hasPendingWork()) {
        showPageLoading();
    }
};

const tryScheduleHide = () => {
    if (hasPendingWork()) {
        return;
    }

    scheduleHide();
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

const isBackgroundPollCommit = (commit) => {
    const calls = commit.calls ?? [];

    if (calls.length === 0) {
        return false;
    }

    return calls.every((call) => call.method === 'pollSubmissionUpdates');
};

const registerLivewireHooks = () => {
    if (livewireHooksReady || ! window.Livewire?.hook) {
        return;
    }

    livewireHooksReady = true;

    window.Livewire.hook('commit', ({ commit, succeed, fail }) => {
        if (! commit.calls?.length || isBackgroundPollCommit(commit)) {
            return;
        }

        pendingActions += 1;
        tryShow();

        const done = () => {
            pendingActions = Math.max(0, pendingActions - 1);
            tryScheduleHide();
        };

        succeed(done);
        fail(done);
    });
};

const waitForLivewireIdle = () => new Promise((resolve) => {
    const started = Date.now();

    const tick = () => {
        const elapsed = Date.now() - started;

        if (! hasPendingWork() && elapsed >= SETTLE_MS) {
            resolve();

            return;
        }

        if (elapsed >= MAX_WAIT_MS) {
            resolve();

            return;
        }

        setTimeout(tick, 50);
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
    navigationPending = true;
    hideToken += 1;
    showPageLoading();
});

document.addEventListener('livewire:navigated', () => {
    navigationPending = false;
    scheduleHide();
});

document.addEventListener('livewire:init', () => {
    registerLivewireHooks();
});

document.addEventListener('livewire-upload-start', () => {
    pendingUploads += 1;
    tryShow();
});

document.addEventListener('livewire-upload-finish', () => {
    pendingUploads = Math.max(0, pendingUploads - 1);
    tryScheduleHide();
});

document.addEventListener('livewire-upload-error', () => {
    pendingUploads = Math.max(0, pendingUploads - 1);
    tryScheduleHide();
});

bootInitialLoad();

export { hidePageLoading, showPageLoading };
