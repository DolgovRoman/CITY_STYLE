<?php

declare(strict_types=1);

function stock_enabled(PDO $pdo): bool
{
    return table_exists($pdo, 'STYLE_sklad_ostatok');
}

/** null — учёт остатков отключён (неограниченно). */
function stock_qty(PDO $pdo, int $idTovar): ?int
{
    if (!stock_enabled($pdo)) {
        return null;
    }
    $st = $pdo->prepare('SELECT kolichestvo FROM STYLE_sklad_ostatok WHERE id_tovar = ? LIMIT 1');
    $st->execute([$idTovar]);
    $row = $st->fetch();
    if (!$row) {
        return 0;
    }

    return max(0, (int) $row['kolichestvo']);
}

function stock_in_stock(PDO $pdo, int $idTovar): bool
{
    $q = stock_qty($pdo, $idTovar);

    return $q === null || $q > 0;
}

function stock_reserve(PDO $pdo, int $idTovar, int $qty): bool
{
    if ($qty <= 0) {
        return true;
    }
    if (!stock_enabled($pdo)) {
        return true;
    }
    $st = $pdo->prepare(
        'UPDATE STYLE_sklad_ostatok SET kolichestvo = kolichestvo - ?
         WHERE id_tovar = ? AND kolichestvo >= ?'
    );
    $st->execute([$qty, $idTovar, $qty]);

    return $st->rowCount() > 0;
}

function stock_ensure_row(PDO $pdo, int $idTovar): void
{
    if (!stock_enabled($pdo)) {
        return;
    }
    $pdo->prepare(
        'INSERT IGNORE INTO STYLE_sklad_ostatok (id_tovar, kolichestvo) VALUES (?, 0)'
    )->execute([$idTovar]);
}

function stock_cap_qty(PDO $pdo, int $idTovar, int $requested): int
{
    $avail = stock_qty($pdo, $idTovar);
    if ($avail === null) {
        return max(1, min(99, $requested));
    }
    if ($avail <= 0) {
        return 0;
    }

    return max(1, min(99, min($requested, $avail)));
}

/**
 * Поступление на склад (увеличение остатка).
 *
 * @return string|null Текст ошибки или null при успехе
 */
function stock_receive(PDO $pdo, int $idTovar, int $qty): ?string
{
    if ($qty <= 0) {
        return 'Укажите количество больше нуля.';
    }
    if (!stock_enabled($pdo)) {
        return null;
    }
    stock_ensure_row($pdo, $idTovar);
    $pdo->prepare(
        'UPDATE STYLE_sklad_ostatok SET kolichestvo = kolichestvo + ? WHERE id_tovar = ?'
    )->execute([$qty, $idTovar]);

    return null;
}

/**
 * Списание со склада (брак, возврат поставщику и т.п.).
 *
 * @return string|null Текст ошибки или null при успехе
 */
function stock_write_off(PDO $pdo, int $idTovar, int $qty): ?string
{
    if ($qty <= 0) {
        return 'Укажите количество больше нуля.';
    }
    if (!stock_enabled($pdo)) {
        return null;
    }
    $avail = stock_qty($pdo, $idTovar);
    if ($avail === null) {
        return null;
    }
    if ($avail < $qty) {
        return 'Недостаточно на складе. Доступно: ' . $avail . ' шт.';
    }
    if (!stock_reserve($pdo, $idTovar, $qty)) {
        return 'Не удалось списать остаток.';
    }

    return null;
}

/**
 * Вернуть на склад позиции отменённого заказа (резерв снимается при оформлении).
 *
 * @param string $previousStatus Статус заказа до отмены
 */
function stock_restore_order(PDO $pdo, int $idZakaz, string $previousStatus): void
{
    if (!stock_enabled($pdo) || $idZakaz <= 0) {
        return;
    }
    $prev = trim($previousStatus);
    // Совпадает с ORDER_STATUS_CANCELLED / ORDER_STATUS_DONE в delivery.php
    if ($prev === 'Отменён' || $prev === 'Завершен') {
        return;
    }

    $st = $pdo->prepare(
        'SELECT id_tovar, kolichestvo FROM STYLE_sostav_zakaza WHERE id_zakaz = ?'
    );
    $st->execute([$idZakaz]);
    foreach ($st->fetchAll() as $row) {
        $tid = (int) ($row['id_tovar'] ?? 0);
        $qty = (int) ($row['kolichestvo'] ?? 0);
        if ($tid > 0 && $qty > 0) {
            stock_receive($pdo, $tid, $qty);
        }
    }
}
