<?php

declare(strict_types=1);

function staff_kind_from_dolzhnost_name(string $nazvanie): string
{
    $n = trim(mb_strtolower($nazvanie, 'UTF-8'));
    if (str_contains($n, 'руковод')) {
        return 'manager';
    }
    if (str_contains($n, 'курьер')) {
        return 'courier';
    }
    return 'employee';
}

/** Тип сотрудника из сессии: courier | employee | manager */
function staff_kind(): ?string
{
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'staff') {
        return null;
    }
    if (!empty($u['staff_kind'])) {
        return (string) $u['staff_kind'];
    }
    $d = (string) ($u['dolzhnost'] ?? '');
    return $d !== '' ? staff_kind_from_dolzhnost_name($d) : 'employee';
}

function require_staff(): void
{
    if (!is_staff()) {
        flash_set('Вход для сотрудников обязателен.', 'error');
        redirect('login.php?redirect=' . rawurlencode('staff/index.php'));
    }
}

/** @param list<string> $kinds courier|employee|manager */
function require_staff_kind(string ...$kinds): void
{
    require_staff();
    $k = staff_kind();
    if ($k === null || !in_array($k, $kinds, true)) {
        flash_set('Недостаточно прав для этого раздела.', 'error');
        redirect('staff/index.php');
    }
}
