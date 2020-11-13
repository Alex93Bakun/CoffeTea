'use strict';

// Модуль корзины
let cart = (function($) {

    let cartData,
        opts = {};

    // Инициализация модуля
    function init(options) {
        _initOptions(options);
        updateData();
        if (opts.renderCartOnInit) {
            renderCart();
        }
        if (opts.renderMenuCartOnInit) {
            renderMenuCart();
        }
        _bindHandlers();
    }

    // Инициализируем настройки
    function _initOptions(options) {
        let defaultOptions = {
            renderCartOnInit: true,
            renderMenuCartOnInit: true,
            elAddToCart: '.js-add-to-cart',
            attrId: 'data-id',
            attrName: 'data-name',
            attrPrice: 'data-price',
            attrDelta: 'data-delta',
            elCart: '#cart',
            elTotalCartCount: '#total-cart-count',
            elTotalCartSumma: '#total-cart-summa',
            elCartItem: '.js-cart-item',
            elCartCount: '.js-count',
            elCartSumma: '.js-summa',
            elChangeCount: '.js-change-count',
            elRemoveFromCart: '.js-remove-from-cart'
        };
        _.defaults(options || {}, defaultOptions);
        opts = _.clone(options);
    }

    // Навешивам события
    function _bindHandlers() {
        _onClickAddBtn();
        _onClickChangeCountInCart();
        _onClickRemoveFromCart();
    }

    // Получаем данные
    function updateData() {
        cartData = JSON.parse(localStorage.getItem('cart')) || [];
        return cartData;
    }

    // Возвращаем данные
    function getData() {
        return cartData;
    }

    // Сохраняем данные в localStorage
    function saveData() {
        localStorage.setItem('cart', JSON.stringify(cartData));
        return cartData;
    }

    // Очищаем данные
    function clearData() {
        cartData = [];
        saveData();
        return cartData;
    }

    // Поиск объекта в коллекции cartData по id
    function getById(id) {
        return _.findWhere(cartData, {id: id});
    }

    // Добавление товара в коллекцию
    function add(item) {
        let oldItem;
        updateData();
        oldItem = getById(item.id);
        if(!oldItem) {
            cartData.push(item);
        } else {
            oldItem.count = oldItem.count + item.count;
        }
        saveData();
        return item;
    }

    // Удаление товара из коллекции
    function remove(id) {
        updateData();
        cartData = _.reject(cartData, function(item) {
            return item.id === id;
        });
        saveData();
        return cartData;
    }

    // Изменение количества товара в коллекции
    function changeCount(id, delta) {
        let item;
        updateData();
        item = getById(id);
        if(item) {
            item.count = item.count + delta;
            if (item.count < 1) {
                remove(id);
            }
            saveData();
        }
        return _.findWhere(cartData, {id: id}) || {};
    }

    // Возвращаем количество товаров (количество видов товаров в корзине)
    function getCount() {
        return _.size(cartData);
    }

    // Возвращаем общее количество товаров 
    function getCountAll() {
        return _.reduce(cartData, function(sum, item) {return sum + item.count}, 0);
    }

    // Возвращаем общую сумму
    function getSumma() {
        return _.reduce(cartData, function(sum, item) {return sum + item.count * item.price}, 0);
    }






    // Рендерим корзину
    function renderCart() {
        let template = _.template($('#cart-template').html()),
            data = {
                goods: cartData
            };
        $(opts.elCart).html(template(data));
        renderTotalCartSumma();
    }

    // Рендерим количество товаров в меню
    function renderMenuCart() {
        let countAll = getCountAll();
        $(opts.elTotalCartCount).html(countAll !== 0 ? countAll : '');
    }

    // Рендерим общую сумму товаров
    function renderTotalCartSumma() {
        $(opts.elTotalCartSumma).html(getSumma());            
    }



    // Поиск продукта в корзине по id
    function findCartElemById(id) {
        return $(opts.elCartItem + '[' + opts.attrId + '="'+id+'"]');
    }

    // Добавление в корзину
    function _onClickAddBtn() {
        $('body').on('click', opts.elAddToCart, function(e) {
            let $this = $(this);
            add({
                id: +$this.attr(opts.attrId),
                name: $this.attr(opts.attrName),
                price: +$this.attr(opts.attrPrice),
                count: 1
            });    
            renderMenuCart();
            alert('Товар додано у кошик');
        });
    }

    // Меняем количество товаров в корзине
    function _onClickChangeCountInCart() {
        $('body').on('click', opts.elChangeCount, function(e) {
            let $this = $(this),
                id = +$this.attr(opts.attrId),
                delta = +$this.attr(opts.attrDelta),
                $cartElem = findCartElemById(id),
                cartItem = changeCount(id, delta);
            if (cartItem.count) {
                $cartElem.find(opts.elCartCount).html(cartItem.count);
                $cartElem.find(opts.elCartSumma).html(cartItem.count * cartItem.price);
            } else {
                $cartElem.remove();
            }
            renderMenuCart();
            renderTotalCartSumma();
        });
    }

    // Удаляем товар из корзине
    function _onClickRemoveFromCart() {
        $('body').on('click', opts.elRemoveFromCart, function(e) {
            if(!confirm('Видалити товар з кошику?')) return false;
            let $this = $(this),
                id = +$this.attr(opts.attrId),
                $cartElem = findCartElemById(id);
            remove(id);
            $cartElem.remove();
            renderMenuCart();
            renderTotalCartSumma();
        });
    }



    // Экспортируем наружу
    return {
        init: init,
        update: updateData,
        getData: getData,
        save: saveData,
        clearData: clearData,
        getById: getById,
        add: add,
        remove: remove,
        changeCount: changeCount,
        getCount: getCount,
        getCountAll: getCountAll,
        getSumma: getSumma
    }

})(jQuery);