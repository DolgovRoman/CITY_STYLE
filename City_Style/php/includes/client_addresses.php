<?php

declare(strict_types=1);

function client_addresses_table_exists(PDO $pdo): bool
{
    return table_exists($pdo, 'STYLE_klient_adresa');
}

/** Префикс в nazvanie: способ доставки + подпись без новой колонки в БД. */
function address_stored_nazvanie_pack(string $tipDostavki, ?string $userLabel): ?string
{
    $tip = delivery_tip_normalize($tipDostavki);
    if (!in_array($tip, delivery_tip_options(), true)) {
        $label = trim((string) $userLabel);

        return $label !== '' ? $label : null;
    }
    $label = trim((string) $userLabel);

    return '@d:' . $tip . '|' . $label;
}

/**
 * @return array{tip: ?string, label: ?string}
 */
function address_stored_nazvanie_unpack(?string $stored): array
{
    $s = trim((string) $stored);
    if ($s === '') {
        return ['tip' => null, 'label' => null];
    }
    if (preg_match('/^@d:(.+)\|(.*)$/us', $s, $m)) {
        $tip = delivery_tip_normalize($m[1]);
        $label = trim($m[2]);

        return [
            'tip' => in_array($tip, delivery_tip_options(), true) ? $tip : null,
            'label' => $label !== '' ? $label : null,
        ];
    }

    return ['tip' => null, 'label' => $s];
}

/** @return list<array<string, mixed>> */
function client_addresses_for_checkout(PDO $pdo, int $clientId): array
{
    $out = [];
    foreach (fetch_client_addresses($pdo, $clientId) as $row) {
        $meta = address_stored_nazvanie_unpack($row['nazvanie'] ?? null);
        $row['tip_dostavki'] = $meta['tip'] ?? '';
        $out[] = $row;
    }

    return $out;
}

/** Улица: буквы (кириллица/латиница), пробелы, точка, дефис. */
function address_ulica_is_valid(string $ulica): bool
{
    $ulica = trim($ulica);

    return $ulica !== '' && preg_match('/^[\p{L}\s.\-—]{2,120}$/u', $ulica) === 1;
}

/** Дом: только цифры (1–6 знаков). */
function address_dom_is_valid(string $dom): bool
{
    $dom = trim($dom);

    return preg_match('/^\d{1,6}$/u', $dom) === 1;
}

/** Квартира: пусто или только цифры (1–5 знаков). */
function address_kvartira_is_valid(string $kvartira): bool
{
    $kvartira = trim($kvartira);
    if ($kvartira === '') {
        return true;
    }

    return preg_match('/^\d{1,5}$/u', $kvartira) === 1;
}

/** Строка адреса для заказа (поле STYLE_zakazy.adres). */
function address_compose_order_string(string $ulica, string $dom, string $kvartira, bool $needFlat): string
{
    $s = 'ул. ' . trim($ulica) . ', д. ' . trim($dom);
    $kv = trim($kvartira);
    if ($needFlat && $kv !== '') {
        $s .= ', кв. ' . $kv;
    }

    return $s;
}

/**
 * Проверка полей адреса при оформлении заказа.
 */
function delivery_validate_address_parts(
    string $tip,
    string $gorod,
    string $ulica,
    string $dom,
    string $kvartira
): ?string {
    $tip = delivery_tip_normalize($tip);
    $gorod = trim($gorod);
    $ulica = trim($ulica);
    $dom = trim($dom);
    $kvartira = trim($kvartira);

    if (!in_array($tip, delivery_tip_options(), true)) {
        return 'Выберите способ доставки.';
    }
    if ($gorod === '') {
        return 'Укажите город.';
    }
    if (!address_ulica_is_valid($ulica)) {
        return 'Улица: только буквы (от 2 символов), без цифр.';
    }
    if (!address_dom_is_valid($dom)) {
        return 'Дом: только цифры (до 6 знаков).';
    }
    if (!address_kvartira_is_valid($kvartira)) {
        return 'Квартира: только цифры (до 5 знаков) или оставьте пустым для ПВЗ.';
    }

    $isCourier = $tip === DELIVERY_TIP_COURIER;
    if ($isCourier && !delivery_city_is_courier_zone($gorod)) {
        return 'Курьер до адреса доступен только в городе ' . DELIVERY_COURIER_CITY . '. Для других городов выберите «' . DELIVERY_TIP_PVZ . '».';
    }

    if ($tip === DELIVERY_TIP_PVZ) {
        $kvartira = '';
    }

    return null;
}

