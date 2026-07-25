<?php

it('renders the language switcher component', function () {
    app()->setLocale('en');
    $html = view('full-translation::language-switcher')->render();
    expect($html)->toContain('language-switcher');
    expect($html)->toContain('español');
    expect($html)->toContain('Français');
    expect($html)->toContain('English');
});

it('marks current locale as active', function () {
    app()->setLocale('es');
    $html = view('full-translation::language-switcher')->render();
    expect($html)->toContain('aria-current="page"');
    expect($html)->toContain('<span class="active"');
});

it('does not link current locale', function () {
    app()->setLocale('en');
    $html = view('full-translation::language-switcher')->render();
    // Current locale should be a span, not a link
    expect($html)->toContain('<span class="active"');
    // Other locales should be links
    expect($html)->toContain('href=');
});
