<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

require_client();

$uid = (int) (current_user()['id'] ?? 0);
$pdo = db();

$buyNow = cart_buy_now_get();
$isBuyNowCheckout = $buyNow !== null;
if ($isBuyNowCheckout) {
    $checkoutCart = [$buyNow['id_tovar'] => $buyNow['qty']];
} else {
    cart_buy_now_clear();
    $checkoutCart = cart_items();
}

$built = cart_build_lines($pdo, $checkoutCart);
$lines = $built['available'];
$sum = 0.0;
foreach ($lines as $row) {
    $sum += $row['line'];
}

if (!$lines) {
    if ($built['unavailable'] !== []) {
        flash_set('В корзине только товары, которых нет в наличии. Выберите другие позиции в каталоге.', 'error');
    } else {
        flash_set('Добавьте товары в корзину.', 'error');
    }
    redirect('cart.php');
}

$savedAddresses = client_addresses_for_checkout($pdo, $uid);
$hasAddressBook = client_addresses_table_exists($pdo);

$error = '';

$form = [
    'tip_dostavki' => '',
    'gorod' => '',
    'ulica' => '',
    'dom' => '',
    'kvartira' => '',
    'saved_adres_id' => '',
    'save_address' => false,
    'address_label' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['tip_dostavki'] = delivery_tip_normalize(trim((string) ($_POST['tip_dostavki'] ?? '')));
    $form['gorod'] = trim((string) ($_POST['gorod'] ?? ''));
    $form['ulica'] = trim((string) ($_POST['ulica'] ?? ''));
    $form['dom'] = trim((string) ($_POST['dom'] ?? ''));
    $form['kvartira'] = trim((string) ($_POST['kvartira'] ?? ''));
    $form['saved_adres_id'] = (string) ($_POST['saved_adres_id'] ?? '');
    $form['save_address'] = !empty($_POST['save_address']);
    $form['address_label'] = trim((string) ($_POST['address_label'] ?? ''));

    $savedId = (int) $form['saved_adres_id'];
    if ($savedId > 0) {
        $saved = fetch_client_address_by_id($pdo, $savedId, $uid);
        if ($saved) {
            $form['gorod'] = trim((string) $saved['gorod']);
            $form['ulica'] = trim((string) $saved['ulica']);
            $form['dom'] = trim((string) $saved['dom']);
            $form['kvartira'] = trim((string) ($saved['kvartira'] ?? ''));
            $meta = address_stored_nazvanie_unpack($saved['nazvanie'] ?? null);
            if ($meta['tip'] !== null) {
                $form['tip_dostavki'] = $meta['tip'];
            } elseif (!empty($saved['tip_dostavki'])) {
                $form['tip_dostavki'] = delivery_tip_normalize((string) $saved['tip_dostavki']);
            }
        }
    }

    if ($form['tip_dostavki'] === DELIVERY_TIP_PVZ) {
        $form['kvartira'] = '';
    }

    $validErr = delivery_validate_address_parts(
        $form['tip_dostavki'],
        $form['gorod'],
        $form['ulica'],
        $form['dom'],
        $form['kvartira']
    );
    if ($validErr !== null) {
        $error = $validErr;
    } else {
        $needFlat = $form['tip_dostavki'] === DELIVERY_TIP_COURIER;
        $adres = address_compose_order_string($form['ulica'], $form['dom'], $form['kvartira'], $needFlat);
        $gorod = $form['gorod'];
        $tip = $form['tip_dostavki'];

        try {
            $pdo->beginTransaction();
            foreach ($lines as $row) {
                $tid = (int) $row['t']['id_tovar'];
                $need = (int) $row['qty'];
                if (!stock_reserve($pdo, $tid, $need)) {
                    throw new RuntimeException('stock');
                }
            }

            $ins = $pdo->prepare(
                'INSERT INTO STYLE_zakazy (klient, status, itogovaya_summa, tip_dostavki, adres, data_sozdaniya, gorod)
                 VALUES (?, ?, ?, ?, ?, CURDATE(), ?)'
            );
            $ins->execute([
                $uid,
                'Новый',
                round($sum, 2),
                $tip,
                $adres,
                $gorod,
            ]);
            $zakazId = (int) $pdo->lastInsertId();

            foreach ($lines as $row) {
                product_insert_order_line($pdo, $zakazId, $row['t'], (int) $row['qty']);
            }

            if ($form['save_address'] && $hasAddressBook && $savedId <= 0) {
                $saveErr = save_client_address(
                    $pdo,
                    $uid,
                    $gorod,
                    $form['ulica'],
                    $form['dom'],
                    $form['kvartira'],
                    $form['address_label'] !== '' ? $form['address_label'] : null,
                    $tip
                );
                if ($saveErr !== null) {
                    throw new RuntimeException('addr:' . $saveErr);
                }
            }

            $pdo->commit();
            if ($isBuyNowCheckout) {
                cart_buy_now_clear();
            } else {
                cart_clear_client($uid);
            }
            flash_set('Заказ №' . $zakazId . ' успешно создан.', 'success');
            redirect('orders.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getMessage() === 'stock') {
                $error = 'Недостаточно товара на складе. Обновите корзину.';
            } elseif (str_starts_with($e->getMessage(), 'addr:')) {
                $error = substr($e->getMessage(), 5);
            } else {
                $error = 'Не удалось сохранить заказ. Проверьте таблицы STYLE_zakazy и STYLE_sostav_zakaza.';
            }
        }
    }
} else {
    $form['tip_dostavki'] = delivery_tip_normalize((string) ($_GET['tip'] ?? ''));
}

