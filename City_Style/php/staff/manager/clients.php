<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/staff_filters.php';

require_staff();
require_staff_kind('manager');

$pdo = db();
$filters = staff_list_filters_from_request();
$params = [];
$where = staff_sql_clients_where($filters, $params);

$sql = 'SELECT id_klient, familiya, imya, otchestvo, email, data_rozhd' . client_phone_select_sql($pdo) . ' FROM STYLE_klienti k WHERE 1=1'
    . $where . ' ORDER BY id_klient DESC LIMIT 300';
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

$pageTitle = 'Клиенты — руководитель';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <h1>Управление клиентами</h1>
    <div class="staff-toolbar">
        <a class="btn btn-primary" href="client_edit.php">Добавить клиента</a>
    </div>
    <?php staff_render_filter_panel('clients.php', $filters, 'clients'); ?>
    <div class="staff-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <?php if (client_phone_enabled($pdo)): ?><th>Телефон</th><?php endif; ?>
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
                    <td class="table-actions">
                        <a class="btn btn-ghost btn-small" href="client_edit.php?id=<?= (int) $c['id_klient'] ?>">Изменить</a>
                        <form method="post" action="client_delete.php" style="display:inline" onsubmit="return confirm('Удалить клиента?');">
                            <input type="hidden" name="id_klient" value="<?= (int) $c['id_klient'] ?>">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:12px">Удалить</button>
                        </form>
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
