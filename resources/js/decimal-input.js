const normalizeDecimalValue = (value) => value
    .replace(/\u00A0/g, '')
    .replace(/ /g, '')
    .replace(/,/g, '.');

const isDecimalInput = (element) => element instanceof HTMLInputElement
    && (element.inputMode === 'decimal' || element.dataset.decimalInput === 'true');

const applyDecimalNormalization = (input) => {
    const normalized = normalizeDecimalValue(input.value);

    if (normalized === input.value) {
        return;
    }

    const start = input.selectionStart ?? normalized.length;
    const end = input.selectionEnd ?? normalized.length;
    const commasBeforeStart = (input.value.slice(0, start).match(/,/g) ?? []).length;

    input.value = normalized;
    input.setSelectionRange(start + commasBeforeStart, end + commasBeforeStart);
    input.dispatchEvent(new Event('input', { bubbles: true }));
};

document.addEventListener('input', (event) => {
    if (! isDecimalInput(event.target)) {
        return;
    }

    applyDecimalNormalization(event.target);
}, true);

document.addEventListener('paste', (event) => {
    if (! isDecimalInput(event.target)) {
        return;
    }

    window.requestAnimationFrame(() => {
        applyDecimalNormalization(event.target);
    });
});

export {};
