<?php

declare(strict_types=1);

/** Колонка телефона в STYLE_klienti. */
const CLIENT_PHONE_COLUMN = 'telefon';

/** Имя колонки телефона в STYLE_klienti (если есть в БД). */
function client_phone_column(PDO $pdo): ?string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached === '' ? null : $cached;
    }
    if (column_exists($pdo, 'STYLE_klienti', CLIENT_PHONE_COLUMN)) {
        $cached = CLIENT_PHONE_COLUMN;

        return CLIENT_PHONE_COLUMN;
    }
    foreach (['nomer_telefona', 'nomer_tel', 'phone', 'tel'] as $col) {
        if (column_exists($pdo, 'STYLE_klienti', $col)) {
            $cached = $col;

            return $col;
        }
    }
    $cached = '';

    return null;
}

function client_phone_enabled(PDO $pdo): bool
{
    return client_phone_column($pdo) !== null;
}

function client_phone_normalize(string $phone): string
{
    $d = preg_replace('/\D+/u', '', $phone);
    if (strlen($d) === 11 && $d[0] === '8') {
        $d = '7' . substr($d, 1);
    }

    return $d;
}

function client_phone_format_display(?string $phone): string
{
    if ($phone === null || trim($phone) === '') {
        return '—';
    }
    $d = client_phone_normalize($phone);
    if (strlen($d) === 11 && $d[0] === '7') {
        return '+7 (' . substr($d, 1, 3) . ') ' . substr($d, 4, 3) . '-' . substr($d, 7, 2) . '-' . substr($d, 9, 2);
    }
    if (strlen($d) === 10) {
        return '+7 (' . substr($d, 0, 3) . ') ' . substr($d, 3, 3) . '-' . substr($d, 6, 2) . '-' . substr($d, 8, 2);
    }

    return trim($phone);
}

/** @return string|null Ошибка или null */
function client_phone_validate(?string $phone, bool $required = false): ?string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return $required ? 'Укажите номер телефона.' : null;
    }
    $d = client_phone_normalize($phone);
    if ($d === '' || (strlen($d) !== 10 && strlen($d) !== 11)) {
        return 'Телефон: 10–11 цифр, можно с +7 или 8 в начале.';
    }

    return null;
}

/** @param array<string, mixed> $row */
function client_phone_from_row(array $row, PDO $pdo): string
{
    $col = client_phone_column($pdo);

    return $col !== null ? trim((string) ($row[$col] ?? '')) : '';
}

/**
 * Фрагмент SELECT для колонки телефона.
 *
 * @param string $alias Префикс таблицы (например `k` в JOIN). Пустая строка — без префикса.
 */
function client_phone_select_sql(PDO $pdo, string $alias = 'k'): string
{
    $col = client_phone_column($pdo);
    if ($col === null) {
        return '';
    }
    $prefix = $alias !== '' ? $alias . '.' : '';

    return ", {$prefix}{$col}";
}

function client_phone_html(?string $phone): string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '—';
    }
    $digits = client_phone_normalize($phone);
    $href = 'tel:+' . ($digits !== '' && strlen($digits) === 10 ? '7' . $digits : $digits);

    return '<a href="' . h($href) . '">' . h(client_phone_format_display($phone)) . '</a>';
}
