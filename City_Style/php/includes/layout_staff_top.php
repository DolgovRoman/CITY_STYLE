<?php

declare(strict_types=1);

/** @var string $pageTitle */

$staffPhpWebPrefix = staff_php_web_prefix();
$staffProjectRootWebPrefix = staff_project_root_web_prefix();

$flash = function_exists('flash_get') ? flash_get() : null;
$staffUser = function_exists('current_user') ? current_user() : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'City Style') ?></title>
    <link rel="stylesheet" href="<?= h($staffProjectRootWebPrefix) ?>css/style.css">
</head>
<body class="staff-app">
    <header class="staff-topbar">
        <div class="container staff-topbar-inner">
            <div class="staff-topbar-start">
                <button type="button" class="nav-toggle nav-toggle--staff" data-nav-toggle="staff" aria-expanded="false"
                        aria-controls="staff-sidebar" aria-label="Открыть меню панели">
                    <span class="nav-toggle__icon" aria-hidden="true"></span>
                </button>
                <div class="staff-topbar-brand">
                    <a class="logo" href="<?= h($staffPhpWebPrefix) ?>index.php">CITY STYLE</a>
                    <span class="staff-topbar-tag">Панель управления</span>
                </div>
            </div>
            <?php if ($staffUser): ?>
                <div class="staff-topbar-user" title="<?= h($staffUser['email'] ?? '') ?>">
                    <span class="staff-topbar-name"><?= h($staffUser['name'] ?? '') ?></span>
                    <?php if (!empty($staffUser['dolzhnost'])): ?>
                        <span class="staff-topbar-role"><?= h((string) $staffUser['dolzhnost']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="staff-topbar-actions">
                <a class="staff-topbar-link" href="<?= h($staffPhpWebPrefix) ?>catalog.php">Каталог</a>
                <a class="staff-topbar-link" href="<?= h($staffPhpWebPrefix) ?>account.php">Кабинет</a>
                <a class="btn btn-primary staff-topbar-btn" href="<?= h($staffPhpWebPrefix) ?>logout.php">Выйти</a>
            </div>
        </div>
    </header>
    <div class="nav-overlay nav-overlay--staff" data-nav-overlay="staff" hidden></div>
    <?php if ($flash): ?>
        <div class="container staff-flash flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
    <div class="staff-shell container">
        <aside class="staff-sidebar" id="staff-sidebar">
