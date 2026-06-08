<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

require_client();

cart_buy_now_clear();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $tid = (int) ($_POST['id_tovar'] ?? 0);
    if ($tid > 0) {
        cart_set_qty($tid, 0);
        flash_set('Товар удалён из корзины.', 'success');
    }
    redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $pdo = db();
    foreach ($_POST['qty'] ?? [] as $idStr => $q) {
        $tid = (int) $idStr;
        $q = (int) $q;
        if ($tid <= 0) {
            continue;
        }
        if (!stock_in_stock($pdo, $tid)) {
            continue;
        }
        $q = stock_cap_qty($pdo, $tid, $q);
        cart_set_qty($tid, $q);
    }
    flash_set('Корзина обновлена.', 'success');
    redirect('cart.php');
}

$pageTitle = 'Корзина — City Style';
require __DIR__ . '/includes/header.php';

$pdo = db();
$built = cart_build_lines($pdo, cart_items());
$lines = $built['available'];
$unavailable = $built['unavailable'];
$sum = 0.0;
foreach ($lines as $row) {
    $sum += $row['line'];
}
$hasAny = $lines !== [] || $unavailable !== [];
?>

<main class="container section">
    <h1>Корзина</h1>
    <?php if (!$hasAny): ?>
        <p>Корзина пуста. <a class="btn btn-primary btn-small" href="catalog.php">Перейти в каталог</a></p>
    <?php else: ?>
        <?php if ($unavailable): ?>
            <section class="panel cart-unavailable-block">
                <h3>Недоступно сейчас</h3>
                <ul class="simple-list cart-lines">
                    <?php foreach ($unavailable as $row): ?>
                        <?php
                        $tid = (int) $row['id'];
                        $t = $row['t'];
                        $img = $t ? product_image_url($t['izobrazhenie'] ?? null) : null;
                        ?>
                        <li class="cart-line cart-line--out">
                            <?php if ($img): ?>
                                <img class="cart-line__img" src="<?= h($img) ?>" alt="">
                            <?php else: ?>
                                <div class="photo-placeholder cart-line__img">Нет фото</div>
                            <?php endif; ?>
                            <div class="cart-line__info">
                                <strong><?= h($row['nazvanie']) ?></strong>
                                <p class="cart-line__out-msg">Товар закончился. Скоро поступит в продажу.</p>
                                <a class="btn btn-small btn-ghost" href="catalog.php">Перейти в каталог</a>
                            </div>
                            <span class="cart-line__qty-static">× <?= (int) $row['qty'] ?></span>
                            <form method="post" class="cart-line__remove">
                                <input type="hidden" name="id_tovar" value="<?= $tid ?>">
                                <button class="btn btn-ghost" type="submit" name="remove" value="1">Убрать</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($lines): ?>
        <form method="post" id="cart-update-form">
            <input type="hidden" name="update" value="1">
        </form>
        <div class="cart-grid">
            <section class="panel">
                <h3>К оформлению</h3>
                <ul class="simple-list cart-lines">
                    <?php foreach ($lines as $row): ?>
                        <?php
                        $t = $row['t'];
                        $img = product_image_url($t['izobrazhenie'] ?? null);
                        $tid = (int) $t['id_tovar'];
                        $maxQ = stock_cap_qty($pdo, $tid, 99);
                        ?>
                        <li class="cart-line">
                            <?php if ($img): ?>
                                <img class="cart-line__img" src="<?= h($img) ?>" alt="">
                            <?php else: ?>
                                <div class="photo-placeholder cart-line__img">Нет фото</div>
                            <?php endif; ?>
                            <div class="cart-line__info">
                                <a href="product.php?id=<?= $tid ?>"><strong><?= h($t['nazvanie']) ?></strong></a>
                                <div class="cart-line__price"><?= h(format_price($t['tsena'])) ?> за шт.</div>
                                <?php if (stock_enabled($pdo) && $maxQ < 99): ?>
                                    <div class="cart-line__stock-hint">В наличии: <?= $maxQ ?> шт.</div>
                                <?php endif; ?>
                            </div>
                            <label class="cart-line__qty">Кол-во
                                <input type="number" form="cart-update-form" name="qty[<?= $tid ?>]" value="<?= (int) $row['qty'] ?>" min="1" max="<?= $maxQ ?>">
                            </label>
                            <form method="post" class="cart-line__remove" onsubmit="return confirm('Удалить этот товар из корзины?');">
                                <input type="hidden" name="id_tovar" value="<?= $tid ?>">
                                <button class="btn btn-ghost" type="submit" name="remove" value="1">Удалить</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="btn btn-ghost" type="submit" form="cart-update-form">Пересчитать</button>
            </section>
            <aside class="panel">
                <h3>Итого</h3>
                <p>Товары: <?= h(format_price($sum)) ?></p>
                <p>Доставка: уточняется при оформлении</p>
                <p><strong>К оплате: <?= h(format_price($sum)) ?></strong></p>
                <a class="btn btn-primary" href="checkout.php">Перейти к оформлению</a>
            </aside>
        </div>
        <?php elseif ($unavailable): ?>
            <p style="margin-top:16px"><a class="btn btn-primary" href="catalog.php">Выбрать товары в каталоге</a></p>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
