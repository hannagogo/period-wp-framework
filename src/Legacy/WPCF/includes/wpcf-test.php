<?php
/* //
function sc_test_sc($atts, $content) { return ;
 $u = 'index.php?action=update-selected&plugins=all-in-one-seo-pack%2Fall_in_one_seo_pack.php%2Ccount-per-day%2Fcounter.php%2Ccustom-field-template%2Fcustom-field-template.php%2Cgoogle-analytics-for-wordpress%2Fgoogleanalytics.php%2Csubscribe2%2Fsubscribe2.php%2Cusc-e-shop%2Fusc-e-shop.php%2Cyet-another-related-posts-plugin%2Fyarpp.php&_wpnonce=068119819e';
 $uu = parse_url($u); my_print_r($uu);
 $queries = array();
 foreach ( explode('&',$uu['query']) as $q) { $qq = explode('=', $q); $queries[$qq[0]] = $qq[1]; }
 foreach ($queries as $k=>$v) { $_GET[$k] =$v; }
 my_print_r($queries);
 delete_site_transient('update_plugins');
 wp_cache_delete( 'plugins', 'plugins' );
 $current = get_site_transient( 'update_plugins' );
 my_print_r($current);
}
add_shortcode('test_sc', 'sc_test_sc');
// */
