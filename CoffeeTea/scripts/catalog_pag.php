<?php

include_once './common.php';

// Получение данных из массива _GET
function getOptions() {
    $categoryId = (isset($_GET['category'])) ? (int)$_GET['category'] : 0;
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
    $limit = (isset($_GET['limit'])) ? (int)$_GET['limit'] : 5;

    return array(
        'category_id' => $categoryId,
        'page' => $page,
        'limit' => $limit
    );
}

// Получение товаров
function getGoods($options, $conn) {
    // Вычисляем номер страницы и параметры для sql limit
    $page = $options['page'];
    $limit = (int)$options['limit'];
    $start = ($page - 1) * $limit;

    // Категория, если есть
    $categoryId = $options['category_id'];
    $categoryWhere =
        ($categoryId !== 0)
            ? " g.category_id = $categoryId and "
            : '';

    // Заготовка запроса, на нем базируется запрос с общим количеством товаров и запрос с сортировками и страницами
    $queryBase = "
        select
            g.id as good_id,
            g.good as good,
            g.category_id as category_id,
            b.brand as brand,
            g.price as price,
            g.rating as rating,
            g.photo as photo
        from
            goods as g,
            brands as b
        where
            $categoryWhere
            g.brand_id = b.id
    ";

    // Запрос на общее количество товаров с указанной категорией
    $queryCountAll = 'select count(*) count_all from (' . $queryBase . ') as tmp';
    $data = $conn->query($queryCountAll);
    $row = $data->fetch_assoc();
    $countAll = (int)$row['count_all'];

    // Запрос с итоговыми данными
    $queryTotal = $queryBase . "
            order by price asc
            limit $start, $limit
        ";
    $data = $conn->query($queryTotal);
    $goods = $data->fetch_all(MYSQLI_ASSOC);

    // Возвращаем результат
    return array(
        'countAll' => $countAll,
        'goods' => $goods
    );
}


try {
    // Подключаемся к базе данных
    $conn = connectDB();

    // Получаем данные от клиента
    $options = getOptions();

    // Получаем товары
    $data = getGoods($options, $conn);

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
