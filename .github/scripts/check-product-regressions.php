<?php
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
/** Isolated rendering harness. No WordPress/database dependency; not a live-site smoke test. */
define( 'ABSPATH', __DIR__ . '/' );
$theme = $argv[1];
$protected = true;
$hooks = array();
$counts = array();
function __( $s, $domain = '' ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url( $s ) { return esc_html( $s ); }
function esc_html_e( $s, $domain = '' ) { echo esc_html( $s ); }
function esc_html__( $s, $domain = '' ) { return esc_html( $s ); }
function esc_attr_e( $s, $domain = '' ) { echo esc_attr( $s ); }
function wp_kses_post( $s ) { return $s; }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function sanitize_html_class( $s ) { return $s; }
function sanitize_key( $s ) { return $s; }
function get_header() {}
function get_footer() {}
function get_query_var( $s ) { return ''; }
function is_search() { return false; }
function is_shop() { return false; }
function is_product_taxonomy() { return false; }
function is_product() { return true; }
function post_password_required() { return $GLOBALS['protected']; }
function get_the_password_form() { return '<form id="password-gate">Protected</form>'; }
function get_queried_object_id() { return 1; }
function get_the_ID() { return 1; }
function have_posts() { static $once = false; if ( $once ) return false; $once = true; return true; }
function the_post() {}
function get_post_meta( $id, $key, $single = true ) { if ( $GLOBALS['protected'] ) throw new Exception( 'Password gate bypassed: metadata read' ); return ''; }
function get_the_title( $id = null ) { return 'Demo product'; }
function the_title() { echo 'Demo product'; }
function get_the_excerpt( $id = null ) { return 'Excerpt'; }
function has_excerpt() { return false; }
function the_content() { echo 'Description'; }
function home_url( $s ) { return 'https://example.test' . $s; }
function get_the_terms( $id, $taxonomy ) { return array(); }
function comments_open() { return false; }
function get_option( $key, $fallback = false ) { return $fallback; }
function wc_product_class( $s, $p ) { echo 'class="' . esc_attr( $s ) . '"'; }
function wc_get_product( $id ) { return new WC_Product(); }
function wc_get_checkout_url() { return home_url( '/checkout/' ); }
function wc_get_account_endpoint_url( $s ) { return home_url( '/my-account/' . $s . '/' ); }
function is_user_logged_in() { return false; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . $value; }
function absint( $v ) { return abs( (int) $v ); }
function wp_get_attachment_image_url( $id, $size ) { return ''; }
function get_post_type( $id ) { return 'attachment'; }
function get_post_status( $id ) { return 'inherit'; }
function get_post_mime_type( $id ) { return 'image/png'; }
function wc_price( $n ) { return number_format( $n, 2, '.', '' ); }
function wc_get_price_to_display( $product, $args ) { return $args['price'] * 1.15; }
class WC_Product {
  function get_id() { return 1; }
  function get_regular_price() { return '100'; }
  function get_sale_price() { return '50'; }
  function is_on_sale() { return true; }
  function get_meta( $key, $single = true ) { return ''; }
  function get_gallery_image_ids() { return array(); }
  function get_image_id() { return 0; }
  function is_purchasable() { return false; }
  function is_in_stock() { return true; }
  function get_price_html() { return '57.50'; }
  function get_price_suffix() { return '<small class="tax-suffix">Tax included</small>'; }
  function is_type( $type ) { return 'simple' === $type; }
}
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) { $GLOBALS['hooks'][$hook][$priority][$callback] = $callback; }
function remove_action( $hook, $callback, $priority = 10 ) { unset( $GLOBALS['hooks'][$hook][$priority][$callback] ); }
function has_action( $hook, $callback ) { foreach ( $GLOBALS['hooks'][$hook] ?? array() as $priority => $callbacks ) if ( isset( $callbacks[$callback] ) ) return $priority; return false; }
function do_action( $hook ) { $GLOBALS['counts'][$hook] = ( $GLOBALS['counts'][$hook] ?? 0 ) + 1; $callbacks = $GLOBALS['hooks'][$hook] ?? array(); ksort( $callbacks ); foreach ( $callbacks as $items ) foreach ( $items as $callback ) $callback(); }
function woocommerce_template_single_title() { echo 'DUPLICATE_STOCK_TITLE'; }
function theme_test_extension() { echo 'EXTENSION_MARKUP'; }
function theme_test_structured_data() { $GLOBALS['structured'] = ( $GLOBALS['structured'] ?? 0 ) + 1; }
function check( $ok, $label ) { echo json_encode( array( 'name' => $label, 'status' => $ok ? 'Passed' : 'Failed' ) ) . "\n"; if ( ! $ok ) $GLOBALS['failures']++; }
$failures = 0;
ob_start();
try { require $theme . '/woocommerce.php'; $protected_markup = ob_get_clean(); check( strpos( $protected_markup, 'password-gate' ) !== false, 'password-protected product requires password before metadata/gallery/purchase UI' ); }
catch ( Throwable $e ) { ob_end_clean(); check( false, 'password gate: ' . $e->getMessage() ); }
$protected = false;
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
add_action( 'woocommerce_single_product_summary', 'theme_test_extension', 15 );
add_action( 'woocommerce_single_product_summary', 'theme_test_structured_data', 60 );
ob_start(); require $theme . '/template-parts/product-digital.php'; $digital = ob_get_clean();
check( strpos( $digital, '>115.00</del>' ) !== false && strpos( $digital, '>57.50</ins>' ) !== false, 'digital sale prices follow display tax mode' );
check( strpos( $digital, 'tax-suffix' ) !== false, 'digital sale price retains tax suffix' );
check( strpos( $digital, 'EXTENSION_MARKUP' ) !== false && strpos( $digital, 'DUPLICATE_STOCK_TITLE' ) === false && ( $structured ?? 0 ) === 1, 'digital canonical summary preserves extensions/structured data without duplicate stock UI' );
$counts = array();
ob_start(); require $theme . '/template-parts/product-exam.php'; $exam = ob_get_clean();
check( strpos( $exam, 'EXTENSION_MARKUP' ) !== false && strpos( $exam, 'DUPLICATE_STOCK_TITLE' ) === false && ( $structured ?? 0 ) === 2, 'exam summary preserves extensions/structured data without duplicate stock UI' );
foreach ( array( 'woocommerce_before_single_product_summary', 'woocommerce_single_product_summary', 'woocommerce_after_single_product_summary', 'woocommerce_after_single_product' ) as $hook ) check( ( $counts[$hook] ?? 0 ) === 1, 'exam calls ' . $hook . ' once' );
check( has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title' ) === 5, 'stock summary callbacks restored after custom rendering' );
exit( $failures ? 1 : 0 );
