<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

$pdo = db();
$builderData = fetch_outfit_builder_items($pdo);

$pageTitle = 'Подбор образа — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="container section">
    <div class="page-title">
        <h1>Подбор образа</h1>
        <p>Листайте товары из каталога и соберите комплект: верх, низ и обувь.</p>
    </div>
    <?php if (!$builderData['top'] && !$builderData['bottom'] && !$builderData['shoes']): ?>
        <div class="panel flash-info">
            <p>В каталоге пока нет товаров для образа (нужны категории вроде футболок, джинсов, обуви и остаток на складе).</p>
            <p><a class="btn btn-primary btn-small" href="catalog.php">Перейти в каталог</a></p>
        </div>
    <?php else: ?>
    <div class="builder-layout">
        <div class="builder-carousels">
            <?php
            $slots = [
                'top' => 'Верх',
                'bottom' => 'Низ',
                'shoes' => 'Обувь',
            ];
            foreach ($slots as $key => $label):
                ?>
            <article class="carousel-card" data-carousel="<?= h($key) ?>">
                <h3><?= h($label) ?></h3>
                <?php if (!$builderData[$key]): ?>
                    <p class="builder-empty">Нет товаров в этой категории.</p>
                <?php else: ?>
                <div class="carousel-controls">
                    <button class="nav-btn" type="button" data-action="prev" aria-label="Предыдущий">&#8592;</button>
                    <div class="carousel-item">
                        <a class="carousel-item__link" data-role="link" href="#">
                            <div class="media-frame media-frame--carousel is-empty" data-role="frame">
                                <img class="media-frame__img" data-role="img" src="" alt="">
                                <div class="media-frame__empty" data-role="ph">Нет фото</div>
                            </div>
                        </a>
                        <p class="item-name" data-role="name"></p>
                        <p class="item-price" data-role="price"></p>
                    </div>
                    <button class="nav-btn" type="button" data-action="next" aria-label="Следующий">&#8594;</button>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <aside class="builder-summary">
            <h3 class="builder-summary__title">Ваш образ</h3>
            <div class="outfit-look" id="outfitLook">
                <p class="outfit-look__hint" id="outfitLookHint">Листайте категории слева — здесь соберётся образ</p>
                <div class="outfit-look__stack" id="outfitLookStack" hidden>
                    <div class="outfit-slot is-empty" data-outfit-layer="top">
                        <span class="outfit-slot__label">Верх</span>
                        <div class="outfit-slot__frame">
                            <img class="outfit-slot__img" data-outfit-img="top" src="" alt="">
                            <span class="outfit-slot__placeholder">выберите</span>
                        </div>
                    </div>
                    <div class="outfit-slot is-empty" data-outfit-layer="bottom">
                        <span class="outfit-slot__label">Низ</span>
                        <div class="outfit-slot__frame">
                            <img class="outfit-slot__img" data-outfit-img="bottom" src="" alt="">
                            <span class="outfit-slot__placeholder">выберите</span>
                        </div>
                    </div>
                    <div class="outfit-slot is-empty" data-outfit-layer="shoes">
                        <span class="outfit-slot__label">Обувь</span>
                        <div class="outfit-slot__frame">
                            <img class="outfit-slot__img" data-outfit-img="shoes" src="" alt="">
                            <span class="outfit-slot__placeholder">выберите</span>
                        </div>
                    </div>
                </div>
                <p class="outfit-look__note" id="outfitLookNote" hidden>Образ собран из выбранных позиций каталога.</p>
            </div>
            <ul>
                <li><span>Верх:</span><span data-summary="top">—</span></li>
                <li><span>Низ:</span><span data-summary="bottom">—</span></li>
                <li><span>Обувь:</span><span data-summary="shoes">—</span></li>
            </ul>
            <p class="total">Итого: <strong data-total>0 ₽</strong></p>
            <div class="stack-buttons">
                <?php if (is_client()): ?>
                    <form method="post" action="outfit_add_to_cart.php" id="outfitAddForm">
                        <input type="hidden" name="id_top" value="0" data-outfit-id="top">
                        <input type="hidden" name="id_bottom" value="0" data-outfit-id="bottom">
                        <input type="hidden" name="id_shoes" value="0" data-outfit-id="shoes">
                        <input type="hidden" name="redirect" value="cart.php">
                        <button class="btn btn-primary" type="submit">Добавить в корзину</button>
                    </form>
                    <a class="btn btn-ghost" href="cart.php">Открыть корзину</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php?redirect=<?= rawurlencode('outfit-builder.php') ?>">Войти для добавления в корзину</a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
    <?php endif; ?>
</main>

<?php if ($builderData['top'] || $builderData['bottom'] || $builderData['shoes']): ?>
<script>
window.OUTFIT_BUILDER_DATA = <?= json_encode($builderData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
