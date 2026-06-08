<?php

declare(strict_types=1);

require_once __DIR__ . '/delivery.php';

/**
 * @return array{q: string, status: string, tip: string, dolzhnost: int, date_from: string, date_to: string}
 */
function staff_list_filters_from_request(): array
{
    return [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? '')),
        'tip' => trim((string) ($_GET['tip'] ?? '')),
        'dolzhnost' => max(0, (int) ($_GET['dolzhnost'] ?? 0)),
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        'month' => trim((string) ($_GET['month'] ?? '')),
    ];
}

/**
 * @param-out list<mixed> $params
 */
function staff_apply_like_q(string &$sql, array &$params, string $q, array $columns): void
{
    if ($q === '') {
        return;
    }
    $parts = [];
    $like = '%' . $q . '%';
    foreach ($columns as $col) {
        $parts[] = $col . ' LIKE ?';
        $params[] = $like;
    }
    $sql .= ' AND (' . implode(' OR ', $parts) . ')';
}

/**
 * @param array{q?: string, status?: string, tip?: string} $f
 * @param-out list<mixed> $params
 */
function staff_sql_orders_where(array $f, array &$params, string $prefix = 'z'): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        $cols = [
            "CAST({$prefix}.id_zakaz AS CHAR)",
            'k.familiya',
            'k.imya',
            'k.email',
            "{$prefix}.gorod",
            "{$prefix}.adres",
        ];
        $phCol = client_phone_column(db());
        if ($phCol !== null) {
            $cols[] = 'k.' . $phCol;
        }
        staff_apply_like_q($sql, $params, $f['q'], $cols);
    }
    if (($f['status'] ?? '') !== '') {
        $sql .= " AND {$prefix}.status = ?";
        $params[] = $f['status'];
    }
    if (($f['tip'] ?? '') !== '') {
        if ($f['tip'] === DELIVERY_TIP_PVZ) {
            $sql .= " AND ({$prefix}.tip_dostavki = ? OR {$prefix}.tip_dostavki = 'Самовывоз')";
            $params[] = DELIVERY_TIP_PVZ;
        } else {
            $sql .= " AND ({$prefix}.tip_dostavki = ? OR {$prefix}.tip_dostavki = 'Курьер')";
            $params[] = $f['tip'];
        }
    }
    if (($f['date_from'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_from'])) {
        $sql .= " AND {$prefix}.data_sozdaniya >= ?";
        $params[] = $f['date_from'];
    }
    if (($f['date_to'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_to'])) {
        $sql .= " AND {$prefix}.data_sozdaniya <= ?";
        $params[] = $f['date_to'];
    }

    return $sql;
}

/**
 * @param array{q?: string} $f
 * @param-out list<mixed> $params
 */
function staff_sql_clients_where(array $f, array &$params): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        $cols = [
            'k.familiya',
            'k.imya',
            'k.otchestvo',
            'k.email',
            'CAST(k.id_klient AS CHAR)',
        ];
        $phCol = client_phone_column(db());
        if ($phCol !== null) {
            $cols[] = 'k.' . $phCol;
        }
        staff_apply_like_q($sql, $params, $f['q'], $cols);
    }

    return $sql;
}

/**
 * @param array{q?: string, dolzhnost?: int} $f
 * @param-out list<mixed> $params
 */
function staff_sql_employees_where(array $f, array &$params): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        staff_apply_like_q($sql, $params, $f['q'], [
            's.familiya',
            's.imya',
            's.email',
            'CAST(s.id_sotrudnik AS CHAR)',
            'd.nazvanie',
        ]);
    }
    if (!empty($f['dolzhnost'])) {
        $sql .= ' AND s.dolzhnost = ?';
        $params[] = (int) $f['dolzhnost'];
    }

    return $sql;
}

/**
 * @param array{q?: string, status?: string} $f
 * @param-out list<mixed> $params
 */
function staff_sql_deliveries_where(array $f, array &$params): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        staff_apply_like_q($sql, $params, $f['q'], [
            'CAST(d.id_dostavka AS CHAR)',
            'CAST(d.id_zakaz AS CHAR)',
            'k.familiya',
            'k.imya',
            'z.gorod',
            'z.adres',
        ]);
    }
    if (($f['status'] ?? '') !== '') {
        $sql .= ' AND d.status = ?';
        $params[] = $f['status'];
    }

    return $sql;
}

