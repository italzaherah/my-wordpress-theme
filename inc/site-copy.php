<?php
/** Theme adapter for the plugin-owned site-copy publication record. */
defined( 'ABSPATH' ) || exit;

function alzaherah_copy_value( $key, $fallback = '' ) {
	return class_exists( 'ALZ_Site_Copy' ) ? ALZ_Site_Copy::value( $key, $fallback ) : (string) $fallback;
}

function alzaherah_copy_e( $key, $fallback ) {
	echo '<span data-alz-copy="' . esc_attr( $key ) . '">' . esc_html( alzaherah_copy_value( $key, $fallback ) ) . '</span>';
}

/** All defaults mirror the actual template and respect current customizations. */
function alzaherah_site_copy_schema( $schema ) {
	$shop = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' );
	$exams = function_exists( 'alzaherah_exams_page_url' ) ? alzaherah_exams_page_url() : home_url( '/exams/' );
	$account = function_exists( 'alzaherah_account_url' ) ? alzaherah_account_url() : home_url( '/my-account/' );
	$signup = function_exists( 'alzaherah_signup_url' ) ? alzaherah_signup_url() : add_query_arg( 'view', 'register', $account );
	$products = class_exists( 'ALZ_Training_Products' ) ? ALZ_Training_Products::page_url() : home_url( '/training-products/' );
	$add = static function ( $section, $key, $label, $default, $type = 'text' ) use ( &$schema ) {
		$schema[ $key ] = array( 'section' => $section, 'label' => $label, 'default' => (string) $default, 'type' => $type );
	};
	$section = 'الافتتاحية';
	$add( $section, 'hero_badge', 'العبارة العلوية', get_theme_mod( 'alzaherah_hero_badge', 'منصتك نحو الاحتراف' ) );
	$add( $section, 'hero_title', 'العنوان الرئيسي', get_theme_mod( 'alzaherah_hero_title', 'طوّر مهاراتك مع أفضل الدورات التدريبية' ) );
	$add( $section, 'hero_text', 'الوصف', get_theme_mod( 'alzaherah_hero_text', 'سجّل، ادفع بأمان، وابدأ رحلتك التعليمية فورًا عبر تجربة عربية متكاملة وسهلة.' ), 'textarea' );
	foreach ( array( 'courses' => array( 'تصفّح الدورات', $shop ), 'exams' => array( 'الاختبارات', $exams ), 'account' => array( 'فتح لوحة حسابي', $account ), 'signup' => array( 'أنشئ حسابك مجانًا', $signup ), 'products' => array( 'المنتجات التدريبية', $products ), 'contact' => array( 'تواصل معنا', home_url( '/contact/' ) ) ) as $key => $button ) {
		$add( $section, 'hero_' . $key . '_label', 'نص زر: ' . $button[0], $button[0] );
		$add( $section, 'hero_' . $key . '_url', 'رابط زر: ' . $button[0], $button[1], 'url' );
	}
	foreach ( array( array( 'دفع عبر قناة محمية', 'تظهر الوسائل المفعّلة قبل تأكيد الطلب' ), array( 'متطلبات واضحة', 'نوع الشهادة والاعتماد — إن وُجدا — موضحان في صفحة الدورة' ), array( 'تأكيد موثّق', 'يُرسل التأكيد بعد ثبوت حالة الدفع أو اعتماده' ) ) as $index => $card ) {
		$add( 'بطاقات الثقة', 'trust_' . $index . '_title', 'عنوان البطاقة ' . ( $index + 1 ), $card[0] );
		$add( 'بطاقات الثقة', 'trust_' . $index . '_text', 'وصف البطاقة ' . ( $index + 1 ), $card[1], 'textarea' );
	}
	$sections = array(
		'courses' => array( 'الدورات المتاحة', 'برامج بمقاعد وموعد', 'الدورات المتاحة للتسجيل', 'دورات حضورية أو عن بُعد أو مدمجة. اختر المجال والموعد ثم أكمل التسجيل.', 'عرض جميع الدورات', $shop ),
		'self_paced' => array( 'الدورات الذاتية', 'ابدأ فور تأكيد الدفع', 'التعلم الذاتي', 'دورات إلكترونية ذاتية أو مسجّلة تصل إلى حسابك مباشرة بعد ثبوت الدفع، بلا موعد حضور.', 'عرض جميع الدورات', $shop ),
		'exams' => array( 'الاختبارات المتاحة', 'قِس مستواك الآن', 'الاختبارات المتاحة', 'اختبارات إلكترونية مستقلة بنتيجة فورية، تبدأ من حسابك بعد تأكيد الدفع.', 'عرض جميع الاختبارات', $exams ),
		'products' => array( 'المنتجات التدريبية الرقمية', 'مواد عملية جاهزة', 'المنتجات التدريبية الرقمية', 'ملفات وقوالب جاهزة للتنزيل بعد ثبوت الدفع، مع صلاحية تنزيل محددة في حسابك.', 'عرض جميع المنتجات', $products ),
		'popular' => array( 'الأكثر طلبًا', 'حسب عدد التسجيلات', 'الدورات الأكثر طلبًا', 'مرتبة وفق إجمالي التسجيلات المكتملة على المنصة، لا وفق ترتيب العرض في قسم الدورات المتاحة.', 'عرض جميع الدورات', $shop ),
		'testimonials' => array( 'آراء المتدربين', 'تجارب موثوقة', 'آراء متدربينا', 'تجارب يشاركها المتدربون وتظهر بعد مراجعتها واعتمادها من إدارة المنصة.' ),
		'news_articles' => array( 'الأخبار والمقالات', 'من المركز', 'أخبارنا ومقالاتنا', 'مستجدات المركز في الأخبار، ومعرفة مهنية قابلة للتطبيق في المقالات.' ),
		'partners' => array( 'شركاء النجاح', 'شراكات تصنع الأثر', 'شركاء النجاح', 'جهات نعتز بالتعاون معها في تطوير التدريب وخدمة المجتمع.' ),
		'final' => array( 'الدعوة الأخيرة', 'ابدأ اليوم', 'جاهز لتطوير مهاراتك؟', 'اختر دورتك، أو اختبارك، أو منتجك التدريبي، ثم أكمل التسجيل من حسابك خلال دقائق.' ),
	);
	foreach ( $sections as $key => $data ) {
		$add( $data[0], $key . '_eyebrow', 'العبارة العلوية', $data[1] );
		$add( $data[0], $key . '_title', 'العنوان', $data[2] );
		$add( $data[0], $key . '_text', 'الوصف', $data[3], 'textarea' );
		if ( isset( $data[4] ) ) { $add( $data[0], $key . '_label', 'نص زر عرض الجميع', $data[4] ); $add( $data[0], $key . '_url', 'رابط عرض الجميع', $data[5], 'url' ); }
	}
	foreach ( array( 'news' => array( 'أخبارنا', 'عرض جميع الأخبار', 'alzaherah-news' ), 'articles' => array( 'مقالاتنا', 'عرض جميع المقالات', 'alzaherah-articles' ) ) as $key => $data ) {
		$term = get_category_by_slug( $data[2] );
		$url = $term ? get_category_link( $term->term_id ) : home_url( '/category/' . $data[2] . '/' );
		if ( is_wp_error( $url ) ) { $url = home_url( '/category/' . $data[2] . '/' ); }
		$add( 'الأخبار والمقالات', $key . '_title', 'عنوان: ' . $data[0], $data[0] );
		$add( 'الأخبار والمقالات', $key . '_label', 'نص زر: ' . $data[0], $data[1] );
		$add( 'الأخبار والمقالات', $key . '_url', 'رابط: ' . $data[0], $url, 'url' );
	}
	foreach ( array( 'partners_all' => array( 'شركاء النجاح', 'عرض جميع الشركاء', home_url( '/partners/' ) ), 'partners_request' => array( 'شركاء النجاح', 'تقديم طلب شراكة', home_url( '/partnership-request/' ) ), 'final_courses' => array( 'الدعوة الأخيرة', 'استعرض الدورات', $shop ), 'final_exams' => array( 'الدعوة الأخيرة', 'استعرض الاختبارات', $exams ), 'final_signup' => array( 'الدعوة الأخيرة', 'إنشاء حساب', $signup ) ) as $key => $data ) {
		$add( $data[0], $key . '_label', 'نص زر: ' . $data[1], $data[1] );
		$add( $data[0], $key . '_url', 'رابط زر: ' . $data[1], $data[2], 'url' );
	}
	$add( 'التذييل', 'footer_name_ar', 'اسم المركز بالعربية', 'مركز الزاهرة للتدريب' );
	$add( 'التذييل', 'footer_name_en', 'اسم المركز بالإنجليزية', 'ALZAHERAH TRAINING CENTER' );
	$add( 'التذييل', 'footer_text', 'النبذة', 'منصة تدريب تجمع نخبة من البرامج الحضورية والإلكترونية، وتمنح الأفراد والمنشآت تجربة تسجيل ودفع متكاملة تحوّل التعلّم إلى أثر ملموس.', 'textarea' );
	foreach ( array( 'quick' => 'روابط سريعة', 'policies' => 'السياسات', 'contact' => 'تواصل معنا' ) as $key => $value ) { $add( 'التذييل', 'footer_' . $key . '_title', 'عنوان مجموعة: ' . $value, $value ); }
	$add( 'التذييل', 'footer_phone', 'رقم التواصل الظاهر في التذييل', get_theme_mod( 'alzaherah_phone', '+966553406661' ), 'tel' );
	$add( 'التذييل', 'footer_email', 'بريد التواصل الظاهر في التذييل', function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa', 'email' );
	$add( 'التذييل', 'footer_location', 'الموقع', get_theme_mod( 'alzaherah_location', 'الباحة، المملكة العربية السعودية' ) );
	$locations = function_exists( 'get_nav_menu_locations' ) ? get_nav_menu_locations() : array();
	$menu = ! empty( $locations['footer'] ) ? wp_get_nav_menu_items( $locations['footer'] ) : array();
	if ( $menu ) {
		foreach ( $menu as $item ) {
			$add( 'روابط التذييل الحالية', 'footer_menu_' . $item->ID . '_label', 'اسم الرابط: ' . $item->title, $item->title );
			$add( 'روابط التذييل الحالية', 'footer_menu_' . $item->ID . '_url', 'رابط: ' . $item->title, $item->url, 'url' );
		}
	} else {
		foreach ( array( 'courses' => array( 'البرامج التدريبية', $shop ), 'exams' => array( 'الاختبارات', $exams ), 'about' => array( 'عن المركز', home_url( '/about/' ) ), 'partners' => array( 'شركاء النجاح', home_url( '/partners/' ) ), 'news' => array( 'أخبارنا', $schema['news_url']['default'] ), 'articles' => array( 'مقالاتنا', $schema['articles_url']['default'] ), 'account' => array( 'حساب المتدرب', $account ) ) as $key => $data ) {
			$add( 'روابط التذييل الحالية', 'footer_link_' . $key . '_label', 'اسم الرابط: ' . $data[0], $data[0] );
			$add( 'روابط التذييل الحالية', 'footer_link_' . $key . '_url', 'رابط: ' . $data[0], $data[1], 'url' );
		}
	}
	$privacy = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
	foreach ( array( 'privacy' => array( 'سياسة الخصوصية', $privacy ? $privacy : home_url( '/privacy-policy/' ) ), 'refund' => array( 'الإلغاء والاسترجاع', home_url( '/refund-policy/' ) ), 'terms' => array( 'الشروط والأحكام', home_url( '/terms/' ) ), 'complaints' => array( 'الشكاوى والمقترحات', home_url( '/complaints-policy/' ) ), 'all' => array( 'جميع السياسات والحقوق', home_url( '/policy-center/' ) ) ) as $key => $data ) {
		$add( 'روابط السياسات في التذييل', 'footer_policy_' . $key . '_label', 'اسم الرابط: ' . $data[0], $data[0] );
		$add( 'روابط السياسات في التذييل', 'footer_policy_' . $key . '_url', 'رابط: ' . $data[0], $data[1], 'url' );
	}
	return $schema;
}
add_filter( 'alz_site_copy_schema', 'alzaherah_site_copy_schema' );

/**
 * Explain the theme-owned visibility contract without letting copy settings
 * publish an empty catalogue section. These checks are intentionally
 * read-only; the public template remains the source of truth.
 */
function alzaherah_site_copy_section_states( $states ) {
	$visible = array(
		'الافتتاحية',
		'بطاقات الثقة',
		'الدورات المتاحة',
		'الأكثر طلبًا',
		'الدعوة الأخيرة',
		'التذييل',
		'روابط التذييل الحالية',
		'روابط السياسات في التذييل',
	);
	foreach ( $visible as $section ) {
		$states[ $section ] = array(
			'visibility' => 'visible',
			'message'    => 'ظاهر حاليًا. تعديل النص لا يغيّر أهلية المحتوى أو حالة نشره.',
		);
	}

	$conditional = array(
		'الدورات الذاتية'          => 'يظهر عند وجود دورة ذاتية منشورة ومؤهلة للعرض.',
		'الاختبارات المتاحة'       => 'يظهر عند وجود اختبار منشور ومنتجه مؤهل للعرض.',
		'المنتجات التدريبية الرقمية' => 'يظهر عند وجود منتج تدريبي رقمي منشور ومؤهل للعرض.',
		'آراء المتدربين'            => 'يظهر عند وجود رأي معتمد أو نموذج إرسال متاح؛ حفظ النص لا يعتمد رأيًا.',
		'الأخبار والمقالات'         => 'يظهر عند وجود خبر أو مقال منشور ومؤهل للعرض.',
		'شركاء النجاح'              => 'يظهر عند وجود شريك معتمد ومؤهل للعرض؛ حفظ النص لا يعتمد شريكًا.',
	);
	foreach ( $conditional as $section => $message ) {
		$states[ $section ] = array(
			'visibility' => 'conditional',
			'message'    => $message,
		);
	}

	$set_current = static function ( $section, $has_content, $visible_message, $hidden_message ) use ( &$states ) {
		$states[ $section ] = array(
			'visibility' => $has_content ? 'visible' : 'hidden',
			'message'    => $has_content ? $visible_message : $hidden_message,
		);
	};
	if ( function_exists( 'get_posts' ) ) {
		$catalog_tax = array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		);
		$self_paced_args = array(
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => $catalog_tax,
			'meta_query'     => function_exists( 'alzaherah_self_paced_course_mode_meta_query' ) ? alzaherah_self_paced_course_mode_meta_query() : array(),
		);
		$self_paced_ids = get_posts( function_exists( 'alzaherah_course_query_args' ) ? alzaherah_course_query_args( $self_paced_args ) : array_merge( array( 'post_type' => 'product' ), $self_paced_args ) );
		$set_current( 'الدورات الذاتية', (bool) $self_paced_ids, 'ظاهر حاليًا لوجود دورة ذاتية مؤهلة.', 'مخفي حاليًا — لا توجد دورة ذاتية منشورة ومؤهلة للعرض.' );

		$exam_visible = false;
		if ( class_exists( 'ALZ_Exams' ) && function_exists( 'wc_get_product' ) ) {
			$exam_ids = get_posts( ALZ_Exams::query_args( array( 'post_status' => 'publish', 'posts_per_page' => 8, 'fields' => 'ids', 'no_found_rows' => true ) ) );
			foreach ( $exam_ids as $exam_id ) {
				$product = wc_get_product( $exam_id );
				if ( $product && $product->is_visible() ) { $exam_visible = true; break; }
			}
		}
		$set_current( 'الاختبارات المتاحة', $exam_visible, 'ظاهر حاليًا لوجود اختبار منشور ومنتج مؤهل.', 'مخفي حاليًا — لا يوجد اختبار منشور ومنتج مؤهل للعرض.' );

		$product_ids = array();
		if ( class_exists( 'ALZ_Training_Products' ) ) {
			$product_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array( array( 'key' => ALZ_Training_Products::KIND_META, 'value' => ALZ_Training_Products::KIND ) ),
					'tax_query'      => $catalog_tax,
				)
			);
		}
		$set_current( 'المنتجات التدريبية الرقمية', (bool) $product_ids, 'ظاهر حاليًا لوجود منتج تدريبي رقمي مؤهل.', 'مخفي حاليًا — لا يوجد منتج تدريبي رقمي منشور ومؤهل للعرض.' );

		$center_ids = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 1,
				'fields'              => 'ids',
				'category_name'       => 'alzaherah-news,alzaherah-articles',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		$set_current( 'الأخبار والمقالات', (bool) $center_ids, 'ظاهر حاليًا لوجود خبر أو مقال منشور.', 'مخفي حاليًا — لا يوجد خبر أو مقال منشور ومؤهل للعرض.' );
	}

	if ( is_callable( array( 'ALZ_Testimonials', 'approved_query' ) ) ) {
		$testimonial_query = ALZ_Testimonials::approved_query( 1 );
		if ( $testimonial_query instanceof WP_Query && $testimonial_query->have_posts() ) {
			$set_current( 'آراء المتدربين', true, 'ظاهر حاليًا لوجود رأي معتمد.', '' );
		} elseif ( is_callable( array( 'ALZ_Testimonials', 'render_public_form' ) ) ) {
			$states['آراء المتدربين'] = array( 'visibility' => 'conditional', 'message' => 'لا توجد آراء معتمدة حاليًا؛ قد يبقى القسم ظاهرًا إذا كان نموذج إرسال الرأي متاحًا للزائر المؤهل.' );
		} else {
			$set_current( 'آراء المتدربين', false, '', 'مخفي حاليًا — لا توجد آراء معتمدة أو نموذج إرسال متاح.' );
		}
	}

	if ( function_exists( 'alzaherah_partner_public_query' ) ) {
		$partner_query = alzaherah_partner_public_query();
		$set_current( 'شركاء النجاح', $partner_query instanceof WP_Query && $partner_query->have_posts(), 'ظاهر حاليًا لوجود شريك معتمد ومؤهل.', 'مخفي حاليًا — لا يوجد شريك معتمد ومؤهل للعرض.' );
	}

	return $states;
}
add_filter( 'alz_site_copy_section_states', 'alzaherah_site_copy_section_states' );

function alzaherah_copy_menu_objects( $items, $args ) {
	if ( 'footer' !== ( $args->theme_location ?? '' ) ) { return $items; }
	foreach ( $items as $index => $item ) {
		// Never modify cached WP menu objects used elsewhere in this request.
		$item = clone $item;
		$item->title = alzaherah_copy_value( 'footer_menu_' . $item->ID . '_label', $item->title );
		$item->url = alzaherah_copy_value( 'footer_menu_' . $item->ID . '_url', $item->url );
		$items[ $index ] = $item;
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'alzaherah_copy_menu_objects', 20, 2 );

function alzaherah_copy_menu_attributes( $attributes, $item, $args ) {
	if ( 'footer' === ( $args->theme_location ?? '' ) ) {
		$attributes['data-alz-copy'] = 'footer_menu_' . $item->ID . '_label';
		$attributes['data-alz-copy-href'] = 'footer_menu_' . $item->ID . '_url';
	}
	return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'alzaherah_copy_menu_attributes', 20, 3 );
