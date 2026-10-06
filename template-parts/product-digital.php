<?php
/**
 * Digital training product single layout (Alzaherah identity).
 *
 * @package Alzaherah
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product ) {
	return;
}

$product_id   = $product->get_id();
$training_url = class_exists( 'ALZ_Training_Products' ) ? ALZ_Training_Products::page_url() : home_url( '/training-products/' );
$regular      = (float) $product->get_regular_price();
$sale         = $product->get_sale_price();
$on_sale      = $product->is_on_sale() && '' !== (string) $sale && $regular > 0 && (float) $sale < $regular;
$discount_pct = $on_sale ? (int) round( ( ( $regular - (float) $sale ) / $regular ) * 100 ) : 0;

$terms        = get_the_terms( $product_id, 'product_cat' );
$category     = '';
if ( $terms && ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		if ( 'uncategorized' === $term->slug || 'غير مصنف' === $term->name ) {
			continue;
		}
		$category = $term->name;
		break;
	}
}

$what_you_get = (string) $product->get_meta( '_alz_tp_what_you_get', true );
$delivery     = (string) $product->get_meta( '_alz_tp_delivery', true );
$faq_raw      = (string) $product->get_meta( '_alz_tp_faq', true );
$gallery_ids  = $product->get_gallery_image_ids();
$main_image   = $product->get_image_id();
$reviews_on   = comments_open() && 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' );
$review_count = $product->get_review_count();

$can_buy = $product->is_purchasable() && $product->is_in_stock();
?>
<main id="main" <?php wc_product_class( 'alz-digital-product', $product ); ?> role="main">
	<nav class="alz-digital-product__crumbs container" aria-label="<?php esc_attr_e( 'مسار التنقل', 'alzaherah' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'الرئيسية', 'alzaherah' ); ?></a>
		<span aria-hidden="true">/</span>
		<a href="<?php echo esc_url( $training_url ); ?>"><?php esc_html_e( 'المنتجات التدريبية', 'alzaherah' ); ?></a>
		<span aria-hidden="true">/</span>
		<span aria-current="page"><?php the_title(); ?></span>
	</nav>

	<section class="section">
		<div class="container">
			<?php do_action( 'woocommerce_before_single_product' ); ?>
			<?php alzaherah_render_single_product_hook( 'woocommerce_before_single_product_summary' ); ?>
		</div>
		<div class="container alz-digital-product__card">
			<div class="alz-digital-product__gallery">
				<figure class="alz-digital-product__figure" data-alz-gallery>
					<?php if ( $main_image ) : ?>
						<?php
						echo wp_get_attachment_image(
							$main_image,
							'large',
							false,
							array(
								'class'    => 'alz-digital-product__img',
								'alt'      => sprintf( __( 'غلاف %s', 'alzaherah' ), get_the_title() ),
								'decoding' => 'async',
							)
						);
						?>
						<button type="button" class="alz-digital-product__zoom" data-alz-zoom aria-label="<?php esc_attr_e( 'تكبير الصورة', 'alzaherah' ); ?>">
							<?php esc_html_e( 'تكبير', 'alzaherah' ); ?>
						</button>
					<?php else : ?>
						<div class="alz-digital-product__placeholder" aria-hidden="true"></div>
					<?php endif; ?>
				</figure>
				<?php if ( ! empty( $gallery_ids ) ) : ?>
					<ul class="alz-digital-product__thumbs" role="list">
						<?php if ( $main_image ) : ?>
							<li><?php echo wp_get_attachment_image( $main_image, 'thumbnail', false, array( 'alt' => '' ) ); ?></li>
						<?php endif; ?>
						<?php foreach ( $gallery_ids as $gid ) : ?>
							<li><?php echo wp_get_attachment_image( $gid, 'thumbnail', false, array( 'alt' => '' ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="alz-digital-product__info">
				<span class="alz-digital-product__badge"><?php esc_html_e( 'منتج رقمي', 'alzaherah' ); ?></span>
				<?php if ( $category ) : ?>
					<span class="alz-digital-product__cat"><?php echo esc_html( $category ); ?></span>
				<?php endif; ?>
				<h1 class="alz-digital-product__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="alz-digital-product__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="alz-digital-product__price">
					<?php if ( $on_sale ) : ?>
						<del class="alz-digital-product__regular"><?php echo wp_kses_post( wc_price( $regular ) ); ?></del>
						<ins class="alz-digital-product__sale"><?php echo wp_kses_post( wc_price( (float) $sale ) ); ?></ins>
						<?php if ( $discount_pct > 0 ) : ?>
							<span class="alz-digital-product__discount"><?php echo esc_html( sprintf( __( 'خصم %d%%', 'alzaherah' ), $discount_pct ) ); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<span class="alz-digital-product__sale"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
					<?php endif; ?>
				</div>

				<ul class="alz-digital-product__perks">
					<li><?php esc_html_e( 'تحميل آمن', 'alzaherah' ); ?></li>
					<li><?php esc_html_e( 'متاح بعد تأكيد الدفع', 'alzaherah' ); ?></li>
					<li><?php esc_html_e( 'صلاحية التنزيل 90 يومًا', 'alzaherah' ); ?></li>
				</ul>

				<?php if ( $can_buy ) : ?>
					<div class="alz-digital-product__cta">
						<?php woocommerce_template_single_add_to_cart(); ?>
					</div>
				<?php else : ?>
					<p class="alz-digital-product__unavailable"><?php esc_html_e( 'هذا المنتج غير متاح للشراء حاليًا.', 'alzaherah' ); ?></p>
				<?php endif; ?>

				<?php alzaherah_render_single_product_hook( 'woocommerce_single_product_summary' ); ?>
			</div>
		</div>
	</section>

	<section class="section section-soft">
		<div class="container alz-digital-product__sections">
			<details class="alz-accordion" open>
				<summary><?php esc_html_e( 'عن المنتج', 'alzaherah' ); ?></summary>
				<div class="alz-accordion__body"><?php the_content(); ?></div>
			</details>

			<?php if ( trim( wp_strip_all_tags( $what_you_get ) ) ) : ?>
				<details class="alz-accordion">
					<summary><?php esc_html_e( 'ماذا ستحصل عليه', 'alzaherah' ); ?></summary>
					<div class="alz-accordion__body"><?php echo wp_kses_post( wpautop( $what_you_get ) ); ?></div>
				</details>
			<?php endif; ?>

			<?php if ( trim( wp_strip_all_tags( $delivery ) ) ) : ?>
				<details class="alz-accordion">
					<summary><?php esc_html_e( 'طريقة الاستلام والتنزيل', 'alzaherah' ); ?></summary>
					<div class="alz-accordion__body"><?php echo wp_kses_post( wpautop( $delivery ) ); ?></div>
				</details>
			<?php endif; ?>

			<?php if ( trim( $faq_raw ) ) : ?>
				<details class="alz-accordion">
					<summary><?php esc_html_e( 'الأسئلة الشائعة', 'alzaherah' ); ?></summary>
					<div class="alz-accordion__body">
						<?php
						$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $faq_raw ) ) );
						echo '<dl class="alz-faq">';
						$q = '';
						foreach ( $lines as $line ) {
							if ( 0 === strpos( $line, 'س:' ) || 0 === strpos( $line, 'س：' ) ) {
								if ( $q ) {
									echo '</dd>';
								}
								$q = trim( substr( $line, strlen( 'س:' ) ) );
								echo '<dt>' . esc_html( $q ) . '</dt><dd>';
							} elseif ( 0 === strpos( $line, 'ج:' ) || 0 === strpos( $line, 'ج：' ) ) {
								echo esc_html( trim( substr( $line, strlen( 'ج:' ) ) ) );
							} else {
								echo esc_html( $line ) . ' ';
							}
						}
						if ( $q ) {
							echo '</dd>';
						}
						echo '</dl>';
						?>
					</div>
				</details>
			<?php endif; ?>

			<?php if ( $reviews_on && $review_count > 0 ) : ?>
				<details class="alz-accordion">
					<summary><?php esc_html_e( 'المراجعات', 'alzaherah' ); ?></summary>
					<div class="alz-accordion__body">
						<?php comments_template(); ?>
					</div>
				</details>
			<?php endif; ?>
		</div>
	</section>

	<div class="container">
		<?php alzaherah_render_single_product_hook( 'woocommerce_after_single_product_summary' ); ?>
	</div>
</main>

<dialog class="alz-lightbox" data-alz-lightbox aria-label="<?php esc_attr_e( 'معاينة صورة المنتج', 'alzaherah' ); ?>">
	<form method="dialog"><button type="submit" aria-label="<?php esc_attr_e( 'إغلاق', 'alzaherah' ); ?>">×</button></form>
	<div class="alz-lightbox__stage"></div>
</dialog>
<script>
(function(){
	var btn=document.querySelector('[data-alz-zoom]');
	var dlg=document.querySelector('[data-alz-lightbox]');
	var img=document.querySelector('.alz-digital-product__img');
	var returnFocus=null;
	if(!btn||!dlg||!img) return;
	btn.addEventListener('click',function(){
		returnFocus=document.activeElement;
		var stage=dlg.querySelector('.alz-lightbox__stage');
		stage.innerHTML='';
		var clone=img.cloneNode(true);
		clone.removeAttribute('class');
		stage.appendChild(clone);
		if(typeof dlg.showModal==='function') {
			dlg.showModal();
			dlg.querySelector('button')?.focus();
		}
	});
	dlg.addEventListener('click',function(e){ if(e.target===dlg) dlg.close(); });
	dlg.addEventListener('keydown',function(e){
		if(e.key==='Escape') {
			e.preventDefault();
			dlg.close();
			return;
		}
		if(e.key!=='Tab') return;
		var focusable=Array.from(dlg.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])')).filter(function(el){ return !el.disabled && !el.hidden; });
		if(!focusable.length) {
			e.preventDefault();
			dlg.focus();
			return;
		}
		var first=focusable[0];
		var last=focusable[focusable.length-1];
		if(e.shiftKey && document.activeElement===first) {
			e.preventDefault();
			last.focus();
		} else if(!e.shiftKey && document.activeElement===last) {
			e.preventDefault();
			first.focus();
		}
	});
	dlg.addEventListener('close',function(){
		if(returnFocus && document.contains(returnFocus)) returnFocus.focus();
		returnFocus=null;
	});
})();
</script>
<?php
do_action( 'woocommerce_after_single_product' );
