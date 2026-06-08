<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require dirname(__DIR__) . '/config.php';

/** @var PDO|null $pdo */
$pdo = null;

function db(): PDO
{
    global $pdo, $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['host'],
        $c['port'],
        $c['name'],
        $c['charset']
    );

    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_price(float|string $value): string
{
    $n = is_string($value) ? (float) $value : $value;
    return number_format($n, 0, '', ' ') . ' ₽';
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $message, string $type = 'info'): void
{
    $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
}

function flash_get(): ?array
{
    if (empty($_SESSION['_flash'])) {
        return null;
    }
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

function cart_init(): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/** @return array<int, int> id_tovar => qty */
function cart_items(): array
{
    if (is_client()) {
        cart_hydrate_from_db();
    }
    cart_init();
    $out = [];
    foreach ($_SESSION['cart'] as $id => $qty) {
        $id = (int) $id;
        $qty = (int) $qty;
        if ($id > 0 && $qty > 0) {
            $out[$id] = $qty;
        }
    }
    return $out;
}

function cart_set_qty(int $id_tovar, int $qty): void
{
    cart_init();
    if ($qty <= 0) {
        unset($_SESSION['cart'][$id_tovar]);
        cart_persist_line($id_tovar, 0);

        return;
    }
    $_SESSION['cart'][$id_tovar] = $qty;
    cart_persist_line($id_tovar, $qty);
}

function cart_add(int $id_tovar, int $qty = 1): void
{
    cart_init();
    if ($id_tovar <= 0 || $qty <= 0) {
        return;
    }
    $prev = (int) ($_SESSION['cart'][$id_tovar] ?? 0);
    $newQty = $prev + $qty;
    $_SESSION['cart'][$id_tovar] = $newQty;
    cart_persist_line($id_tovar, $newQty);
}

/** Оформление «Купить сейчас» — только выбранный товар, корзина не меняется. */
function cart_buy_now_set(int $id_tovar, int $qty): void
{
    if ($id_tovar <= 0 || $qty <= 0) {
        return;
    }
    $_SESSION['buy_now'] = ['id_tovar' => $id_tovar, 'qty' => $qty];
}

/** @return array{id_tovar: int, qty: int}|null */
function cart_buy_now_get(): ?array
{
    if (empty($_SESSION['buy_now']) || !is_array($_SESSION['buy_now'])) {
        return null;
    }
    $id = (int) ($_SESSION['buy_now']['id_tovar'] ?? 0);
    $qty = (int) ($_SESSION['buy_now']['qty'] ?? 0);
    if ($id <= 0 || $qty <= 0) {
        return null;
    }

    return ['id_tovar' => $id, 'qty' => $qty];
}

function cart_buy_now_clear(): void
{
    unset($_SESSION['buy_now']);
}

function current_user(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }
    return $_SESSION['user'];
}

function is_client(): bool
{
    $u = current_user();
    return $u !== null && ($u['role'] ?? '') === 'client';
}

function is_staff(): bool
{
    $u = current_user();
    return $u !== null && ($u['role'] ?? '') === 'staff';
}

function require_client(): void
{
    if (is_client()) {
        return;
    }
    if (is_staff()) {
        flash_set('Этот раздел доступен только клиентам магазина.', 'error');
        redirect('account.php');
    }
    $q = $_SERVER['REQUEST_URI'] ?? 'catalog.php';
    redirect('login.php?redirect=' . rawurlencode($q));
}

/**
 * До 3 имён файлов в поле izobrazhenie: "main.jpg, extra1.jpg, extra2.jpg"
 *
 * @return list<string>
 */
function product_image_filenames(?string $stored): array
{
    if ($stored === null || trim($stored) === '') {
        return [];
    }
    $out = [];
    foreach (preg_split('/\s*,\s*/', trim($stored)) as $part) {
        $safe = basename(trim($part));
        if ($safe !== '') {
            $out[] = $safe;
        }
        if (count($out) >= 3) {
            break;
        }
    }

    return $out;
}

function product_main_image_filename(?string $stored): ?string
{
    $files = product_image_filenames($stored);

    return $files[0] ?? null;
}

function product_image_url(?string $filename): ?string
{
    $main = $filename;
    if ($filename !== null && str_contains($filename, ',')) {
        $main = product_main_image_filename($filename);
    }
    if ($main === null || $main === '') {
        return null;
    }
    $safe = basename($main);
    $root = dirname(__DIR__, 2);
    $path = $root . '/images/catalog/' . $safe;
    if (is_file($path)) {
        return '../images/catalog/' . rawurlencode($safe);
    }

    return null;
}

