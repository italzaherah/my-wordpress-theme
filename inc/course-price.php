<?php
/** Public course price labels only; stored prices and financial totals stay numeric. */
defined( 'ABSPATH' ) || exit;

/**
 * Render a public course price, preserving WooCommerce's paid/range/sale semantics.
 * Deliberately not a get_price_html/wc_price filter: checkout, orders, invoices,
 * administration, exams and digital products must keep their financial output.
 *
 * @param WC_Product $product Product or selected variation.
 * @return string WooCommerce-compatible price markup.
 */
function alzaherah_course_price_html( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$native_html = $product->get_price_html();
	$course_id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	if ( '' === $native_html || ! function_exists( 'alzaherah_is_confirmed_course_product' ) || ! alzaherah_is_confirmed_course_product( $course_id ) ) {
		return $native_html;
	}

	$regular = null;
	if ( $product->is_type( 'variable' ) ) {
		// A zero minimum does not make a mixed-price course free. Use the same
		// visible, display-tax-adjusted variation prices as WooCommerce's range.
		$prices = $product->get_variation_prices( true );
		if ( empty( $prices['price'] ) ) {
			return $native_html;
		}
		foreach ( $prices['price'] as $price ) {
			if ( ! is_numeric( $price ) || 0.0 !== (float) $price ) {
				return $native_html;
			}
		}
		$regular_prices = isset( $prices['regular_price'] ) ? $prices['regular_price'] : array();
		if ( $product->is_on_sale() && $regular_prices && count( array_filter( $regular_prices, 'is_numeric' ) ) === count( $regular_prices ) ) {
			$min_regular = min( $regular_prices );
			$max_regular = max( $regular_prices );
			if ( (float) $min_regular === (float) $max_regular && (float) $min_regular > 0 ) {
				$regular = $min_regular;
			}
		}
	} elseif ( $product->is_type( array( 'simple', 'variation', 'external' ) ) ) {
		$price = $product->get_price();
		// Empty/unset is not free; get_price() also respects active sale dates.
		if ( ! is_numeric( $price ) || 0.0 !== (float) $price ) {
			return $native_html;
		}
		$regular_price = $product->get_regular_price();
		if ( $product->is_on_sale() && is_numeric( $regular_price ) && (float) $regular_price > 0 ) {
			$regular = wc_get_price_to_display( $product, array( 'price' => $regular_price ) );
		}
	} else {
		// Grouped/extension product types may have paid children or custom rules.
		return $native_html;
	}

	$free = '<span class="alz-course-price-free">' . esc_html__( 'مجانًا', 'alzaherah' ) . '</span>';
	return '<span class="alz-course-price">' . ( null !== $regular ? wc_format_sale_price( wc_price( $regular ), $free ) : $free ) . '</span>' . $product->get_price_suffix();
}

/** Keep the selected free variation's public price consistent with its card. */
function alzaherah_course_variation_price_html( $data, $product, $variation ) {
	// An empty value is intentionally suppressed by WooCommerce/extensions.
	if ( isset( $data['price_html'] ) && '' !== $data['price_html'] && $product instanceof WC_Product && $variation instanceof WC_Product && (int) $variation->get_parent_id() === (int) $product->get_id() && function_exists( 'alzaherah_is_confirmed_course_product' ) && alzaherah_is_confirmed_course_product( $product->get_id() ) && is_numeric( $variation->get_price() ) && 0.0 === (float) $variation->get_price() ) {
		$data['price_html'] = '<span class="price">' . alzaherah_course_price_html( $variation ) . '</span>';
	}
	return $data;
}
add_filter( 'woocommerce_available_variation', 'alzaherah_course_variation_price_html', 20, 3 );
