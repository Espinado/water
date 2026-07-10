@props(['appKey'])

@php
    $config = app(\App\Services\PwaContext::class)->appConfig($appKey);
    $pwaContext = app(\App\Services\PwaContext::class);
@endphp

<script>
    window.__PWA_APP__ = @json($appKey);
    window.__PWA_MANIFEST_ID__ = @json($pwaContext->manifestId($appKey));
    window.__PWA_MANIFEST_URL__ = @json($pwaContext->manifestUrl($appKey));
    window.__PWA_ORIGIN__ = @json($pwaContext->manifestOrigin($appKey));
    window.__PWA_THEME__ = @json($config['theme_color']);
    window.__PWA_SECURE__ = @json(request()->secure());
    window.__PWA_INSTALL_PAGE_URL__ = @json($pwaContext->installUrl($appKey));
    window.__PWA_PROMPT_INTERVAL_HOURS__ = @json((int) config('pwa.install_prompt_interval_hours', 72));
    window.__PWA_PROMPT_DELAY_MS__ = @json((int) config('pwa.install_prompt_delay_ms', 2500));
    window.__PWA_DEFERRED_PROMPT__ = window.__PWA_DEFERRED_PROMPT__ ?? null;
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__PWA_DEFERRED_PROMPT__ = event;
        window.dispatchEvent(new CustomEvent('pwa:install-ready'));
    });
    window.__PWA_LABELS__ = {
        preparing: @json(__('Подготовка…')),
        confirm: @json(__('Подтвердите установку в окне браузера')),
        installing: @json(__('Установка…')),
        done: @json(__('Приложение установлено')),
        cancelled: @json(__('Установка отменена')),
        openApp: @json(__('Открыть приложение')),
        retry: @json(__('Попробовать снова')),
        unavailable: @json(__('Автоустановка недоступна. Используйте меню браузера (иконка установки в адресной строке).')),
        needsHttps: @json(__('Для установки нужен HTTPS. Откройте :url', ['url' => 'https://'.request()->getHost()])),
        alreadyInstalled: @json(__('Приложение установлено')),
    installBarMessage: @json(__('Установите приложение — так входить удобнее')),
    install: @json(__('Установить')),
    dismiss: @json(__('Не сейчас')),
    installBarWaiting: @json(__('Подготовка установки…')),
    installConfirmTitle: @json(__('Установить приложение?')),
    installConfirmText: @json(__('Ярлык появится на рабочем столе или главном экране. Затем подтвердите установку в окне браузера.')),
    unavailableTitle: @json(__('Установка недоступна')),
    ok: @json(__('Понятно')),
    installBarOpenPage: @json(__('Подробная инструкция')),
    installIosBarMessage: @json(__('Добавьте приложение на главный экран')),
    installIosHint: @json(__('В Safari нажмите «Поделиться» внизу экрана, затем «На экран Домой».')),
    installManagerHint: @json(__('Это отдельное приложение «K16 — управляющий», не путайте с приложением жильца. В Chrome на этом сайте (manager.water.test) выберите в меню «Установить K16 — управляющий» или иконку установки в адресной строке.')),
};
</script>
