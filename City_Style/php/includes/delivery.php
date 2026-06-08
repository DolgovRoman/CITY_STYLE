<?php

declare(strict_types=1);

const DELIVERY_TIP_COURIER = 'Курьер до адреса';
const DELIVERY_TIP_PVZ = 'Доставка в ПВЗ';
const DELIVERY_COURIER_CITY = 'Ярославль';

const ORDER_STATUS_V_PUTI = 'В пути';
const ORDER_STATUS_DONE = 'Завершен';
const ORDER_STATUS_CANCELLED = 'Отменён';
const DELIVERY_STATUS_DELIVERED = 'Доставлен';

/** @return list<string> */
function delivery_tip_options(): array
{
    return [DELIVERY_TIP_COURIER, DELIVERY_TIP_PVZ];
}

function delivery_tip_normalize(?string $tip): string
{
    $t = trim((string) $tip);
    if ($t === 'Самовывоз' || $t === 'самовывоз') {
        return DELIVERY_TIP_PVZ;
    }
    if ($t === 'Курьер') {
        return DELIVERY_TIP_COURIER;
    }

    return $t;
}

function delivery_tip_label(?string $tip): string
{
    return delivery_tip_normalize($tip);
}

function delivery_city_is_courier_zone(string $gorod): bool
{
    return mb_strtolower(trim($gorod), 'UTF-8') === mb_strtolower(DELIVERY_COURIER_CITY, 'UTF-8');
}

/** Ошибка валидации или null (если adres уже собран в строку). */
function delivery_validate_order(string $tip, string $gorod, string $adres): ?string
{
    $tip = delivery_tip_normalize($tip);
    $gorod = trim($gorod);
    $adres = trim($adres);

    if (!in_array($tip, delivery_tip_options(), true)) {
        return 'Выберите способ доставки.';
    }
    if ($gorod === '' || $adres === '') {
        return 'Укажите город и адрес (или пункт выдачи).';
    }

    if ($tip === DELIVERY_TIP_COURIER && !delivery_city_is_courier_zone($gorod)) {
        return 'Курьер до адреса доступен только в городе ' . DELIVERY_COURIER_CITY . '. Для других городов выберите «' . DELIVERY_TIP_PVZ . '».';
    }

    return null;
}

function delivery_table_exists(PDO $pdo): bool
{
    return table_exists($pdo, 'STYLE_dostavka');
}

function delivery_track_column_exists(PDO $pdo): bool
{
    return column_exists($pdo, 'STYLE_dostavka', 'trek_nomer');
}

/** Базовый статус доставки без суффикса с треком. */
function delivery_status_base(?string $status): string
{
    $s = trim((string) $status);
    if ($s === '') {
        return '';
    }
    $pos = mb_strpos($s, ';трек=', 0, 'UTF-8');
    if ($pos !== false) {
        return trim(mb_substr($s, 0, $pos, 'UTF-8'));
    }

    return $s;
}

/** Трек-номер из строки доставки (колонка trek_nomer или суффикс в status). */
function delivery_get_track(array $deliveryRow): ?string
{
    if (isset($deliveryRow['trek_nomer']) && trim((string) $deliveryRow['trek_nomer']) !== '') {
        return trim((string) $deliveryRow['trek_nomer']);
    }
    $s = (string) ($deliveryRow['status'] ?? '');
    if (preg_match('/;трек=([^;]+)/u', $s, $m)) {
        return trim($m[1]);
    }

    return null;
}

function delivery_status_with_track(string $baseStatus, ?string $track): string
{
    $base = delivery_status_base($baseStatus);
    $track = trim((string) $track);
    if ($track === '') {
        return $base;
    }

    return $base . ';трек=' . $track;
}

function delivery_order_is_pvz(?string $tip): bool
{
    return delivery_tip_normalize($tip) === DELIVERY_TIP_PVZ;
}

/** Заказ в ПВЗ: курьер передал в пункт, клиент ещё не забрал. */
function delivery_pvz_awaiting_client(array $orderRow, ?array $deliveryRow): bool
{
    if (!delivery_order_is_pvz((string) ($orderRow['tip_dostavki'] ?? ''))) {
        return false;
    }
    if ((string) ($orderRow['status'] ?? '') !== ORDER_STATUS_V_PUTI) {
        return false;
    }
    if ($deliveryRow === null) {
        return false;
    }

    return delivery_status_base((string) ($deliveryRow['status'] ?? '')) === DELIVERY_STATUS_DELIVERED;
}

/** Подпись статуса для клиента. */
function order_status_client_label(string $orderStatus, ?string $tip, ?array $deliveryRow): string
{
    if (delivery_pvz_awaiting_client(['tip_dostavki' => $tip, 'status' => $orderStatus], $deliveryRow)) {
        return ORDER_STATUS_V_PUTI . ' — ожидает получения в ПВЗ';
    }

    return $orderStatus;
}

