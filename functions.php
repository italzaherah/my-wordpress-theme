<?php
/**
 * دوال وإعدادات قالب الزاهرة
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ALZAHERAH_THEME_VERSION' ) ) {
	define( 'ALZAHERAH_THEME_VERSION', '4.14.2' );
}

/**
 * رابط صفحة الاختبارات مع حماية من Fatal إن لم تكن نواة الاختبارات محمّلة.
 *
 * @return string
 */
function alzaherah_exams_page_url() {
	if ( class_exists( 'ALZ_Exams' ) && is_callable( array( 'ALZ_Exams', 'page_url' ) ) ) {
		$url = ALZ_Exams::page_url();
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}
	return home_url( '/exams/' );
}

/**
 * Whether the current request is the public exams catalogue.
 *
 * The option name is intentionally kept as a stable fallback so the Theme
 * can provide the correct shell even while Platform Core is loading.
 *
 * @return bool
 */
function alzaherah_is_exams_catalog_page() {
	$page_id = absint( get_option( 'alz_exams_page_id' ) );

	if ( class_exists( 'ALZ_Exams' ) && defined( 'ALZ_Exams::PAGE_OPTION' ) ) {
		$page_id = absint( get_option( ALZ_Exams::PAGE_OPTION ) );
	}

	return ( $page_id && is_page( $page_id ) ) || is_page( 'exams' );
}

/**
 * منع تشغيل توليفة قديمة لا تطبق إصلاحات Checkout والصلاحيات.
 */
