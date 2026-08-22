<?php

function monthly_asset_folder(string $type, ?DateTimeInterface $date = null): string
{
    $date = $date ?: new DateTimeImmutable('now');
    $safeType = preg_replace('/[^a-z0-9_-]/i', '', strtolower($type)) ?: 'misc';
    return $safeType . '/' . $date->format('Y') . '/' . $date->format('m');
}

function build_monthly_asset_path(string $type, string $filename, ?DateTimeInterface $date = null): string
{
    $basename = basename($filename);
    return monthly_asset_folder($type, $date) . '/' . $basename;
}

function to_public_asset_path(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return '';
    }

    if (str_starts_with($value, '/files/')) {
        return $value;
    }

    if ($value[0] === '/') {
        return '/files' . $value;
    }

    return '/files/' . ltrim($value, '/');
}

?>
