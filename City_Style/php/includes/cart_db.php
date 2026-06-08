<?php

declare(strict_types=1);

function cart_db_enabled(PDO $pdo): bool
{
    return table_exists($pdo, 'STYLE_korzina');
}

/** @return array<int, int> */
function cart_db_load(PDO $pdo, int $klientId): array
{
    if (!cart_db_enabled($pdo)) {
        return [];
    }
    $st = $pdo->prepare('SELECT id_tovar, kolichestvo FROM STYLE_korzina WHERE id_klient = ?');
    $st->execute([$klientId]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $id = (int) ($row['id_tovar'] ?? 0);
        $qty = (int) ($row['kolichestvo'] ?? 0);
        if ($id > 0 && $qty > 0) {
            $out[$id] = $qty;
        }
    }

    return $out;
}

function cart_db_sync_all(PDO $pdo, int $klientId, array $items): void
{
    if (!cart_db_enabled($pdo) || $klientId <= 0) {
        return;
    }
    $pdo->prepare('DELETE FROM STYLE_korzina WHERE id_klient = ?')->execute([$klientId]);
    if ($items === []) {
        return;
    }
    $ins = $pdo->prepare('INSERT INTO STYLE_korzina (id_klient, id_tovar, kolichestvo) VALUES (?, ?, ?)');
    foreach ($items as $id => $qty) {
        $id = (int) $id;
        $qty = (int) $qty;
        if ($id > 0 && $qty > 0) {
            $ins->execute([$klientId, $id, $qty]);
        }
    }
}

function cart_db_set_line(PDO $pdo, int $klientId, int $idTovar, int $qty): void
{
    if (!cart_db_enabled($pdo) || $klientId <= 0) {
        return;
    }
    if ($qty <= 0) {
        $pdo->prepare('DELETE FROM STYLE_korzina WHERE id_klient = ? AND id_tovar = ?')
            ->execute([$klientId, $idTovar]);

        return;
    }
    $pdo->prepare(
        'INSERT INTO STYLE_korzina (id_klient, id_tovar, kolichestvo) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE kolichestvo = VALUES(kolichestvo)'
    )->execute([$klientId, $idTovar, $qty]);
}

function cart_client_id(): int
{
    if (!is_client()) {
        return 0;
    }

    return (int) (current_user()['id'] ?? 0);
}

function cart_persist_line(int $idTovar, int $qty): void
{
    $kid = cart_client_id();
    if ($kid <= 0) {
        return;
    }
    cart_db_set_line(db(), $kid, $idTovar, $qty);
}

function cart_persist_all(): void
{
    $kid = cart_client_id();
    if ($kid <= 0) {
        return;
    }
    cart_db_sync_all(db(), $kid, cart_items());
}

function cart_merge_on_login(int $klientId): void
{
    if ($klientId <= 0) {
        return;
    }
    $pdo = db();
    cart_init();
    $fromDb = cart_db_load($pdo, $klientId);
    foreach ($fromDb as $id => $qty) {
        $prev = (int) ($_SESSION['cart'][$id] ?? 0);
        $_SESSION['cart'][$id] = $prev + $qty;
    }
    cart_db_sync_all($pdo, $klientId, cart_items());
}

function cart_clear_client(int $klientId): void
{
    cart_init();
    $_SESSION['cart'] = [];
    if ($klientId > 0) {
        cart_db_sync_all(db(), $klientId, []);
    }
}

function cart_hydrate_from_db(): void
{
    $kid = cart_client_id();
    if ($kid <= 0) {
        return;
    }
    $loadedFor = (int) ($_SESSION['_cart_db_klient'] ?? 0);
    if ($loadedFor === $kid) {
        return;
    }
    cart_init();
    $_SESSION['cart'] = cart_db_load(db(), $kid);
    $_SESSION['_cart_db_klient'] = $kid;
}
