<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

$pdo = db();
$phoneCol = client_phone_column($pdo);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM STYLE_klienti WHERE id_klient = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id_klient'] ?? 0);
    $fam = trim((string) ($_POST['familiya'] ?? ''));
    $im = trim((string) ($_POST['imya'] ?? ''));
    $otch = trim((string) ($_POST['otchestvo'] ?? ''));
    $dr = trim((string) ($_POST['data_rozhd'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telefon = trim((string) ($_POST['telefon'] ?? ''));
    $parol = (string) ($_POST['parol'] ?? '');

    if ($fam === '' || $im === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('Проверьте ФИО и email.', 'error');
        redirect($id > 0 ? 'client_edit.php?id=' . $id : 'client_edit.php');
    }
    if ($phoneCol !== null && ($phoneErr = client_phone_validate($telefon, true)) !== null) {
        flash_set($phoneErr, 'error');
        redirect($id > 0 ? 'client_edit.php?id=' . $id : 'client_edit.php');
    }

    $drVal = $dr === '' ? null : $dr;
    try {
        if ($id > 0) {
            if ($parol !== '') {
                if ($phoneCol !== null) {
                    $pdo->prepare(
                        "UPDATE STYLE_klienti SET familiya=?, imya=?, otchestvo=?, data_rozhd=?, email=?, {$phoneCol}=?, parol=? WHERE id_klient=?"
                    )->execute([$fam, $im, $otch === '' ? null : $otch, $drVal, $email, $telefon, $parol, $id]);
                } else {
                    $pdo->prepare(
                        'UPDATE STYLE_klienti SET familiya=?, imya=?, otchestvo=?, data_rozhd=?, email=?, parol=? WHERE id_klient=?'
                    )->execute([$fam, $im, $otch === '' ? null : $otch, $drVal, $email, $parol, $id]);
                }
            } elseif ($phoneCol !== null) {
                $pdo->prepare(
                    "UPDATE STYLE_klienti SET familiya=?, imya=?, otchestvo=?, data_rozhd=?, email=?, {$phoneCol}=? WHERE id_klient=?"
                )->execute([$fam, $im, $otch === '' ? null : $otch, $drVal, $email, $telefon, $id]);
            } else {
                $pdo->prepare(
                    'UPDATE STYLE_klienti SET familiya=?, imya=?, otchestvo=?, data_rozhd=?, email=? WHERE id_klient=?'
                )->execute([$fam, $im, $otch === '' ? null : $otch, $drVal, $email, $id]);
            }
            flash_set('Клиент обновлён.', 'success');
        } else {
            if ($parol === '') {
                flash_set('Укажите пароль для нового клиента.', 'error');
                redirect('client_edit.php');
            }
            $cols = ['familiya', 'imya', 'otchestvo', 'data_rozhd', 'email', 'parol'];
            $vals = [$fam, $im, $otch === '' ? null : $otch, $drVal, $email, $parol];
            if ($phoneCol !== null) {
                $cols[] = $phoneCol;
                $vals[] = $telefon;
            }
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare(
                'INSERT INTO STYLE_klienti (' . implode(', ', $cols) . ') VALUES (' . $ph . ')'
            )->execute($vals);
            flash_set('Клиент создан.', 'success');
        }
        redirect('clients.php');
    } catch (Throwable $e) {
        flash_set('Ошибка: возможно, email уже занят.', 'error');
    }
}

$pageTitle = ($row ? 'Клиент' : 'Новый клиент') . ' — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <a class="btn btn-ghost btn-small staff-back" href="clients.php">← К списку клиентов</a>
    <h1><?= $row ? 'Редактирование клиента' : 'Новый клиент' ?></h1>
    <form method="post" class="panel" style="max-width:480px">
        <?php if ($row): ?>
            <input type="hidden" name="id_klient" value="<?= (int) $row['id_klient'] ?>">
        <?php endif; ?>
        <label>Фамилия *<input name="familiya" required value="<?= h((string) ($row['familiya'] ?? '')) ?>"></label>
        <label>Имя *<input name="imya" required value="<?= h((string) ($row['imya'] ?? '')) ?>"></label>
        <label>Отчество<input name="otchestvo" value="<?= h((string) ($row['otchestvo'] ?? '')) ?>"></label>
        <label>Дата рождения<input type="date" name="data_rozhd" value="<?= h((string) ($row['data_rozhd'] ?? '')) ?>"></label>
        <label>Email *<input type="email" name="email" required value="<?= h((string) ($row['email'] ?? '')) ?>"></label>
        <?php if ($phoneCol !== null): ?>
            <label>Телефон *<input type="tel" name="telefon" required autocomplete="tel"
                value="<?= h($row ? client_phone_from_row($row, $pdo) : (string) ($_POST['telefon'] ?? '')) ?>"
                placeholder="+7 (999) 123-45-67"></label>
        <?php endif; ?>
        <label>Пароль<?= $row ? ' (оставьте пустым, чтобы не менять)' : ' *' ?>
            <input type="password" name="parol" <?= $row ? '' : 'required' ?> autocomplete="new-password"></label>
        <button class="btn btn-primary" type="submit">Сохранить</button>
    </form>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
