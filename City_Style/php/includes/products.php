<?php

declare(strict_types=1);

function tovary_base_sql(): string
{
    return 'SELECT t.*, k.nazvanie AS kategoriya_nazv, r.nazvanie AS razmer_nazv, c.nazvanie AS tsvet_nazv
            FROM STYLE_tovary t
            LEFT JOIN STYLE_kategorii k ON t.kategoriya = k.id_kategoriya
            LEFT JOIN STYLE_razmer r ON t.razmer = r.id_razmer
            LEFT JOIN STYLE_tcvet c ON t.tsvet = c.id_tcvet';
}

/**
 * @return array{q: string, kategoriya: int, razmer: int, tsvet: int}
 */
function catalog_filters_from_request(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'kategoriya' => max(0, (int) ($_GET['kategoriya'] ?? 0)),
        'razmer' => max(0, (int) ($_GET['razmer'] ?? 0)),
        'tsvet' => max(0, (int) ($_GET['tsvet'] ?? 0)),
    ];
}

/**
 * @param array{q?: string, kategoriya?: int, razmer?: int, tsvet?: int} $filters
 * @param-out list<mixed> $params
 */
function catalog_apply_tovar_filters(string &$sql, array &$params, array $filters, string $alias = 't'): void
{
    if (($filters['q'] ?? '') !== '') {
        $sql .= ' AND (' . $alias . '.nazvanie LIKE ? OR ' . $alias . '.articul LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (!empty($filters['kategoriya'])) {
        $sql .= ' AND ' . $alias . '.kategoriya = ?';
        $params[] = (int) $filters['kategoriya'];
    }
    if (!empty($filters['razmer'])) {
        $sql .= ' AND ' . $alias . '.razmer = ?';
        $params[] = (int) $filters['razmer'];
    }
    if (!empty($filters['tsvet'])) {
        $sql .= ' AND ' . $alias . '.tsvet = ?';
        $params[] = (int) $filters['tsvet'];
    }
}

function catalog_in_stock_sql(PDO $pdo, string $tAlias = 't'): string
{
    if (!stock_enabled($pdo)) {
        return '';
    }

    return ' AND EXISTS (
        SELECT 1 FROM STYLE_tovary tsk
        LEFT JOIN STYLE_sklad_ostatok osk ON osk.id_tovar = tsk.id_tovar
        WHERE tsk.nazvanie = ' . $tAlias . '.nazvanie
          AND (tsk.kategoriya <=> ' . $tAlias . '.kategoriya)
          AND COALESCE(osk.kolichestvo, 0) > 0
    )';
}

/** @return list<array<string, mixed>> */
function fetch_tovary(PDO $pdo, ?int $kategoriyaId = null, ?int $limit = null, array $filters = []): array
{
    $sql = tovary_base_sql() . ' WHERE 1=1';
    $params = [];
    $f = $filters;
    if ($kategoriyaId !== null && $kategoriyaId > 0) {
        $f['kategoriya'] = $kategoriyaId;
    }
    catalog_apply_tovar_filters($sql, $params, $f);
    $sql .= catalog_in_stock_sql($pdo);
    $sql .= ' ORDER BY t.id_tovar ASC';
    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return $st->fetchAll();
}

/**
 * Одна карточка на модель: группировка по названию и категории.
 *
 * @param array{q?: string, kategoriya?: int, razmer?: int, tsvet?: int} $filters
 * @return list<array<string, mixed>>
 */
function fetch_catalog_groups(PDO $pdo, ?int $kategoriyaId = null, ?int $limit = null, array $filters = []): array
{
    $f = $filters;
    if ($kategoriyaId !== null && $kategoriyaId > 0) {
        $f['kategoriya'] = $kategoriyaId;
    }

    $sub = 'SELECT MIN(t.id_tovar) AS id_tovar, t.nazvanie, t.kategoriya,
            MIN(t.tsena) AS min_tsena, MAX(t.tsena) AS max_tsena
            FROM STYLE_tovary t
            WHERE 1=1';
    $params = [];
    catalog_apply_tovar_filters($sub, $params, $f);
    $sub .= catalog_in_stock_sql($pdo);
    $sub .= ' GROUP BY t.nazvanie, t.kategoriya HAVING COUNT(*) > 0';

    $sql = 'SELECT sub.id_tovar, sub.nazvanie, sub.kategoriya, sub.min_tsena, sub.max_tsena, t0.izobrazhenie
            FROM (' . $sub . ') sub
            INNER JOIN STYLE_tovary t0 ON t0.id_tovar = sub.id_tovar
            ORDER BY sub.nazvanie ASC';
    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    if (!$rows) {
        return [];
    }
    $names = [];
    $stK = $pdo->query('SELECT id_kategoriya, nazvanie FROM STYLE_kategorii');
    foreach ($stK->fetchAll() as $k) {
        $names[(int) $k['id_kategoriya']] = $k['nazvanie'];
    }
    foreach ($rows as &$r) {
        $kid = $r['kategoriya'] ?? null;
        $r['kategoriya_nazv'] = $kid !== null ? ($names[(int) $kid] ?? '') : '';
    }
    unset($r);

    return $rows;
}