$pageTitle = 'Оформление заказа — City Style';
require __DIR__ . '/includes/header.php';

$addressesJson = json_encode($savedAddresses, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>

<main class="container section">
    <h1>Оформление заказа</h1>
    <?php if ($isBuyNowCheckout): ?>
        <p class="checkout-hint">Покупка выбранного товара. Содержимое корзины в заказ не входит. <a class="btn btn-ghost btn-small" href="cart.php">В корзину</a></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="flash flash-error" style="margin-bottom:16px"><?= h($error) ?></div>
    <?php endif; ?>
    <div class="checkout-grid">
        <section class="panel">
            <form method="post" id="checkoutForm" class="checkout-form" novalidate>
                <h3>Доставка</h3>
                <p class="checkout-hint">Курьер — только <?= h(DELIVERY_COURIER_CITY) ?>. ПВЗ — в других городах.</p>
                <label>Способ
                    <select name="tip_dostavki" id="tipDostavki" required>
                        <option value="">Выберите</option>
                        <?php foreach (delivery_tip_options() as $opt): ?>
                            <option value="<?= h($opt) ?>" <?= $form['tip_dostavki'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <?php if ($hasAddressBook && $savedAddresses): ?>
                    <label>Сохранённый адрес
                        <select name="saved_adres_id" id="savedAdresSelect">
                            <option value="">Ввести новый адрес</option>
                            <?php foreach ($savedAddresses as $a): ?>
                                <option value="<?= (int) $a['id_adres'] ?>" <?= $form['saved_adres_id'] === (string) $a['id_adres'] ? 'selected' : '' ?>>
                                    <?= h(client_address_label($a)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>

                <label>Город *
                    <input type="text" name="gorod" id="gorodInput" required maxlength="100" value="<?= h($form['gorod']) ?>" autocomplete="address-level2">
                </label>
                <div class="checkout-address-fields" id="addressFields">
                    <label>Улица *
                        <input type="text" name="ulica" id="ulicaInput" required maxlength="120" value="<?= h($form['ulica']) ?>" autocomplete="address-line1">
                    </label>
                    <div class="checkout-address-row" id="addressRowDomKv">
                        <label>Дом *
                            <input type="text" name="dom" id="domInput" required maxlength="6" inputmode="numeric" value="<?= h($form['dom']) ?>">
                        </label>
                        <label id="kvartiraWrap">Квартира
                            <input type="text" name="kvartira" id="kvartiraInput" maxlength="5" inputmode="numeric" value="<?= h($form['kvartira']) ?>">
                        </label>
                    </div>
                </div>
                <p id="deliveryRuleHint" class="checkout-hint"></p>

                <?php if ($hasAddressBook): ?>
                    <div class="checkout-save-address" id="saveAddressBlock">
                        <label class="checkbox-label">
                            <input type="checkbox" name="save_address" value="1" id="saveAddressCb" <?= $form['save_address'] ? 'checked' : '' ?>>
                            Сохранить этот адрес для следующих заказов
                        </label>
                        <label id="addressLabelWrap" class="checkout-address-label-wrap">
                            <input type="text" name="address_label" maxlength="64" placeholder="Название вашего адреса для сохранения" value="<?= h($form['address_label']) ?>">
                        </label>
                    </div>
                    <p class="checkout-hint"><a class="btn btn-ghost btn-small" href="addresses.php">Управление адресами</a></p>
                <?php endif; ?>

                <button class="btn btn-primary" type="submit">Подтвердить заказ</button>
            </form>
        </section>
        <aside class="panel">
            <h3>Ваш заказ</h3>
            <ul class="simple-list">
                <?php foreach ($lines as $row): ?>
                    <li><?= h($row['t']['nazvanie']) ?> × <?= (int) $row['qty'] ?> — <?= h(format_price($row['line'])) ?></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Итого: <?= h(format_price($sum)) ?></strong></p>
        </aside>
    </div>
</main>
<script>
(function () {
    var tip = document.getElementById('tipDostavki');
    var gorod = document.getElementById('gorodInput');
    var hint = document.getElementById('deliveryRuleHint');
    var kvWrap = document.getElementById('kvartiraWrap');
    var addressRow = document.getElementById('addressRowDomKv');
    var kvInput = document.getElementById('kvartiraInput');
    var savedSelect = document.getElementById('savedAdresSelect');
    var saveBlock = document.getElementById('saveAddressBlock');
    var saveCb = document.getElementById('saveAddressCb');
    var addressLabelWrap = document.getElementById('addressLabelWrap');
    var addresses = <?= $addressesJson ?: '[]' ?>;

    var courierCity = <?= json_encode(DELIVERY_COURIER_CITY, JSON_UNESCAPED_UNICODE) ?>;
    var tipCourier = <?= json_encode(DELIVERY_TIP_COURIER, JSON_UNESCAPED_UNICODE) ?>;
    var tipPvz = <?= json_encode(DELIVERY_TIP_PVZ, JSON_UNESCAPED_UNICODE) ?>;

    function normCity(v) {
        return (v || '').trim().toLowerCase();
    }

    function updateDeliveryUi() {
        if (!tip) return;
        var t = tip.value;
        var g = normCity(gorod ? gorod.value : '');
        var isYar = g === normCity(courierCity);
        var isCourier = t === tipCourier;
        var isPvz = t === tipPvz;

        if (kvWrap) {
            if (isPvz) {
                kvWrap.style.display = 'none';
                if (kvInput) {
                    kvInput.required = false;
                    kvInput.value = '';
                }
                if (addressRow) addressRow.classList.add('checkout-address-row--pvz');
            } else {
                kvWrap.style.display = '';
                if (kvInput) {
                    kvInput.required = false;
                }
                if (addressRow) addressRow.classList.remove('checkout-address-row--pvz');
            }
        }

        if (hint) {
            if (isCourier) {
                hint.textContent = isYar
                    ? 'Курьер привезёт заказ по указанному адресу в ' + courierCity + '.'
                    : 'Курьер только в ' + courierCity + '. Выберите «' + tipPvz + '».';
                hint.style.color = isYar ? '' : '#9a5b2e';
            } else if (isPvz) {
                hint.textContent = 'Укажите адрес пункта выдачи.';
                hint.style.color = '';
            } else {
                hint.textContent = '';
            }
        }

        if (saveBlock && saveCb) {
            var usingSaved = savedSelect && savedSelect.value !== '';
            saveBlock.style.display = usingSaved ? 'none' : '';
            if (addressLabelWrap) {
                addressLabelWrap.style.display = saveCb.checked && !usingSaved ? '' : 'none';
            }
        }
    }

    function updateSaveAddressUi() {
        updateDeliveryUi();
    }

    function fillFromSaved(id) {
        var row = addresses.find(function (a) { return String(a.id_adres) === String(id); });
        if (!row) return;
        if (tip && row.tip_dostavki) {
            tip.value = row.tip_dostavki;
        }
        if (gorod) gorod.value = row.gorod || '';
        var u = document.getElementById('ulicaInput');
        var d = document.getElementById('domInput');
        if (u) u.value = row.ulica || '';
        if (d) d.value = row.dom || '';
        if (kvInput) kvInput.value = row.kvartira || '';
        updateDeliveryUi();
    }

    function clearAddressFields() {
        if (gorod) gorod.value = '';
        var u = document.getElementById('ulicaInput');
        var d = document.getElementById('domInput');
        if (u) u.value = '';
        if (d) d.value = '';
        if (kvInput) kvInput.value = '';
    }

    if (savedSelect) {
        savedSelect.addEventListener('change', function () {
            if (savedSelect.value) {
                fillFromSaved(savedSelect.value);
            } else {
                clearAddressFields();
            }
            updateDeliveryUi();
        });
        if (savedSelect.value) {
            fillFromSaved(savedSelect.value);
        }
    }
    if (tip) tip.addEventListener('change', updateDeliveryUi);
    if (gorod) gorod.addEventListener('input', updateDeliveryUi);
    if (saveCb) saveCb.addEventListener('change', updateSaveAddressUi);
    updateDeliveryUi();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
