@props([
    'wireMethod' => null,
    'wireParam' => null,
    'title' => '',
    'text' => null,
    'confirmText' => null,
    'cancelText' => null,
    'tone' => 'default',
    'icon' => null,
    'confirmColor' => null,
])

<x-k16.confirm-button
    :wire-method="$wireMethod"
    :wire-param="$wireParam"
    :title="$title"
    :text="$text"
    :confirm-text="$confirmText"
    :cancel-text="$cancelText"
    :tone="$tone === 'default' && $icon === 'warning' ? 'danger' : ($icon === 'question' ? 'success' : $tone)"
    {{ $attributes }}
>
    {{ $slot }}
</x-k16.confirm-button>
