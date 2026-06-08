<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

if (is_client()) {
    cart_persist_all();
}

unset($_SESSION['user'], $_SESSION['_cart_db_klient']);

redirect('index.php');
