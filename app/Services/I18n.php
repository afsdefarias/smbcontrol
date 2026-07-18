<?php

function smb_lang(): string {
    return $_SESSION['lang'] ?? 'en';
}

function smb_t(string $english, string $portuguese): string {
    return smb_lang() === 'pt' ? $portuguese : $english;
}

function smb_lang_toggle_url(): string {
    $target = smb_lang() === 'pt' ? 'en' : 'pt';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $path . '?lang=' . $target;
}

function smb_lang_label(): string {
    return smb_lang() === 'pt' ? 'EN' : 'PT';
}
