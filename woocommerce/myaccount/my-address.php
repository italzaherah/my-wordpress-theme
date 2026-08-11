<?php
/**
 * عرض بيانات المتدرب الأساسية دون جمع عنوان بريدي لا تحتاجه الدورات.
 *
 * @package WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();
$name        = trim(
	(string) get_user_meta( $customer_id, 'billing_first_name', true ) . ' ' .
	(string) get_user_meta( $customer_id, 'billing_last_name', true )
);
$national_id = (string) get_user_meta( $customer_id, 'national_id', true );
if ( ! $national_id ) {
	$national_id = (string) get_user_meta( $customer_id, 'billing_national_id', true );
}
$fields = array_filter(
	array(
		'name'     => array( __( 'الاسم', 'alzaherah' ), $name, '' ),
		'national' => array( __( 'رقم الهوية أو الإقامة', 'alzaherah' ), $national_id, 'ltr' ),
		'email'    => array( __( 'البريد الإلكتروني', 'alzaherah' ), (string) wp_get_current_user()->user_email, 'ltr' ),
		'phone'    => array( __( 'رقم الجوال', 'alzaherah' ), (string) get_user_meta( $customer_id, 'billing_phone', true ), 'ltr' ),
	),
	static function ( $field ) {
		return '' !== trim( (string) $field[1] );
	}
);
?>

<section class="alz-billing-overview" aria-labelledby="alz-trainee-title">
	<header class="alz-billing-overview__header">
		<div>
			<span class="alz-billing-overview__eyebrow"><?php esc_html_e( 'البيانات المستخدمة في التسجيل', 'alzaherah' ); ?></span>
			<h2 id="alz-trainee-title"><?php esc_html_e( 'بيانات المتدرب', 'alzaherah' ); ?></h2>
			<p><?php esc_html_e( 'تُستخدم هذه البيانات تلقائيًا لتسجيل الدورات وإصدار إثبات التسجيل.', 'alzaherah' ); ?></p>
		</div>
		<a class="button alz-billing-overview__edit" href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">
			<?php esc_html_e( 'تعديل بيانات الحساب', 'alzaherah' ); ?>
		</a>
	</header>

	<?php if ( $fields ) : ?>
		<dl class="alz-billing-overview__grid">
			<?php foreach ( $fields as $field ) : ?>
				<div>
					<dt><?php echo esc_html( $field[0] ); ?></dt>
					<dd<?php echo $field[2] ? ' dir="' . esc_attr( $field[2] ) . '"' : ''; ?>><?php echo esc_html( $field[1] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php else : ?>
		<div class="alz-billing-overview__empty"><?php esc_html_e( 'بيانات المتدرب غير مكتملة. استخدم زر التعديل لإكمالها.', 'alzaherah' ); ?></div>
	<?php endif; ?>
</section>

<?php do_action( 'woocommerce_my_account_after_my_address', 'billing' ); ?>
