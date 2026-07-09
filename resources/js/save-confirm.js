import Swal from 'sweetalert2';

const EXCLUDED_METHODS = new Set([
    'login',
    'register',
    'logout',
    'resetPassword',
    'sendPasswordResetLink',
    'confirmPassword',
    'deleteUser',
]);

function labels() {
    const read = (name, fallback) =>
        document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || fallback;

    return {
        title: read('confirm-save-title', 'Сохранить?'),
        text: read('confirm-save-text', 'Проверьте данные перед сохранением.'),
        confirm: read('confirm-save-confirm', 'Сохранить'),
        cancel: read('confirm-save-cancel', 'Отмена'),
    };
}

function shouldConfirm(method) {
    if (!method || EXCLUDED_METHODS.has(method)) {
        return false;
    }

    return /^(save|create|update)/i.test(method);
}

function parseWireCall(expression) {
    const trimmed = expression.trim();
    const match = trimmed.match(/^([^(]+)(?:\((.*)\))?$/s);

    if (!match) {
        return null;
    }

    const method = match[1].trim();
    const rawParams = match[2]?.trim();

    if (!rawParams) {
        return { method, params: [] };
    }

    const params = rawParams.split(',').map((part) => {
        const value = part.trim();

        if (value === '') {
            return null;
        }

        if (/^-?\d+(\.\d+)?$/.test(value)) {
            return Number(value);
        }

        if (
            (value.startsWith("'") && value.endsWith("'"))
            || (value.startsWith('"') && value.endsWith('"'))
        ) {
            return value.slice(1, -1);
        }

        return value;
    });

    return { method, params };
}

function livewireComponent(element) {
    const host = element?.closest('[wire\\:id]');

    if (!host || typeof window.Livewire === 'undefined') {
        return null;
    }

    return window.Livewire.find(host.getAttribute('wire:id'));
}

async function askToSave() {
    const { title, text, confirm, cancel } = labels();
    const result = await Swal.fire({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirm,
        cancelButtonText: cancel,
        reverseButtons: true,
        focusCancel: true,
        heightAuto: false,
    });

    return result.isConfirmed;
}

function wireSubmitMethod(form) {
    for (const attribute of form.attributes) {
        if (attribute.name.startsWith('wire:submit')) {
            return attribute.value;
        }
    }

    return null;
}

function isOptedOut(element) {
    return element?.closest('[data-no-confirm-save]') !== null
        || element?.dataset?.noConfirmSave === 'true';
}

document.addEventListener(
    'submit',
    async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (isOptedOut(form)) {
            return;
        }

        const method = wireSubmitMethod(form);

        if (!shouldConfirm(method)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (!await askToSave()) {
            return;
        }

        const component = livewireComponent(form);

        if (!component) {
            form.requestSubmit();

            return;
        }

        await component.call(method);
    },
    true,
);

document.addEventListener(
    'click',
    async (event) => {
        const button = event.target.closest('button[wire\\:click]');

        if (!button || isOptedOut(button)) {
            return;
        }

        const expression = button.getAttribute('wire:click');
        const parsed = parseWireCall(expression);

        if (!parsed || !shouldConfirm(parsed.method)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (!await askToSave()) {
            return;
        }

        const component = livewireComponent(button);

        if (!component) {
            return;
        }

        await component.call(parsed.method, ...parsed.params);
    },
    true,
);
