<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
$pageTitle = 'Контакты — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="page-contacts">
    <section class="page-hero page-hero--contacts" data-reveal>
        <div class="container page-hero__grid">
            <div class="page-hero__content">
                <p class="page-hero__eyebrow">Связь с нами</p>
                <h1>Контакты</h1>
                <p class="page-hero__lead">Ответим на вопросы о заказе, размере или доставке. Выберите удобный канал связи.</p>
            </div>
            <div class="page-hero__media">
                <img src="<?= h(static_image_url('kontakty-hero.png')) ?>" alt="Контакты City Style" width="640" height="480" loading="eager">
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container contact-chips" data-reveal>
            <a class="contact-chip" href="tel:+79991112233">
                <span class="contact-chip__label">Телефон</span>
                <strong>+7 (999) 111-22-33</strong>
            </a>
            <a class="contact-chip" href="mailto:info@citystyle.ru">
                <span class="contact-chip__label">Email</span>
                <strong>info@citystyle.ru</strong>
            </a>
            <div class="contact-chip contact-chip--static">
                <span class="contact-chip__label">График</span>
                <strong>Пн–Пт 10:00–20:00</strong>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container contacts-layout">
            <div class="contacts-info" data-reveal>
                <article class="panel contacts-panel">
                    <h2>Как нас найти</h2>
                    <p class="contacts-address">г. Ярославль, ул. Стильная, 10</p>
                    <div class="contacts-map">
                        <img src="<?= h(static_image_url('kontakty-karta.png')) ?>" alt="Карта пункта выдачи" width="520" height="320" loading="lazy">
                    </div>
                    <ul class="contacts-list">
                        <li><span>Телефон</span> <a href="tel:+79991112233">+7 (999) 111-22-33</a></li>
                        <li><span>Email</span> <a href="mailto:info@citystyle.ru">info@citystyle.ru</a></li>
                        <li><span>Адрес</span> г. Ярославль, ул. Стильная, 10</li>
                    </ul>
                </article>
                <article class="panel contacts-panel contacts-panel--accent" data-reveal data-reveal-delay="100">
                    <h3>Служба поддержки</h3>
                    <p>Поможем с оформлением, статусом заказа и подбором размера.</p>
                    <img class="contacts-side-img" src="<?= h(static_image_url('kontakty-ofis.png')) ?>" alt="Офис — kontakty-ofis.png" width="400" height="240" loading="lazy">
                </article>
            </div>
            <section class="panel contacts-form-panel" data-reveal data-reveal-delay="80">
                <h2>Форма обратной связи</h2>
                <p class="contacts-form-hint">Демонстрационная форма (отправка не подключена).</p>
                <div class="contacts-form-body">
                    <label>Ваше имя<input type="text" placeholder="Введите имя"></label>
                    <label>Email<input type="email" placeholder="mail@example.com"></label>
                    <label class="contacts-form-message">Сообщение<textarea rows="8" placeholder="Ваш вопрос"></textarea></label>
                </div>
                <button class="btn btn-primary contacts-form-submit" type="button">Отправить</button>
            </section>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
