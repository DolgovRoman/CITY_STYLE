<?php

declare(strict_types=1);

function order_sostav_has_snapshot(PDO $pdo): bool
{
    return column_exists($pdo, 'STYLE_sostav_zakaza', 'nazvanie');
}

function order_sostav_id_tovar_nullable(PDO $pdo): bool
{
    static $cache = [];

    $key = 'STYLE_sostav_zakaza.id_tovar';
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    if (!table_exists($pdo, 'STYLE_sostav_zakaza')) {
        $cache[$key] = false;

        return false;
    }
    $st = $pdo->prepare(
        'SELECT IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $st->execute(['STYLE_sostav_zakaza', 'id_tovar']);
    $row = $st->fetch();
    $cache[$key] = $row && strtoupper((string) ($row['IS_NULLABLE'] ?? '')) === 'YES';

    return $cache[$key];
}

/** SQL: позиции заказа (название/цена сохраняются после удаления товара из каталога). */
function order_sostav_select_sql(PDO $pdo): string
{
    if (order_sostav_has_snapshot($pdo)) {
        return 'SELECT sz.kolichestvo, sz.id_tovar,
                COALESCE(sz.nazvanie, t.nazvanie) AS nazvanie,
                COALESCE(sz.tsena, t.tsena) AS tsena,
                COALESCE(sz.articul, t.articul) AS articul
             FROM STYLE_sostav_zakaza sz
             LEFT JOIN STYLE_tovary t ON sz.id_tovar = t.id_tovar
             WHERE sz.id_zakaz = ?';
    }

    return 'SELECT sz.kolichestvo, sz.id_tovar, t.nazvanie, t.tsena, t.articul
         FROM STYLE_sostav_zakaza sz
         LEFT JOIN STYLE_tovary t ON sz.id_tovar = t.id_tovar
         WHERE sz.id_zakaz = ?';
}

/** Перед удалением SKU — сохранить данные в строках заказов и отвязать id_tovar. */
function product_preserve_order_lines_before_delete(PDO $pdo, int $idTovar): void
{
    if ($idTovar <= 0 || !table_exists($pdo, 'STYLE_sostav_zakaza')) {
        return;
    }

    if (order_sostav_has_snapshot($pdo)) {
        $pdo->prepare(
            'UPDATE STYLE_sostav_zakaza sz
             INNER JOIN STYLE_tovary t ON sz.id_tovar = t.id_tovar
             SET sz.nazvanie = COALESCE(sz.nazvanie, t.nazvanie),
                 sz.tsena = COALESCE(sz.tsena, t.tsena),
                 sz.articul = COALESCE(sz.articul, t.articul)
             WHERE sz.id_tovar = ?'
        )->execute([$idTovar]);
    }

    if (order_sostav_id_tovar_nullable($pdo)) {
        $pdo->prepare('UPDATE STYLE_sostav_zakaza SET id_tovar = NULL WHERE id_tovar = ?')
            ->execute([$idTovar]);
    }
}

function product_insert_order_line(PDO $pdo, int $idZakaz, array $tovarRow, int $qty): void
{
    $tid = (int) ($tovarRow['id_tovar'] ?? 0);
    if (order_sostav_has_snapshot($pdo)) {
        $pdo->prepare(
            'INSERT INTO STYLE_sostav_zakaza (id_zakaz, id_tovar, kolichestvo, nazvanie, tsena, articul)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $idZakaz,
            $tid,
            $qty,
            (string) ($tovarRow['nazvanie'] ?? ''),
            (float) ($tovarRow['tsena'] ?? 0),
            ($tovarRow['articul'] ?? null) === null || (string) $tovarRow['articul'] === ''
                ? null
                : (string) $tovarRow['articul'],
        ]);

        return;
    }

    $pdo->prepare(
        'INSERT INTO STYLE_sostav_zakaza (id_zakaz, id_tovar, kolichestvo) VALUES (?, ?, ?)'
    )->execute([$idZakaz, $tid, $qty]);
}

/**
 * Удалить товар из каталога; заказы с этим SKU остаются для доставки.
 */
function product_delete_from_catalog(PDO $pdo, int $idTovar): bool
{
    if ($idTovar <= 0) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        product_preserve_order_lines_before_delete($pdo, $idTovar);

        if (table_exists($pdo, 'STYLE_korzina')) {
            $pdo->prepare('DELETE FROM STYLE_korzina WHERE id_tovar = ?')->execute([$idTovar]);
        }
        if (table_exists($pdo, 'STYLE_postupleniya')) {
            $pdo->prepare('DELETE FROM STYLE_postupleniya WHERE id_tovar = ?')->execute([$idTovar]);
        }
        if (table_exists($pdo, 'STYLE_sklad_ostatok')) {
            $pdo->prepare('DELETE FROM STYLE_sklad_ostatok WHERE id_tovar = ?')->execute([$idTovar]);
        }

        $del = $pdo->prepare('DELETE FROM STYLE_tovary WHERE id_tovar = ?');
        $del->execute([$idTovar]);
        if ($del->rowCount() < 1) {
            $pdo->rollBack();

            return false;
        }

        $pdo->commit();

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
