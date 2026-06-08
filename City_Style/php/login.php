<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/staff_auth.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $parol = (string) ($_POST['parol'] ?? '');
    $redirect = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? 'catalog.php');
    if ($redirect === '' || str_contains($redirect, "\n") || str_contains($redirect, '//')) {
        $redirect = 'catalog.php';
    }

    if ($email === '' || $parol === '') {
        $error = 'Введите email и пароль.';
    } else {
        try {
            $pdo = db();
            $stClient = $pdo->prepare(
                'SELECT id_klient, familiya, imya, email, parol FROM STYLE_klienti WHERE email = ? LIMIT 1'
            );
            $stClient->execute([$email]);
            $client = $stClient->fetch();
            if ($client && hash_equals((string) $client['parol'], $parol)) {
                $_SESSION['user'] = [
                    'role' => 'client',
                    'id' => (int) $client['id_klient'],
                    'name' => trim($client['familiya'] . ' ' . $client['imya']),
                    'email' => $client['email'],
                ];
                cart_merge_on_login((int) $client['id_klient']);
                flash_set('С возвращением!', 'success');
                redirect($redirect);
            }

            $stStaff = $pdo->prepare(
                'SELECT s.id_sotrudnik, s.familiya, s.imya, s.email, s.parol, s.dolzhnost AS dolzhnost_id,
                        d.nazvanie AS dolzhnost
                 FROM STYLE_sotrudniki s
                 INNER JOIN STYLE_dolzhnost d ON s.dolzhnost = d.id_dolzhnost
                 WHERE s.email = ? LIMIT 1'
            );
            $stStaff->execute([$email]);
            $staff = $stStaff->fetch();
            if ($staff && hash_equals((string) $staff['parol'], $parol)) {
                $dolzh = (string) ($staff['dolzhnost'] ?? '');
                $_SESSION['user'] = [
                    'role' => 'staff',
                    'id' => (int) $staff['id_sotrudnik'],
                    'name' => trim($staff['familiya'] . ' ' . $staff['imya']),
                    'email' => $staff['email'],
                    'dolzhnost' => $dolzh,
                    'dolzhnost_id' => (int) ($staff['dolzhnost_id'] ?? 0),
                    'staff_kind' => staff_kind_from_dolzhnost_name($dolzh),
                ];
                flash_set('Добро пожаловать, ' . trim($staff['familiya'] . ' ' . $staff['imya']) . '!', 'success');
                if ($redirect === 'catalog.php' || $redirect === 'index.php' || $redirect === '') {
                    $redirect = 'staff/index.php';
                }
                redirect($redirect);
            }

            $error = 'Неверный email или пароль.';
        } catch (Throwable $e) {
            $error = 'Ошибка подключения к базе. Проверьте config.php и что MySQL запущен.';
        }
    }
}

$pageTitle = 'Вход — City Style';
require __DIR__ . '/includes/header.php';
$redirectGet = (string) ($_GET['redirect'] ?? 'catalog.php');
if ($redirectGet === '' || str_contains($redirectGet, '//')) {
    $redirectGet = 'catalog.php';
}
?>

<main class="container section auth-page">
    <h1 class="auth-page__title">Вход</h1>
    <div class="panel auth-panel">
        <?php if ($error !== ''): ?>
            <div class="flash flash-error" style="margin-bottom:16px"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php?redirect=<?= h(rawurlencode($redirectGet)) ?>">
            <input type="hidden" name="redirect" value="<?= h($redirectGet) ?>">
            <label>Email<input type="email" name="email" required autocomplete="username" value="<?= h((string) ($_POST['email'] ?? '')) ?>"></label>
            <label>Пароль<input type="password" name="parol" required autocomplete="current-password"></label>
            <button class="btn btn-primary" type="submit">Войти</button>
        </form>
        <p style="margin-top:16px;font-size:14px;color:var(--muted)">Нет аккаунта? <a class="btn btn-ghost btn-small" href="register.php">Зарегистрироваться</a></p>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