function alzaherah_core_compatibility_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Keep in step with min_compatible_core in the plugin's alz-release-manifest.json.
	$min_core = '3.6.0';
	if ( ! defined( 'ALZ_CORE_VERSION' ) ) {
		/* translators: 1: theme version, 2: minimum plugin version. */
		echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'ثيم الزاهرة %1$s يحتاج إلى إضافة Alzaherah Platform Core %2$s لتشغيل تجربة الاختبارات وعقد الهوية v4.1.3 بأمان.', 'alzaherah' ), ALZAHERAH_THEME_VERSION, $min_core ) ) . '</p></div>';
		return;
	}
	if ( version_compare( ALZ_CORE_VERSION, $min_core, '<' ) ) {
		/* translators: %s: minimum plugin version. */
		echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'نسخة Alzaherah Platform Core الحالية غير متوافقة مع الثيم. حدّثها إلى %s قبل استقبال محاولات اختبار جديدة.', 'alzaherah' ), $min_core ) ) . '</p></div>';
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

	/* هوية v4: قصّ مستقل لسطح المكتب (1024×725) وآخر للجوال 4:3. */
	add_image_size( 'alz_announcement_desktop', 1024, 725, true );
	add_image_size( 'alz_announcement_mobile', 900, 675, true );

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
	$tokens_file   = get_template_directory() . '/assets/design-tokens-v4.css';
	$style_file    = get_stylesheet_directory() . '/style.css';
	$v4_file       = get_template_directory() . '/assets/theme-v4.css';
	$app_file      = get_template_directory() . '/assets/app.js';
	$tokens_version = file_exists( $tokens_file ) ? (string) filemtime( $tokens_file ) : $theme_version;
	$style_version = file_exists( $style_file ) ? (string) filemtime( $style_file ) : $theme_version;
	$v4_version    = file_exists( $v4_file ) ? (string) filemtime( $v4_file ) : $theme_version;
	$app_version   = file_exists( $app_file ) ? (string) filemtime( $app_file ) : $theme_version;

	wp_enqueue_style( 'alzaherah-design-tokens-v4', get_template_directory_uri() . '/assets/design-tokens-v4.css', array(), $tokens_version );
	wp_enqueue_style( 'alzaherah-style', get_stylesheet_uri(), array( 'alzaherah-design-tokens-v4' ), $style_version );
	wp_enqueue_style( 'alzaherah-theme-v4', get_template_directory_uri() . '/assets/theme-v4.css', array( 'alzaherah-style' ), $v4_version );
	wp_enqueue_script( 'alzaherah-app', get_template_directory_uri() . '/assets/app.js', array(), $app_version, true );

	// هوية v4 فوق أسطح محرك التعلم الداخلي (دورة/درس/اختبار قصير) دون تعديل ملفاته.
	if ( is_singular( array( 'courses', 'lesson', 'tutor_quiz', 'tutor_assignments' ) ) ) {
		$elearning_file = get_template_directory() . '/assets/elearning.css';
		if ( file_exists( $elearning_file ) ) {
			wp_enqueue_style(
				'alzaherah-elearning',
				get_template_directory_uri() . '/assets/elearning.css',
				array( 'alzaherah-theme-v4' ),
				(string) filemtime( $elearning_file )
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'alzaherah_assets' );

/** Shared components follow surface styles and are loaded only once for Theme + Core. */
function alzaherah_shared_components_assets() {
	$dependencies = array( 'alzaherah-design-tokens-v4', 'alzaherah-theme-v4' );
	foreach ( array( 'alzaherah-commerce', 'alzaherah-elearning', 'alz-core-platform', 'alz-core-exams-admin', 'alz-core-exams', 'alz-core-exam-catalog' ) as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) { $dependencies[] = $handle; }
	}
	$file = get_template_directory() . '/assets/shared-components.css';
	if ( ! is_readable( $file ) ) { return; }
	$version = ALZAHERAH_THEME_VERSION . '.' . substr( (string) hash_file( 'sha256', $file ), 0, 12 );
	wp_enqueue_style( 'alzaherah-shared-components', get_template_directory_uri() . '/assets/shared-components.css', $dependencies, $version );
}
add_action( 'wp_enqueue_scripts', 'alzaherah_shared_components_assets', 90 );

/** Preload only the Arabic Alexandria subset used above the fold. */
function alzaherah_preload_primary_font() {
	$font = get_theme_file_uri( 'assets/fonts/alexandria-var-arabic.woff2' );
	echo '<link rel="preload" href="' . esc_url( $font ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'alzaherah_preload_primary_font', 2 );

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

	if ( alzaherah_is_exams_catalog_page() ) {
		$classes[] = 'alz-exams-catalog';
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

	$shop_url              = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' );
	$training_products_url = class_exists( 'ALZ_Training_Products' ) ? ALZ_Training_Products::page_url() : home_url( '/training-products/' );
	$exams_url             = function_exists( 'alzaherah_exams_page_url' ) ? alzaherah_exams_page_url() : home_url( '/exams/' );

	echo '<ul class="' . esc_attr( $menu_class ) . '">';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'home' ) ) . '"><a href="' . esc_url( home_url( '/' ) ) . '"' . alzaherah_primary_nav_current_attr( 'home' ) . '>' . esc_html__( 'الرئيسية', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'courses' ) ) . '"><a href="' . esc_url( $shop_url ) . '"' . alzaherah_primary_nav_current_attr( 'courses' ) . '>' . esc_html__( 'الدورات', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'training-products' ) ) . '"><a href="' . esc_url( $training_products_url ) . '"' . alzaherah_primary_nav_current_attr( 'training-products' ) . '>' . esc_html__( 'المنتجات التدريبية', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'exams' ) ) . '"><a href="' . esc_url( $exams_url ) . '"' . alzaherah_primary_nav_current_attr( 'exams' ) . '>' . esc_html__( 'الاختبارات', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'policies' ) ) . '"><a href="' . esc_url( home_url( '/policy-center/' ) ) . '"' . alzaherah_primary_nav_current_attr( 'policies' ) . '>' . esc_html__( 'السياسات', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'partners' ) ) . '"><a href="' . esc_url( home_url( '/partners/' ) ) . '"' . alzaherah_primary_nav_current_attr( 'partners' ) . '>' . esc_html__( 'شركاء النجاح', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'about' ) ) . '"><a href="' . esc_url( home_url( '/about/' ) ) . '"' . alzaherah_primary_nav_current_attr( 'about' ) . '>' . esc_html__( 'عن المركز', 'alzaherah' ) . '</a></li>';
	echo '<li class="' . esc_attr( alzaherah_primary_nav_item_class( 'contact' ) ) . '"><a href="' . esc_url( home_url( '/contact/' ) ) . '"' . alzaherah_primary_nav_current_attr( 'contact' ) . '>' . esc_html__( 'تواصل معنا', 'alzaherah' ) . '</a></li>';
	echo '</ul>';
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
	$load = is_front_page() || is_page_template( 'page-registration.php' ) || is_page_template( 'page-training-products.php' ) || alzaherah_is_exams_catalog_page();
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

	$wp_styles = wp_styles();
	if ( isset( $wp_styles->registered['alzaherah-theme-v4'] ) && ! in_array( 'alzaherah-commerce', $wp_styles->registered['alzaherah-theme-v4']->deps, true ) ) {
		$wp_styles->registered['alzaherah-theme-v4']->deps[] = 'alzaherah-commerce';
	}
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

/** Present the Tap gateway in Arabic without changing its processing contract. */
function alzaherah_tap_gateway_title( $title, $gateway_id ) {
	return 'tap' === sanitize_key( (string) $gateway_id )
		? __( 'الدفع الإلكتروني عبر Tap', 'alzaherah' )
		: $title;
}
add_filter( 'woocommerce_gateway_title', 'alzaherah_tap_gateway_title', 20, 2 );

function alzaherah_tap_gateway_description( $description, $gateway_id ) {
	return 'tap' === sanitize_key( (string) $gateway_id )
		? __( 'أكمل الدفع بأمان عبر صفحة Tap. تظهر الوسائل المتاحة فعليًا حسب جهازك وإعدادات العملية.', 'alzaherah' )
		: $description;
}
add_filter( 'woocommerce_gateway_description', 'alzaherah_tap_gateway_description', 20, 2 );

