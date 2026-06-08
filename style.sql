-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 24 2026 г., 14:59
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `style`
--

-- --------------------------------------------------------

--
-- Структура таблицы `style_dolzhnost`
--

CREATE TABLE `style_dolzhnost` (
  `id_dolzhnost` int(11) NOT NULL,
  `nazvanie` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_dolzhnost`
--

INSERT INTO `style_dolzhnost` (`id_dolzhnost`, `nazvanie`) VALUES
(1, 'Сотрудник'),
(2, 'Курьер'),
(3, 'Руководитель');

-- --------------------------------------------------------

--
-- Структура таблицы `style_dostavka`
--

CREATE TABLE `style_dostavka` (
  `id_dostavka` int(11) NOT NULL,
  `id_zakaz` int(11) NOT NULL,
  `id_sotrudnik_kurier` int(11) NOT NULL,
  `status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_dostavka`
--

INSERT INTO `style_dostavka` (`id_dostavka`, `id_zakaz`, `id_sotrudnik_kurier`, `status`) VALUES
(1, 1, 2, 'В пути'),
(2, 2, 5, 'Подготовка'),
(3, 3, 8, 'Доставлен'),
(4, 4, 11, 'Ожидает отправки'),
(5, 5, 14, 'Доставлен'),
(6, 6, 2, 'В пути'),
(7, 7, 5, 'Собирается'),
(8, 8, 8, 'Доставлен'),
(9, 9, 11, 'Передан курьеру'),
(10, 10, 14, 'Доставлен'),
(11, 17, 17, 'Доставлен'),
(12, 19, 17, 'Доставлен;трек=126783123'),
(13, 20, 17, 'Доставлен;трек=2432434'),
(14, 21, 17, 'Отменён'),
(15, 24, 17, 'Отменён;трек=100500'),
(16, 25, 17, 'Доставлен;трек=1233123123'),
(17, 26, 17, 'Доставлен;трек=34543535');

-- --------------------------------------------------------

--
-- Структура таблицы `style_kategorii`
--

CREATE TABLE `style_kategorii` (
  `id_kategoriya` int(11) NOT NULL,
  `nazvanie` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_kategorii`
--

INSERT INTO `style_kategorii` (`id_kategoriya`, `nazvanie`) VALUES
(1, 'Верхняя одежда'),
(2, 'Обувь'),
(3, 'Футболки'),
(4, 'Штаны'),
(5, 'Аксессуары'),
(6, 'Худи'),
(7, 'Рубашки'),
(8, 'Свитеры'),
(9, 'Шорты');

-- --------------------------------------------------------

--
-- Структура таблицы `style_klienti`
--

CREATE TABLE `style_klienti` (
  `id_klient` int(11) NOT NULL,
  `familiya` varchar(100) NOT NULL,
  `imya` varchar(100) NOT NULL,
  `otchestvo` varchar(100) DEFAULT NULL,
  `data_rozhd` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `parol` varchar(255) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_klienti`
--

INSERT INTO `style_klienti` (`id_klient`, `familiya`, `imya`, `otchestvo`, `data_rozhd`, `email`, `parol`, `telefon`) VALUES
(1, 'Морозов', 'Егор', 'Ильич', '2000-05-12', 'egor@mail.ru', '12345', '8-901-111-11-11'),
(2, 'Васильева', 'Алина', 'Дмитриевна', '1999-07-21', 'alina@mail.ru', '12345', '8-902-222-22-22'),
(3, 'Федоров', 'Кирилл', 'Максимович', '2001-03-14', 'kirill@mail.ru', '12345', '8-903-333-33-33'),
(4, 'Николаева', 'Дарья', 'Сергеевна', '1998-11-30', 'darya@mail.ru', '12345', '8-904-444-44-44'),
(5, 'Попов', 'Артем', 'Иванович', '2002-01-18', 'artem@mail.ru', '12345', '8-905-555-55-55'),
(6, 'Соколова', 'Екатерина', 'Олеговна', '2003-05-17', 'katya@mail.ru', '12345', '8-906-666-66-66'),
(7, 'Лебедев', 'Михаил', 'Петрович', '1997-06-15', 'lebedev@mail.ru', '12345', '8-907-777-77-77'),
(8, 'Киселева', 'Ольга', 'Андреевна', '2003-09-11', 'olga@mail.ru', '12345', '8-908-888-88-88'),
(9, 'Захаров', 'Владислав', 'Сергеевич', '1996-12-01', 'zaharov@mail.ru', '12345', '8-909-999-99-99'),
(10, 'Макарова', 'Юлия', 'Игоревна', '2000-04-22', 'yulia@mail.ru', '12345', '8-910-101-10-10'),
(11, 'Григорьев', 'Илья', 'Романович', '1995-08-19', 'grig@mail.ru', '12345', '8-911-202-20-20'),
(12, 'Павлова', 'Марина', 'Александровна', '2001-10-30', 'marina@mail.ru', '12345', '8-912-303-30-30'),
(13, 'Данилов', 'Степан', 'Владимирович', '1999-03-08', 'stepan@mail.ru', '12345', '8-913-404-40-40'),
(14, 'Ковалева', 'Виктория', 'Павловна', '2002-11-27', 'vika@mail.ru', '12345', '8-914-505-50-50'),
(15, 'Борисов', 'Тимур', 'Олегович', '1998-02-14', 'timur@mail.ru', '12345', '8-915-606-60-60'),
(16, 'Долгов', 'Роман', 'Сергеевич', '2006-05-15', 'roman@bk.ru', 'roman', '8-915-986-21-94');

-- --------------------------------------------------------

--
-- Структура таблицы `style_klient_adresa`
--

CREATE TABLE `style_klient_adresa` (
  `id_adres` int(11) NOT NULL,
  `id_klient` int(11) NOT NULL,
  `nazvanie` varchar(64) DEFAULT NULL,
  `gorod` varchar(100) NOT NULL,
  `ulica` varchar(120) NOT NULL,
  `dom` varchar(16) NOT NULL,
  `kvartira` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_klient_adresa`
--

INSERT INTO `style_klient_adresa` (`id_adres`, `id_klient`, `nazvanie`, `gorod`, `ulica`, `dom`, `kvartira`) VALUES
(2, 16, '@d:Доставка в ПВЗ|Почта', 'Москва', 'Тверская', '55', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `style_korzina`
--

CREATE TABLE `style_korzina` (
  `id_korzina` int(11) NOT NULL,
  `id_klient` int(11) NOT NULL,
  `id_tovar` int(11) NOT NULL,
  `kolichestvo` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_korzina`
--

INSERT INTO `style_korzina` (`id_korzina`, `id_klient`, `id_tovar`, `kolichestvo`) VALUES
(75, 16, 3, 1),
(76, 16, 13, 1),
(77, 16, 6, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `style_postupleniya`
--

CREATE TABLE `style_postupleniya` (
  `id_postuplenie` int(11) NOT NULL,
  `data_postupleniya` date NOT NULL,
  `id_tovar` int(11) NOT NULL,
  `kolichestvo` int(11) NOT NULL,
  `primechanie` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_postupleniya`
--

INSERT INTO `style_postupleniya` (`id_postuplenie`, `data_postupleniya`, `id_tovar`, `kolichestvo`, `primechanie`) VALUES
(1, '2024-01-05', 1, 30, 'Поставка зимней коллекции'),
(2, '2024-01-05', 2, 20, 'Доп. размеры'),
(3, '2024-01-06', 3, 35, 'Новая партия'),
(4, '2024-01-06', 4, 15, 'Пополнение склада'),
(5, '2024-01-07', 5, 50, 'Спортивная коллекция'),
(6, '2024-01-07', 6, 30, 'Популярная модель'),
(7, '2024-01-08', 7, 18, 'Зимняя обувь'),
(8, '2024-01-08', 8, 25, 'Базовая линейка'),
(9, '2024-01-09', 9, 60, 'Базовые футболки'),
(10, '2024-01-09', 10, 45, 'Пополнение'),
(11, '2024-01-10', 11, 40, 'Новая поставка'),
(12, '2024-01-10', 12, 25, 'С принтом'),
(13, '2024-01-11', 13, 35, 'Джинсы'),
(14, '2024-01-11', 14, 20, 'Зауженные модели'),
(15, '2024-01-12', 15, 15, 'Классика'),
(16, '2024-01-12', 16, 40, 'Спортивные'),
(17, '2024-01-13', 17, 50, 'Аксессуары'),
(18, '2024-01-13', 18, 25, 'Зимняя партия'),
(19, '2024-01-14', 19, 15, 'Рюкзаки'),
(20, '2024-01-14', 20, 40, 'Ремни'),
(21, '2024-01-15', 21, 35, 'Худи'),
(22, '2024-01-15', 22, 25, 'На молнии'),
(23, '2024-01-16', 23, 40, 'Базовая модель'),
(24, '2024-01-16', 24, 20, 'Классические рубашки'),
(25, '2024-01-17', 25, 30, 'Рубашки в клетку'),
(26, '2024-01-17', 26, 18, 'Шерстяные свитеры'),
(27, '2024-01-18', 27, 28, 'Вязаные свитеры'),
(28, '2024-01-18', 28, 12, 'Шорты спортивные'),
(29, '2024-01-19', 29, 35, 'Джинсовые шорты'),
(30, '2026-05-20', 14, 10, 'осенняя коллекция'),
(31, '2026-05-20', 13, 25, 'весенняя'),
(32, '2026-05-22', 31, 25, NULL),
(33, '2026-05-23', 3, 25, NULL),
(34, '2026-05-23', 4, 2, NULL),
(35, '2026-05-24', 4, -1, 'Списание: Брак'),
(36, '2026-05-24', 4, 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `style_razmer`
--

CREATE TABLE `style_razmer` (
  `id_razmer` int(11) NOT NULL,
  `nazvanie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_razmer`
--

INSERT INTO `style_razmer` (`id_razmer`, `nazvanie`) VALUES
(1, 'XS'),
(2, 'S'),
(3, 'M'),
(4, 'L'),
(5, 'XL'),
(6, '38'),
(7, '39'),
(8, '40'),
(9, '41'),
(10, '42'),
(11, '43');

-- --------------------------------------------------------

--
-- Структура таблицы `style_sklad_ostatok`
--

CREATE TABLE `style_sklad_ostatok` (
  `id_tovar` int(11) NOT NULL,
  `kolichestvo` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_sklad_ostatok`
--

INSERT INTO `style_sklad_ostatok` (`id_tovar`, `kolichestvo`) VALUES
(1, 25),
(2, 18),
(3, 24),
(4, 14),
(5, 40),
(6, 26),
(7, 16),
(8, 18),
(9, 55),
(10, 43),
(11, 38),
(12, 24),
(13, 24),
(14, 26),
(15, 10),
(16, 31),
(17, 44),
(18, 20),
(19, 11),
(20, 36),
(21, 28),
(22, 19),
(23, 33),
(24, 15),
(25, 24),
(26, 13),
(27, 21),
(28, 9),
(29, 30),
(31, 23);

-- --------------------------------------------------------

--
-- Структура таблицы `style_sostav_zakaza`
--

CREATE TABLE `style_sostav_zakaza` (
  `id_sostav_zakaza` int(11) NOT NULL,
  `id_zakaz` int(11) NOT NULL,
  `id_tovar` int(11) NOT NULL,
  `kolichestvo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_sostav_zakaza`
--

INSERT INTO `style_sostav_zakaza` (`id_sostav_zakaza`, `id_zakaz`, `id_tovar`, `kolichestvo`) VALUES
(1, 1, 1, 1),
(2, 1, 9, 1),
(3, 2, 5, 1),
(4, 3, 21, 1),
(5, 3, 18, 1),
(6, 4, 2, 1),
(7, 5, 13, 1),
(8, 5, 20, 1),
(9, 6, 6, 1),
(10, 7, 26, 1),
(11, 8, 3, 1),
(12, 8, 24, 1),
(13, 9, 22, 1),
(14, 10, 7, 1),
(15, 11, 7, 1),
(16, 12, 4, 1),
(17, 12, 11, 1),
(18, 13, 4, 1),
(19, 13, 2, 1),
(20, 14, 1, 1),
(21, 14, 3, 1),
(22, 15, 15, 1),
(23, 16, 14, 1),
(24, 17, 4, 1),
(25, 18, 3, 1),
(26, 19, 25, 2),
(27, 20, 31, 1),
(28, 21, 31, 1),
(29, 22, 8, 1),
(30, 23, 8, 1),
(31, 24, 8, 1),
(32, 24, 13, 1),
(33, 25, 3, 1),
(34, 26, 27, 1),
(35, 26, 15, 1),
(36, 26, 6, 1),
(37, 27, 4, 1),
(38, 28, 4, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `style_sotrudniki`
--

CREATE TABLE `style_sotrudniki` (
  `id_sotrudnik` int(11) NOT NULL,
  `familiya` varchar(100) NOT NULL,
  `imya` varchar(100) NOT NULL,
  `otchestvo` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `parol` varchar(255) NOT NULL,
  `dolzhnost` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_sotrudniki`
--

INSERT INTO `style_sotrudniki` (`id_sotrudnik`, `familiya`, `imya`, `otchestvo`, `email`, `parol`, `dolzhnost`) VALUES
(1, 'Иванов', 'Иван', 'Сергеевич', 'ivanov1@mail.ru', '12345', 1),
(2, 'Петров', 'Алексей', 'Игоревич', 'petrov1@mail.ru', '12345', 2),
(3, 'Сидоров', 'Максим', 'Олегович', 'sidorov1@mail.ru', '12345', 1),
(4, 'Кузнецов', 'Дмитрий', 'Андреевич', 'kuznetsov1@mail.ru', '12345', 3),
(5, 'Смирнова', 'Анна', 'Владимировна', 'smirnova1@mail.ru', '12345', 2),
(6, 'Орлова', 'Мария', 'Павловна', 'orlova1@mail.ru', '12345', 1),
(7, 'Фролов', 'Никита', 'Евгеньевич', 'frolov@mail.ru', '12345', 1),
(8, 'Семенов', 'Павел', 'Ильич', 'semenov@mail.ru', '12345', 2),
(9, 'Егорова', 'Полина', 'Александровна', 'egorova@mail.ru', '12345', 1),
(10, 'Виноградов', 'Артем', 'Сергеевич', 'vino@mail.ru', '12345', 1),
(11, 'Тихонов', 'Денис', 'Олегович', 'tihonov@mail.ru', '12345', 2),
(12, 'Беляева', 'Елена', 'Максимовна', 'bel@mail.ru', '12345', 1),
(13, 'Жуков', 'Кирилл', 'Андреевич', 'zhukov@mail.ru', '12345', 1),
(14, 'Гаврилова', 'Дарья', 'Ивановна', 'gav@mail.ru', '12345', 2),
(15, 'Крылов', 'Роман', 'Викторович', 'krylov@mail.ru', '12345', 1),
(16, 'Иванов', 'Иван', 'Иванович', 'sot@bk.ru', 'sot', 1),
(17, 'Петров', 'Петр', 'Петрович', 'kur@bk.ru', 'kur', 2),
(18, 'Пластинина', 'Анна', 'Станиславовна', 'admin@bk.ru', 'admin', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `style_tcvet`
--

CREATE TABLE `style_tcvet` (
  `id_tcvet` int(11) NOT NULL,
  `nazvanie` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_tcvet`
--

INSERT INTO `style_tcvet` (`id_tcvet`, `nazvanie`) VALUES
(1, 'Черный'),
(2, 'Белый'),
(3, 'Красный'),
(4, 'Синий'),
(5, 'Зеленый'),
(6, 'Серый'),
(7, 'Бежевый'),
(8, 'Коричневый'),
(9, 'Желтый'),
(10, 'Розовый');

-- --------------------------------------------------------

--
-- Структура таблицы `style_tovary`
--

CREATE TABLE `style_tovary` (
  `id_tovar` int(11) NOT NULL,
  `nazvanie` varchar(255) NOT NULL,
  `articul` varchar(100) DEFAULT NULL,
  `opisanie` varchar(255) DEFAULT NULL,
  `sostav` varchar(255) DEFAULT NULL,
  `tsena` decimal(10,2) NOT NULL,
  `razmer` int(11) DEFAULT NULL,
  `kategoriya` int(11) DEFAULT NULL,
  `izobrazhenie` varchar(255) DEFAULT NULL,
  `tsvet` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_tovary`
--

INSERT INTO `style_tovary` (`id_tovar`, `nazvanie`, `articul`, `opisanie`, `sostav`, `tsena`, `razmer`, `kategoriya`, `izobrazhenie`, `tsvet`) VALUES
(1, 'Куртка зимняя', 'A001', 'Теплая зимняя куртка', 'Полиэстер', 12990.00, 4, 1, 'jacket1.jpg, jacket1_2.jpg, jacket1_3.jpg', 1),
(2, 'Куртка зимняя', 'A002', 'Теплая зимняя куртка', 'Полиэстер', 12990.00, 5, 1, 'jacket2.jpg, jacket2_2.jpg, jacket2_3.jpg', 2),
(3, 'Куртка демисезонная', 'A003', 'Легкая куртка', 'Нейлон', 8990.00, 3, 1, 'jacket3.jpg, jacket3_2.jpg, jacket3_3.jpg', 6),
(4, 'Бомбер', 'A004', 'Стильный бомбер', 'Хлопок', 7590.00, 4, 1, 'bomber1.jpg, bomber1_2.jpg, bomber1_3.jpg', 3),
(5, 'Кроссовки спортивные', 'B001', 'Удобные кроссовки', 'Текстиль', 7990.00, 6, 2, 'shoes1.jpg, shoes1_2.jpg, shoes1_3.jpg', 1),
(6, 'Кроссовки повседневные', 'B002', 'Повседневная обувь', 'Текстиль', 6990.00, 11, 2, 'shoes2.jpg, shoes2_2.jpg, shoes2_3.jpg', 2),
(7, 'Ботинки зимние', 'B003', 'Теплые ботинки', 'Кожа', 10990.00, 8, 2, 'boots1.jpg, boots1_2.jpg, boots1_3.jpg', 8),
(8, 'Кеды классические', 'B004', 'Легкие кеды', 'Текстиль', 4990.00, 10, 2, 'keds1.jpg, keds1_2.jpg, keds1_3.jpg', 4),
(9, 'Футболка базовая', 'C001', 'Однотонная футболка', 'Хлопок', 1990.00, 2, 3, 'shirt1.jpg, shirt1_2.jpg, shirt1_3.jpg', 2),
(10, 'Футболка базовая', 'C002', 'Однотонная футболка', 'Хлопок', 1990.00, 3, 3, 'shirt2.jpg, shirt2_2.jpg, shirt2_3.jpg', 1),
(11, 'Футболка oversize', 'C003', 'Свободная футболка', 'Хлопок', 2590.00, 4, 3, 'shirt3.jpg, shirt3_2.jpg, shirt3_3.jpg', 6),
(12, 'Футболка с принтом', 'C004', 'Футболка с рисунком', 'Хлопок', 2990.00, 3, 3, 'shirt4.jpg, shirt4_2.jpg, shirt4_3.jpg', 3),
(13, 'Джинсы прямые', 'D001', 'Классические джинсы', 'Деним', 5990.00, 3, 4, 'jeans1.jpg, jeans1_2.jpg, jeans1_3.jpg', 4),
(14, 'Джинсы зауженные', 'D002', 'Стильные джинсы', 'Деним', 6490.00, 4, 4, 'jeans2.jpg, jeans2_2.jpg, jeans2_3.jpg', 1),
(15, 'Брюки классические', 'D003', 'Офисные брюки', 'Вискоза', 5590.00, 3, 4, 'pants1.jpg, pants1_2.jpg, pants1_3.jpg', 1),
(16, 'Спортивные штаны', 'D004', 'Комфортные штаны', 'Хлопок', 3990.00, 5, 4, 'pants2.jpg, pants2_2.jpg, pants2_3.jpg', 6),
(17, 'Шапка зимняя', 'E001', 'Теплая шапка', 'Шерсть', 1990.00, 2, 5, 'hat1.jpg, hat1_2.jpg, hat1_3.jpg', 1),
(18, 'Шарф', 'E002', 'Мягкий шарф', 'Шерсть', 2490.00, 3, 5, 'scarf1.jpg, scarf1_2.jpg, scarf1_3.jpg', 8),
(19, 'Рюкзак городской', 'E003', 'Вместительный рюкзак', 'Полиэстер', 4590.00, 4, 5, 'bag1.jpg, bag1_2.jpg, bag1_3.jpg', 1),
(20, 'Ремень', 'E004', 'Кожаный ремень', 'Кожа', 1790.00, 3, 5, 'belt1.jpg, belt1_2.jpg, belt1_3.jpg', 8),
(21, 'Худи oversize', 'F001', 'Теплое худи', 'Хлопок', 4590.00, 4, 6, 'hoodie1.jpg, hoodie1_2.jpg, hoodie1_3.jpg', 6),
(22, 'Худи на молнии', 'F002', 'Удобное худи', 'Хлопок', 4990.00, 5, 6, 'hoodie2.jpg, hoodie2_2.jpg, hoodie2_3.jpg', 1),
(23, 'Худи базовое', 'F003', 'Однотонное худи', 'Хлопок', 4290.00, 3, 6, 'hoodie3.jpg, hoodie3_2.jpg, hoodie3_3.jpg', 2),
(24, 'Рубашка классическая', 'G001', 'Белая рубашка', 'Хлопок', 3990.00, 3, 7, 'shirtclassic1.jpg, shirtclassic1_2.jpg, shirtclassic1_3.jpg', 2),
(25, 'Рубашка в клетку', 'G002', 'Повседневная рубашка', 'Хлопок', 4490.00, 4, 7, 'shirtclassic2.jpg, shirtclassic2_2.jpg, shirtclassic2_3.jpg', 3),
(26, 'Свитер шерстяной', 'H001', 'Теплый свитер', 'Шерсть', 5590.00, 4, 8, 'sweater1.jpg, sweater1_2.jpg, sweater1_3.jpg', 6),
(27, 'Свитер вязаный', 'H002', 'Мягкий свитер', 'Шерсть', 5990.00, 5, 8, 'sweater2.jpg, sweater2_2.jpg, sweater2_3.jpg', 8),
(28, 'Шорты спортивные', 'I001', 'Легкие шорты', 'Полиэстер', 2490.00, 3, 9, 'shorts1.jpg, shorts1_2.jpg, shorts1_3.jpg', 1),
(29, 'Шорты джинсовые', 'I002', 'Повседневные шорты', 'Деним', 3290.00, 4, 9, 'shorts2.jpg, shorts2_2.jpg, shorts2_3.jpg', 4),
(31, 'Ботинки зимние', 'B005', 'Теплые ботинки', 'Кожа', 10990.00, 11, 2, 'boots1.jpg, boots1_2.jpg, boots1_3.jpg,', 8);

-- --------------------------------------------------------

--
-- Структура таблицы `style_zakazy`
--

CREATE TABLE `style_zakazy` (
  `id_zakaz` int(11) NOT NULL,
  `klient` int(11) NOT NULL,
  `status` varchar(100) DEFAULT NULL,
  `itogovaya_summa` decimal(10,2) DEFAULT NULL,
  `tip_dostavki` varchar(100) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `data_sozdaniya` date DEFAULT NULL,
  `gorod` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `style_zakazy`
--

INSERT INTO `style_zakazy` (`id_zakaz`, `klient`, `status`, `itogovaya_summa`, `tip_dostavki`, `adres`, `data_sozdaniya`, `gorod`) VALUES
(1, 1, 'Новый', 14980.00, 'Курьер', 'ул. Ленина 10', '2024-01-10', 'Москва'),
(2, 2, 'Доставляется', 8990.00, 'Самовывоз', 'ул. Мира 15', '2024-01-11', 'Казань'),
(3, 3, 'Завершен', 6580.00, 'Курьер', 'ул. Гагарина 7', '2024-01-12', 'Сочи'),
(4, 4, 'Новый', 12990.00, 'Курьер', 'ул. Центральная 25', '2024-01-13', 'Самара'),
(5, 5, 'Завершен', 10580.00, 'Самовывоз', 'ул. Победы 3', '2024-01-14', 'Уфа'),
(6, 6, 'Новый', 7990.00, 'Курьер', 'ул. Молодежная 8', '2024-01-15', 'Москва'),
(7, 7, 'Новый', 5590.00, 'Курьер', 'ул. Садовая 11', '2024-01-16', 'Казань'),
(8, 8, 'Завершен', 12980.00, 'Самовывоз', 'ул. Лесная 20', '2024-01-17', 'Пермь'),
(9, 9, 'Новый', 4590.00, 'Курьер', 'ул. Полевая 17', '2024-01-18', 'Омск'),
(10, 10, 'Доставлен', 8990.00, 'Курьер', 'ул. Парковая 5', '2024-01-19', 'Тюмень'),
(11, 1, 'Новый', 10990.00, 'Самовывоз', 'Машиностроителей, дом 2, квартира 102', '2026-05-07', 'Ярославль'),
(12, 1, 'Отменён', 10180.00, 'Самовывоз', 'улица Ленина, дом 52, квартира 105', '2026-05-07', 'Ярославль'),
(13, 1, 'Отменён', 20580.00, 'Самовывоз', 'Чайковского 55', '2026-05-13', 'Ярославль'),
(14, 1, 'Новый', 21980.00, 'Самовывоз', 'ул. Чайковского, д. 52, кв. 112', '2026-05-13', 'Ярославль'),
(15, 1, 'Готов к отправке', 5590.00, 'Курьер', 'улица Пушкина, дом 11, кв 12', '2026-05-20', 'Киров'),
(16, 1, 'Завершен', 6490.00, 'Самовывоз', 'Томная, 12, 78', '2026-05-20', 'Киров'),
(17, 16, 'Завершен', 7590.00, 'Доставка в ПВЗ', 'Новая, д.55, кв.18', '2026-05-20', 'Москва'),
(18, 16, 'Отменён', 8990.00, 'Доставка в ПВЗ', 'Клубная, 47', '2026-05-20', 'Москва'),
(19, 16, 'Завершен', 8980.00, 'Доставка в ПВЗ', 'Московская, дом 23', '2026-05-20', 'Москва'),
(20, 16, 'Завершен', 10990.00, 'Доставка в ПВЗ', 'Свердлова, 102', '2026-05-22', 'Москва'),
(21, 16, 'Отменён', 10990.00, 'Доставка в ПВЗ', '1', '2026-05-22', 'Москва'),
(22, 16, 'Отменён', 4990.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 50', '2026-05-23', 'Москва'),
(23, 16, 'Отменён', 4990.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-23', 'Москва'),
(24, 16, 'Отменён', 10980.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-23', 'Москва'),
(25, 16, 'Завершен', 8990.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-23', 'Москва'),
(26, 16, 'Завершен', 18570.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-23', 'Москва'),
(27, 16, 'Отменён', 7590.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-24', 'Москва'),
(28, 16, 'Отменён', 7590.00, 'Доставка в ПВЗ', 'ул. Тверская, д. 55', '2026-05-24', 'Москва');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `style_dolzhnost`
--
ALTER TABLE `style_dolzhnost`
  ADD PRIMARY KEY (`id_dolzhnost`);

--
-- Индексы таблицы `style_dostavka`
--
ALTER TABLE `style_dostavka`
  ADD PRIMARY KEY (`id_dostavka`),
  ADD KEY `id_zakaz` (`id_zakaz`),
  ADD KEY `id_sotrudnik_kurier` (`id_sotrudnik_kurier`);

--
-- Индексы таблицы `style_kategorii`
--
ALTER TABLE `style_kategorii`
  ADD PRIMARY KEY (`id_kategoriya`);

--
-- Индексы таблицы `style_klienti`
--
ALTER TABLE `style_klienti`
  ADD PRIMARY KEY (`id_klient`);

--
-- Индексы таблицы `style_klient_adresa`
--
ALTER TABLE `style_klient_adresa`
  ADD PRIMARY KEY (`id_adres`),
  ADD KEY `id_klient` (`id_klient`);

--
-- Индексы таблицы `style_korzina`
--
ALTER TABLE `style_korzina`
  ADD PRIMARY KEY (`id_korzina`),
  ADD UNIQUE KEY `uk_korzina_klient_tovar` (`id_klient`,`id_tovar`),
  ADD KEY `id_tovar` (`id_tovar`);

--
-- Индексы таблицы `style_postupleniya`
--
ALTER TABLE `style_postupleniya`
  ADD PRIMARY KEY (`id_postuplenie`),
  ADD KEY `id_tovar` (`id_tovar`);

--
-- Индексы таблицы `style_razmer`
--
ALTER TABLE `style_razmer`
  ADD PRIMARY KEY (`id_razmer`);

--
-- Индексы таблицы `style_sklad_ostatok`
--
ALTER TABLE `style_sklad_ostatok`
  ADD PRIMARY KEY (`id_tovar`);

--
-- Индексы таблицы `style_sostav_zakaza`
--
ALTER TABLE `style_sostav_zakaza`
  ADD PRIMARY KEY (`id_sostav_zakaza`),
  ADD KEY `id_zakaz` (`id_zakaz`),
  ADD KEY `id_tovar` (`id_tovar`);

--
-- Индексы таблицы `style_sotrudniki`
--
ALTER TABLE `style_sotrudniki`
  ADD PRIMARY KEY (`id_sotrudnik`),
  ADD KEY `dolzhnost` (`dolzhnost`);

--
-- Индексы таблицы `style_tcvet`
--
ALTER TABLE `style_tcvet`
  ADD PRIMARY KEY (`id_tcvet`);

--
-- Индексы таблицы `style_tovary`
--
ALTER TABLE `style_tovary`
  ADD PRIMARY KEY (`id_tovar`),
  ADD KEY `razmer` (`razmer`),
  ADD KEY `kategoriya` (`kategoriya`),
  ADD KEY `tsvet` (`tsvet`);

--
-- Индексы таблицы `style_zakazy`
--
ALTER TABLE `style_zakazy`
  ADD PRIMARY KEY (`id_zakaz`),
  ADD KEY `klient` (`klient`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `style_dolzhnost`
--
ALTER TABLE `style_dolzhnost`
  MODIFY `id_dolzhnost` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `style_dostavka`
--
ALTER TABLE `style_dostavka`
  MODIFY `id_dostavka` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `style_kategorii`
--
ALTER TABLE `style_kategorii`
  MODIFY `id_kategoriya` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `style_klienti`
--
ALTER TABLE `style_klienti`
  MODIFY `id_klient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `style_klient_adresa`
--
ALTER TABLE `style_klient_adresa`
  MODIFY `id_adres` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `style_korzina`
--
ALTER TABLE `style_korzina`
  MODIFY `id_korzina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT для таблицы `style_postupleniya`
--
ALTER TABLE `style_postupleniya`
  MODIFY `id_postuplenie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `style_razmer`
--
ALTER TABLE `style_razmer`
  MODIFY `id_razmer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `style_sostav_zakaza`
--
ALTER TABLE `style_sostav_zakaza`
  MODIFY `id_sostav_zakaza` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `style_sotrudniki`
--
ALTER TABLE `style_sotrudniki`
  MODIFY `id_sotrudnik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `style_tcvet`
--
ALTER TABLE `style_tcvet`
  MODIFY `id_tcvet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `style_tovary`
--
ALTER TABLE `style_tovary`
  MODIFY `id_tovar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT для таблицы `style_zakazy`
--
ALTER TABLE `style_zakazy`
  MODIFY `id_zakaz` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `style_dostavka`
--
ALTER TABLE `style_dostavka`
  ADD CONSTRAINT `style_dostavka_ibfk_1` FOREIGN KEY (`id_zakaz`) REFERENCES `style_zakazy` (`id_zakaz`),
  ADD CONSTRAINT `style_dostavka_ibfk_2` FOREIGN KEY (`id_sotrudnik_kurier`) REFERENCES `style_sotrudniki` (`id_sotrudnik`);

--
-- Ограничения внешнего ключа таблицы `style_klient_adresa`
--
ALTER TABLE `style_klient_adresa`
  ADD CONSTRAINT `style_klient_adresa_ibfk_1` FOREIGN KEY (`id_klient`) REFERENCES `style_klienti` (`id_klient`);

--
-- Ограничения внешнего ключа таблицы `style_korzina`
--
ALTER TABLE `style_korzina`
  ADD CONSTRAINT `style_korzina_ibfk_1` FOREIGN KEY (`id_klient`) REFERENCES `style_klienti` (`id_klient`),
  ADD CONSTRAINT `style_korzina_ibfk_2` FOREIGN KEY (`id_tovar`) REFERENCES `style_tovary` (`id_tovar`);

--
-- Ограничения внешнего ключа таблицы `style_postupleniya`
--
ALTER TABLE `style_postupleniya`
  ADD CONSTRAINT `style_postupleniya_ibfk_1` FOREIGN KEY (`id_tovar`) REFERENCES `style_tovary` (`id_tovar`);

--
-- Ограничения внешнего ключа таблицы `style_sklad_ostatok`
--
ALTER TABLE `style_sklad_ostatok`
  ADD CONSTRAINT `style_sklad_ostatok_ibfk_1` FOREIGN KEY (`id_tovar`) REFERENCES `style_tovary` (`id_tovar`);

--
-- Ограничения внешнего ключа таблицы `style_sostav_zakaza`
--
ALTER TABLE `style_sostav_zakaza`
  ADD CONSTRAINT `style_sostav_zakaza_ibfk_1` FOREIGN KEY (`id_zakaz`) REFERENCES `style_zakazy` (`id_zakaz`),
  ADD CONSTRAINT `style_sostav_zakaza_ibfk_2` FOREIGN KEY (`id_tovar`) REFERENCES `style_tovary` (`id_tovar`);

--
-- Ограничения внешнего ключа таблицы `style_sotrudniki`
--
ALTER TABLE `style_sotrudniki`
  ADD CONSTRAINT `style_sotrudniki_ibfk_1` FOREIGN KEY (`dolzhnost`) REFERENCES `style_dolzhnost` (`id_dolzhnost`);

--
-- Ограничения внешнего ключа таблицы `style_tovary`
--
ALTER TABLE `style_tovary`
  ADD CONSTRAINT `style_tovary_ibfk_1` FOREIGN KEY (`razmer`) REFERENCES `style_razmer` (`id_razmer`),
  ADD CONSTRAINT `style_tovary_ibfk_2` FOREIGN KEY (`kategoriya`) REFERENCES `style_kategorii` (`id_kategoriya`),
  ADD CONSTRAINT `style_tovary_ibfk_3` FOREIGN KEY (`tsvet`) REFERENCES `style_tcvet` (`id_tcvet`);

--
-- Ограничения внешнего ключа таблицы `style_zakazy`
--
ALTER TABLE `style_zakazy`
  ADD CONSTRAINT `style_zakazy_ibfk_1` FOREIGN KEY (`klient`) REFERENCES `style_klienti` (`id_klient`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
