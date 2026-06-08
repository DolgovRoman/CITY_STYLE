<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/staff_auth.php';
require __DIR__ . '/../../includes/products.php';

require_staff();
require_staff_kind('employee');

$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM STYLE_tovary WHERE id_tovar = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch() ?: null;
}

$kat = $pdo->query('SELECT id_kategoriya, nazvanie FROM STYLE_kategorii ORDER BY nazvanie')->fetchAll();
$raz = $pdo->query('SELECT id_razmer, nazvanie FROM STYLE_razmer ORDER BY id_razmer')->fetchAll();
$cvet = $pdo->query('SELECT id_tcvet, nazvanie FROM STYLE_tcvet ORDER BY id_tcvet')->fetchAll();

$existingPhotos = product_image_filenames($row['izobrazhenie'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id_tovar'] ?? 0);
    $nazvanie = trim((string) ($_POST['nazvanie'] ?? ''));
    $articul = trim((string) ($_POST['articul'] ?? ''));
    $opisanie = trim((string) ($_POST['opisanie'] ?? ''));
    $sostav = trim((string) ($_POST['sostav'] ?? ''));
    $tsena = (float) str_replace(',', '.', (string) ($_POST['tsena'] ?? '0'));
    $kategoriya = (int) ($_POST['kategoriya'] ?? 0);
    $razmer = (int) ($_POST['razmer'] ?? 0);
    $tsvet = (int) ($_POST['tsvet'] ?? 0);
    $izobrazhenieManual = trim((string) ($_POST['izobrazhenie'] ?? ''));
    $existingStored = null;
    if ($id > 0) {
        $prevSt = $pdo->prepare('SELECT izobrazhenie FROM STYLE_tovary WHERE id_tovar = ? LIMIT 1');
        $prevSt->execute([$id]);
        $prevRow = $prevSt->fetch();
        $existingStored = $prevRow ? (string) ($prevRow['izobrazhenie'] ?? '') : null;
    }

    if ($nazvanie === '' || $tsena <= 0 || $kategoriya <= 0 || $razmer <= 0 || $tsvet <= 0) {
        flash_set('Заполните название, цену, категорию, размер и цвет.', 'error');
        redirect($id > 0 ? 'product_edit.php?id=' . $id : 'product_edit.php');
    }

    [$izobrazhenie, $uploadFailed] = product_resolve_izobrazhenie_from_request($articul, $izobrazhenieManual, $existingStored);
    if ($uploadFailed && $izobrazhenie === null) {
        flash_set('Не удалось загрузить фото. Допустимы JPG, PNG, WebP или GIF.', 'error');
        redirect($id > 0 ? 'product_edit.php?id=' . $id : 'product_edit.php');
    }

    try {
        if ($id > 0) {
            $pdo->prepare(
                'UPDATE STYLE_tovary SET nazvanie=?, articul=?, opisanie=?, sostav=?, tsena=?, tsvet=?, razmer=?, kategoriya=?, izobrazhenie=?
                 WHERE id_tovar=?'
            )->execute([
                $nazvanie,
                $articul === '' ? null : $articul,
                $opisanie === '' ? null : $opisanie,
                $sostav === '' ? null : $sostav,
                $tsena,
                $tsvet,
                $razmer,
                $kategoriya,
                $izobrazhenie,
                $id,
            ]);
            flash_set('Товар обновлён.', 'success');
        } else {
            $pdo->prepare(
                'INSERT INTO STYLE_tovary (nazvanie, articul, opisanie, sostav, tsena, tsvet, razmer, kategoriya, izobrazhenie)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([
                $nazvanie,
                $articul === '' ? null : $articul,
                $opisanie === '' ? null : $opisanie,
                $sostav === '' ? null : $sostav,
                $tsena,
                $tsvet,
                $razmer,
                $kategoriya,
                $izobrazhenie,
            ]);
            stock_ensure_row($pdo, (int) $pdo->lastInsertId());
            flash_set('Товар добавлен.', 'success');
        }
        redirect('products.php');
    } catch (Throwable $e) {
        flash_set('Ошибка сохранения.', 'error');
    }
}

$pageTitle = ($row ? 'Редактирование' : 'Новый товар') . ' — City Style';
$staffHome = '../';
require __DIR__ . '/../../includes/layout_staff_top.php';
require __DIR__ . '/../_nav.php';

