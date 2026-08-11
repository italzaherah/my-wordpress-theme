<?php
/**
 * تجاوز قالب "نظرة عامة" في حساب المتدرب.
 *
 * يحذف ترحيب WooCommerce الافتراضي ويعرض لوحة المتدرب
 * المخصصة القادمة من inc/account.php فقط.
 *
 * المسار داخل القالب: woocommerce/myaccount/dashboard.php
 *
 * @package WooCommerce\Templates
 * @version 4.4.0
 * @since   2.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * لوحة المتدرب المخصصة (alzaherah_account_dashboard).
 *
 * @hooked alzaherah_account_dashboard
 */
do_action( 'woocommerce_account_dashboard' );
