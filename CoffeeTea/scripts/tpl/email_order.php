<html>
<head>
    <title><?php echo $data['title'] ?></title>
</head>

<body style="width: 98%">
<div style="font-family: Arial; font-size: 16px; width: 100%; padding: 5px 0">
    <div><b>Дякуємо, що обрали наш магазин!</b></div>
    <div>Ваше замовлення №<?php echo $data['order_id'] ?></div>
    <div style="margin: 20px 0">
        <b>Загальні відомості:</b>
        <br />
        Ім'я: <?php echo $data['name']; ?><br />
        Email: <?php echo $data['email']; ?><br />
        Телефон: <?php echo $data['phone']; ?><br />
        Адреса: <?php echo $data['address']; ?><br />
        Поввідомлення: <?php echo $data['message']; ?>
    </div>
    <div><b>Склад замовлення:</b></div>
    <table cellspacing="0" style="border: none; width: 100%; font-size: 14px">
        <thead>
            <th style="text-align: left">id товару</th>
            <th style="text-align: left">Назва</th>
            <th style="text-align: left">Ціна</th>
            <th style="text-align: left">Кількість</th>
        </thead>
        <tbody>
            <?php
            foreach($cart as $cartItem) {
                echo sprintf(
                    "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
                    $cartItem['id'],
                    $cartItem['name'],
                    $cartItem['price'],
                    $cartItem['count']
                );
            }
            ?>
        </tbody>
    </table>
    <div style="margin: 20px 0">
        Доставка: <?php echo $data['delivery_type']; ?><br />
        Сума доставки: <?php echo $data['delivery_summa']; ?> гривень<br />
        Загально з доставкою: <?php echo $data['full_summa']; ?> гривень<br />
    </div>
</div>
</body>
</html>