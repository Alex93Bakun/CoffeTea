<?php

// Объявляем нужные константы
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'store');

// Подключаемся к базе данных
function connectDB() {
    $errorMessage = 'Невозможно подключиться к серверу базы данных';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if (!$conn)
        throw new Exception($errorMessage);
    else {
        $query = $conn->query('set names utf8');
        if (!$query)
            throw new Exception($errorMessage);
        else
            return $conn;
    }
}

// Получение данных из массива _GET
function getOptions() {
    // Категория, цены и дополнительные данные
    $categoryId = (isset($_GET['category'])) ? (int)$_GET['category'] : 0;
    $minPrice = (isset($_GET['min_price'])) ? (int)$_GET['min_price'] : 0;
    $maxPrice = (isset($_GET['max_price'])) ? (int)$_GET['max_price'] : 1000000;
    $needsData = (isset($_GET['needs_data'])) ? explode(',', $_GET['needs_data']) : array();

    // Бренды
    $brands = (isset($_GET['brands'])) ? implode($_GET['brands'], ',') : null;

    // Сортировка
    $sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'price_asc';
    $sort = explode('_', $sort);
    $sortBy = $sort[0];
    $sortDir = $sort[1];

    return array(
        'brands' => $brands,
        'category_id' => $categoryId,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'sort_by' => $sortBy,
        'sort_dir' => $sortDir,
        'needs_data' => $needsData
    );
}

// Получение товаров
function getGoods($options, $conn) {
    // Обязательные параметры
    $minPrice = $options['min_price'];
    $maxPrice = $options['max_price'];
    $sortBy = $options['sort_by'];
    $sortDir = $options['sort_dir'];

    // Необязательные параметры
    $categoryId = $options['category_id'];
    $categoryWhere =
        ($categoryId !== 0)
            ? " g.category_id = $categoryId and "
            : '';

    $brands = $options['brands'];
    $brandsWhere =
        ($brands !== null)
            ? " g.brand_id in ($brands) and "
            : '';

    $query = "
        select
            g.id as good_id,
            g.good as good,
            g.category_id as category_id,
            b.brand as brand,
            g.price as price,
            g.rating as rating,
						g.photo as photo,
            g.quantity AS quantity
        from
            goods as g,
            brands as b
        where
            $categoryWhere
            $brandsWhere
            g.brand_id = b.id and
            (g.price between $minPrice and $maxPrice)
        order by $sortBy $sortDir
    ";

    $data = $conn->query($query);
    return $data->fetch_all(MYSQLI_ASSOC);
}

// Получаем бренды по категории
function getBrands($categoryId, $conn) {
    if ($categoryId !== 0) {
        $query = "
            select
                distinct b.id as id,
                b.brand as brand
            from
                brands as b,
                goods as g
            where
                g.category_id = $categoryId and
                g.brand_id = b.id
        ";
    } else {
        $query = 'select id, brand from brands';
    }
    $data = $conn->query($query);
    return $data->fetch_all(MYSQLI_ASSOC);
}

// Получаем минимальную и максимальную цену
function getPrices($categoryId, $conn) {
    $query = "
        select
            min(price) as min_price,
            max(price) as max_price
        from
            goods
    ";
    if ($categoryId !== 0) {
        $query .= " where category_id = $categoryId";
    }
    $data = $conn->query($query);
    return $data->fetch_assoc();
}

// Получение всех данных
function getData($options, $conn) {
    $result = array(
        'goods' => getGoods($options, $conn)
    );

    $needsData = $options['needs_data'];
    if (empty($needsData)) return $result;

    if (in_array('brands', $needsData)) {
        $result['brands'] = getBrands($options['category_id'], $conn);
    }
    if (in_array('prices', $needsData)) {
        $result['prices'] = getPrices($options['category_id'], $conn);
    }

    return $result;
}


try {
    // Подключаемся к базе данных
    $conn = connectDB();

    // Получаем данные от клиента
    $options = getOptions();

    // Получаем товары
    $data = getData($options, $conn);

    // Возвращаем клиенту успешный ответ
    echo json_encode(array(
        'code' => 'success',
        'data' => $data
    ));
}
catch (Exception $e) {
    // Возвращаем клиенту ответ с ошибкой
    echo json_encode(array(
        'code' => 'error',
        'message' => $e->getMessage()
    ));
}
