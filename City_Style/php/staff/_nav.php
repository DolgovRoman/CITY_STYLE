<?php

declare(strict_types=1);

/** Путь от текущей папки к папке staff/ (напр. '' из staff/, '../' из staff/courier/) */
$staffHome = $staffHome ?? '';
/** Префикс к php/ для ссылок на витрину и выход (совпадает с $staffPhpWebPrefix из layout) */
$phpWeb = isset($staffPhpWebPrefix) ? (string) $staffPhpWebPrefix : staff_php_web_prefix();

if (!function_exists('staff_kind')) {
    return;
}
$sk = staff_kind();

$here = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isIndex = $here === 'index.php';
$isCourierList = str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'courier/deliveries');
$isCourierDetail = str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'courier/delivery');
?>
        <div class="staff-sidebar-card">
            <p class="staff-nav-heading">Меню</p>
            <nav class="staff-nav" aria-label="Разделы панели">
                <ul class="staff-nav-list">
                    <li>
                        <a class="staff-nav-link<?= $isIndex ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>index.php">Обзор</a>
                    </li>
                    <?php if ($sk === 'courier'): ?>
                        <li>
                            <a class="staff-nav-link<?= ($isCourierList || $isCourierDetail) ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>courier/deliveries.php">Мои доставки</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($sk === 'employee'): ?>
                        <li>
                            <a class="staff-nav-link<?= in_array($here, ['products.php', 'product_edit.php'], true) ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>employee/products.php">Товары</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= in_array($here, ['orders.php', 'order.php'], true) ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>employee/orders.php">Заказы</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= $here === 'clients.php' ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>employee/clients.php">Клиенты</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($sk === 'manager'): ?>
                        <li>
                            <a class="staff-nav-link<?= str_starts_with($here, 'client') ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/clients.php">Клиенты</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= str_starts_with($here, 'employee') ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/employees.php">Сотрудники</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= $here === 'reports.php' ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/reports.php">Отчёты</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= $here === 'finance.php' ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/finance.php">Финансы</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= $here === 'warehouse.php' ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/warehouse.php">Склад</a>
                        </li>
                        <li>
                            <a class="staff-nav-link<?= $here === 'orders.php' ? ' is-active' : '' ?>" href="<?= h($staffHome) ?>manager/orders.php">Контроль заказов</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <p class="staff-nav-heading staff-nav-heading--spaced">Сайт</p>
                <ul class="staff-nav-list">
                    <li>
                        <a class="staff-nav-link staff-nav-link--muted" href="<?= h($phpWeb) ?>catalog.php">Витрина магазина</a>
                    </li>
                    <li>
                        <a class="staff-nav-link staff-nav-link--muted" href="<?= h($phpWeb) ?>logout.php">Выход</a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
        <div class="staff-content">