/**
 * URL всех фото модели для страницы товара (макс. 3).
 *
 * @return list<string>
 */
function product_gallery_urls(?string $stored, int $max = 3): array
{
    $urls = [];
    foreach (product_image_filenames($stored) as $file) {
        $url = product_image_url($file);
        if ($url !== null) {
            $urls[] = $url;
        }
        if (count($urls) >= $max) {
            break;
        }
    }

    return $urls;
}

/** Абсолютный путь к папке images/catalog. */
function product_catalog_dir(): string
{
    return project_images_dir() . DIRECTORY_SEPARATOR . 'catalog';
}

/**
 * URL фото каталога для страниц сотрудника (из php/staff/...).
 */
function product_staff_catalog_image_url(string $filename): ?string
{
    $safe = basename($filename);
    if ($safe === '') {
        return null;
    }
    $path = product_catalog_dir() . DIRECTORY_SEPARATOR . $safe;
    if (!is_file($path)) {
        return null;
    }

    return staff_project_root_web_prefix() . 'images/catalog/' . rawurlencode($safe);
}

/**
 * Сохранить одно загруженное фото в images/catalog.
 *
 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
 */
function product_save_uploaded_image(array $file, string $nameHint = 'tovar'): ?string
{
    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($err !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return null;
    }
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!is_string($mime) || !isset($extMap[$mime])) {
        return null;
    }
    $ext = $extMap[$mime];
    $hint = preg_replace('/[^a-zA-Z0-9_-]+/u', '-', $nameHint) ?? '';
    $hint = trim($hint, '-');
    if ($hint === '') {
        $hint = 'tovar';
    }

    $dir = product_catalog_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }

    $base = $hint . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $base;
    $n = 0;
    while (is_file($dest)) {
        ++$n;
        $base = $hint . '_' . date('Ymd_His') . '_' . $n . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $base;
    }

    return move_uploaded_file($tmp, $dest) ? $base : null;
}

/**
 * Собрать поле izobrazhenie: загрузки photo1–photo3, сохранённые имена при редактировании, ручной ввод.
 *
 * @return array{0: ?string, 1: bool} [значение для БД, была ошибка загрузки]
 */
function product_resolve_izobrazhenie_from_request(string $articul, string $manual, ?string $existingStored = null): array
{
    $hint = trim($articul) !== '' ? trim($articul) : 'tovar';
    $names = [];
    $uploadFailed = false;

    for ($i = 1; $i <= 3; ++$i) {
        $fileKey = 'photo' . $i;
        if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey])) {
            $uploadErr = (int) ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadErr === UPLOAD_ERR_OK) {
                $saved = product_save_uploaded_image($_FILES[$fileKey], $hint . '-' . $i);
                if ($saved !== null) {
                    $names[] = $saved;
                    continue;
                }
                $uploadFailed = true;
            } elseif ($uploadErr !== UPLOAD_ERR_NO_FILE) {
                $uploadFailed = true;
            }
        }
        $keep = trim((string) ($_POST['keep_photo_' . $i] ?? ''));
        if ($keep !== '') {
            $names[] = basename($keep);
        }
    }

    if ($names !== []) {
        return [implode(', ', array_slice($names, 0, 3)), $uploadFailed];
    }

    $manual = trim($manual);
    if ($manual !== '') {
        $fromManual = product_image_filenames($manual);

        return [$fromManual === [] ? null : implode(', ', $fromManual), $uploadFailed];
    }

    if ($existingStored !== null && trim($existingStored) !== '') {
        $fromExisting = product_image_filenames($existingStored);

        return [$fromExisting === [] ? null : implode(', ', $fromExisting), $uploadFailed];
    }

    return [null, $uploadFailed];
}

/**
 * Относительный путь из текущего php-скрипта к корню проекта (../ или ../../).
 * Не зависит от имени папки проекта (CityStyle16, CityStyle17 и т.д.).
 */
function static_image_relative_prefix(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#/php/(.*)$#', $script, $m)) {
        $depth = substr_count($m[1], '/');

        return str_repeat('../', $depth + 1);
    }

    return '../';
}

/** Корневая папка всех изображений проекта. */
function project_images_dir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images';
}

