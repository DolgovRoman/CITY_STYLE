<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
$pageTitle = 'Доставка и оплата — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="page-delivery">
    <section class="page-hero page-hero--delivery" data-reveal>
        <div class="container page-hero__grid">
            <div class="page-hero__content">
                <p class="page-hero__eyebrow">Условия покупки</p>
                <h1>Доставка и оплата</h1>
                <p class="page-hero__lead">Оформляйте заказ спокойно: доставка в City Style <strong>всегда бесплатная</strong>, способы оплаты — на выбор.</p>
                <span class="badge-free">Бесплатная доставка на все заказы</span>
            </div>
            <div class="page-hero__media">
                <img src="<?= h(static_image_url('dostavka-hero.png')) ?>" alt="Доставка City Style" width="640" height="480" loading="eager">
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container delivery-highlight" data-reveal>
            <div class="delivery-highlight__icon" aria-hidden="true">✓</div>
            <div>
                <h2>0 ₽ за доставку</h2>
                <p>Независимо от суммы заказа и выбранного способа — курьер или пункт выдачи. Никаких скрытых доплат за пересылку.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <h2>Способы доставки</h2>
                <p class="section-head__muted">Выберите удобный вариант при оформлении</p>
            </div>
            <div class="delivery-cards">
                <article class="delivery-card" data-reveal data-reveal-delay="0">
                    <div class="delivery-card__img">
                        <img src="<?= h(static_image_url('dostavka-kurer.png')) ?>" alt="Курьер — dostavka-kurer.png" width="420" height="260" loading="lazy">
                    </div>
                    <div class="delivery-card__body">
                        <h3><?= h(DELIVERY_TIP_COURIER) ?></h3>
                        <p>Курьер привозит заказ по указанному адресу в городе <strong><?= h(DELIVERY_COURIER_CITY) ?></strong>.</p>
                        <ul class="delivery-card__meta">
                            <li><span>Срок</span> 1–3 рабочих дня</li>
                            <li><span>Стоимость</span> бесплатно</li>
                        </ul>
                    </div>
                </article>
                <article class="delivery-card" data-reveal data-reveal-delay="120">
                    <div class="delivery-card__img">
                        <img src="<?= h(static_image_url('dostavka-pvz.png')) ?>" alt="ПВЗ — dostavka-pvz.png" width="420" height="260" loading="lazy">
                    </div>
                    <div class="delivery-card__body">
                        <h3><?= h(DELIVERY_TIP_PVZ) ?></h3>
                        <p>Заказ передаётся в пункт выдачи. После прибытия вы получите трек-номер для отслеживания.</p>
                        <ul class="delivery-card__meta">
                            <li><span>Срок</span> 2–5 рабочих дней</li>
                            <li><span>Стоимость</span> бесплатно</li>
                        </ul>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--muted">
        <div class="container">
            <div class="section-head section-head--center" data-reveal>
                <h2>Как проходит заказ</h2>
            </div>
            <ol class="steps-timeline">
                <li class="steps-timeline__item" data-reveal data-reveal-delay="0">
                    <span class="steps-timeline__num">1</span>
                    <h3>Корзина и оформление</h3>
                    <p>Добавьте товары, укажите адрес и способ доставки в личном кабинете.</p>
                </li>
                <li class="steps-timeline__item" data-reveal data-reveal-delay="80">
                    <span class="steps-timeline__num">2</span>
                    <h3>Оплата</h3>
                    <p>Оплатите заказ удобным способом — данные защищены.</p>
                </li>
                <li class="steps-timeline__item" data-reveal data-reveal-delay="160">
                    <span class="steps-timeline__num">3</span>
                    <h3>Доставка</h3>
                    <p>Курьер или ПВЗ — статус заказа отображается в разделе «Мои заказы».</p>
                </li>
                <li class="steps-timeline__item" data-reveal data-reveal-delay="240">
                    <span class="steps-timeline__num">4</span>
                    <h3>Получение</h3>
                    <p>Заберите покупку и при необходимости повторите заказ в один клик.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <h2>Способы оплаты</h2>
            </div>
            <div class="pay-grid">
                <article class="pay-card" data-reveal data-reveal-delay="0">
                    <div class="pay-card__img">
                        <img src="<?= h(static_image_url('oplata-karta.png')) ?>" alt="Карта — oplata-karta.png" width="320" height="200" loading="lazy">
                    </div>
                    <h3>Банковская карта</h3>
                    <p>Visa, Mastercard, «Мир» — оплата на сайте при оформлении.</p>
                </article>
                <article class="pay-card" data-reveal data-reveal-delay="80">
                    <div class="pay-card__img">
                        <img src="<?= h(static_image_url('oplata-sbp.png')) ?>" alt="СБП — oplata-sbp.png" width="320" height="200" loading="lazy">
                    </div>
                    <h3>СБП</h3>
                    <p>Быстрый перевод через приложение банка по QR или ссылке.</p>
                </article>
                <article class="pay-card" data-reveal data-reveal-delay="160">
                    <div class="pay-card__img">
                        <img src="<?= h(static_image_url('oplata-pri-poluchenii.png')) ?>" alt="При получении — oplata-pri-poluchenii.png" width="320" height="200" loading="lazy">
                    </div>
                    <h3>При получении</h3>
                    <p>Доступно для отдельных регионов — уточняйте при оформлении.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section cta-strip" data-reveal>
        <div class="container cta-strip__inner">
            <h2>Остались вопросы?</h2>
            <p>Напишите нам — ответим в рабочее время.</p>
            <a class="btn btn-primary" href="contacts.php">Контакты</a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
