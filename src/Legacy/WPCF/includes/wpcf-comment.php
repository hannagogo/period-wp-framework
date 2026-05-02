<?php
/* ////// Comments ////// */
function custom_comment_format($comment, $args, $depth) {
 global $custom_language_domain;
 $GLOBALS['comment'] = $comment;
 $html = '';
 $html .= createHTMLElement('li', 'start', array(
  'class'=>preg_replace('/^class=/', '', comment_class(NULL,NULL,NULL,FALSE)),
  'id' => 'li-comment-'.get_comment_ID()
 ) );
 $html .= createHTMLElement('div', 'start', array( 'id' => 'comment-'.get_comment_ID() ) )
  . createHTMLElement('div', array('class'=>'comment-author vcard'),
     get_avatar($comment,$size='48',$default=NULL )
	 . createHTMLElement( 'cite', array('class'=>'fn'), get_comment_author_link() )
	 . ' '
	 . createHTMLElement( 'span', array('class'=>'says'), __('says:', $custom_language_domain) )
    )
 ;
 if ($comment->comment_approved == '0') {
  $html .= createHTMLElement('em', NULL, __('Your comment is awaiting moderation.', $custom_language_domain) );
 }

 $edit_comment_link_href = preg_replace('/^.*?href=[\x27\x22](.*?)[\x27\x22].*?$/', '$1', get_edit_comment_link($comment->comment_ID) );
 $html .= createHTMLElement('div', array( 'class'=>"comment-meta commentmetadata"), 
    createHTMLElement('a', array('href'=>htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ),
     sprintf(__('%1$s at %2$s', $custom_language_domain), get_comment_date(), get_comment_time())
    )
  . ($edit_comment_link_href ?
     createHTMLElement('span', array('class'=>"edit_comment_link"),
      createHTMLElement('a', array('href'=> $edit_comment_link_href),
       sprintf( '(%s)', __('Edit', $custom_language_domain) )
	  )
	 ) : ''
	)
 );
 
 $comment_text = get_comment_text($comment->comment_ID);
 $html .= $comment_text;
 $html .= createHTMLElement('div', array('class'=>'reply'), 
  get_comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'])) )
 );
 $html .= createHTMLElement('div', 'end');
 echo $html;
}


function custom_comment_fields($html) {
 $fields = apply_filters('WPCF_Custom_Comment_Format', NULL);
 return $html . $fields ;
}
// Function 'custom_comment_fields' simply appends HTML string from:
//  apply_filters('WPCF_Custom_Comment_Format', NULL);)
// to given one (i.e. comment_text() )

function wrap_with_dl($html) {
 return "<dl>".$html."</dl>";
}

function build_comments($args) {
 global $custom_language_domain, $wp_custom_functions;
 $args = parse_args(array(
  'elements'				 => array('navigation', 'comments', 'navigation', 'form'),
  'wp_list_comments_args'	 => array(),
  'comment_form_args'		 => array(),
  'password_required_message'=>
    __( 'This post is password protected. Enter the password to view any comments.', $custom_language_domain ),
  'comment_closed_message'	 =>
	__( 'Comments are closed.', $custom_language_domain ),
  'previous_comments_link'	 =>
	sprintf(
	 __('%1$s&larr;%2$s Older Comments', $custom_language_domain),
	 createHTMLElement('span', 'start', array('class'=>'meta-nav nav_prev') ), createHTMLElement('span','end')
	),
  'next_comments_link'		 =>
    sprintf(
     __('Newer Comments %1$s&larr;%2$s', $custom_language_domain),
     createHTMLElement('span', 'start', array('class'=>'meta-nav nav_next') ), createHTMLElement('span','end')
    ),
 ), $args);

 $html = '';
 $html .= createHTMLElement('div', 'start', array('id'=>'comments') );
 $comments_end_tag = createHTMLElement('div', 'end') . createHTMLElement('_comment', 'END OF #comments') ;
 if ( post_password_required() ) { 
  $html .= createHTMLElement('p', array('class'=>"nopassword"), $args['password_required_message'] )
  . $comments_end_tag;
  return $html;
 }
 
 $elements = array();
 foreach ((array) $args['elements'] as $e) {
  if ( $h = array_value($elements, $e) ) { $html .= $h; continue; }

  switch ($e) {
   case 'navigation' :
    if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) {
	 if ($h = array_value($elements, $e)) {
	  $html .= $h;
	 }
	 else {
	  $elements[$e] = createHTMLElement('div', array('class'=>'navigation comment_navi'),
	    createHTMLElement('div', array('class'=>'nav-pervious comment_nav_prev'), $args['previous_comment_link'] )
	  . createHTMLElement('div', array('class'=>'nav-next comment_nav_next'), $args['next_comments_link'] ) 
	  );
	  $html .= $elements[$e];
	 }
    };
   break;

   case 'form' :
    ob_start();
    comment_form( $args['comment_form_args'] );
    $v = ob_get_clean();
	if ($b = array_value($args['comment_form_args'], 'before') ) $v = $b . $v;
	if ($a = array_value($args['comment_form_args'], 'after') ) $v .= $a;
	$elements[$e] = $v;
    $html .= $elements[$e];
   break;

   case 'comments' :
	$v = '';
    ob_start();
	wp_list_comments(
     $wp_custom_functions->parse_args( array(
	  'avatar_size'	 =>32,
	  'style'		 => 'ul',
	  'callback'		 => NULL
	 ), $args['wp_list_comments_args'] )
	);
	$v = createHTMLElement('ol', array( 'class'=>"commentlist"), ob_get_clean() );
	if ($b = array_value($args['wp_list_comments_args'], 'before') ) $v = $b . $v;
	if ($a = array_value($args['wp_list_comments_args'], 'after') ) $v .= $a;
	$elements[$e] = $v;
	$html .= $elements[$e];
   break;
  } // end of switch
 } // end foreach
 if ( ! comments_open() ) {
  $html .= createHTMLElement('p', array('class'=>'nocomments'), $args['comment_closed_message'] );
 }
 $html .= $comments_end_tag;

 return $html;
}
