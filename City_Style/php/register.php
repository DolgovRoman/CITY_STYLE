<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

if (is_client()) {
    redirect('account.php');
}

$error = '';
$pdo = db();
$phoneCol = client_phone_column($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $familiya = trim((string) ($_POST['familiya'] ?? ''));
    $imya = trim((string) ($_POST['imya'] ?? ''));
    $otchestvo = trim((string) ($_POST['otchestvo'] ?? ''));
    $data_rozhd = trim((string) ($_POST['data_rozhd'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telefon = trim((string) ($_POST['telefon'] ?? ''));
    $parol = (string) ($_POST['parol'] ?? '');
    $parol2 = (string) ($_POST['parol2'] ?? '');

    if ($familiya === '' || $imya === '' || $email === '' || $parol === '') {
        $error = 'Заполните обязательные поля: фамилия, имя, email, пароль.';
    } elseif ($phoneCol !== null && ($phoneErr = client_phone_validate($telefon, true)) !== null) {
        $error = $phoneErr;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email.';
    } elseif ($parol !== $parol2) {
        $error = 'Пароли не совпадают.';
    } elseif (strlen($parol) < 3) {
        $error = 'Пароль слишком короткий.';
    } else {
        try {
            $check = $pdo->prepare('SELECT id_klient FROM STYLE_klienti WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'Пользователь с таким email уже зарегистрирован.';
            } else {
                $dr = $data_rozhd === '' ? null : $data_rozhd;
                if ($dr !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dr)) {
                    $error = 'Дата рождения в формате ГГГГ-ММ-ДД.';
                } else {
                    $cols = ['familiya', 'imya', 'otchestvo', 'data_rozhd', 'email', 'parol'];
                    $vals = [
                        $familiya,
                        $imya,
                        $otchestvo === '' ? null : $otchestvo,
                        $dr,
                        $email,
                        $parol,
                    ];
                    if ($phoneCol !== null) {
                        $cols[] = $phoneCol;
                        $vals[] = $telefon;
                    }
                    $placeholders = implode(',', array_fill(0, count($cols), '?'));
                    $ins = $pdo->prepare(
                        'INSERT INTO STYLE_klienti (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')'
                    );
                    $ins->execute($vals);
                    $id = (int) $pdo->lastInsertId();
                    $_SESSION['user'] = [
                        'role' => 'client',
                        'id' => $id,
                        'name' => trim($familiya . ' ' . $imya),
                        'email' => $email,
                    ];
                    cart_merge_on_login($id);
                    flash_set('Регистрация прошла успешно.', 'success');
                    redirect('catalog.php');
                }
            }
        } catch (Throwable $e) {
            $error = 'Не удалось сохранить данные. Проверьте базу и таблицу STYLE_klienti.';
        }
    }
}

$pageTitle = 'Регистрация — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="container section auth-page">
    <h1 class="auth-page__title">Регистрация</h1>
    <div class="panel auth-panel">
        <?php if ($error !== ''): ?>
            <div class="flash flash-error" style="margin-bottom:16px"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Фамилия *<input type="text" name="familiya" required value="<?= h((string) ($_POST['familiya'] ?? '')) ?>"></label>
            <label>Имя *<input type="text" name="imya" required value="<?= h((string) ($_POST['imya'] ?? '')) ?>"></label>
            <label>Отчество<input type="text" name="otchestvo" value="<?= h((string) ($_POST['otchestvo'] ?? '')) ?>"></label>
            <label>Дата рождения<input type="date" name="data_rozhd" value="<?= h((string) ($_POST['data_rozhd'] ?? '')) ?>"></label>
            <label>Email *<input type="email" name="email" required value="<?= h((string) ($_POST['email'] ?? '')) ?>"></label>
            <?php if ($phoneCol !== null): ?>
                <label>Телефон *<input type="tel" name="telefon" required autocomplete="tel"
                    value="<?= h((string) ($_POST['telefon'] ?? '')) ?>" placeholder="+7 (999) 123-45-67"></label>
            <?php endif; ?>
            <label>Пароль *<input type="password" name="parol" required minlength="3" autocomplete="new-password"></label>
            <label>Пароль ещё раз *<input type="password" name="parol2" required minlength="3" autocomplete="new-password"></label>
            <button class="btn btn-primary" type="submit">Зарегистрироваться</button>
        </form>
        <p style="margin-top:16px;font-size:14px;color:var(--muted)">Уже есть аккаунт? <a class="btn btn-ghost btn-small" href="login.php">Войти</a></p>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
