<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';

require_staff();
require_staff_kind('manager');

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM STYLE_sotrudniki WHERE id_sotrudnik = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch() ?: null;
}

$dolzhnosti = $pdo->query('SELECT id_dolzhnost, nazvanie FROM STYLE_dolzhnost ORDER BY id_dolzhnost')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id_sotrudnik'] ?? 0);
    $fam = trim((string) ($_POST['familiya'] ?? ''));
    $im = trim((string) ($_POST['imya'] ?? ''));
    $otch = trim((string) ($_POST['otchestvo'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $parol = (string) ($_POST['parol'] ?? '');
    $dol = (int) ($_POST['dolzhnost'] ?? 0);

    if ($fam === '' || $im === '' || $email === '' || $dol <= 0) {
        flash_set('Заполните обязательные поля.', 'error');
        redirect($id > 0 ? 'employee_edit.php?id=' . $id : 'employee_edit.php');
    }

    try {
        if ($id > 0) {
            if ($parol !== '') {
                $pdo->prepare(
                    'UPDATE STYLE_sotrudniki SET familiya=?, imya=?, otchestvo=?, email=?, parol=?, dolzhnost=? WHERE id_sotrudnik=?'
                )->execute([$fam, $im, $otch === '' ? null : $otch, $email, $parol, $dol, $id]);
            } else {
                $pdo->prepare(
                    'UPDATE STYLE_sotrudniki SET familiya=?, imya=?, otchestvo=?, email=?, dolzhnost=? WHERE id_sotrudnik=?'
                )->execute([$fam, $im, $otch === '' ? null : $otch, $email, $dol, $id]);
            }
            flash_set('Сотрудник обновлён.', 'success');
        } else {
            if ($parol === '') {
                flash_set('Укажите пароль.', 'error');
                redirect('employee_edit.php');
            }
            $pdo->prepare(
                'INSERT INTO STYLE_sotrudniki (familiya, imya, otchestvo, email, parol, dolzhnost) VALUES (?,?,?,?,?,?)'
            )->execute([$fam, $im, $otch === '' ? null : $otch, $email, $parol, $dol]);
            flash_set('Сотрудник добавлен.', 'success');
        }
        redirect('employees.php');
    } catch (Throwable $e) {
        flash_set('Ошибка сохранения (email занят?).', 'error');
    }
}

$pageTitle = ($row ? 'Сотрудник' : 'Новый сотрудник') . ' — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';
?>

<main class="staff-main">
    <a class="btn btn-ghost btn-small staff-back" href="employees.php">← К списку сотрудников</a>
    <h1><?= $row ? 'Редактирование' : 'Новый сотрудник' ?></h1>
    <form method="post" class="panel" style="max-width:480px">
        <?php if ($row): ?>
            <input type="hidden" name="id_sotrudnik" value="<?= (int) $row['id_sotrudnik'] ?>">
        <?php endif; ?>
        <label>Фамилия *<input name="familiya" required value="<?= h((string) ($row['familiya'] ?? '')) ?>"></label>
        <label>Имя *<input name="imya" required value="<?= h((string) ($row['imya'] ?? '')) ?>"></label>
        <label>Отчество<input name="otchestvo" value="<?= h((string) ($row['otchestvo'] ?? '')) ?>"></label>
        <label>Email *<input type="email" name="email" required value="<?= h((string) ($row['email'] ?? '')) ?>"></label>
        <label>Пароль<?= $row ? ' (пусто = не менять)' : ' *' ?>
            <input type="password" name="parol" <?= $row ? '' : 'required' ?>></label>
        <label>Должность *
            <select name="dolzhnost" required>
                <?php foreach ($dolzhnosti as $d): ?>
                    <option value="<?= (int) $d['id_dolzhnost'] ?>" <?= ((int) ($row['dolzhnost'] ?? 0) === (int) $d['id_dolzhnost']) ? 'selected' : '' ?>><?= h($d['nazvanie']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Сохранить</button>
    </form>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