/**
 * هوية v4 فصل 12: زر الدفع يعرض المبلغ النهائي «ادفع 1,399.00 ر.س».
 */
function alzaherah_identity_order_button_text( $text ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $text;
	}
	$total = wp_strip_all_tags( html_entity_decode( (string) WC()->cart->get_total(), ENT_QUOTES, 'UTF-8' ) );
	$total = trim( preg_replace( '/\s+/u', ' ', $total ) );
	if ( '' === $total ) {
		return $text;
	}
	/* translators: %s: formatted order total, e.g. 1,399.00 ر.س */
	return sprintf( __( 'ادفع %s', 'alzaherah' ), $total );
}
add_filter( 'woocommerce_order_button_text', 'alzaherah_identity_order_button_text', 40 );

/**
 * بوابة Tap تطبع وصفًا إنجليزيًا داخل payment_box. استبداله بنص الثيم العربي المعتمد.
 */
function alzaherah_localize_tap_payment_box() {
	if ( ! function_exists( 'is_checkout' ) || ( ! is_checkout() && ! is_page_template( 'page-registration.php' ) ) ) {
		return;
	}
	$copy = wp_json_encode( __( 'أكمل الدفع بأمان عبر صفحة Tap. تظهر الوسائل المتاحة فعليًا حسب جهازك وإعدادات العملية.', 'alzaherah' ) );
	?>
	<script>
	(function () {
		var copy = <?php echo $copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode. ?>;
		function alzLocalizeTapBox() {
			document.querySelectorAll('.payment_method_tap .payment_box > p').forEach(function (p) {
				if (/Pay with your credit card via Tap/i.test(p.textContent || '')) {
					p.textContent = copy;
				}
			});
			var btn = document.getElementById('place_order');
			if (btn && document.querySelector('form.checkout.processing')) {
				btn.setAttribute('aria-busy', 'true');
			} else if (btn) {
				btn.removeAttribute('aria-busy');
			}
		}
		document.addEventListener('DOMContentLoaded', alzLocalizeTapBox);
		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout checkout_error', alzLocalizeTapBox);
			window.jQuery(document.body).on('checkout_place_order', function () {
				var btn = document.getElementById('place_order');
				if (btn) btn.setAttribute('aria-busy', 'true');
			});
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'alzaherah_localize_tap_payment_box', 40 );

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
 * صياغة زر الإضافة حسب نوع المنتج (دورة مقابل منتج رقمي).
 *
 * @return string
 */
function alzaherah_course_add_to_cart_text() {
	$product_id = 0;
	if ( function_exists( 'wc_get_product' ) ) {
		global $product;
		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$product_id = get_queried_object_id();
		}
	}
	if ( $product_id && function_exists( 'alzaherah_is_training_product_id' ) && alzaherah_is_training_product_id( $product_id ) ) {
		return is_product() ? __( 'أضف إلى السلة', 'alzaherah' ) : __( 'اشتر الآن', 'alzaherah' );
	}
	return __( 'سجّل الآن', 'alzaherah' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'alzaherah_course_add_to_cart_text' );
add_filter( 'woocommerce_product_add_to_cart_text', 'alzaherah_course_add_to_cart_text' );

/**
 * الدورات تذهب للدفع؛ المنتجات الرقمية تبقى في السلة لتعديل الكمية.
 *
 * @param string $url رابط التحويل الأصلي.
 * @return string
 */
function alzaherah_add_to_cart_redirect( $url ) {
	$product_id = absint( $_REQUEST['add-to-cart'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $product_id && function_exists( 'alzaherah_is_training_product_id' ) && alzaherah_is_training_product_id( $product_id ) ) {
		return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $url;
	}
	return function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'alzaherah_add_to_cart_redirect' );

/**
 * أصول متحكم الكمية وتحديث السلة عبر Store API.
 */
function alzaherah_qty_cart_assets() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	$load = is_product() || is_cart();
	if ( ! $load ) {
		return;
	}
	$path = get_template_directory() . '/assets/js/alz-qty-cart.js';
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : ALZAHERAH_THEME_VERSION;
	wp_enqueue_script(
		'alz-qty-cart',
		get_template_directory_uri() . '/assets/js/alz-qty-cart.js',
		array(),
		$ver,
		true
	);
	wp_localize_script(
		'alz-qty-cart',
		'alzCart',
		array(
			'storeNonce'     => wp_create_nonce( 'wc_store_api' ),
			'storeUpdateUrl' => esc_url_raw( rest_url( 'wc/store/v1/cart/update-item' ) ),
			'cartUrl'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'alzaherah_qty_cart_assets', 35 );

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
require_once get_template_directory() . '/inc/course-price.php';
require_once get_template_directory() . '/inc/trainee-profile.php';
require_once get_template_directory() . '/inc/account-security.php';
require_once get_template_directory() . '/inc/email-branding.php';
require_once get_template_directory() . '/inc/legal-compliance.php';
require_once get_template_directory() . '/inc/partners.php';
require_once get_template_directory() . '/inc/site-copy.php';
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
