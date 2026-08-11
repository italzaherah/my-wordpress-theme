<?php
/**
 * دوال وإعدادات قالب الزاهرة
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ALZAHERAH_THEME_VERSION' ) ) {
	define( 'ALZAHERAH_THEME_VERSION', '4.8.4' );
}

/**
 * منع تشغيل توليفة قديمة لا تطبق إصلاحات Checkout والصلاحيات.
 */
function alzaherah_core_compatibility_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! defined( 'ALZ_CORE_VERSION' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'ثيم الزاهرة 4.8.4 يحتاج إلى إضافة Alzaherah Platform Core 3.0.8 لتشغيل كتالوج المنتجات الرقمية ومسار الدفع الموحّد بأمان.', 'alzaherah' ) . '</p></div>';
		return;
	}
	if ( version_compare( ALZ_CORE_VERSION, '3.0.8', '<' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'نسخة Alzaherah Platform Core الحالية غير متوافقة مع الثيم. حدّثها إلى 3.0.8 قبل استقبال طلبات جديدة.', 'alzaherah' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'alzaherah_core_compatibility_notice' );

/**
 * فرض قالب كتالوج المنتجات الرقمية حتى لو لم تُحفظ صفحة WordPress على القالب بعد.
 *
 * @param string $template مسار القالب.
 * @return string
 */
function alzaherah_training_products_template_include( $template ) {
	if ( ! class_exists( 'ALZ_Training_Products' ) ) {
		return $template;
	}
	$page_id = absint( get_option( ALZ_Training_Products::PAGE_OPTION ) );
	if ( ! $page_id || ! is_page( $page_id ) ) {
		return $template;
	}
	$custom = trailingslashit( get_template_directory() ) . 'page-training-products.php';
	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'alzaherah_training_products_template_include', 40 );

/**
 * إعداد القالب الأساسي.
 */
function alzaherah_setup() {

	// تحميل ملفات الترجمة من مجلد /languages (عربي / إنجليزي).
	load_theme_textdomain( 'alzaherah', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 90,
			'width'       => 280,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// دعم WooCommerce + معرض صور المنتج (تكبير، عرض شرائح، نافذة منبثقة).
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'القائمة الرئيسية', 'alzaherah' ),
			'footer'  => __( 'روابط التذييل', 'alzaherah' ),
		)
	);
}
add_action( 'after_setup_theme', 'alzaherah_setup' );

/**
 * أيقونة التبويب من ملف علامة المركز نفسه المستخدم في رأس الموقع.
 *
 * @return string[]
 */
function alzaherah_brand_favicon_links() {
	$version  = defined( 'ALZAHERAH_THEME_VERSION' ) ? ALZAHERAH_THEME_VERSION : wp_get_theme()->get( 'Version' );
	$icon_png = add_query_arg( 'ver', $version, get_theme_file_uri( 'assets/logo-mark.png' ) );

	return array(
		'<link rel="icon" href="' . esc_url( $icon_png ) . '" type="image/png">',
		'<link rel="shortcut icon" href="' . esc_url( $icon_png ) . '" type="image/png">',
		'<link rel="apple-touch-icon" href="' . esc_url( $icon_png ) . '">',
	);
}

/** استبدال Site Icon القديم في الواجهة ولوحة الإدارة بنفس العلامة الواضحة. */
function alzaherah_filter_site_icon_meta_tags( $meta_tags ) {
	return alzaherah_brand_favicon_links();
}
add_filter( 'site_icon_meta_tags', 'alzaherah_filter_site_icon_meta_tags', 20 );

/** طباعة البديل عند عدم تعيين Site Icon من ووردبريس. */
function alzaherah_brand_favicon_tags() {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}

	echo implode( "\n", alzaherah_brand_favicon_links() ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- الروابط هُرّبت داخل الدالة المولدة.
	echo '<meta name="theme-color" content="#ffffff">' . "\n";
}
add_action( 'wp_head', 'alzaherah_brand_favicon_tags', 100 );
add_action( 'admin_head', 'alzaherah_brand_favicon_tags', 100 );
add_action( 'login_head', 'alzaherah_brand_favicon_tags', 100 );

/**
 * تحميل ملفات التنسيق والسكربت.
 */
