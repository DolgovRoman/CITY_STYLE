<?php

declare(strict_types=1);

require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/staff_auth.php';

require_staff();

$pageTitle = 'Панель сотрудника — City Style';
$u = current_user();
$k = staff_kind();

$staffHome = '';
require __DIR__ . '/../includes/layout_staff_top.php';
require __DIR__ . '/_nav.php';
?>

<main class="staff-main">
    <h1>Рабочая панель</h1>
    <p class="staff-intro">Здравствуйте, <strong><?= h($u['name'] ?? '') ?></strong>. Ниже — быстрые действия для вашей роли.</p>

    <?php if ($k === 'courier'): ?>
        <div class="staff-dashboard">
            <a class="staff-dash-card" href="courier/deliveries.php">
                <h3>Доставки</h3>
                <p>Список заказов, адреса, способ доставки и смена статуса.</p>
                <span class="staff-dash-cta">Открыть →</span>
            </a>
        </div>
    <?php elseif ($k === 'employee'): ?>
        <div class="staff-dashboard">
            <a class="staff-dash-card" href="employee/products.php">
                <h3>Товары</h3>
                <p>Каталог SKU: добавление, правка и удаление позиций.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="employee/orders.php">
                <h3>Заказы</h3>
                <p>Статусы, подготовка к отправке, связь с клиентом.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="employee/clients.php">
                <h3>Клиенты</h3>
                <p>Контакты и быстрый переход на email.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
        </div>
    <?php elseif ($k === 'manager'): ?>
        <div class="staff-dashboard">
            <a class="staff-dash-card" href="manager/clients.php">
                <h3>Клиенты</h3>
                <p>Учёт покупателей, создание и редактирование записей.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="manager/employees.php">
                <h3>Сотрудники</h3>
                <p>Штат и должности.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="manager/reports.php">
                <h3>Отчёты</h3>
                <p>Динамика продаж и топ товаров.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="manager/finance.php">
                <h3>Финансы</h3>
                <p>Выручка и незавершённые заказы.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="manager/warehouse.php">
                <h3>Склад</h3>
                <p>Остатки и поступления.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
            <a class="staff-dash-card" href="manager/orders.php">
                <h3>Заказы</h3>
                <p>Контроль статусов по всей системе.</p>
                <span class="staff-dash-cta">Перейти →</span>
            </a>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/layout_staff_bottom.php'; ?>
