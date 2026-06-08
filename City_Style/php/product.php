<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/products.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pdo = db();
$t = $id > 0 ? fetch_tovar_by_id($pdo, $id) : null;

if (!$t) {
    http_response_code(404);
    $pageTitle = 'Товар не найден — City Style';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container section"><p>Товар не найден. <a class="btn btn-primary btn-small" href="catalog.php">В каталог</a></p></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$variants = fetch_variants_for_rep_id($pdo, $id, false);
if (!$variants) {
    $variants = [$t];
}

if (stock_enabled($pdo)) {
    $anyInStock = false;
    foreach ($variants as $v) {
        if (stock_in_stock($pdo, (int) $v['id_tovar'])) {
            $anyInStock = true;
            break;
        }
    }
    if (!$anyInStock) {
        $pageTitle = (string) $t['nazvanie'] . ' — нет в наличии';
        require __DIR__ . '/includes/header.php';
        echo '<main class="container section"><div class="panel"><h1>' . h($t['nazvanie']) . '</h1>';
        echo '<p>Товар временно отсутствует на складе. Скоро поступит в продажу.</p>';
        echo '<p><a class="btn btn-primary" href="catalog.php">Перейти в каталог</a></p></div></main>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }
}

$selectedId = $id;
$found = false;
foreach ($variants as $v) {
    if ((int) $v['id_tovar'] === $id) {
        $found = true;
        break;
    }
}
if (!$found && $variants) {
    $selectedId = (int) $variants[0]['id_tovar'];
}

$variantPayload = [];
foreach ($variants as $v) {
    $vid = (int) $v['id_tovar'];
    $inStock = stock_in_stock($pdo, $vid);
    $img = product_image_url($v['izobrazhenie'] ?? null);
    $gallery = product_gallery_urls($v['izobrazhenie'] ?? null, 3);
    if ($gallery === [] && $img !== null) {
        $gallery = [$img];
    }
    $label = trim(
        implode(' — ', array_filter([
            (string) ($v['tsvet_nazv'] ?? ''),
            (string) ($v['razmer_nazv'] ?? ''),
        ]))
    );
    if ($label === '') {
        $label = 'Вариант #' . $vid;
    }
    if (!$inStock) {
        $label .= ' (нет в наличии)';
    }
    $ostatok = stock_qty($pdo, $vid);
    $variantPayload[] = [
        'id' => $vid,
        'price' => (float) $v['tsena'],
        'priceFmt' => format_price($v['tsena']),
        'img' => $img,
        'gallery' => $gallery,
        'label' => $label,
        'articul' => (string) ($v['articul'] ?? ''),
        'inStock' => $inStock,
        'maxQty' => $inStock ? stock_cap_qty($pdo, $vid, 99) : 0,
        'stockHint' => $ostatok === null ? '' : ('Остаток: ' . $ostatok . ' шт.'),
    ];
}

$initial = null;
foreach ($variantPayload as $vp) {
    if ($vp['id'] === $selectedId && $vp['inStock']) {
        $initial = $vp;
        break;
    }
}
if ($initial === null) {
    foreach ($variantPayload as $vp) {
        if ($vp['inStock']) {
            $initial = $vp;
            $selectedId = $vp['id'];
            break;
        }
    }
}
if ($initial === null) {
    $initial = $variantPayload[0];
}
$galleryUrls = $initial['gallery'] ?? [];
$variantGalleries = [];
foreach ($variantPayload as $vp) {
    $variantGalleries[(int) $vp['id']] = $vp['gallery'];
}

$pageTitle = (string) $t['nazvanie'] . ' — City Style';
require __DIR__ . '/includes/header.php';

$client = is_client();
$redir = 'product.php?id=' . $selectedId;
$loginUrl = 'login.php?redirect=' . rawurlencode($redir);
$backUrl = product_page_back_url();
$backLabels = [
    'catalog.php' => 'каталог',
    'outfit-builder.php' => 'подбор образа',
    'index.php' => 'главную',
];
$backLabel = 'каталог';
foreach ($backLabels as $page => $label) {
    if (str_starts_with($backUrl, $page)) {
        $backLabel = $label;
        break;
    }
}
?>

<main class="container section">
    <p class="page-back"><a class="btn btn-ghost btn-small" href="<?= h($backUrl) ?>">← Назад в <?= h($backLabel) ?></a></p>
    <div class="product-layout">
        <div class="panel gallery">
            <div class="product-gallery-extra" id="productGalleryExtra">
                <div class="product-thumbs" id="productThumbs" role="list"></div>
                <p class="product-gallery-hint" id="productGalleryHint" hidden>Нажмите на фото, чтобы открыть крупно.</p>
            </div>
            <div id="productMainImage" class="product-main-image">
                <?php $selImg = $galleryUrls[0] ?? ($initial['img'] ?? null); ?>
                <button type="button" class="product-hero-btn" id="productHeroBtn" <?= $selImg ? '' : 'disabled' ?> aria-label="Открыть фото">
                    <div class="media-frame media-frame--hero<?= $selImg ? '' : ' is-empty' ?>" id="productHeroFrame">
                        <img class="media-frame__img" id="productHeroImg" src="<?= $selImg ? h($selImg) : '' ?>" alt="<?= h($t['nazvanie']) ?>">
                        <div class="media-frame__empty" id="productHeroPlaceholder">Нет фотографии</div>
                    </div>
                </button>
            </div>
        </div>
        <article class="panel">
            <h1><?= h($t['nazvanie']) ?></h1>
            <p>Артикул: <span id="productArticul"><?= h($initial['articul'] ?: '—') ?></span></p>
            <p><strong id="productPrice"><?= h($initial['priceFmt']) ?></strong></p>
            <h4>Вариант (цвет и размер)</h4>
            <select id="productVariantSelect" class="variant-select" style="width:100%;max-width:360px;padding:10px 12px;border-radius:10px;border:1px solid var(--line);font:inherit;margin-bottom:12px">
                <?php foreach ($variantPayload as $vp): ?>
                    <option value="<?= $vp['id'] ?>" data-img="<?= h($vp['img'] ?? '') ?>" data-price="<?= h($vp['priceFmt']) ?>" data-articul="<?= h($vp['articul']) ?>" data-in-stock="<?= $vp['inStock'] ? '1' : '0' ?>" data-max-qty="<?= (int) $vp['maxQty'] ?>" data-stock-hint="<?= h($vp['stockHint']) ?>" <?= $vp['id'] === $selectedId ? 'selected' : '' ?> <?= $vp['inStock'] ? '' : 'disabled' ?>>
                        <?= h($vp['label']) ?> — <?= h($vp['priceFmt']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p id="productStockHint" class="product-stock-hint" style="font-size:14px;color:var(--muted);margin:0 0 12px"><?= h($initial['stockHint']) ?></p>
            <?php if (!empty($t['kategoriya_nazv'])): ?>
                <p style="color:var(--muted);font-size:14px">Категория: <?= h($t['kategoriya_nazv']) ?></p>
            <?php endif; ?>
            <div class="stack-buttons">
                <?php if ($client && $initial['inStock']): ?>
                    <form method="post" action="cart_add.php" class="stack-buttons" id="productCartForm">
                        <input type="hidden" name="id_tovar" id="cartIdTovar" value="<?= $selectedId ?>">
                        <input type="hidden" name="redirect" id="productRedirect" value="product.php?id=<?= $selectedId ?>">
                        <label>Количество<input type="number" name="qty" id="productQty" value="1" min="1" max="<?= (int) $initial['maxQty'] ?>" style="max-width:120px"></label>
                        <button class="btn btn-primary" type="submit" name="redirect_target" value="cart">Добавить в корзину</button>
                        <button class="btn btn-ghost" type="submit" name="redirect_target" value="checkout">Купить сейчас</button>
                    </form>
                <?php elseif ($client): ?>
                    <p class="product-out-msg">Выбранный вариант отсутствует на складе. Выберите другой или вернитесь в <a class="btn btn-ghost btn-small" href="catalog.php">каталог</a>.</p>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= h($loginUrl) ?>">Войти</a>
                <?php endif; ?>
            </div>
            <hr>
            <h4>Описание</h4>
            <p><?= h($t['opisanie'] ?? '—') ?></p>
            <h4>Состав</h4>
            <p><?= h($t['sostav'] ?? '—') ?></p>
        </article>
    </div>
    <div class="image-lightbox" id="imageLightbox" hidden>
        <button type="button" class="image-lightbox__close" id="lightboxClose" aria-label="Закрыть">&times;</button>
        <button type="button" class="image-lightbox__nav image-lightbox__nav--prev" id="lightboxPrev" aria-label="Предыдущее">&#8592;</button>
        <img class="image-lightbox__img" id="lightboxImg" src="" alt="">
        <button type="button" class="image-lightbox__nav image-lightbox__nav--next" id="lightboxNext" aria-label="Следующее">&#8594;</button>
    </div>
</main>
<script>
(function () {
    var variantGalleries = <?= json_encode($variantGalleries, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var galleryUrls = [];
    var lightboxIdx = 0;

    var sel = document.getElementById('productVariantSelect');
    var heroFrame = document.getElementById('productHeroFrame');
    var heroImg = document.getElementById('productHeroImg');
    var heroBtn = document.getElementById('productHeroBtn');
    var priceEl = document.getElementById('productPrice');
    var artEl = document.getElementById('productArticul');
    var hiddenId = document.getElementById('cartIdTovar');
    var redir = document.getElementById('productRedirect');
    var qtyInp = document.getElementById('productQty');
    var stockHint = document.getElementById('productStockHint');
    var cartForm = document.getElementById('productCartForm');
    var lightbox = document.getElementById('imageLightbox');
    var lightboxImg = document.getElementById('lightboxImg');
    var lightboxClose = document.getElementById('lightboxClose');
    var lightboxPrev = document.getElementById('lightboxPrev');
    var lightboxNext = document.getElementById('lightboxNext');

    function setHero(url) {
        if (!url) {
            if (heroFrame) heroFrame.classList.add('is-empty');
            if (heroImg) heroImg.removeAttribute('src');
            if (heroBtn) heroBtn.disabled = true;
            return;
        }
        if (heroFrame) heroFrame.classList.remove('is-empty');
        if (heroImg) heroImg.src = url;
        if (heroBtn) heroBtn.disabled = false;
    }

    function openLightbox(idx) {
        if (!lightbox || !lightboxImg || !galleryUrls.length) return;
        lightboxIdx = (idx + galleryUrls.length) % galleryUrls.length;
        lightboxImg.src = galleryUrls[lightboxIdx];
        lightbox.hidden = false;
        document.body.classList.add('lightbox-open');
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.hidden = true;
        document.body.classList.remove('lightbox-open');
    }

    function bindThumbClicks() {
        document.querySelectorAll('.product-thumb').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-gallery-url');
                var idx = parseInt(btn.getAttribute('data-gallery-index') || '0', 10);
                document.querySelectorAll('.product-thumb').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                setHero(url);
                lightboxIdx = idx;
            });
        });
    }

    function applyGallery(urls) {
        galleryUrls = urls || [];
        var thumbs = document.getElementById('productThumbs');
        var hint = document.getElementById('productGalleryHint');
        if (thumbs) {
            thumbs.innerHTML = '';
            if (galleryUrls.length > 1) {
                galleryUrls.forEach(function (url, gi) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'product-thumb' + (gi === 0 ? ' is-active' : '');
                    btn.setAttribute('data-gallery-index', String(gi));
                    btn.setAttribute('data-gallery-url', url);
                    btn.setAttribute('aria-label', 'Фото ' + (gi + 1));
                    var frame = document.createElement('div');
                    frame.className = 'media-frame media-frame--thumb';
                    var thumbImg = document.createElement('img');
                    thumbImg.className = 'media-frame__img';
                    thumbImg.src = url;
                    thumbImg.alt = '';
                    frame.appendChild(thumbImg);
                    btn.appendChild(frame);
                    thumbs.appendChild(btn);
                });
                bindThumbClicks();
                if (hint) hint.hidden = false;
            } else if (hint) {
                hint.hidden = true;
            }
        }
        setHero(galleryUrls[0] || '');
        lightboxIdx = 0;
    }

    if (heroBtn) {
        heroBtn.addEventListener('click', function () {
            var active = document.querySelector('.product-thumb.is-active');
            var idx = active ? parseInt(active.getAttribute('data-gallery-index') || '0', 10) : 0;
            var url = (heroFrame && !heroFrame.classList.contains('is-empty') && heroImg && heroImg.src)
                ? heroImg.src
                : (galleryUrls[idx] || galleryUrls[0]);
            if (url) openLightbox(galleryUrls.indexOf(url) >= 0 ? galleryUrls.indexOf(url) : idx);
        });
    }
    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightbox) lightbox.addEventListener('click', function (e) { if (e.target === lightbox) closeLightbox(); });
    if (lightboxPrev) lightboxPrev.addEventListener('click', function (e) { e.stopPropagation(); openLightbox(lightboxIdx - 1); });
    if (lightboxNext) lightboxNext.addEventListener('click', function (e) { e.stopPropagation(); openLightbox(lightboxIdx + 1); });
    document.addEventListener('keydown', function (e) {
        if (lightbox && lightbox.hidden) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') openLightbox(lightboxIdx - 1);
        if (e.key === 'ArrowRight') openLightbox(lightboxIdx + 1);
    });

    function applyVariant() {
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt || opt.disabled) {
            for (var i = 0; i < sel.options.length; i++) {
                if (!sel.options[i].disabled) { sel.selectedIndex = i; opt = sel.options[i]; break; }
            }
        }
        if (!opt) return;
        var img = opt.getAttribute('data-img') || '';
        var price = opt.getAttribute('data-price') || '';
        var art = opt.getAttribute('data-articul') || '—';
        var inStock = opt.getAttribute('data-in-stock') === '1';
        var maxQty = parseInt(opt.getAttribute('data-max-qty') || '99', 10);
        if (priceEl) priceEl.textContent = price;
        if (artEl) artEl.textContent = art || '—';
        if (hiddenId) hiddenId.value = opt.value;
        if (redir) redir.value = 'product.php?id=' + encodeURIComponent(opt.value);
        if (stockHint) stockHint.textContent = opt.getAttribute('data-stock-hint') || '';
        if (qtyInp) { qtyInp.max = Math.max(1, maxQty); if (+qtyInp.value > maxQty) qtyInp.value = maxQty; }
        if (cartForm) cartForm.style.display = inStock ? '' : 'none';
        var vid = parseInt(opt.value, 10);
        applyGallery(variantGalleries[vid] || (img ? [img] : []));
    }

    if (sel) sel.addEventListener('change', applyVariant);
    applyGallery(variantGalleries[<?= (int) $selectedId ?>] || <?= json_encode($galleryUrls, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
