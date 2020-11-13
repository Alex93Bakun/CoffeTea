<?php

// Объявляем нужные константы
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'store');

define('EMAIL_ADMIN', 'bakun.alex93@gmail.com');
define('EMAIL_FROM_NAME', 'Інтернет-магазин CoffeTea');
define('SITE', 'coffetea.zzz.com.ua');


// Подключаемся к базе данных
function connectDB() {
	$errorMessage = 'Неможливо підключитися до серверу бази даних';
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

// Получаем данные из массива POST и экранируем их
function getParam($param, $conn, $default = '') {
	return (isset($_POST[$param])) ? mysqli_real_escape_string($conn, $_POST[$param]) : $default;
}

// Подготавливаем данные
function getData($conn) {
	return array(
		'name' => getParam('name', $conn, 'noname'),
		'email' => getParam('email', $conn, 'unknown email'),
		'phone' => getParam('phone', $conn),
		'address' => getParam('address', $conn),
		'message' => getParam('message', $conn),
		'delivery_type' => getParam('delivery_type', $conn),
		'delivery_summa' => getParam('delivery_summa', $conn),
		'full_summa' => getParam('full_summa', $conn),
		'cart' => isset($_POST['cart']) ? stripslashes($_POST['cart']) : '[]'
	);
}

// Добавление клиента
function addClient($data, $conn) {
	$query = sprintf(
		"insert into clients (`id`, `name`, `email`, `phone`, `dt_added`) 
		 values ((SELECT MAX(a.ID) + 1 FROM clients AS a), '%s', '%s', '%s', NOW())
		 on duplicate key update 
			 `name` = '%s', 
			 `phone` = '%s',
			 `dt_added` = NOW()",
		$data['name'],
		$data['email'],
		$data['phone'],
		$data['name'],
		$data['phone']
	);
	$conn->query($query);
	return $conn->insert_id;
}

// Добавление заказа
function addOrder($data, $conn) {
	$query = sprintf(
		"insert into orders (`client_id`, `address`, `message`) values (%d, '%s', '%s')",
		$data['client_id'],
		$data['address'],
		$data['message']
	);
	$conn->query($query);
	return $conn->insert_id;
}

// Добавление деталей заказа
function addDetails($data, $conn) {
	$cart = json_decode($data['cart'], true);
	$orderId = $data['order_id'];
	$values = array();
	foreach($cart as $cartItem) {
		$value = sprintf(
			"(%d, %d, '%s', %d, %d)",
			$orderId,
			$cartItem['id'],
			mysqli_real_escape_string($conn, $cartItem['name']),
			$cartItem['price'],
			$cartItem['count']
		);
		array_push($values, $value);
	}
	$query = sprintf(
		"insert into details (`order_id`, `good_id`, `good`, `price`, `count`) values %s",
		implode(',', $values)
	);
	$conn->query($query);
}

// Отправка письма
function sendEmail($options) {
	$headers = "Content-type: text/html; charset=utf-8 \r\n";
	$headers .= 'From: =?utf-8?B?' . base64_encode($options['fromName']) . '?=<' . $options['fromEmail'] . '>';
	return mail($options['toEmail'], $options['subject'], $options['body'], $headers);
}

// Отправка письма с заказом
function sendEmailOrder($data) {
	$data['title'] = 'Заказ с сайта ' . SITE;
	$cart = json_decode($data['cart'], true);
	ob_start();
	include('tpl/email_order.php');
	$body = ob_get_contents();
	ob_end_clean();
	$sendClient = sendEmail(array(
		'subject' => 'Ваше замовлення з сайту ' . SITE,
		'fromName' => EMAIL_FROM_NAME,
		'fromEmail' => EMAIL_ADMIN,
		'toEmail' => $data['email'],
		'body' => $body
	));
	if (!$sendClient) {
		throw new Exception('Помилка відправки пошти на email клієнта');
	}
	$sendAdmin = sendEmail(array(
	'subject' => 'Новый заказ с сайта ' . SITE,
	'fromName' => EMAIL_FROM_NAME,
	'fromEmail' => EMAIL_ADMIN,
	'toEmail' => EMAIL_ADMIN,
	'body' => $body
	));
	if (!$sendAdmin) {
		throw new Exception('Помилка відправки пошти на email адміна');
	}
}

try {
	// Подключаемся к базе данных
	$conn = connectDB();
	// Получаем данные из массива POST
	$data = getData($conn);

	// Добавляем запись в таблицу Клиенты
	$clientId = addClient($data, $conn);
	$data['client_id'] = $clientId;

	// Добавляем запись в таблицу Заказы
	$orderId = addOrder($data, $conn);
	$data['order_id'] = $orderId;

	// Добавляем товары в таблицу Детали
	addDetails($data, $conn);

	// Отправляем письмо
	sendEmailOrder($data);

	// Возвращаем клиенту успешный ответ
	echo json_encode(array(
		'code' => 'success'
	));
}
catch (Exception $e) {
	// Возвращаем клиенту ответ с ошибкой
	echo json_encode(array(
		'code' => 'error',
		'message' => $e->getMessage()
	));
}
