<?php
/*
 * @package      Meridian-function
 * @version      1.0
 * @author       DaoJing Gao <me@gaodaojing.com>
 * @copyright    2014 all rights reserved
 * @license:     GNU General Public License v2 or later
 * @license URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

define('IsMobile', wp_is_mobile());

define('THEMEVER', "1.0");

define("TPLDIR", get_bloginfo('template_directory'));

// Theme functions
if( is_admin() ) :
  get_template_part('functions/meridian-widget');
  get_template_part('functions/meridian-admin');
else :
  get_template_part('functions/meridian-meta');
  get_template_part('functions/meridian-comment');
  get_template_part('functions/meridian-page');
endif;

// Add rss feed
add_theme_support( 'automatic-feed-links' );

//Reomve wordpress none use header
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

// Register wordpress menu
register_nav_menus(array(
  'topMenu' => '主菜单'
));

// Enqueue style-file, if it exists.
add_action('wp_enqueue_scripts', 'meridian_script');
function meridian_script() {
  wp_enqueue_style('style', TPLDIR . '/public/dist/css/main.css', array(), THEMEVER, 'screen');
  wp_enqueue_script('script', TPLDIR . '/public/dist/js/main.js', array(), THEMEVER, false);
}

// Pagenavi of archive and index part
function pagenavi( $p = 5 ) {
  if ( is_singular() ) return;
  global $wp_query, $paged;
  $max_page = $wp_query->max_num_pages;
  if ( $max_page == 1 ) return;
  if ( empty( $paged ) ) $paged = 1;
  if ( $paged > 1 ) p_link( $paged - 1, '« Previous', '«' );
  if ( $paged > $p + 2 ) echo '<span class="page-numbers">...</span>';
  for( $i = $paged - $p; $i <= $paged + $p; $i++ ) {
    if ( $i > 0 && $i <= $max_page ) $i == $paged ? print "<span class='page-numbers current'>{$i}</span> " : p_link( $i );
  }
  if ( $paged < $max_page - $p - 1 ) echo '<span class="page-numbers">...</span>';
  if ( $paged < $max_page ) p_link( $paged + 1,'Next »', '»' );
}

function p_link( $i, $title = '', $linktype = '' ) {
  if ( $title == '' ) $title = "第 {$i} 页";
  if ( $linktype == '' ) { $linktext = $i; } else { $linktext = $linktype; }
  echo "<a class='page-numbers' href='", esc_html( get_pagenum_link( $i ) ), "' title='{$title}'>{$linktext}</a> ";
}

function time_since($older_date,$comment_date = false) {
  $chunks = array(
    array(86400 , '天前'),
    array(3600 , '小时前'),
    array(60 , '分钟前'),
    array(1 , '秒前'),
  );
  $newer_date = time();
  $since = abs($newer_date - $older_date);
  if($since < 2592000){
    for ($i = 0, $j = count($chunks); $i < $j; $i++){
      $seconds = $chunks[$i][0];
      $name = $chunks[$i][1];
      if (($count = floor($since / $seconds)) != 0) break;
    }
    $output = $count.$name;
  }else{
    $output = !$comment_date ? (date('Y-m-j G:i', $older_date)) : (date('Y-m-j', $older_date));
  }
  return $output;
}

// Count words in post
function count_words ($text) {
  global $post;
  if ( '' == $text ) {
    $text = $post->post_content;
    if (mb_strlen($output, 'UTF-8') < mb_strlen($text, 'UTF-8'))
      $output .= mb_strlen(preg_replace('/\s/','',html_entity_decode(strip_tags($post->post_content))),'UTF-8'). ' words';
    return $output;
  }
}

// Post thumbnail
add_theme_support( 'post-thumbnails' );
function meridian_thumbnail($width=130, $height=130){
  global $post;
  $title = $post->post_title;
  if( has_post_thumbnail() ){
    $timthumb_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID),'full');
    $src = $timthumb_src[0];
    return array(
      "hasThumbnail" => true,
      "src" => TPLDIR . "/timthumb.php&#63;src={$src}&#38;w={$width}&#38;h={$height}&#38;zc=1&#38;q=100"
    );
  }else{
    ob_start();
    ob_end_clean();
    $output = preg_match_all('/\<img.+?src="(.+?)".*?\/>/is',$post->post_content,$matches ,PREG_SET_ORDER);
    $cnt = count( $matches );
    if($cnt>0){
      $src = $matches[0][1];
      return array(
        "hasThumbnail" => true,
        "src" => TPLDIR . "/timthumb.php&#63;src={$src}&#38;w={$width}&#38;h={$height}&#38;zc=1&#38;q=100"
      );
    }
  }
  return array(
    "hasThumbnail" => false,
    "src" => null
  );
}

// Avatar
function local_avatar($avatar) {
  $tmp = strpos($avatar, 'http');
  $g = substr($avatar, $tmp, strpos($avatar, "'", $tmp) - $tmp);
  $tmp = strpos($g, 'avatar/') + 7;
  $f = substr($g, $tmp, strpos($g, "?", $tmp) - $tmp);
  $w = get_bloginfo('wpurl');
  $e = ABSPATH .'avatar/'. $f .'.jpg';
  $t = 1209600; //設定14天, 單位:秒
  if ( !is_file($e) || (time() - filemtime($e)) > $t ) { //當頭像不存在或文件超過14天才更新
    copy(htmlspecialchars_decode($g), $e);
  } else  $avatar = strtr($avatar, array($g => $w.'/avatar/'.$f.'.jpg'));
  if (filesize($e) < 500) copy($w.'/avatar/default.jpg', $e);
  return $avatar;
}
//add_filter('get_avatar', 'local_avatar');

function get_v2ex_avatar($avatar) {
  $avatar = preg_replace('/.*\/avatar\/(.*)\?s=([\d]+)&.*/','<img src="https://cdn.v2ex.com/gravatar/$1?s=$2" class="avatar avatar-$2" height="$2" width="$2">',$avatar);
  return $avatar;
}
add_filter('get_avatar', 'get_v2ex_avatar');

