<?php
/**
 * Quantity stepper (− N +) shared by product and cart.
 *
 * @package Alzaherah
 * @version 10.1.0
 *
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

$qty_input_id     = isset( $input_id ) ? $input_id : 'quantity_' . wp_unique_id();
$qty_input_name   = isset( $input_name ) ? $input_name : 'quantity';
$qty_input_value  = isset( $input_value ) ? $input_value : '1';
$qty_max          = isset( $max_value ) ? $max_value : '';
$qty_min          = isset( $min_value ) ? $min_value : '';
$qty_step         = isset( $step ) ? $step : 1;
$qty_classes      = isset( $classes ) && is_array( $classes ) ? implode( ' ', $classes ) : 'input-text qty text';
$qty_inputmode    = isset( $inputmode ) ? $inputmode : 'numeric';
$qty_placeholder  = isset( $placeholder ) ? $placeholder : '';
$qty_readonly     = ! empty( $readonly );
$qty_autocomplete = isset( $autocomplete ) ? $autocomplete : 'on';
$qty_type         = isset( $type ) ? $type : 'number';
$qty_is_hidden    = 'hidden' === $qty_type;

/* translators: %s: product name. */
$qty_label = ! empty( $args['product_name'] )
	? sprintf( __( 'كمية %s', 'alzaherah' ), wp_strip_all_tags( $args['product_name'] ) )
	: __( 'الكمية', 'alzaherah' );
?>
<div class="quantity alz-qty<?php echo $qty_is_hidden ? ' is-hidden-input' : ''; ?>" data-alz-qty>
	<?php if ( ! $qty_is_hidden ) : ?>
		<button type="button" class="alz-qty__btn alz-qty__minus" aria-label="<?php esc_attr_e( 'إنقاص الكمية', 'alzaherah' ); ?>" aria-controls="<?php echo esc_attr( $qty_input_id ); ?>" data-alz-qty-minus <?php disabled( $qty_readonly ); ?>>−</button>
	<?php endif; ?>
	<?php
	/** This hook is part of WooCommerce's quantity template contract. */
	do_action( 'woocommerce_before_quantity_input_field' );
	?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $qty_input_id ); ?>"><?php echo esc_html( $qty_label ); ?></label>
	<input
		type="<?php echo esc_attr( $qty_type ); ?>"
		id="<?php echo esc_attr( $qty_input_id ); ?>"
		class="<?php echo esc_attr( $qty_classes ); ?> alz-qty__input"
		name="<?php echo esc_attr( $qty_input_name ); ?>"
		value="<?php echo esc_attr( $qty_input_value ); ?>"
		aria-label="<?php echo esc_attr( $qty_label ); ?>"
		<?php if ( '' !== $qty_min ) : ?>
			aria-valuemin="<?php echo esc_attr( $qty_min ); ?>"
			min="<?php echo esc_attr( $qty_min ); ?>"
		<?php endif; ?>
		<?php if ( 0 < $qty_max ) : ?>
			aria-valuemax="<?php echo esc_attr( $qty_max ); ?>"
			max="<?php echo esc_attr( $qty_max ); ?>"
		<?php endif; ?>
		<?php if ( ! $qty_readonly ) : ?>
			step="<?php echo esc_attr( $qty_step ); ?>"
			placeholder="<?php echo esc_attr( $qty_placeholder ); ?>"
			inputmode="<?php echo esc_attr( $qty_inputmode ); ?>"
			autocomplete="<?php echo esc_attr( $qty_autocomplete ); ?>"
		<?php else : ?>
			readonly="readonly"
		<?php endif; ?>
		data-alz-qty-input
	/>
	<?php
	/** This hook is part of WooCommerce's quantity template contract. */
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
	<?php if ( ! $qty_is_hidden ) : ?>
		<button type="button" class="alz-qty__btn alz-qty__plus" aria-label="<?php esc_attr_e( 'زيادة الكمية', 'alzaherah' ); ?>" aria-controls="<?php echo esc_attr( $qty_input_id ); ?>" data-alz-qty-plus <?php disabled( $qty_readonly ); ?>>+</button>
		<span class="screen-reader-text" data-alz-qty-live aria-live="polite" aria-atomic="true"></span>
	<?php endif; ?>
</div>
