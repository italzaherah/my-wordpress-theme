<?php
/**
 * بطاقة الدورة — تفويض كامل إلى بطاقة البيع الموحدة (هوية v4 فصل 10).
 *
 * أبقينا هذا الملف حفاظًا على جميع الاستدعاءات القائمة
 * get_template_part( 'template-parts/course', 'card', $args )
 * بينما المصدر الوحيد للعرض هو template-parts/sales-card.php.
 *
 * @package Alzaherah
 * @since   3.10.8
 */

defined( 'ABSPATH' ) || exit;

get_template_part( 'template-parts/sales', 'card', isset( $args ) && is_array( $args ) ? $args : array() );