function alzaherah_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );
	$style_file    = get_stylesheet_directory() . '/style.css';
	$app_file      = get_template_directory() . '/assets/app.js';
	$style_version = file_exists( $style_file ) ? (string) filemtime( $style_file ) : $theme_version;
	$app_version   = file_exists( $app_file ) ? (string) filemtime( $app_file ) : $theme_version;

	wp_enqueue_style( 'alzaherah-style', get_stylesheet_uri(), array(), $style_version );
	wp_enqueue_script( 'alzaherah-app', get_template_directory_uri() . '/assets/app.js', array(), $app_version, true );
}
add_action( 'wp_enqueue_scripts', 'alzaherah_assets' );

/**
 * رابط صفحة الدورات (متجر WooCommerce) مع بديل آمن.
 *
 * @return string
 */
function alzaherah_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/shop/' );
}

/**
 * رابط حساب المتدرب (صفحة "حسابي" في WooCommerce) مع بديل آمن.
 *
 * @return string
 */
function alzaherah_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/my-account/' );
}

/**
 * رابط تسجيل الدخول المستقل.
 *
 * @return string
 */
function alzaherah_login_url() {
	return add_query_arg( 'view', 'login', alzaherah_account_url() );
}

/**
 * رابط إنشاء الحساب المستقل.
 *
 * @return string
 */
function alzaherah_signup_url() {
	return add_query_arg( 'view', 'register', alzaherah_account_url() );
}

/**
 * إضافة كلاس للصفحة لتحديد نموذج حسابي المطلوب عرضه.
 *
 * WooCommerce يضع تسجيل الدخول وإنشاء الحساب في صفحة واحدة؛
 * لذلك نستخدم ?view=login و ?view=register لإظهار النموذج المقصود فقط.
 *
 * @param array $classes كلاسات body.
 * @return array
 */
function alzaherah_account_view_body_class( $classes ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
		$view = isset( $_GET['view'] )
			? sanitize_key( wp_unslash( $_GET['view'] ) )
			: 'login';

		$classes[] = ( 'register' === $view )
			? 'alzaherah-register-view'
			: 'alzaherah-login-view';
	}

	return $classes;
}
add_filter( 'body_class', 'alzaherah_account_view_body_class' );

/**
 * قائمة احتياطية تظهر قبل إنشاء قائمة من لوحة التحكم.
 */