/**
 * Список SKU для сотрудников (с остатком при наличии склада).
 *
 * @param array{q?: string, kategoriya?: int, razmer?: int, tsvet?: int} $filters
 * @return list<array<string, mixed>>
 */
function fetch_staff_products(PDO $pdo, array $filters = []): array
{
    $stockJoin = stock_enabled($pdo)
        ? ' LEFT JOIN STYLE_sklad_ostatok o ON o.id_tovar = t.id_tovar'
        : '';
    $stockSel = stock_enabled($pdo) ? ', COALESCE(o.kolichestvo, 0) AS ostatok' : '';

    $sql = 'SELECT t.id_tovar, t.nazvanie, t.articul, t.tsena, k.nazvanie AS kat, r.nazvanie AS razm, c.nazvanie AS cvet' . $stockSel . '
            FROM STYLE_tovary t
            LEFT JOIN STYLE_kategorii k ON t.kategoriya = k.id_kategoriya
            LEFT JOIN STYLE_razmer r ON t.razmer = r.id_razmer
            LEFT JOIN STYLE_tcvet c ON t.tsvet = c.id_tcvet' . $stockJoin . '
            WHERE 1=1';
    $params = [];
    catalog_apply_tovar_filters($sql, $params, $filters);
    $sql .= ' ORDER BY t.nazvanie ASC, t.id_tovar DESC';

    $st = $pdo->prepare($sql);
    $st->execute($params);

    return $st->fetchAll();
}

/**
 * Все SKU одной модели (то же название и категория, что у представителя id_tovar).
 *
 * @return list<array<string, mixed>>
 */
function fetch_variants_for_rep_id(PDO $pdo, int $repId, bool $inStockOnly = false): array
{
    $base = fetch_tovar_by_id($pdo, $repId);
    if (!$base) {
        return [];
    }
    $nazv = (string) $base['nazvanie'];
    $kat = $base['kategoriya'] ?? null;
    $sql = tovary_base_sql() . ' WHERE t.nazvanie = ? AND (t.kategoriya <=> ?)';
    $params = [$nazv, $kat];
    if ($inStockOnly && stock_enabled($pdo)) {
        $sql .= ' AND EXISTS (
            SELECT 1 FROM STYLE_sklad_ostatok o
            WHERE o.id_tovar = t.id_tovar AND o.kolichestvo > 0
        )';
    }
    $sql .= ' ORDER BY t.tsvet, t.razmer, t.id_tovar';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['ostatok'] = stock_qty($pdo, (int) $r['id_tovar']);
    }
    unset($r);

    return $rows;
}

/** @return array<string, mixed>|null */
function fetch_tovar_by_id(PDO $pdo, int $id): ?array
{
    $sql = tovary_base_sql() . ' WHERE t.id_tovar = ? LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    $row['ostatok'] = stock_qty($pdo, $id);

    return $row;
}

/** @return list<array<string, mixed>> */
function fetch_kategorii(PDO $pdo): array
{
    return $pdo->query('SELECT id_kategoriya, nazvanie FROM STYLE_kategorii ORDER BY nazvanie')->fetchAll();
}

/** @return list<array<string, mixed>> */
function fetch_razmer(PDO $pdo): array
{
    return $pdo->query('SELECT id_razmer, nazvanie FROM STYLE_razmer ORDER BY id_razmer')->fetchAll();
}

/** @return list<array<string, mixed>> */
function fetch_tsveta(PDO $pdo): array
{
    return $pdo->query('SELECT id_tcvet, nazvanie FROM STYLE_tcvet ORDER BY id_tcvet')->fetchAll();
}

/**
 * @return array{available: list<array{t: array, qty: int, line: float}>, unavailable: list<array{t: array|null, qty: int, id: int, nazvanie: string}>}
 */
