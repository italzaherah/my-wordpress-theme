#!/usr/bin/env bash
# HTTP smoke test for an installed WordPress + WooCommerce site running the
# Alzaherah theme (and, when present, the Alzaherah Platform Core plugin).
#
# Required environment:
#   BASE_URL   Site URL without trailing slash, e.g. http://127.0.0.1:8080
#   DEBUG_LOG  Path to the WordPress debug.log (WP_DEBUG_LOG)
#   WP         WP-CLI command prefix, e.g. "wp --path=/tmp/wp --allow-root"
# Optional:
#   ADMIN_USER / ADMIN_PASS  (default admin / admin)
#   WITH_PLUGIN=1            also exercise plugin pages and dashboard sections
#   OUT_DIR                  where response bodies are kept (default: mktemp)
set -uo pipefail

: "${BASE_URL:?}" "${DEBUG_LOG:?}" "${WP:?}"
ADMIN_USER=${ADMIN_USER:-admin}
ADMIN_PASS=${ADMIN_PASS:-admin}
WITH_PLUGIN=${WITH_PLUGIN:-0}
OUT_DIR=${OUT_DIR:-$(mktemp -d)}
mkdir -p "$OUT_DIR"
failures=0

fail() { echo "::error::$*"; failures=$((failures + 1)); }

# ---------------------------------------------------------------- fixtures
$WP eval '
$pages = array(
	"about" => "About", "contact" => "Contact", "faq" => "FAQ", "partners" => "Partners",
	"partnership-request" => "Partnership request", "registration" => "Registration",
	"training-products" => "Training products",
	"smoke-exams" => "[alz_exams]", "smoke-dashboard" => "[alz_platform_dashboard]",
	"smoke-products" => "[alz_training_products]",
);
foreach ( $pages as $slug => $content ) {
	if ( ! get_page_by_path( $slug ) ) {
		wp_insert_post( array( "post_type" => "page", "post_status" => "publish", "post_name" => $slug, "post_title" => $slug, "post_content" => $content ) );
	}
}
if ( ! get_page_by_path( "smoke-post", OBJECT, "post" ) ) {
	wp_insert_post( array( "post_type" => "post", "post_status" => "publish", "post_name" => "smoke-post", "post_title" => "Smoke test post", "post_content" => "Smoke test content" ) );
}
$products = array(
	"smoke-course"  => array( "Smoke course", "100", false ),
	"smoke-digital" => array( "Smoke digital", "50", true ),
	"smoke-exam"    => array( "Smoke exam", "30", true ),
);
foreach ( $products as $slug => $spec ) {
	if ( class_exists( "WC_Product_Simple" ) && ! get_page_by_path( $slug, OBJECT, "product" ) ) {
		$p = new WC_Product_Simple();
		$p->set_name( $spec[0] ); $p->set_slug( $slug ); $p->set_regular_price( $spec[1] );
		$p->set_virtual( $spec[2] ); $p->set_status( "publish" ); $p->save();
	}
}
// Product kinds owned by the plugin, so the theme routes them to their own templates.
$digital = get_page_by_path( "smoke-digital", OBJECT, "product" );
if ( $digital && class_exists( "ALZ_Training_Products" ) ) {
	update_post_meta( $digital->ID, ALZ_Training_Products::KIND_META, ALZ_Training_Products::KIND );
}
$exam = get_page_by_path( "smoke-exam", OBJECT, "product" );
if ( $exam && class_exists( "ALZ_Exams" ) ) {
	update_post_meta( $exam->ID, ALZ_Exams::KIND_META, ALZ_Exams::KIND );
}
' || fail "could not create smoke fixtures"
# The plugin creates its dashboard page only while an administrator is logged in.
$WP eval 'if ( class_exists( "ALZ_Frontend_Dashboard" ) ) { ALZ_Frontend_Dashboard::maybe_create_page(); }' --user="$ADMIN_USER" >/dev/null 2>&1
$WP rewrite flush >/dev/null 2>&1

