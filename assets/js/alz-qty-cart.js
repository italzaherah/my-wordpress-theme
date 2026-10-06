/**
 * Alzaherah quantity stepper + cart Store API updates (no full reload).
 */
(function () {
	'use strict';

	function parseQty(value) {
		var n = parseInt(String(value).trim(), 10);
		return Number.isFinite(n) ? n : NaN;
	}

	function clamp(n, min, max) {
		if (Number.isFinite(min) && n < min) n = min;
		if (Number.isFinite(max) && max > 0 && n > max) n = max;
		return n;
	}

	function announce(root, message) {
		var live = root.querySelector('[data-alz-qty-live]');
		if (live) live.textContent = message || '';
	}

	function bindStepper(root) {
		if (root.getAttribute('data-alz-qty-bound') === '1') return;
		root.setAttribute('data-alz-qty-bound', '1');
		var input = root.querySelector('[data-alz-qty-input]');
		var minus = root.querySelector('[data-alz-qty-minus]');
		var plus = root.querySelector('[data-alz-qty-plus]');
		if (!input) return;

		var lastValid = parseQty(input.value);
		if (!Number.isFinite(lastValid) || lastValid < 1) lastValid = 1;

		function limits() {
			var min = parseQty(input.getAttribute('min'));
			var max = parseQty(input.getAttribute('max'));
			if (!Number.isFinite(min) || min < 1) min = 1;
			if (!Number.isFinite(max)) max = Infinity;
			return { min: min, max: max };
		}

		// An emptied or non-numeric field steps from the last valid quantity instead of NaN.
		function current() {
			var n = parseQty(input.value);
			return Number.isFinite(n) ? n : lastValid;
		}

		function setValue(n, announceMsg) {
			var lim = limits();
			n = clamp(n, lim.min, lim.max);
			input.value = String(n);
			lastValid = n;
			input.setAttribute('aria-valuenow', String(n));
			if (announceMsg) announce(root, announceMsg.replace('%d', String(n)));
			input.dispatchEvent(new Event('change', { bubbles: true }));
			input.dispatchEvent(new Event('alz-qty-change', { bubbles: true }));
		}

		function commitTyped() {
			var raw = String(input.value).trim();
			if (!/^\d+$/.test(raw)) {
				input.value = String(lastValid);
				announce(root, 'كمية غير صالحة. أُعيدت الكمية السابقة.');
				return;
			}
			var n = parseQty(raw);
			if (!Number.isFinite(n) || n < 1) {
				input.value = String(lastValid);
				announce(root, 'الحد الأدنى للكمية هو 1.');
				return;
			}
			var lim = limits();
			if (Number.isFinite(lim.max) && lim.max > 0 && n > lim.max) {
				setValue(lim.max, 'الحد الأقصى هو %d');
				return;
			}
			setValue(n, 'الكمية %d');
		}

		if (minus) {
			minus.addEventListener('click', function () {
				setValue(current() - 1, 'الكمية %d');
			});
		}
		if (plus) {
			plus.addEventListener('click', function () {
				setValue(current() + 1, 'الكمية %d');
			});
		}
		input.addEventListener('blur', commitTyped);
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				commitTyped();
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				setValue(current() + 1, 'الكمية %d');
			} else if (e.key === 'ArrowDown') {
				e.preventDefault();
				setValue(current() - 1, 'الكمية %d');
			}
		});
	}

	function bootSteppers(scope) {
		(scope || document).querySelectorAll('[data-alz-qty]').forEach(bindStepper);
	}

	/* ---------- Per-item Store API queue (no global busy / no reload) ---------- */
	var debounceTimers = {};
	var controllers = {};
	var queues = {};

	function storeNonce() {
		return (window.alzCart && window.alzCart.storeNonce) || '';
	}

	function updateNonceFromResponse(res) {
		var next = res.headers.get('Nonce') || res.headers.get('nonce');
		if (next && window.alzCart) window.alzCart.storeNonce = next;
	}

	function moneyFromMinor(amount, minor) {
		minor = Number.isFinite(minor) ? minor : 2;
		var n = Number(amount) / Math.pow(10, minor);
		return n.toFixed(minor) + ' ر.س';
	}

	function showItemState(row, state, message) {
		if (!row) return;
		row.classList.toggle('is-updating', state === 'loading');
		row.classList.toggle('is-error', state === 'error');
		row.classList.toggle('is-success', state === 'success');
		var status = row.querySelector('[data-alz-cart-status]');
		if (status) status.textContent = message || '';
	}

	function applyCartJson(cart) {
		if (!cart) return;
		var minor = (cart.totals && cart.totals.currency_minor_unit) || 2;
		if (cart.totals) {
			var totalText = moneyFromMinor(cart.totals.total_price, minor);
			document.querySelectorAll('[data-alz-cart-total]').forEach(function (el) {
				el.textContent = totalText;
			});
			var totalsTable = document.querySelector('.cart_totals table, .alz-cart__summary .shop_table');
			if (totalsTable && cart.totals.total_items != null) {
				var sub = totalsTable.querySelector('.cart-subtotal .amount, .cart-subtotal td');
				if (sub) sub.innerHTML = moneyFromMinor(cart.totals.total_items, minor);
			}
			var orderTotal = document.querySelector('.order-total .amount, .order-total td strong, .order-total td');
			if (orderTotal) orderTotal.innerHTML = moneyFromMinor(cart.totals.total_price, minor);
			if (cart.totals.total_discount && Number(cart.totals.total_discount) > 0) {
				var disc = document.querySelector('.cart-discount .amount, .cart-discount td');
				if (disc) disc.innerHTML = '-' + moneyFromMinor(cart.totals.total_discount, minor);
			}
		}
		if (Array.isArray(cart.items)) {
			cart.items.forEach(function (item) {
				var row = document.querySelector('[data-alz-cart-row][data-cart-key="' + item.key + '"]');
				if (!row) return;
				var input = row.querySelector('[data-alz-qty-input]');
				if (input && document.activeElement !== input) {
					input.value = String(item.quantity);
					row.setAttribute('data-alz-last-qty', String(item.quantity));
				}
				var line = row.querySelector('.alz-cart__line-total');
				if (line && item.totals && item.totals.line_total != null) {
					line.innerHTML = moneyFromMinor(item.totals.line_total, minor);
				}
			});
		}
		if (window.jQuery && window.wc_cart_fragments_params) {
			window.jQuery(document.body).trigger('wc_fragment_refresh');
		}
	}

	function enqueueUpdate(cartItemKey, quantity, row) {
		if (!queues[cartItemKey]) queues[cartItemKey] = Promise.resolve();
		queues[cartItemKey] = queues[cartItemKey].then(function () {
			return updateCartItem(cartItemKey, quantity, row);
		}).catch(function () {
			/* keep queue alive */
		});
	}

	function updateCartItem(cartItemKey, quantity, row) {
		if (controllers[cartItemKey]) {
			try { controllers[cartItemKey].abort(); } catch (e) {}
		}
		var ac = new AbortController();
		controllers[cartItemKey] = ac;
		showItemState(row, 'loading', 'جارٍ التحديث…');
		var url = (window.alzCart && window.alzCart.storeUpdateUrl) || '/wp-json/wc/store/v1/cart/update-item';
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			signal: ac.signal,
			headers: {
				'Content-Type': 'application/json',
				Nonce: storeNonce()
			},
			body: JSON.stringify({ key: cartItemKey, quantity: quantity })
		})
			.then(function (res) {
				updateNonceFromResponse(res);
				return res.json().then(function (data) {
					return { ok: res.ok, data: data };
				});
			})
			.then(function (result) {
				if (controllers[cartItemKey] !== ac) return;
				if (!result.ok) {
					var msg =
						(result.data && result.data.message) ||
						'تعذر تحديث الكمية. أُعيدت القيمة السابقة.';
					showItemState(row, 'error', msg);
					var input = row && row.querySelector('[data-alz-qty-input]');
					if (input && row.getAttribute('data-alz-last-qty')) {
						input.value = row.getAttribute('data-alz-last-qty');
						announce(row.querySelector('[data-alz-qty]') || row, msg);
					}
					return;
				}
				row.setAttribute('data-alz-last-qty', String(quantity));
				showItemState(row, 'success', 'تم تحديث الكمية');
				applyCartJson(result.data);
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') return;
				showItemState(row, 'error', 'تعذر تحديث الكمية. حاول مرة أخرى.');
				var input = row && row.querySelector('[data-alz-qty-input]');
				if (input && row.getAttribute('data-alz-last-qty')) {
					input.value = row.getAttribute('data-alz-last-qty');
				}
			});
	}

	function bindCartQty() {
		document.querySelectorAll('[data-alz-cart-row]').forEach(function (row) {
			var key = row.getAttribute('data-cart-key');
			var input = row.querySelector('[data-alz-qty-input]');
			if (!key || !input || row.getAttribute('data-alz-cart-bound') === '1') return;
			row.setAttribute('data-alz-cart-bound', '1');
			row.setAttribute('data-alz-last-qty', String(input.value));
			input.addEventListener('alz-qty-change', function () {
				var qty = parseQty(input.value);
				if (!Number.isFinite(qty) || qty < 1) return;
				if (debounceTimers[key]) window.clearTimeout(debounceTimers[key]);
				debounceTimers[key] = window.setTimeout(function () {
					enqueueUpdate(key, qty, row);
				}, 450);
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		bootSteppers(document);
		bindCartQty();
	});

	// WooCommerce's classic cart replaces the cart form over AJAX and announces it with jQuery
	// events (not native DOM events); bind the new steppers when that happens.
	if (window.jQuery) {
		window.jQuery(document.body).on('updated_wc_div updated_cart_totals', function () {
			bootSteppers(document);
			bindCartQty();
		});
	}
})();