/*
 * Escape special characters in pre.prettyprint into their HTML entities
 */
function meridian_esc_html($content) {

  $prettify_code = false;

  $regex = '/(<pre\s+[^>]*?class\s*?=\s*?[",\'].*?prettyprint.*?[",\'].*?>)(.*?)(<\/pre>)/si';
  $content = preg_replace_callback($regex, parse_content_pre, $content);

  $regex = '/(<code\s+[^>]*?class\s*?=\s*?[",\']\s*?prettyprint.*?[",\'].*?>)(.*?)(<\/code>)/si';
  $content = preg_replace_callback($regex, parse_content_code, $content);

  return $content;
}

function parse_content_pre($matches) {
  $tags_open = $matches[1];
  $code = $matches[2];
  $tags_close = $matches[3];

  $regex = '/(<code.*?>)(.*?)(<\/code>)/si';
  preg_match($regex, $code, $matches);
  if(!empty($matches)) {
    $tags_open .= $matches[1];
    $code = $matches[2];
    $tags_close = $matches[3].$tags_close;
  }

  $parsed_code = htmlspecialchars($code, ENT_NOQUOTES, get_bloginfo('charset'), true);

  $parsed_code = str_replace('&amp;#038;', '&amp;', $parsed_code);
  return $tags_open.$parsed_code.$tags_close;
}

function parse_content_code($matches) {
  $tags_open = $matches[1];
  $code = $matches[2];
  $tags_close = $matches[3];

  $parsed_code = htmlspecialchars($code, ENT_NOQUOTES, get_bloginfo('charset'), true);
  $parsed_code = str_replace('&amp;#038;', '&amp;', $parsed_code);
  return $tags_open.$parsed_code2.$tags_close;
}

add_filter('the_content', 'meridian_esc_html');
add_filter('comment_text', 'meridian_esc_html');

?>