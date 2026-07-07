<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolveLocale($request));

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $supported = array_keys(config('locales.supported', []));

        $user = $request->user();

        // 1. Явно сохранённый язык профиля.
        if ($user instanceof User && $this->isSupported($user->locale, $supported)) {
            return $user->locale;
        }

        // 2. Выбор в сессии (для гостей и до сохранения в профиль).
        $sessionLocale = $request->session()->get('locale');
        if ($this->isSupported($sessionLocale, $supported)) {
            return $sessionLocale;
        }

        // 3. Управляющий без явного выбора — язык по умолчанию для админки.
        if ($user instanceof User && $user->isManager()) {
            return config('locales.manager_default', 'ru');
        }

        // 4. Жилец/гость — язык устройства (Accept-Language), иначе латышский.
        return $this->fromBrowser($request, $supported)
            ?? config('locales.resident_default', 'lv');
    }

    /**
     * Первый язык из Accept-Language (в порядке приоритета устройства),
     * который мы поддерживаем. null — если ни один не поддерживается.
     *
     * @param  list<string>  $supported
     */
    protected function fromBrowser(Request $request, array $supported): ?string
    {
        foreach ($request->getLanguages() as $language) {
            // Нормализуем ru_RU / lv-LV → ru / lv (берём основной субтег).
            $primary = strtolower(explode('_', str_replace('-', '_', $language))[0]);
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $supported
     */
    protected function isSupported(?string $locale, array $supported): bool
    {
        return $locale !== null && in_array($locale, $supported, true);
    }
}