$photoLabels = ['Фото 1 (главное)', 'Фото 2', 'Фото 3'];
?>

<main class="staff-main">
    <a class="btn btn-ghost btn-small staff-back" href="products.php">← К списку товаров</a>
    <h1><?= $row ? 'Редактирование SKU' : 'Новый SKU' ?></h1>
    <form method="post" class="panel product-edit-form" enctype="multipart/form-data">
        <?php if ($row): ?>
            <input type="hidden" name="id_tovar" value="<?= (int) $row['id_tovar'] ?>">
        <?php endif; ?>
        <label>Название модели *<input name="nazvanie" required value="<?= h((string) ($row['nazvanie'] ?? ($_POST['nazvanie'] ?? ''))) ?>"></label>
        <label>Артикул<input name="articul" value="<?= h((string) ($row['articul'] ?? ($_POST['articul'] ?? ''))) ?>"></label>
        <label>Цена *<input name="tsena" type="number" step="0.01" required value="<?= h((string) ($row['tsena'] ?? ($_POST['tsena'] ?? ''))) ?>"></label>
        <label>Категория *
            <select name="kategoriya" required>
                <option value="">—</option>
                <?php foreach ($kat as $k): ?>
                    <option value="<?= (int) $k['id_kategoriya'] ?>" <?= ((int) ($row['kategoriya'] ?? 0) === (int) $k['id_kategoriya']) ? 'selected' : '' ?>><?= h($k['nazvanie']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Размер *
            <select name="razmer" required>
                <?php foreach ($raz as $r): ?>
                    <option value="<?= (int) $r['id_razmer'] ?>" <?= ((int) ($row['razmer'] ?? 0) === (int) $r['id_razmer']) ? 'selected' : '' ?>><?= h($r['nazvanie']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Цвет *
            <select name="tsvet" required>
                <?php foreach ($cvet as $c): ?>
                    <option value="<?= (int) $c['id_tcvet'] ?>" <?= ((int) ($row['tsvet'] ?? 0) === (int) $c['id_tcvet']) ? 'selected' : '' ?>><?= h($c['nazvanie']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset class="product-photos-fieldset">
            <legend>Фотографии товара</legend>
            <p class="staff-hint">До 3 файлов (JPG, PNG, WebP, GIF). Сохраняются в <code>images/catalog/</code>. В базе — имена через запятую; первое фото — главное в каталоге.</p>
            <?php for ($i = 1; $i <= 3; ++$i): ?>
                <?php
                $current = $existingPhotos[$i - 1] ?? '';
                $previewUrl = $current !== '' ? product_staff_catalog_image_url($current) : null;
                ?>
                <div class="product-photo-slot">
                    <label><?= h($photoLabels[$i - 1]) ?>
                        <input type="file" name="photo<?= $i ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                    </label>
                    <?php if ($current !== ''): ?>
                        <input type="hidden" name="keep_photo_<?= $i ?>" value="<?= h($current) ?>">
                        <div class="product-photo-slot__current">
                            <?php if ($previewUrl): ?>
                                <img src="<?= h($previewUrl) ?>" alt="" width="120" height="120" loading="lazy">
                            <?php endif; ?>
                            <span class="muted"><?= h($current) ?></span>
                            <span class="staff-hint">Оставьте поле пустым, чтобы сохранить это фото.</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </fieldset>

        <details class="product-photos-manual">
            <summary>Или указать имена файлов вручную</summary>
            <label>Имена в папке catalog
                <input name="izobrazhenie" value="<?= h((string) ($row['izobrazhenie'] ?? ($_POST['izobrazhenie'] ?? ''))) ?>" placeholder="main.jpg, photo2.jpg, photo3.jpg" autocomplete="off">
            </label>
            <p class="staff-hint">Используйте, если файлы уже лежат в <code>images/catalog/</code>. При загрузке файлов выше ручной ввод не нужен.</p>
        </details>

        <label>Описание<input name="opisanie" value="<?= h((string) ($row['opisanie'] ?? ($_POST['opisanie'] ?? ''))) ?>"></label>
        <label>Состав<input name="sostav" value="<?= h((string) ($row['sostav'] ?? ($_POST['sostav'] ?? ''))) ?>"></label>
        <button class="btn btn-primary" type="submit">Сохранить</button>
    </form>
</main>

<?php require __DIR__ . '/../../includes/layout_staff_bottom.php'; ?>