function alzaherah_fallback_menu( $args = array() ) {
	$menu_class = 'nav-links';
	if ( is_object( $args ) && ! empty( $args->menu_class ) ) {
		$menu_class = $args->menu_class;
	} elseif ( is_array( $args ) && ! empty( $args['menu_class'] ) ) {
		$menu_class = $args['menu_class'];
	}

	echo '<div class="' . esc_attr( $menu_class ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'الرئيسية', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( alzaherah_shop_url() ) . '">' . esc_html__( 'الدورات', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( class_exists( 'ALZ_Training_Products' ) ? ALZ_Training_Products::page_url() : home_url( '/training-products/' ) ) . '">' . esc_html__( 'المنتجات التدريبية', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( home_url( '/policy-center/' ) ) . '">' . esc_html__( 'السياسات', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( home_url( '/partners/' ) ) . '">' . esc_html__( 'شركاء النجاح', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'عن المركز', 'alzaherah' ) . '</a>';
	echo '<a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'تواصل معنا', 'alzaherah' ) . '</a>';
	echo '</div>';
}

/**
 * سهم واجهة موحد مرسوم كـ SVG حتى لا يعتمد شكله على خط الجهاز.
 *
 * @param string $direction forward (يسار في RTL) أو back (يمين).
 * @return string
 */
function alzaherah_ui_arrow( $direction = 'forward' ) {
	$path = 'back' === $direction ? 'M9 5l7 7-7 7' : 'M15 5l-7 7 7 7';

	return '<svg class="alz-ui-arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . esc_attr( $path ) . '"/></svg>';
}

/**
 * إعدادات "تخصيص المظهر".
 *
 * ملاحظة مهمة: القيم الافتراضية هنا يجب أن تبقى مطابقة تمامًا
 * للقيم الافتراضية المستخدمة في front-page.php حتى لا يظهر
 * محتوى مختلف بين المعاينة والموقع الفعلي.
 *
 * @param WP_Customize_Manager $wp_customize كائن التخصيص.
 */
function alzaherah_customize_register( $wp_customize ) {

	/* ---------- قسم الصفحة الرئيسية ---------- */
	$wp_customize->add_section(
		'alzaherah_home',
		array(
			'title'    => __( 'إعدادات الصفحة الرئيسية', 'alzaherah' ),
			'priority' => 25,
		)
	);

	$home_fields = array(
		'hero_badge'        => array(
			__( 'النص أعلى العنوان', 'alzaherah' ),
			__( 'منصتك نحو الاحتراف', 'alzaherah' ),
			'text',
		),
		'hero_title'        => array(
			__( 'العنوان الرئيسي', 'alzaherah' ),
			__( 'طوّر مهاراتك مع أفضل الدورات التدريبية', 'alzaherah' ),
			'text',
		),
		'hero_text'         => array(
			__( 'الوصف الرئيسي', 'alzaherah' ),
			__( 'سجّل، ادفع بأمان، وابدأ رحلتك التعليمية فورًا عبر تجربة عربية متكاملة وسهلة.', 'alzaherah' ),
			'textarea',
		),
	);

	foreach ( $home_fields as $key => $data ) {
		$wp_customize->add_setting(
			'alzaherah_' . $key,
			array(
				'default'           => $data[1],
				'sanitize_callback' => ( 'textarea' === $data[2] ) ? 'sanitize_textarea_field' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'alzaherah_' . $key,
			array(
				'label'   => $data[0],
				'section' => 'alzaherah_home',
				'type'    => $data[2],
			)
		);
	}

	// رابط صفحة "انضم كمدرب" المستخدم في قسم دعوة المدربين بالصفحة الرئيسية.
	$wp_customize->add_setting(
		'alzaherah_trainer_join_url',
		array(
			'default'           => home_url( '/join-as-trainer/' ),
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'alzaherah_trainer_join_url',
		array(
			'label'       => __( 'رابط صفحة انضمام المدربين', 'alzaherah' ),
			'description' => __( 'الصفحة التي يصل إليها زر «انضم كمدرب» في الصفحة الرئيسية.', 'alzaherah' ),
			'section'     => 'alzaherah_home',
			'type'        => 'url',
		)
	);

	/* ---------- قسم بيانات التواصل ---------- */
	$wp_customize->add_section(
		'alzaherah_contact',
		array(
			'title'    => __( 'بيانات مركز الزاهرة', 'alzaherah' ),
			'priority' => 30,
		)
	);

	$contact_fields = array(
		'phone'    => array( __( 'رقم الجوال', 'alzaherah' ), '+966553406661' ),
		'email'    => array( __( 'البريد الإلكتروني', 'alzaherah' ), 'contact@alzaherah.edu.sa' ),
		'location' => array( __( 'الموقع', 'alzaherah' ), __( 'الباحة، المملكة العربية السعودية', 'alzaherah' ) ),
	);

	foreach ( $contact_fields as $key => $data ) {
		$wp_customize->add_setting(
			'alzaherah_' . $key,
			array(
				'default'           => $data[1],
				'sanitize_callback' => ( 'email' === $key ) ? 'sanitize_email' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			'alzaherah_' . $key,
			array(
				'label'   => $data[0],
				'section' => 'alzaherah_contact',
				'type'    => ( 'email' === $key ) ? 'email' : 'text',
			)
		);
	}
}
add_action( 'customize_register', 'alzaherah_customize_register' );

/**
 * بريد التواصل المعتمد للمركز.
 *
 * عند نسخ الموقع إلى نطاق تجريبي قد تستبدل أداة الترحيل اسم النطاق داخل
 * البريد أيضًا، فيتحول إلى عنوان مؤقت على hostingersite.com. نمنع عرض هذا
 * العنوان للزوار ونعود إلى بريد المركز الرسمي مع إبقاء الحقل قابلًا للتعديل.
 *
 * @return string
 */
function alzaherah_local_placeholder_image_url() {
	return get_template_directory_uri() . '/assets/logo-mark.png';
}

/**
 * نوع كتالوج الدفع الحالي: courses | training | mixed | empty | other.
 *
 * @param WC_Order|null $order طلب اختياري لصفحات التأكيد.
 * @return string
 */
function alzaherah_catalog_checkout_kind( $order = null ) {
	if ( class_exists( 'ALZ_Registration' ) && is_callable( array( 'ALZ_Registration', 'catalog_checkout_kind' ) ) ) {
		return ALZ_Registration::catalog_checkout_kind( $order );
	}
	return 'courses';
}

/**
 * هل معرف المنتج منتج تدريبي رقمي؟
 *
 * @param int $product_id رقم المنتج.
 * @return bool
 */
function alzaherah_is_training_product_id( $product_id ) {
	return function_exists( 'alz_core_is_training_product' ) && alz_core_is_training_product( $product_id );
}

/**
 * طباعة بيانات بند الملخص في صفحة الدفع/التأكيد حسب نوع المنتج.
 *
 * @param int        $product_id رقم المنتج.
 * @param int        $quantity   الكمية.
 * @param WC_Product $product    كائن المنتج إن وُجد.
 * @return void
 */
function alzaherah_render_checkout_line_meta( $product_id, $quantity, $product = null ) {
	$product_id = absint( $product_id );
	$quantity   = max( 1, absint( $quantity ) );

	if ( alzaherah_is_training_product_id( $product_id ) ) {
		echo '<small>' . esc_html__( 'منتج رقمي', 'alzaherah' ) . '</small>';
		echo '<small>' . esc_html( sprintf( __( 'الكمية: %d', 'alzaherah' ), $quantity ) ) . '</small>';
		if ( $product instanceof WC_Product ) {
			$regular = $product->get_regular_price();
			$sale    = $product->get_sale_price();
			if ( '' !== (string) $sale && '' !== (string) $regular && (float) $sale < (float) $regular ) {
				echo '<small class="registration-price-compare"><del>' . wp_kses_post( wc_price( $regular ) ) . '</del> <ins>' . wp_kses_post( wc_price( $sale ) ) . '</ins></small>';
			} else {
				echo '<small class="registration-line-price">' . wp_kses_post( $product->get_price_html() ) . '</small>';
			}
		}
		echo '<small class="registration-download-note">' . esc_html__( 'رابط التنزيل يصبح متاحًا بعد تأكيد الدفع لمدة 90 يومًا.', 'alzaherah' ) . '</small>';
		return;
	}

	echo '<small>' . esc_html( sprintf( __( 'عدد المقاعد: %d', 'alzaherah' ), $quantity ) ) . '</small>';
	$date = $product_id ? get_post_meta( $product_id, '_alz_course_date', true ) : '';
	$date = function_exists( 'alzaherah_format_course_date' ) ? alzaherah_format_course_date( $date ) : $date;
	$time = $product_id ? get_post_meta( $product_id, '_alz_course_time', true ) : '';
	if ( $date || $time ) {
		echo '<small class="registration-course-date">' . esc_html( trim( $date . ' ' . $time ) ) . '</small>';
	}
}

function alzaherah_contact_email() {
	$fallback = 'contact@alzaherah.edu.sa';
	$email    = sanitize_email( get_theme_mod( 'alzaherah_email', $fallback ) );

	if ( ! is_email( $email ) ) {
		return $fallback;
	}

	$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
	if ( preg_match( '/(^|\.)hostingersite\.com$/i', $domain ) ) {
		return $fallback;
	}

	return $email;
}

/**
 * اعتماد بريد التواصل الجديد مرة واحدة عند ترقية القالب النشط.
 * يبقى الحقل قابلًا للتعديل لاحقًا من المخصص إذا تغيّر البريد مستقبلًا.
 */
function alzaherah_migrate_contact_email() {
	$version = '3.12.0';
	if ( get_option( 'alzaherah_contact_email_version' ) === $version ) {
		return;
	}

	$fallback = 'contact@alzaherah.edu.sa';
	$current  = sanitize_email( get_theme_mod( 'alzaherah_email', $fallback ) );
	$domain   = strtolower( (string) substr( strrchr( $current, '@' ), 1 ) );

	if ( ! is_email( $current ) || preg_match( '/(^|\.)hostingersite\.com$/i', $domain ) ) {
		set_theme_mod( 'alzaherah_email', $fallback );
	}

	update_option( 'alzaherah_contact_email_version', $version, false );
}
add_action( 'admin_init', 'alzaherah_migrate_contact_email' );
add_action( 'after_switch_theme', 'alzaherah_migrate_contact_email' );
/**
 * تحميل تنسيقات صفحات التجارة والتسجيل عند الحاجة.
 */
function alzaherah_commerce_assets() {
	$load = is_front_page() || is_page_template( 'page-registration.php' ) || is_page_template( 'page-training-products.php' );
	if ( class_exists( 'ALZ_Training_Products' ) ) {
		$page_id = absint( get_option( ALZ_Training_Products::PAGE_OPTION ) );
		$load    = $load || ( $page_id && is_page( $page_id ) ) || is_page( ALZ_Training_Products::PAGE_SLUG );
	}

	if ( function_exists( 'is_woocommerce' ) ) {
		$load = $load || is_woocommerce() || is_cart() || is_checkout() || is_account_page();
	}

	if ( ! $load ) {
		return;
	}

	$css_file = get_template_directory() . '/assets/commerce.css';
	$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'alzaherah-commerce',
		get_template_directory_uri() . '/assets/commerce.css',
		array( 'alzaherah-style' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'alzaherah_commerce_assets', 20 );

/**
 * رمز الريال السعودي المختصر.
 *
 * @param string $symbol رمز العملة الحالي.
 * @param string $currency رمز ISO.
 * @return string
 */
function alzaherah_currency_symbol( $symbol, $currency ) {
	return 'SAR' === $currency ? 'ر.س' : $symbol;
}
add_filter( 'woocommerce_currency_symbol', 'alzaherah_currency_symbol', 10, 2 );

/**
 * تثبيت اتجاه عرض السعر: 19,000 ر.س.
 *
 * @return string
 */
function alzaherah_price_format() {
	return '%2$s&nbsp;%1$s';
}
add_filter( 'woocommerce_price_format', 'alzaherah_price_format' );

/**
 * شارة سلة محدثة عبر WooCommerce fragments حتى لا يعرض Cache الصفحة رقمًا قديمًا.
 */
function alzaherah_cart_count_markup() {
	$count  = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
	$hidden = $count > 0 ? '' : ' hidden';
	return '<small class="alz-cart-count" aria-live="polite"' . $hidden . '>' . esc_html( $count > 0 ? number_format_i18n( $count ) : '' ) . '</small>';
}

function alzaherah_cart_count_fragment( $fragments ) {
	$fragments['.alz-cart-link .alz-cart-count'] = alzaherah_cart_count_markup();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'alzaherah_cart_count_fragment' );

function alzaherah_cart_fragment_assets() {
	if ( function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'alzaherah_cart_fragment_assets', 30 );

/**
 * رابط صفحة التسجيل والدفع.
 *
 * @return string
 */
function alzaherah_registration_url() {
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		$url = wc_get_checkout_url();
		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/registration/' );
}

/**
 * صياغة زر الدورة بشكل يناسب التسجيل.
 *
 * @return string
 */
function alzaherah_course_add_to_cart_text() {
	return __( 'سجّل الآن', 'alzaherah' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'alzaherah_course_add_to_cart_text' );
add_filter( 'woocommerce_product_add_to_cart_text', 'alzaherah_course_add_to_cart_text' );

/**
 * الانتقال إلى صفحة التسجيل بعد إضافة دورة إلى السلة.
 *
 * @param string $url رابط التحويل الأصلي.
 * @return string
 */
function alzaherah_add_to_cart_redirect( $url ) {
	return function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'alzaherah_add_to_cart_redirect' );

/**
 * عدد الدورات في كل صفحة.
 *
 * @return int
 */
function alzaherah_products_per_page() {
	return 12;
}
add_filter( 'loop_shop_per_page', 'alzaherah_products_per_page', 20 );

/**
 * إنشاء صفحة التسجيل وربطها بـ WooCommerce مرة واحدة، من دون تعديل الصفحات الأخرى.
 */
function alzaherah_prepare_registration_page() {
	if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$setup_version = ALZAHERAH_THEME_VERSION;
	if ( get_option( 'alzaherah_registration_setup_version' ) === $setup_version ) {
		return;
	}

	$page = get_page_by_path( 'registration' );

	if ( ! $page ) {
		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'التسجيل والدفع', 'alzaherah' ),
				'post_name'    => 'registration',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
	} else {
		$page_id = $page->ID;
	}

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-registration.php' );

		// لا نستبدل Checkout قائمًا أعدّه صاحب الموقع أو بوابة الدفع.
		$current_checkout_id = absint( get_option( 'woocommerce_checkout_page_id' ) );
		if ( ! $current_checkout_id || ! get_post( $current_checkout_id ) ) {
			update_option( 'woocommerce_checkout_page_id', (int) $page_id );
		}
	}

	update_option( 'alzaherah_registration_setup_version', $setup_version );
}
add_action( 'admin_init', 'alzaherah_prepare_registration_page' );

/**
 * تنبيه غير هدّام عندما تكون صفحة دفع أخرى مستخدمة بدل صفحة التسجيل المصممة في القالب.
 */
function alzaherah_checkout_page_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$registration = get_page_by_path( 'registration' );
	$checkout_id  = absint( get_option( 'woocommerce_checkout_page_id' ) );
	if ( ! $registration || (int) $registration->ID === $checkout_id ) {
		return;
	}
	echo '<div class="notice notice-info"><p>'
		. esc_html__( 'قالب الزاهرة لم يستبدل صفحة الدفع الحالية حفاظًا على إعدادات المتجر. لاستخدام تصميم التسجيل المرفق، اختر صفحة «التسجيل والدفع» من WooCommerce ← الإعدادات ← متقدم.', 'alzaherah' )
		. '</p></div>';
}
add_action( 'admin_notices', 'alzaherah_checkout_page_notice' );
require_once get_template_directory() . '/inc/platform.php';
require_once get_template_directory() . '/inc/account.php';
require_once get_template_directory() . '/inc/course-fields.php';
require_once get_template_directory() . '/inc/registrations.php';
require_once get_template_directory() . '/inc/course-status.php';
require_once get_template_directory() . '/inc/trainee-profile.php';
require_once get_template_directory() . '/inc/account-security.php';
require_once get_template_directory() . '/inc/email-branding.php';
require_once get_template_directory() . '/inc/legal-compliance.php';
require_once get_template_directory() . '/inc/partners.php';
/**
 * تلوين كلمات محورية في عنوان الواجهة دون السماح بأي HTML من إعدادات المستخدم.
 *
 * @param string $title العنوان الخام.
 * @return string HTML آمن.
 */
function alzaherah_highlight_hero_title( $title ) {
	$title = esc_html( $title );

	$replacements = array(
		'أفضل'    => '<span class="alz-highlight-gold">أفضل</span>',
		'الدورات' => '<span class="alz-highlight-green">الدورات</span>',
	);

	return strtr( $title, $replacements );
}

/**
 * تعريف نموذج دخول WooCommerce لمديري كلمات المرور في المتصفحات.
 * بعض القوالب/إصدارات WooCommerce لا تضع autocomplete على النموذج نفسه.
 */
function alzaherah_password_manager_login_attributes() {
	if ( is_user_logged_in() ) {
		return;
	}
	?>
	<script id="alz-password-manager-compat">
	var alzLoginObserver;
	function alzPrepareLoginForm() {
		var form = document.querySelector('form.woocommerce-form-login, form.login');
		if (!form) return;
		form.setAttribute('autocomplete', 'on');
		var username = form.querySelector('input[name="username"], input#username');
		var password = form.querySelector('input[name="password"], input#password');
		if (username) {
			username.setAttribute('autocomplete', 'username');
			username.setAttribute('autocapitalize', 'none');
			username.setAttribute('spellcheck', 'false');
		}
		if (password) password.setAttribute('autocomplete', 'current-password');
		if (alzLoginObserver) alzLoginObserver.disconnect();
	}
	document.addEventListener('DOMContentLoaded', alzPrepareLoginForm);
	alzLoginObserver = new MutationObserver(alzPrepareLoginForm);
	alzLoginObserver.observe(document.documentElement, { childList: true, subtree: true });
	</script>
	<?php
}
add_action( 'wp_footer', 'alzaherah_password_manager_login_attributes', 30 );
