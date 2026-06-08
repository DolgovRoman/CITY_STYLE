<?php

declare(strict_types=1);

if (!function_exists('h')) {
    require __DIR__ . '/init.php';
}

$u = current_user();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'City Style') ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="site-header-bar">
        <div class="site-header-bar__inner container">
            <header class="site-header">
                <div class="header-row">
                    <a class="logo" href="index.php">CITY STYLE</a>
                    <button type="button" class="nav-toggle" data-nav-toggle="site" aria-expanded="false"
                            aria-controls="site-nav-panel" aria-label="Открыть меню">
                        <span class="nav-toggle__icon" aria-hidden="true"></span>
                    </button>
                </div>
            </header>
            <div id="site-nav-panel" class="header-nav-panel">
                <nav class="main-nav" aria-label="Основное меню">
                    <a href="catalog.php">Каталог</a>
                    <a href="outfit-builder.php">Подбор образа</a>
                    <a href="about.php">О нас</a>
                    <a href="delivery-payment.php">Доставка и оплата</a>
                    <a href="contacts.php">Контакты</a>
                </nav>
                <div class="header-actions">
                    <?php if ($u): ?>
                        <?php if (is_staff()): ?>
                            <span class="header-hint" title="Роль"><?= h($u['dolzhnost'] ?? 'Сотрудник') ?></span>
                        <?php endif; ?>
                        <a class="btn btn-ghost btn-header" href="account.php"><?= is_client() ? 'Профиль' : 'Кабинет' ?></a>
                        <?php if (is_client()): ?>
                            <a class="btn btn-ghost btn-header" href="cart.php">Корзина</a>
                        <?php endif; ?>
                        <?php if (is_staff()): ?>
                            <a class="btn btn-ghost btn-header" href="staff/index.php">Панель</a>
                        <?php endif; ?>
                        <a class="btn btn-primary btn-header" href="logout.php">Выйти</a>
                    <?php else: ?>
                        <a class="btn btn-ghost btn-header" href="login.php">Вход</a>
                        <a class="btn btn-primary btn-header" href="register.php">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="nav-overlay" data-nav-overlay="site" hidden></div>
    <?php if ($flash): ?>
        <div class="container flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
