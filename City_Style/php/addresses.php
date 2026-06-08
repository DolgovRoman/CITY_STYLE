<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

require_client();

$uid = (int) (current_user()['id'] ?? 0);
$pdo = db();
$error = '';

if (!client_addresses_table_exists($pdo)) {
    $pageTitle = 'Мои адреса — City Style';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container section"><div class="panel flash-info">';
    echo '<p>Функция адресов недоступна: выполните скрипт <code>php/sql/schema_client_addresses.sql</code> в базе STYLE.</p>';
    echo '<p class="page-back"><a class="btn btn-ghost btn-small" href="account.php">← В личный кабинет</a></p></div></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_adres'])) {
    $aid = (int) ($_POST['id_adres'] ?? 0);
    $err = delete_client_address($pdo, $aid, $uid);
    if ($err !== null) {
        $error = $err;
    } else {
        flash_set('Адрес удалён.', 'success');
        redirect('addresses.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_adres'])) {
    $postTip = delivery_tip_normalize((string) ($_POST['tip_dostavki'] ?? ''));
    $postKv = trim((string) ($_POST['kvartira'] ?? ''));
    if ($postTip === DELIVERY_TIP_PVZ) {
        $postKv = '';
    }
    $err = save_client_address(
        $pdo,
        $uid,
        (string) ($_POST['gorod'] ?? ''),
        (string) ($_POST['ulica'] ?? ''),
        (string) ($_POST['dom'] ?? ''),
        $postKv,
        (string) ($_POST['nazvanie'] ?? ''),
        $postTip
    );
    if ($err !== null) {
        $error = $err;
    } else {
        flash_set('Адрес сохранён.', 'success');
        redirect('addresses.php');
    }
}

$addresses = fetch_client_addresses($pdo, $uid);

$formTip = delivery_tip_normalize((string) ($_POST['tip_dostavki'] ?? delivery_tip_options()[0] ?? ''));

$pageTitle = 'Мои адреса — City Style';
require __DIR__ . '/includes/header.php';
?>

<main class="container section">
    <h1>Мои адреса</h1>
    <p class="page-back"><a class="btn btn-ghost btn-small" href="account.php">← Личный кабинет</a></p>
    <?php if ($error !== ''): ?>
        <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($addresses): ?>
        <div class="panel" style="margin-bottom:20px">
            <h3>Сохранённые адреса</h3>
            <ul class="simple-list">
                <?php foreach ($addresses as $a): ?>
                    <li class="address-list-item">
                        <span><?= h(client_address_label($a)) ?></span>
                        <form method="post" class="address-delete-form" onsubmit="return confirm('Удалить этот адрес?');">
                            <input type="hidden" name="delete_adres" value="1">
                            <input type="hidden" name="id_adres" value="<?= (int) $a['id_adres'] ?>">
                            <button type="submit" class="btn btn-ghost btn-small">Удалить</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h3>Добавить адрес</h3>
        <form method="post" class="address-form checkout-form" id="addressAddForm">
            <input type="hidden" name="save_adres" value="1">
            <label>Способ доставки
                <select name="tip_dostavki" id="tipDostavki" required>
                    <?php foreach (delivery_tip_options() as $opt): ?>
                        <option value="<?= h($opt) ?>" <?= $formTip === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <input type="text" name="nazvanie" maxlength="64" placeholder="Название вашего адреса для сохранения" value="<?= h((string) ($_POST['nazvanie'] ?? '')) ?>">
            </label>
            <label>Город *
                <input type="text" name="gorod" id="gorodInput" required maxlength="100" value="<?= h((string) ($_POST['gorod'] ?? '')) ?>">
            </label>
            <label>Улица *
                <input type="text" name="ulica" id="ulicaInput" required maxlength="120" value="<?= h((string) ($_POST['ulica'] ?? '')) ?>">
            </label>
            <div class="checkout-address-row" id="addressRowDomKv">
                <label>Дом *
                    <input type="text" name="dom" id="domInput" required maxlength="6" inputmode="numeric" value="<?= h((string) ($_POST['dom'] ?? '')) ?>">
                </label>
                <label id="kvartiraWrap">Квартира
                    <input type="text" name="kvartira" id="kvartiraInput" maxlength="5" inputmode="numeric" value="<?= h((string) ($_POST['kvartira'] ?? '')) ?>">
                </label>
            </div>
            <p id="addressFormHint" class="checkout-hint"></p>
            <button class="btn btn-primary" type="submit">Сохранить адрес</button>
        </form>
    </div>
</main>
<script>
(function () {
    var tip = document.getElementById('tipDostavki');
    var gorod = document.getElementById('gorodInput');
    var hint = document.getElementById('addressFormHint');
    var kvWrap = document.getElementById('kvartiraWrap');
    var kvInput = document.getElementById('kvartiraInput');
    var addressRow = document.getElementById('addressRowDomKv');
    var courierCity = <?= json_encode(DELIVERY_COURIER_CITY, JSON_UNESCAPED_UNICODE) ?>;
    var tipCourier = <?= json_encode(DELIVERY_TIP_COURIER, JSON_UNESCAPED_UNICODE) ?>;
    var tipPvz = <?= json_encode(DELIVERY_TIP_PVZ, JSON_UNESCAPED_UNICODE) ?>;

    function normCity(v) {
        return (v || '').trim().toLowerCase();
    }

    function updateAddressFormUi() {
        if (!tip) return;
        var t = tip.value;
        var isCourier = t === tipCourier;
        var isPvz = t === tipPvz;
        var isYar = normCity(gorod ? gorod.value : '') === normCity(courierCity);

        if (kvWrap) {
            if (isPvz) {
                kvWrap.style.display = 'none';
                if (kvInput) {
                    kvInput.value = '';
                    kvInput.required = false;
                }
                if (addressRow) addressRow.classList.add('checkout-address-row--pvz');
            } else {
                kvWrap.style.display = '';
                if (kvInput) kvInput.required = false;
                if (addressRow) addressRow.classList.remove('checkout-address-row--pvz');
            }
        }

        if (hint) {
            if (isCourier) {
                hint.textContent = isYar
                    ? 'Адрес для доставки курьером в ' + courierCity + '.'
                    : 'Курьер только в ' + courierCity + '. Для другого города выберите «' + tipPvz + '».';
                hint.style.color = isYar ? '' : '#9a5b2e';
            } else if (isPvz) {
                hint.textContent = 'Адрес пункта выдачи (квартира не указывается).';
                hint.style.color = '';
            } else {
                hint.textContent = '';
            }
        }
    }

    if (tip) tip.addEventListener('change', updateAddressFormUi);
    if (gorod) gorod.addEventListener('input', updateAddressFormUi);
    updateAddressFormUi();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
