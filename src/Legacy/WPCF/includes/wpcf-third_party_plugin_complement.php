<?php
function get_yarpp_related_posts($post_types=array()) {
 if (function_exists('related_posts')) { return related_posts(array('post_type'=>(array) $post_types), false, 0); }
 return NULL;
}


function get_term_image_id($term_id, $taxonomy='category', $field='term_id') { 
 /* // Taxonomy Images // */

 $terms = apply_filters('taxonomy-images-get-terms', '', array(
  'taxonomy'	 => $taxonomy,
  'term_args'	 => array('hide_empty'=>false),
  'having_images' => true
 ) ); 
 if (empty($terms)) return 0;
 foreach ($terms as $t) {
  if ($t->{$field} == $term_id) { return $t->image_id; }
 }
 return 0;
}

