@php
    $locales = \Pikbdesigns\FullTranslation\Facades\FullLocalization::getAvailableLocales();
    $current = \Pikbdesigns\FullTranslation\Facades\FullLocalization::getLocale();
    $requestUrl = request()->getRequestUri();
@endphp

<div class="language-switcher">
    @foreach ($locales as $locale)
        @if ($locale['code'] !== $current)
            <a href="{{ \Pikbdesigns\FullTranslation\Facades\FullLocalization::getLocalizedUrl($locale['code'], $requestUrl, true) }}"
               hreflang="{{ $locale['code'] }}"
               title="{{ $locale['native'] ?? $locale['name'] }}">
                {{ $locale['native'] ?? strtoupper($locale['code']) }}
            </a>
        @else
            <span class="active" aria-current="page">
                {{ $locale['native'] ?? strtoupper($locale['code']) }}
            </span>
        @endif
    @endforeach
</div>