function cart_build_lines(PDO $pdo, array $items): array
{
    $available = [];
    $unavailable = [];
    foreach ($items as $id => $qty) {
        $id = (int) $id;
        $qty = (int) $qty;
        if ($id <= 0 || $qty <= 0) {
            continue;
        }
        $t = fetch_tovar_by_id($pdo, $id);
        if (!$t) {
            $unavailable[] = [
                't' => null,
                'qty' => $qty,
                'id' => $id,
                'nazvanie' => 'Товар №' . $id,
            ];
            continue;
        }
        if (!stock_in_stock($pdo, $id)) {
            $unavailable[] = [
                't' => $t,
                'qty' => $qty,
                'id' => $id,
                'nazvanie' => (string) $t['nazvanie'],
            ];
            continue;
        }
        $cap = stock_cap_qty($pdo, $id, $qty);
        if ($cap < $qty) {
            cart_set_qty($id, $cap);
            $qty = $cap;
        }
        if ($qty <= 0) {
            $unavailable[] = [
                't' => $t,
                'qty' => (int) ($items[$id] ?? 1),
                'id' => $id,
                'nazvanie' => (string) $t['nazvanie'],
            ];
            continue;
        }
        $price = (float) $t['tsena'];
        $available[] = ['t' => $t, 'qty' => $qty, 'line' => $price * $qty];
    }

    return ['available' => $available, 'unavailable' => $unavailable];
}

/** Слот конструктора образа: top | bottom | shoes */
function outfit_builder_slot_for_text(?string $text): ?string
{
    if ($text === null || trim($text) === '') {
        return null;
    }
    $n = mb_strtolower(trim($text), 'UTF-8');

    $shoesWords = ['обув', 'кроссов', 'ботинк', 'туфл', 'сапог', 'кед', 'лофер', 'сандал', 'мокасин', 'сникер', 'слипон', 'балетк', 'шлепан'];
    $bottomWords = ['джинс', 'брюк', 'шорт', 'юбк', 'бегги', 'чинос', 'карго', 'леггин', 'штаны', 'брюки', 'шорты', 'плать', 'комбинезон', 'кюлот', 'брюки-'];
    $topWords = ['футболк', 'свитер', 'худи', 'рубаш', 'куртк', 'пиджак', 'жакет', 'толстовк', 'поло', 'майк', 'свитшот', 'водолазк', 'кофт', 'свитш', 'джемпер', 'жилет', 'бомбер', 'пальто', 'плащ', 'ветровк', 'анорак', 'тренч', 'блуз', 'лонгслив', 'кардиган', 'жилетк'];
    $topCategoryWords = ['верх', 'толстов', 'футбол', 'рубаш', 'куртк', 'свитер', 'худи'];
    $bottomCategoryWords = ['низ', 'брюк', 'джинс', 'шорт', 'юбк', 'плать'];

    foreach ($shoesWords as $w) {
        if (str_contains($n, $w)) {
            return 'shoes';
        }
    }
    foreach ($bottomWords as $w) {
        if (str_contains($n, $w)) {
            return 'bottom';
        }
    }
    foreach ($topWords as $w) {
        if (str_contains($n, $w)) {
            return 'top';
        }
    }
    foreach ($bottomCategoryWords as $w) {
        if (str_contains($n, $w)) {
            return 'bottom';
        }
    }
    foreach ($topCategoryWords as $w) {
        if (str_contains($n, $w)) {
            return 'top';
        }
    }

    return null;
}

function outfit_builder_slot_for_item(?string $categoryName, ?string $productName): ?string
{
    return outfit_builder_slot_for_text($categoryName)
        ?? outfit_builder_slot_for_text($productName);
}

/**
 * Товары из каталога для конструктора образа (только в наличии, как в витрине).
 *
 * @return array{top: list<array<string, mixed>>, bottom: list<array<string, mixed>>, shoes: list<array<string, mixed>>}
 */
function fetch_outfit_builder_items(PDO $pdo): array
{
    $result = ['top' => [], 'bottom' => [], 'shoes' => []];
    $groups = fetch_catalog_groups($pdo);

    foreach ($groups as $g) {
        $slot = outfit_builder_slot_for_item($g['kategoriya_nazv'] ?? '', $g['nazvanie'] ?? '');
        if ($slot === null) {
            continue;
        }
        $id = (int) $g['id_tovar'];
        $price = (float) $g['min_tsena'];
        $result[$slot][] = [
            'id' => $id,
            'name' => (string) $g['nazvanie'],
            'price' => $price,
            'priceFmt' => format_price($price),
            'img' => product_image_url($g['izobrazhenie'] ?? null),
            'url' => 'product.php?id=' . $id . '&back=' . rawurlencode('outfit-builder.php'),
        ];
    }

    return $result;
}