# ---------------------------------------------------------------- requests
# Each entry: "<expected>|<path>[|<marker>]" where expected is "ok" (2xx/3xx) or an exact status,
# and the optional marker is text the response body must contain.
ANON=(
	"ok|/" "ok|/?s=smoke" "ok|/?s=" "ok|/?s=%3Cscript%3Ealert(1)%3C%2Fscript%3E" "ok|/?s=zzzz-no-match"
	"ok|/?s=smoke&post_type=product" "404|/smoke-missing-page/" "404|/?p=999999999"
	"ok|/shop/" "ok|/product/smoke-course/" "ok|/product/smoke-digital/" "ok|/cart/" "ok|/checkout/"
	"ok|/my-account/" "ok|/my-account/lost-password/" "ok|/category/uncategorized/" "ok|/smoke-post/"
	"ok|/about/" "ok|/contact/" "ok|/faq/" "ok|/partners/" "ok|/partnership-request/"
	"ok|/registration/" "ok|/training-products/" "ok|/feed/" "ok|/wp-login.php" "ok|/?add-to-cart=0"
)
ADMIN=(
	"ok|/wp-admin/" "ok|/wp-admin/edit.php?post_type=product" "ok|/wp-admin/post-new.php?post_type=product"
	"ok|/wp-admin/admin.php?page=wc-settings" "ok|/wp-admin/themes.php" "ok|/wp-admin/plugins.php"
	"ok|/wp-admin/users.php" "ok|/wp-admin/profile.php" "ok|/my-account/" "ok|/my-account/edit-account/"
	"ok|/my-account/edit-address/" "ok|/my-account/orders/" "ok|/" "ok|/?s=smoke" "404|/smoke-missing-page/"
)
if [ "$WITH_PLUGIN" = "1" ]; then
	# The plugin creates and owns its dashboard page; the shortcode page is only a fallback.
	DASH=$($WP eval 'echo wp_make_link_relative( (string) get_permalink( (int) get_option( "alz_front_dashboard_page_id" ) ) );' 2>/dev/null | tail -n 1)
	if [[ "$DASH" != /* ]] || [ "$DASH" = "/" ]; then
		fail "the plugin dashboard page was not created"
		DASH=/smoke-dashboard/
	fi
	echo "dashboard page: $DASH"
	ANON+=( "ok|/product/smoke-exam/" )
	if $WP theme is-active alzaherah-theme-v3 >/dev/null 2>&1; then
		ANON+=( "ok|/product/smoke-digital/|alz-digital-product" )
	fi
	ANON+=( "ok|$DASH" "ok|/smoke-exams/" "ok|/smoke-dashboard/" "ok|/smoke-products/" "404|/alz-download/0123456789abcdef0123456789abcdef/" )
	ADMIN+=( "ok|/my-account/my-exams/" "ok|/my-account/account-security/" "ok|/smoke-exams/" "ok|/smoke-products/" )
	for slug in alzaherah-platform alz-core-affiliate-integrity alz-core-analytics alz-core-audit-log alz-core-commissions alz-core-marketers alz-core-settings; do
		ADMIN+=( "ok|/wp-admin/admin.php?page=$slug" )
	done
	for section in overview courses exams question_bank exam_templates exam_models exam_settings training_products enrollments trainees orders coupons marketers commissions payouts homepage announcements news partners users recycle_bin policies audit release_quality settings; do
		ADMIN+=( "ok|$DASH?section=$section" )
	done
fi

touch "$DEBUG_LOG"
request() { # expected path name [cookie-jar] [marker]
	local expected=$1 path=$2 name=$3 jar=${4:-} marker=${5:-}
	local code
	code=$(curl -s -o "$OUT_DIR/$name.html" -w '%{http_code}' --max-time 90 ${jar:+-b "$jar" -c "$jar"} "$BASE_URL$path")
	printf '%s\t%s\n' "$code" "$path"
	if grep -qE 'There has been a critical error|Fatal error' "$OUT_DIR/$name.html"; then
		fail "$path rendered a PHP fatal error page"
	fi
	if [ -n "$marker" ] && ! grep -qF -- "$marker" "$OUT_DIR/$name.html"; then
		fail "$path does not contain \"$marker\""
	fi
	if [ "$expected" = "ok" ]; then
		[[ "$code" =~ ^[23][0-9][0-9]$ ]] || fail "$path returned HTTP $code (expected 2xx/3xx)"
	elif [ "$code" != "$expected" ]; then
		fail "$path returned HTTP $code (expected $expected)"
	fi
}

i=0
split() { # entry -> expected, path, marker
	expected=${1%%|*}; rest=${1#*|}; path=${rest%%|*}; marker=""
	[ "$rest" != "$path" ] && marker=${rest#*|}
}
for entry in "${ANON[@]}"; do i=$((i + 1)); split "$entry"; request "$expected" "$path" "anon-$i" "" "$marker"; done

JAR="$OUT_DIR/cookies.txt"
curl -s -c "$JAR" -o /dev/null "$BASE_URL/wp-login.php"
curl -s -b "$JAR" -c "$JAR" -o /dev/null -b "wordpress_test_cookie=WP%20Cookie%20check" \
	--data-urlencode "log=$ADMIN_USER" --data-urlencode "pwd=$ADMIN_PASS" -d "wp-submit=Log+In&testcookie=1" "$BASE_URL/wp-login.php"
grep -q 'wordpress_logged_in' "$JAR" || fail "administrator login failed"

i=0
for entry in "${ADMIN[@]}"; do i=$((i + 1)); split "$entry"; request "$expected" "$path" "admin-$i" "$JAR" "$marker"; done

# ---------------------------------------------------------------- PHP log
echo "---- PHP log summary"
if grep -E 'PHP (Fatal error|Parse error)|Uncaught ' "$DEBUG_LOG"; then
	fail "PHP fatal errors were logged"
fi
own=$(grep -E 'PHP (Warning|Notice|Deprecated)' "$DEBUG_LOG" | grep -E 'alzaherah-platform-core/|alzaherah-theme-v3/' | sed -E 's/^\[[^]]+\] //' | sort | uniq -c)
if [ -n "$own" ]; then
	echo "$own"
	fail "PHP warnings/notices originate from the Alzaherah plugin or theme"
fi
grep -E 'PHP (Warning|Notice|Deprecated)' "$DEBUG_LOG" | sed -E 's/^\[[^]]+\] //' | sort | uniq -c | sort -rn | head -20 | sed 's/^/  (info) /'

echo "smoke-test: $failures failure(s)"
exit $(( failures > 0 ))
