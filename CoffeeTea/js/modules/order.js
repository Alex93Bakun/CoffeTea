'use strict';

// Модуль корзины
let order = (function($) {

	let ui = {
			$orderForm: $('#order-form'),
			$messageCart: $('#order-message'),
			$orderBtn: $('#order-btn'),
			$alertValidation: $('#alert-validation'),
			$alertOrderDone: $('#alert-order-done'),
			$orderMessageTemplate: $('#order-message-template'),
			$fullSumma: $('#full-summa'),
			$delivery: {
					type: $('#delivery-type'),
					summa: $('#delivery-summa'),
					btn: $('.js-delivery-type'),
					alert: $('#alert-delivery')
			}
	};

	let freeDelivery = {
			enabled: false,
			summa: 10000
	};

	// Инициализация модуля
	function init() {
			_renderMessage();
			_checkCart();
			_initDelivery();
			_bindHandlers();
	}

	// Рендерим сообщение о количестве товаров и общей сумме
	function _renderMessage() {
			let template = _.template(ui.$orderMessageTemplate.html()),
					data;
			cart.update();
			data = {
					count: cart.getCountAll(),
					summa: cart.getSumma()
			};
			ui.$messageCart.html(template(data));
	}

	// В случае пустой корзины отключаем кнопку Отправки заказа
	function _checkCart() {
			if (cart.getCountAll() === 0) {
					ui.$orderBtn.attr('disabled', 'disabled');
			}
	}

	// Меняем способ доставки
	function _changeDelivery() {
			let $item = ui.$delivery.btn.filter(':checked'),
					deliveryType = $item.attr('data-type'),
					deliverySumma = freeDelivery.enabled ? 0 : +$item.attr('data-summa'),
					cartSumma = cart.getSumma(),
					fullSumma = deliverySumma + cartSumma,
					alert =
							freeDelivery.enabled
									? 'Ми даруємо Вам безкоштовну доставку!'
									:
											'Сума доставки ' + deliverySumma + ' гривень. ' +
											'Загальна сума замовлення: ' +
											cartSumma + ' + ' + deliverySumma + ' = ' + fullSumma + ' гривень';

			ui.$delivery.type.val(deliveryType);
			ui.$delivery.summa.val(deliverySumma);
			ui.$fullSumma.val(fullSumma);
			ui.$delivery.alert.html(alert);
	}

	// Инициализация доставки
	function _initDelivery() {
			// Устанавливаем опцию бесплатной доставки
			freeDelivery.enabled = (cart.getSumma() >= freeDelivery.summa);

			// Навешиваем событие на смену способа доставки
			ui.$delivery.btn.on('change', _changeDelivery);

			_changeDelivery();
	}

	// Навешиваем события
	function _bindHandlers() {
			ui.$orderForm.on('click', '.js-close-alert', _closeAlert);
			ui.$orderForm.on('submit', _onSubmitForm);
	}

	// Закрытие alert-а
	function _closeAlert(e) {
			$(e.target).parent().addClass('d-none');
	}

	// Валидация формы
	function _validate() {
			let formData = ui.$orderForm.serializeArray(),
					name = _.find(formData, {name: 'name'}).value,
					email = _.find(formData, {name: 'email'}).value,
					isValid = (name !== '') && (email !== '');
			return isValid;
	}

	// Подготовка данных корзины к отправке заказа
	function _getCartData() {
			let cartData = cart.getData();
			_.each(cart.getData(), function(item) {
					item.name = encodeURIComponent(item.name);
			});
			return cartData;
	}

	// Успешная отправка
	function _orderSuccess(responce) {
			console.info('responce', responce);
			ui.$orderForm[0].reset();
			ui.$alertOrderDone.removeClass('d-none');
	}

	// Ошибка отправки
	function _orderError(responce) {
			console.error('responce', responce);
	}

	// Отправка завершилась
	function _orderComplete() {
			ui.$orderBtn.removeAttr('disabled').text('Відправити замовлення');
	}

	// Оформляем заказ
	function _onSubmitForm(e) {
		let isValid,
			formData,
			cartData,
			orderData;
		e.preventDefault();
		ui.$alertValidation.addClass('d-none');
		isValid = _validate();
		if (!isValid) {
			ui.$alertValidation.removeClass('d-none');
			return false;
		}
		formData = ui.$orderForm.serialize();
		cartData = _getCartData();
		orderData = formData + '&cart=' + JSON.stringify(cartData);
		ui.$orderBtn.attr('disabled', 'disabled').text('Йде відправка замовлення...');
		$.ajax({
			url: 'scripts/order.php',
			data: orderData,
			type: 'POST',
			cache: false,
			dataType: 'json',
			error: _orderError,
			success: function(responce) {
				if (responce.code === 'success') {
					_orderSuccess(responce);
				} else {
					_orderError(responce);
				}
			},
			complete: _orderComplete
		});
	}



	// Экспортируем наружу
	return {
			init: init
	}

})(jQuery);