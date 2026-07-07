@props([
    'wireMethod',
    'wireParam' => null,
    'title',
    'text' => null,
    'confirmText' => null,
    'cancelText' => null,
    'icon' => 'warning',
    'confirmColor' => '#d97706',
])

<button
    type="button"
    {{ $attributes }}
    x-data
    @click="
        Swal.fire({
            title: @js($title),
            text: @js($text),
            icon: @js($icon),
            showCancelButton: true,
            confirmButtonText: @js($confirmText ?? __('Подтвердить')),
            cancelButtonText: @js($cancelText ?? __('Отмена')),
            confirmButtonColor: @js($confirmColor),
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.call(@js($wireMethod), @js($wireParam));
            }
        });
    "
>
    {{ $slot }}
</button>
