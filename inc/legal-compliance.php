<?php
/**
 * Legal & policies presentation + Theme fallback when Core is absent.
 *
 * When Core ≥ 2.7.0 owns policies/legal profile:
 * - Theme does not own schema, defaults, or canonical save logic.
 * - Shortcode / pages / settings UI remain presentation.
 *
 * @package Alzaherah
 */

defined( 'ABSPATH' ) || exit;

/**
 * هل Core يملك السياسات والملف القانوني؟
 *
 * @return bool
 */
function alzaherah_core_owns_policies() {
	return class_exists( 'ALZ_Policies' )
		&& class_exists( 'ALZ_Legal_Profile' )
		&& defined( 'ALZ_CORE_VERSION' )
		&& version_compare( ALZ_CORE_VERSION, '2.7.0', '>=' );
}

/**
 * Return editable organisation data.
 *
 * @return array<string,string>
 */
function alzaherah_legal_profile() {
	if ( alzaherah_core_owns_policies() ) {
		return ALZ_Legal_Profile::get();
	}
	$defaults = array(
		'legal_name'       => 'مركز الزاهرة للتدريب',
		'commercial_name'  => 'مركز الزاهرة للتدريب',
		'commercial_reg'   => '5800102911',
		'tvtc_license'     => '2024121182-001812',
		'tax_registered'   => 'no',
		'tax_number'       => '',
		'national_address' => '',
		'phone'            => get_theme_mod( 'alzaherah_phone', '+966553406661' ),
		'email'            => function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa',
		'service_hours'    => '',
		'complaint_reply'  => '',
		'complaint_close'  => '',
		'refund_days'      => '',
		'business_verify'  => '',
		'license_verify'   => '',
	);
	$saved = get_option( 'alzaherah_legal_profile', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * Show a value or an honest completion marker to administrators.
 *
 * @param string $key Profile key.
 * @return string
 */
function alzaherah_legal_value( $key ) {
	if ( alzaherah_core_owns_policies() ) {
		return ALZ_Legal_Profile::display_value( $key );
	}
	$profile = alzaherah_legal_profile();
	$value   = isset( $profile[ $key ] ) ? trim( (string) $profile[ $key ] ) : '';
	if ( '' !== $value ) {
		return esc_html( $value );
	}
	return current_user_can( 'manage_options' )
		? '<mark class="alz-legal-missing">مطلوب استكماله قبل الإطلاق</mark>'
		: 'غير منشور بعد';
}

/**
 * Policy definitions — Core when available, Theme fallback otherwise.
 *
 * @return array<string,array<string,mixed>>
 */
function alzaherah_policy_definitions() {
	if ( alzaherah_core_owns_policies() ) {
		return ALZ_Policies::definitions();
	}
	return alzaherah_policy_definitions_fallback();
}

/**
 * Local fallback definitions (Theme-only when Core absent).
 *
 * @return array<string,array<string,mixed>>
 */
function alzaherah_policy_definitions_fallback() {
	$definitions = array(
		'policy-center' => array(
			'title'   => 'مركز السياسات والحقوق',
			'summary' => 'مرجع موحد لحقوق المتدرب، ضوابط التسجيل والدفع، حماية البيانات، الشكاوى، والنزاهة الأكاديمية.',
			'sections' => array(
				array( 'العلاقة النظامية', 'تُقرأ كل سياسة مع تفاصيل الدورة والفاتورة وأي شروط خاصة تظهر قبل إتمام التسجيل. وعند التعارض يُعمل بالنص الأكثر تحديدًا وبما لا ينتقص من الحقوق النظامية.' ),
				array( 'سهولة الوصول', 'تظهر روابط السياسات في تذييل الموقع وصفحة التسجيل والدفع، ويمكن حفظها أو طباعتها. يوضح أعلى كل سياسة تاريخ آخر تحديث.' ),
				array( 'الاستفسارات', 'يمكن طلب إيضاح أي بند عبر وسائل التواصل المنشورة. لا تعد هذه الصفحات بديلًا عن الأنظمة السارية أو الاستشارة القانونية المتخصصة.' ),
			),
		),
		'privacy-policy' => array(
			'title'   => 'سياسة الخصوصية وحماية البيانات الشخصية',
			'summary' => 'توضح هذه السياسة كيف يجمع المركز بيانات المتدربين ويستخدمها ويحميها، وكيف يمكن ممارسة الحقوق المتعلقة بها.',
			'sections' => array(
				array( 'جهة التحكم والتواصل', 'جهة التحكم هي ' . alzaherah_legal_value( 'legal_name' ) . '، ويمكن التواصل بشأن الخصوصية عبر ' . alzaherah_legal_value( 'email' ) . '.' ),
				array( 'البيانات التي نجمعها', 'قد تشمل بيانات الهوية والتواصل والحساب، بيانات التسجيل والدورة والحضور والتقييم والشهادة، تفاصيل الطلب والفاتورة وحالة الدفع، المراسلات والشكاوى، وبيانات تقنية ضرورية للأمن وتشغيل الموقع. لا يحفظ المركز أرقام البطاقات البنكية الكاملة داخل WordPress؛ تعالجها بوابة الدفع المختارة.' ),
				array( 'الأغراض والمسوغ', 'تعالج البيانات لتنفيذ عقد التسجيل وتقديم التدريب، التحقق من الأهلية والهوية والحضور، إدارة الدفع والفواتير والاسترداد، التواصل التشغيلي، إصدار الشهادات، منع الاحتيال والغش، الوفاء بالالتزامات النظامية، وتحسين الخدمة. التسويق المباشر لا يتم إلا بموافقة مستقلة قابلة للسحب.' ),
				array( 'الإفصاح والمعالجة', 'قد تفصح البيانات بالقدر اللازم لمزودي الاستضافة والدفع والبريد والتواصل، والمدربين أو الجهات المشرفة على البرنامج، والجهات الحكومية المختصة عند وجود مسوغ. يجب تقييد المتعهدين بالتعليمات والسرية والحماية المناسبة.' ),
				array( 'النقل خارج المملكة', 'إذا استلزم مزود تقني معالجة البيانات خارج المملكة، تُراجع المتطلبات النظامية والضمانات الواجبة قبل النقل، ويُوضح ذلك لصاحب البيانات متى كان مطلوبًا.' ),
				array( 'مدة الاحتفاظ والإتلاف', 'تحتفظ البيانات للمدة اللازمة للغرض أو المدد النظامية المتعلقة بالسجلات التدريبية والمالية، ثم تتلف أو تُجهّل بطريقة آمنة. يجب أن يعتمد المركز جدول احتفاظ تفصيليًا قبل الإطلاق.' ),
				array( 'حقوق صاحب البيانات', 'وفق الأنظمة المنطبقة، يحق لصاحب البيانات العلم والوصول والحصول على نسخة وتصحيح البيانات وطلب إتلافها أو سحب الموافقة في الحالات التي يكون فيها ذلك ممكنًا نظامًا، وتقديم شكوى إلى الجهة المختصة.' ),
				array( 'الأمن والحوادث', 'تطبق ضوابط وصول وصلاحيات ونسخ احتياطي وتشفير أثناء النقل ومراقبة وتحديثات أمنية. لا يمكن ضمان أمن مطلق، وتُدار حوادث البيانات ويُبلغ المتأثرون والجهات المختصة متى أوجب النظام.' ),
			),
		),
		'terms' => array(
			'title'   => 'الشروط والأحكام',
			'summary' => 'تنظم هذه الشروط التسجيل في البرامج التدريبية واستخدام الموقع والتزامات المركز والمتدرب.',
			'sections' => array(
				array( 'القبول ونطاق العقد', 'يتم العقد عند إتمام الطلب وقبول الدفع أو اعتماد التحويل البنكي وإرسال تأكيد التسجيل. صفحة الدورة، وهذه الشروط، وسياسات الخصوصية والإلغاء والدفع أجزاء مكملة للعقد.' ),
				array( 'صحة البيانات والأهلية', 'يلتزم المتدرب بتقديم بيانات صحيحة وحديثة تخصه، وعدم مشاركة الحساب، واستيفاء المتطلبات المسبقة الموضحة للدورة. قد يطلب المركز إثبات الهوية للأغراض التدريبية أو إصدار الشهادة.' ),
				array( 'التسجيل والمقاعد', 'إضافة الدورة إلى السلة لا تحجز مقعدًا. يصبح المقعد مؤكدًا بعد نجاح الدفع أو اعتماد التحويل، ما لم توضح صفحة الدورة خلاف ذلك. الطلبات غير المدفوعة قد تُلغى بعد المهلة المعلنة.' ),
				array( 'تقديم البرنامج', 'يلتزم المركز بتقديم البرنامج وفق الوصف المنشور. يجوز تعديل المدرب أو القاعة أو الجدول لسبب مشروع مع إشعار المسجلين وعدم الانتقاص الجوهري من الخدمة، وتطبق سياسة الإلغاء عند التعذر.' ),
				array( 'الحضور والشهادات', 'استحقاق الشهادة مرتبط بنوع البرنامج، ونسبة الحضور، وأي متطلبات تقييم أو اجتياز معلنة. لا تستخدم عبارة «معتمدة» إلا إذا كان اعتماد البرنامج قائمًا ومبينًا برقم وجهة الاعتماد.' ),
				array( 'السلوك والاستخدام المقبول', 'يحظر الإساءة أو تعطيل المنصة أو مشاركة المحتوى أو بيانات الدخول أو التحايل على الحضور والاختبارات أو انتهاك حقوق الملكية. يجوز تعليق الوصول بعد التحقق والإشعار وبما يتناسب مع المخالفة.' ),
				array( 'المسؤولية', 'لا يضمن التسجيل نتيجة وظيفية أو مهنية بعينها. لا تستبعد هذه الشروط أي مسؤولية أو حق لا يجوز استبعاده نظامًا.' ),
			),
		),
		'refund-policy' => array(
			'title'   => 'سياسة الإلغاء والاسترجاع واسترداد الأموال',
			'summary' => 'توضح الحالات والمدد والإجراءات عند إلغاء المتدرب أو المركز، مع عدم الانتقاص من الحقوق النظامية.',
			'sections' => array(
				array( 'طلب الإلغاء', 'يقدم الطلب من الحساب أو عبر البريد الرسمي مع رقم الطلب واسم المتدرب. يعتمد وقت الاستلام المسجل لدى المركز، ويُبلغ المتدرب بنتيجة الطلب.' ),
				array( 'إلغاء المتدرب', 'يجب أن يعتمد المركز جدولًا واضحًا للخصم أو الاسترداد بحسب المدة السابقة لبداية الدورة، وأن يظهر قبل الدفع. لا يجوز تطبيق نسبة أو رسم غير معلن عند التعاقد. المدة المعتمدة حاليًا: ' . alzaherah_legal_value( 'refund_days' ) . '.' ),
				array( 'إلغاء المركز أو التغيير الجوهري', 'إذا ألغى المركز الدورة، يعرض إعادة كامل المبلغ المدفوع أو النقل إلى موعد بديل بموافقة المتدرب. وإذا طرأ تغيير جوهري في الموعد أو طريقة التقديم، يُمنح المتدرب خيارًا مناسبًا وفق الظروف والحقوق النظامية.' ),
				array( 'بدء الدورة والمحتوى الرقمي', 'بعد بدء تقديم الخدمة أو إتاحة محتوى رقمي مستهلك، يقيّم الاسترداد بحسب الجزء المنفذ والوصف الذي وافق عليه المتدرب، دون الإخلال بحقوقه عند العيب أو عدم المطابقة.' ),
				array( 'طريقة ومدة إعادة المبلغ', 'يعاد المبلغ إلى وسيلة الدفع الأصلية متى أمكن، وقد تستغرق المعالجة البنكية مدة إضافية خارجة عن سيطرة المركز. يجب نشر مدة تنفيذ المركز للاسترداد قبل الإطلاق.' ),
				array( 'الاستثناءات والنزاع', 'أي استثناء يجب أن يكون واضحًا ومحددًا قبل الدفع. يمكن تصعيد الاعتراض من خلال سياسة الشكاوى، ولا تمنع هذه السياسة اللجوء للجهات المختصة.' ),
			),
		),
		'complaints-policy' => array(
			'title'   => 'سياسة الشكاوى والمقترحات',
			'summary' => 'قناة عادلة وموثقة لاستقبال الشكاوى وتصنيفها ومعالجتها والتصعيد عند الحاجة.',
			'sections' => array(
				array( 'قنوات التقديم', 'تقدم الشكوى عبر ' . alzaherah_legal_value( 'email' ) . ' أو الهاتف ' . alzaherah_legal_value( 'phone' ) . ' مع رقم الطلب أو الدورة ووصف الواقعة والمرفقات ذات الصلة.' ),
				array( 'الإقرار والاستجابة', 'يرسل رقم مرجعي أو إقرار بالاستلام خلال المدة المنشورة: ' . alzaherah_legal_value( 'complaint_reply' ) . '. ويستهدف إغلاق الشكوى خلال: ' . alzaherah_legal_value( 'complaint_close' ) . '، أو إشعار مقدمها بسبب التأخير والموعد الجديد.' ),
				array( 'العدالة والسرية', 'تفحص الشكوى بموضوعية ومن موظف لا يوجد لديه تعارض مصالح قدر الإمكان، وتقتصر مشاركة البيانات على من يلزم للمعالجة، ولا يتعرض مقدم الشكوى لإجراء انتقامي بسبب شكوى حسنة النية.' ),
				array( 'القرار والتصعيد', 'يبلغ مقدم الشكوى بالنتيجة والأسباب والإجراء التصحيحي إن وجد وطريق الاعتراض الداخلي. لا يحد ذلك من حقه في التواصل مع الجهة الحكومية المختصة.' ),
				array( 'التحسين المستمر', 'تسجل الشكاوى وتصنف أسبابها وتحلل دوريًا دون استخدام غير مشروع للبيانات الشخصية، وتتابع الإجراءات التصحيحية حتى الإغلاق.' ),
			),
		),
		'payment-billing-policy' => array(
			'title'   => 'سياسة الدفع والفوترة',
			'summary' => 'توضح السعر النهائي ووسائل الدفع وتأكيد العملية ومعالجة التكرار والفشل وإصدار الفواتير.',
			'sections' => array(
				array( 'الأسعار والضرائب', 'يعرض السعر النهائي بالريال السعودي قبل تأكيد الطلب. حالة التسجيل في ضريبة القيمة المضافة: ' . ( 'yes' === alzaherah_legal_profile()['tax_registered'] ? 'مسجل' : 'غير مسجل بحسب البيانات الحالية' ) . '. عند انطباق الضريبة يظهر مقدارها أو أن السعر شامل لها.' ),
				array( 'وسائل الدفع', 'تظهر الوسائل المتاحة فعليًا عند الدفع. تعالج بيانات البطاقة لدى بوابة دفع خارجية معتمدة، ولا يخزن الموقع رقم البطاقة الكامل أو رمز الأمان.' ),
				array( 'التحويل البنكي', 'لا يعد رفع إيصال التحويل تأكيدًا للدفع. يراجع المركز التحويل ويؤكد الطلب بعد المطابقة، ويجوز إلغاء الطلب غير المدفوع بعد المهلة المعلنة لتحرير المقعد.' ),
				array( 'الفشل أو التكرار', 'عند فشل العملية لا يعاد الدفع قبل التحقق من البنك وسجل الطلب. إذا ثبت الخصم المكرر، يعالج رد المبلغ المكرر بعد المطابقة دون تحميل العميل كلفة غير مستحقة.' ),
				array( 'الفاتورة', 'تصدر فاتورة أو مستند مالي بعد نجاح الدفع يتضمن رقمًا وتاريخًا ووصف الخدمة والمبلغ والضريبة عند انطباقها وبيانات المورد والعميل بالقدر المناسب. المنشأة المسجلة ضريبيًا ملزمة بحل فوترة إلكترونية متوافق مع متطلبات الهيئة.' ),
			),
		),
		'cookie-policy' => array(
			'title'   => 'سياسة ملفات تعريف الارتباط',
			'summary' => 'توضح استخدام ملفات الارتباط الضرورية والتحليلية والتسويقية وخيارات المستخدم.',
			'sections' => array(
				array( 'الملفات الضرورية', 'تستخدم لتسجيل الدخول، حماية الجلسة، السلة، الدفع، تفضيلات اللغة والأمن. لا يمكن تعطيلها من أداة الموافقة إذا كان الموقع لن يعمل بصورة صحيحة بدونها.' ),
				array( 'التحليل والتسويق', 'لا تُفعّل أدوات التحليل أو الإعلان غير الضرورية قبل موافقة المستخدم عندما تكون الموافقة هي المسوغ المناسب، ويجب عرض أسماء المزودين والأغراض والمدد في أداة إدارة الموافقة.' ),
				array( 'إدارة الموافقة', 'يمكن قبول الفئات الاختيارية أو رفضها أو سحب الموافقة لاحقًا بسهولة. لا يُستخدم حائط ملفات ارتباط يمنع الخدمة الأساسية لمجرد رفض التتبع غير الضروري.' ),
				array( 'إعدادات المتصفح', 'يمكن حذف الملفات أو حجبها من المتصفح، وقد يؤثر حجب الملفات الضرورية على بعض وظائف الحساب والشراء.' ),
			),
		),
		'attendance-policy' => array(
			'title'   => 'سياسة الحضور والاجتياز والشهادات',
			'summary' => 'تحدد ضوابط إثبات الحضور والتأخر والغياب ومتطلبات استحقاق الشهادة.',
			'sections' => array(
				array( 'إثبات الحضور', 'يسجل الحضور بالطريقة المعلنة للبرنامج، وقد يشمل التحقق من الهوية أو سجل الدخول للفصل الافتراضي. يمنع تسجيل الحضور بالنيابة عن متدرب آخر.' ),
				array( 'النسبة المطلوبة', 'تحدد نسبة الحضور ومتطلبات الاجتياز في صفحة كل دورة قبل التسجيل. لا يعتمد رقم موحد ما لم يصدر عن الجهة المشرفة أو سياسة المركز المعتمدة.' ),
				array( 'التأخر والغياب', 'يبلغ المتدرب بالعواقب المحتملة للتأخر أو الغياب، وآلية تقديم عذر، وقرار قبول العذر. لا تمنح الشهادة إذا لم تتحقق متطلبات البرنامج المعلنة.' ),
				array( 'الشهادة', 'يبين نوع الشهادة والجهة المصدرة والاعتماد، إن وجد، في صفحة الدورة. تصحح الأخطاء الناتجة عن بيانات المركز، أما أخطاء البيانات المقدمة من المتدرب فتخضع لإجراء التصحيح المعلن.' ),
			),
		),
		'academic-integrity' => array(
			'title'   => 'سياسة النزاهة الأكاديمية والتحقق من الهوية',
			'summary' => 'تحمي مصداقية التدريب والتقييمات وتمنع الغش وانتحال الهوية مع مراعاة الخصوصية والتناسب.',
			'sections' => array(
				array( 'المخالفات', 'تشمل انتحال الهوية، تقديم عمل الغير، مشاركة الإجابات دون تصريح، استخدام أدوات أو مصادر محظورة، التلاعب بسجل الحضور، أو التحايل التقني على الاختبار.' ),
				array( 'التحقق', 'يقتصر التحقق من الهوية والمراقبة على القدر اللازم والمعلن، وتوضح الأداة والبيانات والغرض ومدة الاحتفاظ قبل جمعها. لا تسجل جلسات أو تجمع بيانات حيوية دون مسوغ وإشعار مناسب.' ),
				array( 'الإجراء العادل', 'توثق المخالفة ويُخطر المتدرب ويمنح فرصة للرد قبل القرار، مع مراعاة جسامة المخالفة وسوابقها. قد تشمل النتائج التنبيه أو إعادة التقييم أو الحرمان من الشهادة أو إنهاء التسجيل.' ),
				array( 'الاعتراض', 'يحق للمتدرب الاعتراض خلال المدة والقناة المعلنة، ويراجع الاعتراض من شخص مختلف قدر الإمكان.' ),
			),
		),
		'intellectual-property' => array(
			'title'   => 'سياسة الملكية الفكرية وحقوق النشر',
			'summary' => 'تنظم استخدام محتوى المركز وأعمال المدربين والمتدربين والمواد المرخصة.',
			'sections' => array(
				array( 'محتوى المركز', 'تبقى حقوق المواد والعلامات والتصاميم والتسجيلات لأصحابها. يمنح التسجيل حق استخدام شخصي غير حصري خلال النطاق والمدة المعلنة، ولا ينقل الملكية.' ),
				array( 'الاستخدام المحظور', 'يمنع النسخ أو إعادة النشر أو البيع أو التسجيل أو إزالة إشعارات الحقوق أو مشاركة الوصول ما لم يوجد إذن مكتوب أو استثناء نظامي.' ),
				array( 'أعمال المتدرب', 'تبقى ملكية العمل الأصلي للمتدرب، ولا يستخدمه المركز في التسويق أو النشر باسم صاحبه دون إذن مناسب، عدا ما يلزم للتقييم والحفظ النظامي.' ),
				array( 'الإبلاغ', 'ترسل بلاغات الانتهاك مع تحديد العمل والرابط وبيانات مقدم البلاغ إلى البريد الرسمي، ويُراجع المحتوى ويتخذ الإجراء المناسب مع حفظ حق الرد.' ),
			),
		),
		'virtual-training-policy' => array(
			'title'   => 'سياسة الفصول الافتراضية والتدريب الإلكتروني',
			'summary' => 'تنطبق عند تقديم برنامج متزامن أو غير متزامن عبر الوسائل الإلكترونية.',
			'sections' => array(
				array( 'نطاق التطبيق والترخيص', 'لا تُعرض دورة على أنها تدريب إلكتروني مرخص إلا بعد تحقق المركز من الترخيص والربط الفني والمعايير المطلوبة للبرنامج والجهة المقدمة.' ),
				array( 'المتطلبات التقنية', 'توضح للمتدرب قبل الشراء متطلبات الجهاز والاتصال والمنصة والبرامج، وآلية الدعم، وسياسة التسجيلات، والحد الأدنى للمشاركة.' ),
				array( 'الحضور والخصوصية', 'توضح طريقة رصد الحضور والمشاركة وأي تسجيل صوتي أو مرئي والغرض ومدة الاحتفاظ ومن يملك الوصول. يجب توفير إشعار مناسب قبل بدء التسجيل.' ),
				array( 'الانقطاع', 'إذا كان الخلل من منصة المركز أو مزوده، يوفر بديلًا معقولًا مثل إعادة الجلسة أو التسجيل أو إعادة الجدولة بحسب أثر الانقطاع. أما أعطال جهاز المتدرب فتدار وفق إرشادات الدعم المعلنة.' ),
				array( 'إتاحة المحتوى', 'توضح مدة الوصول للمحتوى، ومنع مشاركته، ومتطلبات الاختبارات، ومعايير الدعم وإمكانية الوصول لذوي الإعاقة بالقدر الممكن.' ),
			),
		),
		'beneficiary-rights' => array(
			'title'   => 'حقوق المستفيد ومسؤولياته',
			'summary' => 'توضح الحقوق الأساسية للمتدرب والمستفيد، وما يقابلها من مسؤوليات عند استخدام الموقع والتسجيل في البرامج.',
			'sections' => array(
				array( 'حقوق المستفيد', 'للمستفيد الحق في معرفة بيانات المنشأة وترخيصها، ووصف الدورة واعتمادها وسعرها النهائي قبل الدفع، وحماية بياناته، والحصول على تأكيد ومستند مالي، والاطلاع على سياسات الإلغاء والخصوصية والشكاوى.' ),
				array( 'الاختيار والموافقة', 'لا تكون الموافقة على التسويق شرطًا للتسجيل، ويجب أن تكون السياسات متاحة قبل إتمام الطلب. للمستفيد سحب الموافقة التسويقية دون التأثير على الإشعارات التشغيلية الضرورية.' ),
				array( 'مسؤوليات المستفيد', 'يلتزم بتقديم بيانات صحيحة، وحماية الحساب، والسداد عبر الوسائل الرسمية، واحترام الحضور والنزاهة والملكية الفكرية، وإبلاغ المركز بالمشكلة خلال وقت مناسب.' ),
				array( 'عدم الانتقاص', 'لا يفسر أي بند بما ينتقص من حق مقرر نظامًا للمستهلك أو صاحب البيانات، ويمكن للمستفيد اللجوء إلى الجهة المختصة.' ),
			),
		),
		'beneficiary-satisfaction' => array(
			'title'   => 'سياسة قياس رضا المستفيدين',
			'summary' => 'تنظم جمع آراء المستفيدين وتحليلها وتحويلها إلى إجراءات تحسين قابلة للمتابعة.',
			'sections' => array(
				array( 'أدوات القياس', 'قد يستخدم المركز استبيانات بعد التسجيل وأثناء الدورة وبعدها، ومؤشرات الشكاوى والدعم، والمقابلات أو المجموعات المركزة عند الحاجة.' ),
				array( 'المشاركة والخصوصية', 'المشاركة في قياس الرضا اختيارية ما لم يكن السؤال ضروريًا لتقديم الخدمة، ويحدد الغرض بوضوح. لا تنشر إجابة أو شهادة منسوبة لصاحبها دون إذن مناسب.' ),
				array( 'المؤشرات', 'تقاس موضوعات مثل جودة المحتوى والمدرب والتنظيم والمنصة والدعم وسهولة التسجيل والقيمة المتحققة، مع تجنب صياغة أسئلة مضللة.' ),
				array( 'التحليل والتحسين', 'تحلل النتائج دوريًا على مستوى البرنامج والقناة والفترة، وتحدد أسباب الانخفاض وخطة الإجراء والمسؤول والموعد، ثم تقاس فاعلية التحسين.' ),
				array( 'الشفافية', 'يجوز نشر نتائج مجمعة لا تكشف هوية المشاركين. تحفظ البيانات وفق سياسة الخصوصية وجدول الاحتفاظ المعتمد.' ),
			),
		),
		'trainer-development-policy' => array(
			'title'   => 'خطة تأهيل وتطوير المدربين',
			'summary' => 'إطار لاختيار المدربين وتهيئتهم وتقييمهم وتطويرهم المستمر بما يحافظ على جودة البرامج.',
			'sections' => array(
				array( 'الاختيار والتحقق', 'يتحقق المركز من المؤهلات والخبرة والهوية والتراخيص المهنية عند انطباقها، ومن ملاءمة المدرب لمجال البرنامج قبل الإسناد.' ),
				array( 'التهيئة', 'يتلقى المدرب تعريفًا بسياسات المركز، ونتائج التعلم، وإدارة الحضور والتقييم، والخصوصية، والنزاهة الأكاديمية، واستخدام المنصة وخطة الطوارئ.' ),
				array( 'التطوير المستمر', 'تحدد احتياجات التطوير من تقييم المتدربين والملاحظة ونتائج البرامج، وتشمل مهارات التدريب والتقويم والتقنية وإتاحة المحتوى وإدارة الفصول.' ),
				array( 'التقييم', 'يقيم الأداء وفق معايير معلنة ومتوازنة، ولا يعتمد على استبيان واحد فقط. يوثق التقييم وخطة التحسين والمتابعة.' ),
				array( 'التعارض والسرية', 'يفصح المدرب عن تعارض المصالح، ويحافظ على سرية بيانات المتدربين ومحتوى التقييم، ولا يستخدم البيانات لأغراض شخصية أو تسويقية غير مصرح بها.' ),
			),
		),
		'support-guides-policy' => array(
			'title'   => 'الأدلة الإرشادية والدعم والتدريب',
			'summary' => 'توضح قنوات الدعم والأدلة المتاحة للمستفيدين والمدربين وآلية إدارة الأعطال والطلبات.',
			'sections' => array(
				array( 'الأدلة المتاحة', 'يوفر المركز إرشادات إنشاء الحساب والتسجيل والدفع والدخول للفصل ورفع الواجب وحل المشكلات الشائعة، بصيغة واضحة ومحدثة.' ),
				array( 'قنوات الدعم', 'تنشر قنوات الدعم الرسمية وساعات العمل والمدة المستهدفة للاستجابة، ويمنح الطلب رقمًا مرجعيًا عند الحاجة.' ),
				array( 'الأولوية والتصعيد', 'تصنف الطلبات بحسب الأثر والاستعجال؛ وتعطى الأعطال التي تمنع الدفع أو الدخول أو الاختبار أولوية أعلى، مع مسار تصعيد واضح.' ),
				array( 'الدعم أثناء التدريب', 'توضح قناة الدعم المتاحة أثناء الجلسة، والبدائل عند تعطل المنصة، وكيفية توثيق المشكلة لحفظ حق المتدرب.' ),
				array( 'إمكانية الوصول والتحسين', 'تستقبل احتياجات إمكانية الوصول، وتراجع الأدلة بناءً على المشكلات المتكررة وتغييرات المنصة والسياسات.' ),
			),
		),
	);

	return apply_filters( 'alzaherah_policy_definitions', $definitions );
}

/**
 * Render a managed policy.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function alzaherah_policy_shortcode( $atts ) {
	$atts     = shortcode_atts( array( 'key' => '' ), $atts, 'alzaherah_policy' );
	$policies = alzaherah_policy_definitions();
	$key      = sanitize_key( $atts['key'] );

	if ( ! isset( $policies[ $key ] ) ) {
		return '';
	}

	$policy  = $policies[ $key ];
	$updated = ! empty( $policy['updated_at'] ) ? (string) $policy['updated_at'] : wp_date( 'Y/m/d' );
	$version = ! empty( $policy['version'] ) ? (string) $policy['version'] : '1.0';
	$email   = alzaherah_legal_profile()['email'] ?? '';
	ob_start();
	?>
	<article class="alz-policy" data-policy="<?php echo esc_attr( $key ); ?>">
		<header class="alz-policy-hero">
			<p class="alz-policy-kicker"><?php esc_html_e( 'سياسات مركز الزاهرة للتدريب', 'alzaherah' ); ?></p>
			<h1><?php echo esc_html( $policy['title'] ); ?></h1>
			<p><?php echo esc_html( $policy['summary'] ); ?></p>
			<div class="alz-policy-meta">
				<span><?php printf( esc_html__( 'آخر تحديث: %s', 'alzaherah' ), esc_html( $updated ) ); ?></span>
				<span><?php printf( esc_html__( 'الإصدار: %s', 'alzaherah' ), esc_html( $version ) ); ?></span>
			</div>
		</header>
		<?php if ( 'policy-center' === $key ) : ?>
			<div class="alz-policy-directory">
				<?php foreach ( $policies as $policy_key => $item ) : ?>
					<?php if ( 'policy-center' === $policy_key ) { continue; } ?>
					<a href="<?php echo esc_url( home_url( '/' . $policy_key . '/' ) ); ?>">
						<span><?php echo esc_html( $item['title'] ); ?></span>
						<small><?php echo esc_html( $item['summary'] ); ?></small>
						<b aria-hidden="true"><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></b>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<nav class="alz-policy-index" aria-label="<?php esc_attr_e( 'محتويات السياسة', 'alzaherah' ); ?>">
			<strong><?php esc_html_e( 'في هذه الصفحة', 'alzaherah' ); ?></strong>
			<ol>
				<?php foreach ( $policy['sections'] as $index => $section ) : ?>
					<li><a href="#policy-section-<?php echo esc_attr( $index + 1 ); ?>"><?php echo esc_html( $section[0] ); ?></a></li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<div class="alz-policy-sections">
			<?php foreach ( $policy['sections'] as $index => $section ) : ?>
				<section id="policy-section-<?php echo esc_attr( $index + 1 ); ?>">
					<span class="alz-policy-number"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<div><h2><?php echo esc_html( $section[0] ); ?></h2><p><?php echo wp_kses_post( $section[1] ); ?></p></div>
				</section>
			<?php endforeach; ?>
		</div>
		<footer class="alz-policy-contact">
			<strong><?php esc_html_e( 'استفسار أو طلب متعلق بهذه السياسة؟', 'alzaherah' ); ?></strong>
			<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
		</footer>
	</article>
	<?php
	return ob_get_clean();
}
add_shortcode( 'alzaherah_policy', 'alzaherah_policy_shortcode' );

/**
 * Create missing policy pages once, without overwriting user-authored pages.
 *
 * @return void
 */
function alzaherah_ensure_policy_pages() {
	if ( '1.2' === get_option( 'alzaherah_policy_pages_version' ) ) {
		return;
	}

	foreach ( alzaherah_policy_definitions() as $slug => $policy ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$content       = trim( (string) $existing->post_content );
			$is_managed    = '1' === get_post_meta( $existing->ID, '_alzaherah_managed_policy', true );
			$visible_text  = html_entity_decode( wp_strip_all_tags( strip_shortcodes( $content ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$visible_text  = preg_replace( '/[\s\x{00A0}]+/u', '', $visible_text );
			$is_empty_page = '' === $visible_text && ! has_shortcode( $content, 'alzaherah_policy' );

			if ( $is_managed || $is_empty_page ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_title'   => $policy['title'],
						'post_content' => '[alzaherah_policy key="' . $slug . '"]',
					)
				);
				update_post_meta( $existing->ID, '_alzaherah_managed_policy', '1' );
			}
			continue;
		}
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $policy['title'],
				'post_name'    => $slug,
				'post_content' => '[alzaherah_policy key="' . $slug . '"]',
			),
			true
		);
		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_alzaherah_managed_policy', '1' );
		}
	}

	update_option( 'alzaherah_policy_pages_version', '1.2', false );
}
add_action( 'after_switch_theme', 'alzaherah_ensure_policy_pages' );
add_action( 'admin_init', 'alzaherah_ensure_policy_pages' );