/**
 * @param array{q?: string} $f
 * @param-out list<mixed> $params
 */
function staff_sql_warehouse_where(array $f, array &$params): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        staff_apply_like_q($sql, $params, $f['q'], [
            't.nazvanie',
            't.articul',
            'CAST(t.id_tovar AS CHAR)',
        ]);
    }

    return $sql;
}

/**
 * @param array{q?: string} $f
 * @param-out list<mixed> $params
 */
function staff_sql_reports_top_where(array $f, array &$params): string
{
    $sql = '';
    if (($f['q'] ?? '') !== '') {
        staff_apply_like_q($sql, $params, $f['q'], ['t.nazvanie', 't.articul']);
    }

    return $sql;
}

/**
 * @param array<string, mixed> $filters
 * @param array<string, string> $extraFields
 */
function staff_render_filter_panel(string $formAction, array $filters, string $type, array $extraFields = []): void
{
    $q = (string) ($filters['q'] ?? '');
    $status = (string) ($filters['status'] ?? '');
    $tip = (string) ($filters['tip'] ?? '');
    $dolzhnost = (int) ($filters['dolzhnost'] ?? 0);
    $dateFrom = (string) ($filters['date_from'] ?? '');
    $dateTo = (string) ($filters['date_to'] ?? '');
    ?>
    <div class="panel filter-form staff-filter-panel">
        <h3>Поиск и фильтры</h3>
        <form method="get" action="<?= h($formAction) ?>" class="staff-filter-grid">
            <label class="filter-label">Поиск
                <input type="search" name="q" value="<?= h($q) ?>" placeholder="<?php
                    echo match ($type) {
                        'orders' => '№ заказа, клиент, город…',
                        'clients' => 'ФИО, email, ID…',
                        'employees' => 'ФИО, email, должность…',
                        'deliveries' => '№ доставки, заказ, адрес…',
                        'warehouse' => 'Товар, артикул…',
                        'reports' => 'Название товара…',
                        default => 'Поиск…',
                    };
                ?>">
            </label>
            <?php if (in_array($type, ['orders', 'deliveries'], true)): ?>
                <label class="filter-label">Статус
                    <select name="status">
                        <option value="">Все</option>
                        <?php
                        $statusOpts = $type === 'deliveries'
                            ? [ORDER_STATUS_V_PUTI, 'Доставлен', 'Отменён']
                            : order_statuses_manager();
                        foreach ($statusOpts as $opt):
                            ?>
                            <option value="<?= h($opt) ?>" <?= $status === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <?php if ($type === 'orders'): ?>
                <label class="filter-label">Способ доставки
                    <select name="tip">
                        <option value="">Все</option>
                        <?php foreach (delivery_tip_options() as $opt): ?>
                            <option value="<?= h($opt) ?>" <?= $tip === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="filter-label">Дата с
                    <input type="date" name="date_from" value="<?= h($dateFrom) ?>">
                </label>
                <label class="filter-label">Дата по
                    <input type="date" name="date_to" value="<?= h($dateTo) ?>">
                </label>
            <?php endif; ?>
            <?php if ($type === 'employees' && isset($extraFields['dolzhnosti'])): ?>
                <label class="filter-label">Должность
                    <select name="dolzhnost">
                        <option value="">Все</option>
                        <?php foreach ($extraFields['dolzhnosti'] as $d): ?>
                            <option value="<?= (int) $d['id_dolzhnost'] ?>" <?= $dolzhnost === (int) $d['id_dolzhnost'] ? 'selected' : '' ?>><?= h($d['nazvanie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <?php if ($type === 'reports' && isset($extraFields['months'])): ?>
                <?php $month = (string) ($filters['month'] ?? ''); ?>
                <label class="filter-label">Месяц
                    <select name="month">
                        <option value="">Все месяцы</option>
                        <?php foreach ($extraFields['months'] as $ym): ?>
                            <option value="<?= h((string) $ym) ?>" <?= $month === (string) $ym ? 'selected' : '' ?>><?= h((string) $ym) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Найти</button>
                <a class="btn btn-ghost" href="<?= h($formAction) ?>">Сбросить</a>
            </div>
        </form>
    </div>
    <?php
}
