<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('employee');

$pdo = db();
$filters = staff_list_filters_from_request();
$params = [];
$where = staff_sql_clients_where($filters, $params);

$sql = 'SELECT id_klient, familiya, imya, otchestvo, email, data_rozhd' . client_phone_select_sql($pdo) . ' FROM STYLE_klienti k WHERE 1=1'
    . $where . ' ORDER BY familiya, imya LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

$pageTitle = 'Клиенты — сотрудник';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Клиенты</h1>
    <?php staff_render_filter_panel('clients.php', $filters, 'clients'); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <?php if (client_phone_enabled($pdo)): ?><th>Телефон</th><?php endif; ?>
                <th>Дата рождения</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $c): ?>
                <tr>
                    <td><?= (int) $c['id_klient'] ?></td>
                    <td><?= h(trim(($c['familiya'] ?? '') . ' ' . ($c['imya'] ?? '') . ' ' . (string) ($c['otchestvo'] ?? ''))) ?></td>
                    <td><?= h((string) ($c['email'] ?? '')) ?></td>
                    <?php if (client_phone_enabled($pdo)): ?>
                        <td><?= client_phone_html(client_phone_from_row($c, $pdo)) ?></td>
                    <?php endif; ?>
                    <td><?= h((string) ($c['data_rozhd'] ?? '')) ?></td>
                    <td class="table-actions">
                        <a class="btn btn-ghost btn-small" href="mailto:<?= h((string) ($c['email'] ?? '')) ?>">Написать</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if (!$list): ?>
        <p>Клиенты не найдены.</p>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