/** @return list<array<string, mixed>> */
function fetch_couriers(PDO $pdo): array
{
    return $pdo->query(
        "SELECT s.id_sotrudnik, s.familiya, s.imya, s.email, d.nazvanie AS dolzhnost
         FROM STYLE_sotrudniki s
         INNER JOIN STYLE_dolzhnost d ON s.dolzhnost = d.id_dolzhnost
         WHERE LOWER(d.nazvanie) LIKE '%курьер%'
         ORDER BY s.familiya, s.imya"
    )->fetchAll();
}

/** @return array<string, mixed>|null */
function fetch_delivery_for_order(PDO $pdo, int $idZakaz): ?array
{
    if (!delivery_table_exists($pdo)) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT d.*, s.familiya AS kur_fam, s.imya AS kur_imya
         FROM STYLE_dostavka d
         LEFT JOIN STYLE_sotrudniki s ON d.id_sotrudnik_kurier = s.id_sotrudnik
         WHERE d.id_zakaz = ? LIMIT 1'
    );
    $st->execute([$idZakaz]);
    $row = $st->fetch();

    return $row ?: null;
}

function assign_courier_to_order(PDO $pdo, int $idZakaz, int $courierId): ?string
{
    if (!delivery_table_exists($pdo)) {
        return 'Таблица STYLE_dostavka не найдена в базе STYLE.';
    }

    $st = $pdo->prepare('SELECT status FROM STYLE_zakazy WHERE id_zakaz = ? LIMIT 1');
    $st->execute([$idZakaz]);
    $z = $st->fetch();
    if (!$z) {
        return 'Заказ не найден.';
    }
    $status = (string) ($z['status'] ?? '');
    if (in_array($status, [ORDER_STATUS_DONE, ORDER_STATUS_CANCELLED], true)) {
        return 'Нельзя назначить курьера для завершённого или отменённого заказа.';
    }

    $chk = $pdo->prepare(
        "SELECT s.id_sotrudnik FROM STYLE_sotrudniki s
         INNER JOIN STYLE_dolzhnost d ON s.dolzhnost = d.id_dolzhnost
         WHERE s.id_sotrudnik = ? AND LOWER(d.nazvanie) LIKE '%курьер%' LIMIT 1"
    );
    $chk->execute([$courierId]);
    if (!$chk->fetch()) {
        return 'Выбранный сотрудник не является курьером.';
    }

    $existing = fetch_delivery_for_order($pdo, $idZakaz);
    try {
        $pdo->beginTransaction();
        if ($existing) {
            $pdo->prepare(
                'UPDATE STYLE_dostavka SET id_sotrudnik_kurier = ?, status = ? WHERE id_zakaz = ?'
            )->execute([$courierId, ORDER_STATUS_V_PUTI, $idZakaz]);
        } else {
            $pdo->prepare(
                'INSERT INTO STYLE_dostavka (id_zakaz, id_sotrudnik_kurier, status) VALUES (?, ?, ?)'
            )->execute([$idZakaz, $courierId, ORDER_STATUS_V_PUTI]);
        }
        $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')
            ->execute([ORDER_STATUS_V_PUTI, $idZakaz]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return 'Не удалось назначить курьера.';
    }

    return null;
}

function courier_save_track(PDO $pdo, int $idDostavka, int $courierId, string $track): ?string
{
    if (!delivery_table_exists($pdo)) {
        return 'Таблица доставок недоступна.';
    }

    $track = trim($track);
    if ($track === '') {
        return 'Введите трек-номер.';
    }
    if (mb_strlen($track, 'UTF-8') > 64) {
        return 'Трек-номер слишком длинный (макс. 64 символа).';
    }

    $st = $pdo->prepare(
        'SELECT d.*, z.tip_dostavki, z.status AS status_zakaza
         FROM STYLE_dostavka d
         INNER JOIN STYLE_zakazy z ON z.id_zakaz = d.id_zakaz
         WHERE d.id_dostavka = ? AND d.id_sotrudnik_kurier = ? LIMIT 1'
    );
    $st->execute([$idDostavka, $courierId]);
    $row = $st->fetch();
    if (!$row) {
        return 'Доставка не найдена.';
    }

    if (!delivery_order_is_pvz((string) ($row['tip_dostavki'] ?? ''))) {
        return 'Трек-номер нужен только для доставки в ПВЗ.';
    }

    if ((string) ($row['status_zakaza'] ?? '') !== ORDER_STATUS_V_PUTI) {
        return 'Заказ не в доставке.';
    }

    $baseStatus = delivery_status_base((string) ($row['status'] ?? ORDER_STATUS_V_PUTI));

    try {
        if (delivery_track_column_exists($pdo)) {
            $pdo->prepare('UPDATE STYLE_dostavka SET trek_nomer = ? WHERE id_dostavka = ?')
                ->execute([$track, $idDostavka]);
        } else {
            $pdo->prepare('UPDATE STYLE_dostavka SET status = ? WHERE id_dostavka = ?')
                ->execute([delivery_status_with_track($baseStatus, $track), $idDostavka]);
        }
    } catch (Throwable $e) {
        return 'Не удалось сохранить трек-номер.';
    }

    return null;
}

function courier_mark_delivered(PDO $pdo, int $idDostavka, int $courierId): ?string
{
    if (!delivery_table_exists($pdo)) {
        return 'Таблица доставок недоступна.';
    }

    $st = $pdo->prepare(
        'SELECT d.*, z.status AS status_zakaza, z.tip_dostavki
         FROM STYLE_dostavka d
         INNER JOIN STYLE_zakazy z ON z.id_zakaz = d.id_zakaz
         WHERE d.id_dostavka = ? AND d.id_sotrudnik_kurier = ? LIMIT 1'
    );
    $st->execute([$idDostavka, $courierId]);
    $row = $st->fetch();
    if (!$row) {
        return 'Доставка не найдена.';
    }

    if ((string) ($row['status_zakaza'] ?? '') === ORDER_STATUS_DONE) {
        return 'Заказ уже завершён.';
    }

    if ((string) ($row['status_zakaza'] ?? '') !== ORDER_STATUS_V_PUTI) {
        return 'Заказ ещё не в статусе «' . ORDER_STATUS_V_PUTI . '».';
    }

    $isPvz = delivery_order_is_pvz((string) ($row['tip_dostavki'] ?? ''));
    $track = delivery_get_track($row);
    if ($isPvz && ($track === null || $track === '')) {
        return 'Для доставки в ПВЗ сначала укажите трек-номер.';
    }

    $deliveredStatus = delivery_status_with_track(DELIVERY_STATUS_DELIVERED, $track);

    try {
        $pdo->beginTransaction();
        if (delivery_track_column_exists($pdo)) {
            $pdo->prepare(
                'UPDATE STYLE_dostavka SET status = ?, trek_nomer = ? WHERE id_dostavka = ?'
            )->execute([DELIVERY_STATUS_DELIVERED, $track, $idDostavka]);
        } else {
            $pdo->prepare('UPDATE STYLE_dostavka SET status = ? WHERE id_dostavka = ?')
                ->execute([$deliveredStatus, $idDostavka]);
        }
        if (!$isPvz) {
            $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')
                ->execute([ORDER_STATUS_DONE, (int) $row['id_zakaz']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return 'Не удалось обновить статус.';
    }

    return null;
}

/** Отмена заказа: статус доставки «Отменён», трек-номер сохраняется. */
function delivery_mark_order_cancelled(PDO $pdo, int $idZakaz): void
{
    if (!delivery_table_exists($pdo)) {
        return;
    }
    $d = fetch_delivery_for_order($pdo, $idZakaz);
    if (!$d) {
        return;
    }
    $track = delivery_get_track($d);
    if (delivery_track_column_exists($pdo)) {
        $pdo->prepare('UPDATE STYLE_dostavka SET status = ? WHERE id_zakaz = ?')
            ->execute([ORDER_STATUS_CANCELLED, $idZakaz]);
    } else {
        $pdo->prepare('UPDATE STYLE_dostavka SET status = ? WHERE id_zakaz = ?')
            ->execute([delivery_status_with_track(ORDER_STATUS_CANCELLED, $track), $idZakaz]);
    }
}

/** Клиент подтвердил получение заказа в ПВЗ. */
function client_confirm_pvz_received(PDO $pdo, int $idZakaz, int $clientId): ?string
{
    $st = $pdo->prepare('SELECT * FROM STYLE_zakazy WHERE id_zakaz = ? AND klient = ? LIMIT 1');
    $st->execute([$idZakaz, $clientId]);
    $z = $st->fetch();
    if (!$z) {
        return 'Заказ не найден.';
    }

    $delivery = fetch_delivery_for_order($pdo, $idZakaz);
    if (!delivery_pvz_awaiting_client($z, $delivery)) {
        return 'Подтвердить получение можно только для заказа в ПВЗ, который уже передан в пункт выдачи.';
    }

    try {
        $pdo->prepare('UPDATE STYLE_zakazy SET status = ? WHERE id_zakaz = ?')
            ->execute([ORDER_STATUS_DONE, $idZakaz]);
    } catch (Throwable $e) {
        return 'Не удалось завершить заказ.';
    }

    return null;
}

/** @return list<string> */
function order_statuses_employee(): array
{
    return ['Новый', 'В обработке', 'Готов к отправке', ORDER_STATUS_CANCELLED];
}

/** @return list<string> */
function order_statuses_manager(): array
{
    return ['Новый', 'В обработке', 'Готов к отправке', ORDER_STATUS_V_PUTI, ORDER_STATUS_DONE, ORDER_STATUS_CANCELLED];
}