/**
 * إنهاء الترحيل القديم دون حقن معرفات منشأة.
 */
function alzaherah_migrate_verified_legal_identifiers() {
	if ( '3' === get_option( 'alzaherah_verified_identifiers_version' ) ) {
		return;
	}
	update_option( 'alzaherah_verified_identifiers_version', '3', false );
}
add_action( 'after_switch_theme', 'alzaherah_migrate_verified_legal_identifiers' );
add_action( 'admin_init', 'alzaherah_migrate_verified_legal_identifiers' );

function alzaherah_missing_legal_identifiers_notice() {
	$can = alzaherah_core_owns_policies()
		? ( ALZ_Legal_Profile::current_user_can_manage() || current_user_can( 'manage_options' ) )
		: current_user_can( 'manage_options' );
	if ( ! $can ) {
		return;
	}
	$profile = get_option( 'alzaherah_legal_profile', array() );
	$profile = is_array( $profile ) ? $profile : array();
	if ( ! empty( $profile['commercial_reg'] ) && ! empty( $profile['tvtc_license'] ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>'
		. esc_html__( 'بيانات السجل التجاري أو ترخيص التدريب غير مكتملة. أدخل القيم المعتمدة يدويًا من إعدادات الملف القانوني قبل النشر.', 'alzaherah' )
		. '</p></div>';
}
add_action( 'admin_notices', 'alzaherah_missing_legal_identifiers_notice' );

/**
 * Theme fallback: register legal settings only when Core does not own them.
 *
 * @return void
 */
function alzaherah_register_legal_settings() {
	if ( alzaherah_core_owns_policies() ) {
		return;
	}
	register_setting(
		'alzaherah_legal',
		'alzaherah_legal_profile',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'alzaherah_sanitize_legal_profile',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'alzaherah_register_legal_settings' );

/**
 * Theme-fallback sanitize for legal profile.
 *
 * @param mixed $input Raw option.
 * @return array<string,string>
 */
function alzaherah_sanitize_legal_profile( $input ) {
	$clean = array();
	foreach ( alzaherah_legal_profile() as $key => $unused ) {
		$value = is_array( $input ) && isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
		$value = is_string( $value ) ? $value : '';
		if ( 'email' === $key ) {
			$clean[ $key ] = sanitize_email( $value );
		} elseif ( in_array( $key, array( 'business_verify', 'license_verify' ), true ) ) {
			$clean[ $key ] = esc_url_raw( $value );
		} else {
			$clean[ $key ] = sanitize_text_field( $value );
		}
	}
	$clean['tax_registered'] = isset( $input['tax_registered'] ) && 'yes' === $input['tax_registered'] ? 'yes' : 'no';
	return $clean;
}

/**
 * Add the compliance page under Appearance.
 *
 * @return void
 */
function alzaherah_add_legal_settings_page() {
	add_theme_page(
		__( 'بيانات الامتثال النظامي', 'alzaherah' ),
		__( 'الامتثال النظامي', 'alzaherah' ),
		'manage_options',
		'alzaherah-legal',
		'alzaherah_render_legal_settings_page'
	);
}
add_action( 'admin_menu', 'alzaherah_add_legal_settings_page' );

/**
 * Render legal settings form (presentation). Save uses Core sanitize when Core owns.
 *
 * @return void
 */
function alzaherah_render_legal_settings_page() {
	$can = alzaherah_core_owns_policies()
		? ( ALZ_Legal_Profile::current_user_can_manage() || current_user_can( 'manage_options' ) )
		: current_user_can( 'manage_options' );
	if ( ! $can ) {
		wp_die( esc_html__( 'ليست لديك صلاحية تعديل الملف القانوني.', 'alzaherah' ), 403 );
	}
	$profile = alzaherah_legal_profile();
	$fields  = array(
		'legal_name'       => 'الاسم القانوني للمنشأة',
		'commercial_name'  => 'الاسم التجاري',
		'commercial_reg'   => 'رقم السجل التجاري',
		'tvtc_license'     => 'رقم ترخيص المؤسسة العامة للتدريب التقني والمهني',
		'tax_number'       => 'الرقم الضريبي (يترك فارغًا عند عدم التسجيل)',
		'national_address' => 'العنوان الوطني / عنوان المقر',
		'phone'            => 'رقم خدمة العملاء',
		'email'            => 'البريد الرسمي',
		'service_hours'    => 'ساعات خدمة العملاء',
		'complaint_reply'  => 'مدة الإقرار باستلام الشكوى',
		'complaint_close'  => 'المدة المستهدفة لمعالجة الشكوى',
		'refund_days'      => 'جدول أو مدة الاسترداد المعتمدة',
		'business_verify'  => 'رابط توثيق المتجر في المركز السعودي للأعمال',
		'license_verify'   => 'رابط التحقق من الترخيص',
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'بيانات الامتثال النظامي', 'alzaherah' ); ?></h1>
		<p><?php esc_html_e( 'لن يعرض القالب رقمًا غير مدخل. استكمل البيانات من المستندات الرسمية ثم راجعها قانونيًا قبل الإطلاق.', 'alzaherah' ); ?></p>
		<?php if ( alzaherah_core_owns_policies() ) : ?>
			<p><em><?php esc_html_e( 'Schema والتحقق والحفظ مملوكة لإضافة المنصة (Core). هذا النموذج للعرض والحفظ عبر Settings API فقط.', 'alzaherah' ); ?></em></p>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'alzaherah_legal' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="alz-legal-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="regular-text" id="alz-legal-<?php echo esc_attr( $key ); ?>" name="alzaherah_legal_profile[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $profile[ $key ] ); ?>" type="<?php echo 'email' === $key ? 'email' : 'text'; ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'مسجل في ضريبة القيمة المضافة', 'alzaherah' ); ?></th>
					<td><label><input type="checkbox" name="alzaherah_legal_profile[tax_registered]" value="yes" <?php checked( $profile['tax_registered'], 'yes' ); ?>> <?php esc_html_e( 'نعم', 'alzaherah' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