/** Картинки информационных страниц (главная, о нас, доставка и т.д.). */
function static_images_pages_dir(): string
{
    return project_images_dir() . DIRECTORY_SEPARATOR . 'pages';
}

/** Картинки для информационных страниц (папка images/pages/). */
function static_image_url(string $filename): string
{
    $safe = basename($filename);
    $root = dirname(__DIR__, 2);
    $rel = static_image_relative_prefix();
    $encoded = rawurlencode($safe);

    $locations = [
        ['path' => static_images_pages_dir() . DIRECTORY_SEPARATOR . $safe, 'url' => 'images/pages/'],
        ['path' => project_images_dir() . DIRECTORY_SEPARATOR . $safe, 'url' => 'images/'],
        ['path' => $root . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . $safe, 'url' => 'images/pages/'],
    ];
    foreach ($locations as $loc) {
        if (is_file($loc['path'])) {
            return $rel . $loc['url'] . $encoded;
        }
    }

    return $rel . 'images/pages/' . $encoded;
}

/** Ссылка «Назад» со страницы товара (каталог, образ или главная). */
function product_page_back_url(): string
{
    if (!empty($_GET['back']) && is_string($_GET['back'])) {
        $b = (string) $_GET['back'];
        $path = strtok($b, '?') ?: $b;
        if (in_array($path, ['catalog.php', 'outfit-builder.php', 'index.php'], true)) {
            return $b;
        }
    }
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref !== '') {
        foreach (['catalog.php', 'outfit-builder.php', 'index.php'] as $page) {
            if (str_contains($ref, $page)) {
                return $page;
            }
        }
    }

    return 'catalog.php';
}

function order_status_client_can_cancel(string $status): bool
{
    $s = trim($status);
    return in_array($s, ['Новый', 'В обработке'], true);
}

/** Отмена на этапе ожидания в ПВЗ (после передачи курьером в пункт). */
function order_status_client_can_cancel_pvz(array $orderRow, ?array $deliveryRow): bool
{
    return delivery_pvz_awaiting_client($orderRow, $deliveryRow);
}

function table_exists(PDO $pdo, string $table): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!$db) {
        return false;
    }
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?'
    );
    $st->execute([(string) $db, $table]);
    return (int) $st->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!$db) {
        return false;
    }
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = ? AND LOWER(table_name) = LOWER(?) AND LOWER(column_name) = LOWER(?)'
    );
    $st->execute([(string) $db, $table, $column]);

    return (int) $st->fetchColumn() > 0;
}

require_once __DIR__ . '/stock.php';
require_once __DIR__ . '/cart_db.php';
require_once __DIR__ . '/delivery.php';
require_once __DIR__ . '/order_items.php';
require_once __DIR__ . '/client_addresses.php';
require_once __DIR__ . '/client_phone.php';

/**
 * Сегменты пути каталога текущего скрипта (без ведущего/конечного слэша).
 *
 * @return array{0: list<string>, 1: int|false}
 */
function staff_script_dir_segments(): array
{
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = trim(str_replace('\\', '/', $dir), '/');
    $segments = array_values(array_filter(explode('/', $dir), static fn(string $s): bool => $s !== ''));
    $phpIdx = array_search('php', $segments, true);

    return [$segments, $phpIdx];
}

/**
 * Префикс URL от текущего скрипта к папке php/ (catalog.php, logout.php, index.php витрины).
 * Пример: из .../php/staff/employee/ → "../../" (два уровня: employee → staff → php).
 */
function staff_php_web_prefix(): string
{
    [$segments, $phpIdx] = staff_script_dir_segments();
    if ($phpIdx === false) {
        $steps = 1;
    } else {
        $steps = count($segments) - (int) $phpIdx - 1;
    }
    $rel = str_repeat('../', max(0, $steps));
    if ($rel === '' || $rel === '.') {
        return '';
    }
    return rtrim($rel, '/') . '/';
}

/**
 * Префикс до корня проекта (родитель каталога php/), где лежит css/ рядом с php/.
 */
function staff_project_root_web_prefix(): string
{
    [$segments, $phpIdx] = staff_script_dir_segments();
    if ($phpIdx === false) {
        $steps = 2;
    } else {
        $steps = count($segments) - (int) $phpIdx;
    }
    $rel = str_repeat('../', max(0, $steps));
    if ($rel === '' || $rel === '.') {
        return '';
    }
    return rtrim($rel, '/') . '/';
}
