<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
$pageTitle = 'О нас — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="page-about">
    <section class="page-hero page-hero--about" data-reveal>
        <div class="container page-hero__grid">
            <div class="page-hero__content">
                <p class="page-hero__eyebrow">О бренде</p>
                <h1>City Style — магазин для тех, кто ценит простоту и стиль</h1>
                <p class="page-hero__lead">Мы создаём удобный онлайн-опыт: от выбора вещей в каталоге до сборки готового образа и оформления заказа.</p>
            </div>
            <div class="page-hero__media">
                <img src="<?= h(static_image_url('o-nas-hero.png')) ?>" alt="Интерьер City Style" width="640" height="480" loading="eager">
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container stats-row">
            <div class="stat-card" data-reveal data-reveal-delay="0">
                <strong>100%</strong>
                <span>фокус на повседневном гардеробе</span>
            </div>
            <div class="stat-card" data-reveal data-reveal-delay="80">
                <strong>3</strong>
                <span>шага в подборе образа</span>
            </div>
            <div class="stat-card" data-reveal data-reveal-delay="160">
                <strong>0 ₽</strong>
                <span>доставка всегда бесплатно</span>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container split-block" data-reveal>
            <div class="split-block__media split-block__media--left">
                <img src="<?= h(static_image_url('o-nas-magazin.png')) ?>" alt="Витрина магазина — o-nas-magazin.png" width="560" height="420" loading="lazy">
            </div>
            <div class="split-block__text">
                <h2>Наша миссия</h2>
                <p>Помогать покупателю быстро собрать гармоничный комплект одежды без долгого поиска подходящих вещей в разных магазинах.</p>
                <p>City Style объединяет каталог, подбор образа и понятные условия доставки в одном сервисе.</p>
            </div>
        </div>
    </section>

    <section class="section section--muted">
        <div class="container">
            <div class="section-head section-head--center" data-reveal>
                <h2>Что мы предлагаем</h2>
                <p class="section-head__muted">Три опоры бренда</p>
            </div>
            <div class="about-cards">
                <article class="about-card" data-reveal data-reveal-delay="0">
                    <div class="about-card__img">
                        <img src="<?= h(static_image_url('o-nas-katalog.png')) ?>" alt="Каталог — o-nas-katalog.png" width="400" height="280" loading="lazy">
                    </div>
                    <h3>Актуальные коллекции</h3>
                    <p>Базовые модели и сезонные новинки с фильтрами по категории, размеру и цене.</p>
                </article>
                <article class="about-card" data-reveal data-reveal-delay="100">
                    <div class="about-card__img">
                        <img src="<?= h(static_image_url('o-nas-obraz.png')) ?>" alt="Подбор образа — o-nas-obraz.png" width="400" height="280" loading="lazy">
                    </div>
                    <h3>Подбор образа</h3>
                    <p>Интерактивный конструктор: верх, низ и обувь — готовый лук за несколько кликов.</p>
                </article>
                <article class="about-card" data-reveal data-reveal-delay="200">
                    <div class="about-card__img">
                        <img src="<?= h(static_image_url('o-nas-servis.png')) ?>" alt="Сервис — o-nas-servis.png" width="400" height="280" loading="lazy">
                    </div>
                    <h3>Забота о клиенте</h3>
                    <p>Личный кабинет, история заказов, бесплатная доставка и поддержка по контактам.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container split-block split-block--reverse" data-reveal>
            <div class="split-block__text">
                <h2>Философия стиля</h2>
                <p>Мы верим в лаконичные силуэты, нейтральную палитру и вещи, которые легко сочетать между собой.</p>
                <ul class="check-list">
                    <li>Качественные материалы и продуманные детали</li>
                    <li>Размерная сетка с понятной посадкой</li>
                    <li>Образы для города, работы и отдыха</li>
                </ul>
                <a class="btn btn-primary" href="catalog.php">Перейти в каталог</a>
            </div>
            <div class="split-block__media">
                <img src="<?= h(static_image_url('o-nas-stil.png')) ?>" alt="Стиль City Style — o-nas-stil.png" width="560" height="420" loading="lazy">
            </div>
        </div>
    </section>

    <section class="section cta-strip" data-reveal>
        <div class="container cta-strip__inner">
            <h2>Готовы обновить гардероб?</h2>
            <p>Откройте каталог или соберите образ на отдельной странице.</p>
            <div class="cta-strip__actions">
                <a class="btn btn-primary" href="catalog.php">Каталог</a>
                <a class="btn btn-ghost" href="outfit-builder.php">Подбор образа</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
