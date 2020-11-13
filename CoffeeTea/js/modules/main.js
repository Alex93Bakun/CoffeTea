'use strict';

// Модуль приложения
let app = (function($) {

    let $body = $('body'),
        page = $body.data('page'),
        options = {
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
        },
        optionsCatalog = _.extend({
            renderCartOnInit: false,
            renderMenuCartOnInit: true
        }, options),
        optionsCart = _.extend({
            renderCartOnInit: true,
            renderMenuCartOnInit: true
        }, options),
        optionsOrder = _.extend({
            renderCartOnInit: false,
            renderMenuCartOnInit: true
        }, options);

	function init() {
		if (page === 'catalogDB') {
			catalogDB.init();
			cart.init(optionsCatalog);
		}
		if (page === 'cart') {
			cart.init(optionsCart);
		}
		if (page === 'order') {
			order.init();
			cart.init(optionsOrder);
		}
	}
    
    return {
        init: init
    }    

})(jQuery);

jQuery(document).ready(app.init);