/** @return list<array<string, mixed>> */
function fetch_client_addresses(PDO $pdo, int $clientId): array
{
    if (!client_addresses_table_exists($pdo)) {
        return [];
    }
    $st = $pdo->prepare(
        'SELECT id_adres, nazvanie, gorod, ulica, dom, kvartira
         FROM STYLE_klient_adresa WHERE id_klient = ? ORDER BY id_adres DESC'
    );
    $st->execute([$clientId]);

    return $st->fetchAll();
}

/** @return array<string, mixed>|null */
function fetch_client_address_by_id(PDO $pdo, int $addressId, int $clientId): ?array
{
    if (!client_addresses_table_exists($pdo) || $addressId <= 0) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT id_adres, nazvanie, gorod, ulica, dom, kvartira
         FROM STYLE_klient_adresa WHERE id_adres = ? AND id_klient = ? LIMIT 1'
    );
    $st->execute([$addressId, $clientId]);
    $row = $st->fetch();

    return $row ?: null;
}

function client_address_label(array $row): string
{
    $meta = address_stored_nazvanie_unpack($row['nazvanie'] ?? null);
    $gorod = trim((string) ($row['gorod'] ?? ''));
    $ulica = trim((string) ($row['ulica'] ?? ''));
    $dom = trim((string) ($row['dom'] ?? ''));
    $kv = trim((string) ($row['kvartira'] ?? ''));
    $line = $gorod . ', ул. ' . $ulica . ', д. ' . $dom;
    if ($kv !== '') {
        $line .= ', кв. ' . $kv;
    }
    $prefix = [];
    if ($meta['label'] !== null) {
        $prefix[] = $meta['label'];
    }
    if ($meta['tip'] === DELIVERY_TIP_COURIER) {
        $prefix[] = 'курьер';
    } elseif ($meta['tip'] === DELIVERY_TIP_PVZ) {
        $prefix[] = 'ПВЗ';
    }
    if ($prefix !== []) {
        return implode(' · ', $prefix) . ' — ' . $line;
    }

    return $line;
}

function save_client_address(
    PDO $pdo,
    int $clientId,
    string $gorod,
    string $ulica,
    string $dom,
    string $kvartira,
    ?string $nazvanie = null,
    string $tipDostavki = ''
): ?string {
    if (!client_addresses_table_exists($pdo)) {
        return 'Таблица сохранённых адресов не создана. Выполните sql/schema_client_addresses.sql.';
    }

    $gorod = trim($gorod);
    if ($gorod === '') {
        return 'Укажите город.';
    }
    if (!address_ulica_is_valid($ulica)) {
        return 'Улица: только буквы (от 2 символов).';
    }
    if (!address_dom_is_valid($dom)) {
        return 'Дом: только цифры.';
    }
    if (!address_kvartira_is_valid($kvartira)) {
        return 'Квартира: только цифры или оставьте пустым.';
    }

    $packedNazv = address_stored_nazvanie_pack($tipDostavki, $nazvanie);

    try {
        $pdo->prepare(
            'INSERT INTO STYLE_klient_adresa (id_klient, nazvanie, gorod, ulica, dom, kvartira)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $clientId,
            $packedNazv,
            trim($gorod),
            trim($ulica),
            trim($dom),
            trim($kvartira) !== '' ? trim($kvartira) : null,
        ]);
    } catch (Throwable $e) {
        return 'Не удалось сохранить адрес.';
    }

    return null;
}

function delete_client_address(PDO $pdo, int $addressId, int $clientId): ?string
{
    if (!client_addresses_table_exists($pdo)) {
        return 'Таблица адресов недоступна.';
    }
    $st = $pdo->prepare('DELETE FROM STYLE_klient_adresa WHERE id_adres = ? AND id_klient = ?');
    $st->execute([$addressId, $clientId]);
    if ($st->rowCount() === 0) {
        return 'Адрес не найден.';
    }

    return null;
}
