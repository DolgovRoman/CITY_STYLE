<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$u = current_user();
if (!$u) {
    redirect('login.php?redirect=' . rawurlencode('account.php'));
}

$pdo = db();
$phoneCol = client_phone_column($pdo);
$clientRow = null;
$profileError = '';

if (is_client()) {
    $sel = 'SELECT familiya, imya, otchestvo, data_rozhd, email' . client_phone_select_sql($pdo, '') . ' FROM STYLE_klienti WHERE id_klient = ? LIMIT 1';
    $st = $pdo->prepare($sel);
    $st->execute([(int) $u['id']]);
    $clientRow = $st->fetch() ?: null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile']) && $clientRow) {
        $telefon = trim((string) ($_POST['telefon'] ?? ''));
        if ($phoneCol !== null) {
            $profileError = client_phone_validate($telefon, true) ?? '';
            if ($profileError === '') {
                $pdo->prepare("UPDATE STYLE_klienti SET {$phoneCol} = ? WHERE id_klient = ?")
                    ->execute([$telefon, (int) $u['id']]);
                $clientRow[$phoneCol] = $telefon;
                flash_set('Телефон обновлён.', 'success');
                redirect('account.php');
            }
        }
    }
}

$pageTitle = 'Личный кабинет — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="container section">
    <h1>Личный кабинет</h1>
    <div class="account-grid">
        <aside class="panel">
            <h3>Разделы</h3>
            <ul class="account-nav">
                <li><a class="btn btn-ghost" href="account.php">Профиль</a></li>
                <?php if (is_client()): ?>
                    <li><a class="btn btn-ghost" href="orders.php">Мои заказы</a></li>
                    <li><a class="btn btn-ghost" href="addresses.php">Мои адреса</a></li>
                    <li><a class="btn btn-ghost" href="cart.php">Корзина</a></li>
                <?php endif; ?>
            </ul>
        </aside>
        <section class="panel">
            <?php if (is_staff()): ?>
                <h3>Сотрудник</h3>
                <p>Здравствуйте, <strong><?= h($u['name']) ?></strong>.</p>
                <p>Должность: <strong><?= h($u['dolzhnost'] ?? '') ?></strong></p>
                <p>Email: <?= h($u['email'] ?? '') ?></p>
                <p style="margin-top:12px"><a class="btn btn-primary" href="staff/index.php">Панель сотрудника</a></p>
                <p style="color:var(--muted);font-size:14px">Корзина и оформление заказов — только для клиентов.</p>
            <?php else: ?>
                <h3>Профиль клиента</h3>
                <?php if ($clientRow): ?>
                    <p><strong>ФИО:</strong> <?= h(trim($clientRow['familiya'] . ' ' . $clientRow['imya'] . ' ' . (string) ($clientRow['otchestvo'] ?? ''))) ?></p>
                    <p><strong>Email:</strong> <?= h($clientRow['email']) ?></p>
                    <p><strong>Дата рождения:</strong> <?= h((string) ($clientRow['data_rozhd'] ?? '—')) ?></p>
                    <?php if ($phoneCol !== null): ?>
                        <p><strong>Телефон:</strong> <?= client_phone_html(client_phone_from_row($clientRow, $pdo)) ?></p>
                        <?php if ($profileError !== ''): ?>
                            <div class="flash flash-error" style="margin:12px 0"><?= h($profileError) ?></div>
                        <?php endif; ?>
                        <form method="post" style="margin-top:16px;max-width:360px">
                            <input type="hidden" name="save_profile" value="1">
                            <label>Изменить телефон *
                                <input type="tel" name="telefon" required autocomplete="tel"
                                       value="<?= h(client_phone_from_row($clientRow, $pdo)) ?>"
                                       placeholder="+7 (999) 123-45-67">
                            </label>
                            <button class="btn btn-primary" type="submit" style="margin-top:12px">Сохранить телефон</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Не удалось загрузить профиль из базы.</p>
                <?php endif; ?>
                <p style="margin-top:16px"><a class="btn btn-primary" href="orders.php">Мои заказы</a></p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
