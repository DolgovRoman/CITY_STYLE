<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

$pageTitle = 'City Style — интернет-магазин одежды';
require __DIR__ . '/includes/header.php';

$pdo = db();
$hits = fetch_catalog_groups($pdo, null, 4);
?>

<main class="page-home">
    <section class="hero-home hero-home--cover">
        <img class="hero-home__bg" src="<?= h(static_image_url('glavnaya.png')) ?>" alt="" decoding="async" fetchpriority="high">
        <div class="hero-home__overlay" aria-hidden="true"></div>
        <div class="container hero-home__inner">
            <div class="hero-home__content">
                <p class="hero-home__tag"><span class="hero-home__tag-line"></span>Новая коллекция</p>
                <h1 class="hero-home__title">
                    Твой стиль. Твой образ.<br>
                    <span class="hero-home__accent">Твой City Style.</span>
                </h1>
                <p class="hero-home__lead">Собирай полноценные образы онлайн: верх, низ и обувь в несколько кликов.</p>
                <div class="hero-home__actions">
                    <a class="btn btn-primary btn-hero" href="outfit-builder.php">Собрать образ <span class="btn-arrow" aria-hidden="true">→</span></a>
                    <a class="btn btn-ghost btn-hero" href="catalog.php">Перейти в каталог</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="features-grid">
                <article class="feature-card" data-reveal data-reveal-delay="0">
                    <div class="feature-card__icon" aria-hidden="true">01</div>
                    <h3>Актуальный каталог</h3>
                    <p>Базовые модели и сезонные новинки — удобные фильтры и карточки товаров.</p>
                    <a class="feature-card__link" href="catalog.php">Смотреть каталог →</a>
                </article>
                <article class="feature-card" data-reveal data-reveal-delay="80">
                    <div class="feature-card__icon" aria-hidden="true">02</div>
                    <h3>Подбор образа</h3>
                    <p>Соберите лук из верха, низа и обуви на отдельной странице конструктора.</p>
                    <a class="feature-card__link" href="outfit-builder.php">Собрать образ →</a>
                </article>
                <article class="feature-card" data-reveal data-reveal-delay="160">
                    <div class="feature-card__icon" aria-hidden="true">03</div>
                    <h3>Бесплатная доставка</h3>
                    <p>Курьер по Москве или пункт выдачи — доставка всегда бесплатно.</p>
                    <a class="feature-card__link" href="delivery-payment.php">Подробнее →</a>
                </article>
            </div>
        </div>
    </section>

    <section class="section promo-banner" data-reveal>
        <div class="container promo-banner__inner">
            <div class="promo-banner__text">
                <p class="promo-banner__label">City Style</p>
                <h2>Готовый образ за несколько минут</h2>
                <p>Откройте конструктор, выберите верх, низ и обувь — и переходите к покупке.</p>
                <a class="btn btn-primary" href="outfit-builder.php">Открыть подбор образа</a>
            </div>
            <div class="promo-banner__media">
                <img src="<?= h(static_image_url('glavnaya-banner.png')) ?>" alt="Подбор образа City Style" width="480" height="360" loading="lazy">
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <h2>Хиты продаж</h2>
                    <p class="section-head__muted">Популярные позиции из каталога</p>
                </div>
                <a class="btn btn-ghost btn-small" href="catalog.php">Смотреть все</a>
            </div>
            <div class="cards-grid cards-grid--home">
                <?php foreach ($hits as $i => $g): ?>
                    <?php
                    $img = product_image_url($g['izobrazhenie'] ?? null);
                    $minP = (float) $g['min_tsena'];
                    $maxP = (float) $g['max_tsena'];
                    $priceLabel = $minP === $maxP ? format_price($minP) : ('от ' . format_price($minP));
                    ?>
                    <article class="product-card product-card--animated" data-reveal data-reveal-delay="<?= (int) ($i * 60) ?>">
                        <div class="media-frame media-frame--card<?= $img ? '' : ' is-empty' ?>">
                            <?php if ($img): ?>
                                <img class="media-frame__img product-card-img" src="<?= h($img) ?>" alt="<?= h($g['nazvanie']) ?>" loading="lazy">
                            <?php else: ?>
                                <img class="media-frame__img product-card-img" alt="" hidden>
                            <?php endif; ?>
                            <div class="media-frame__empty">Нет фото</div>
                        </div>
                        <h3><?= h($g['nazvanie']) ?></h3>
                        <p class="product-card__price"><?= h($priceLabel) ?></p>
                        <a class="btn btn-small btn-primary" href="product.php?id=<?= (int) $g['id_tovar'] ?>&back=<?= rawurlencode('index.php') ?>">Подробнее</a>
                    </article>
                <?php endforeach; ?>
                <?php if (!$hits): ?>
                    <p class="catalog-empty-msg" data-reveal>Товары появятся после загрузки каталога в базу.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
