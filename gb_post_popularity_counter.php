<?php
/**
 * Plugin Name: Popular Posts
 * Description: This plugin is used to list out most popular post based on highest view.
 * Version: 1.0
 * Author: tosagor, aihimel
 * License: GPLv3
 *
 **/

/**
 *  Plugin External Libraries
 */

if(!defined('ABSPATH')) die();

function pp_external_lib() {

    // Bootstrat Stylesheet CSS
    wp_enqueue_style( 'bootstrap', plugins_url( 'public/css/bootstrap.css' , __FILE__ ));
    // Custom StyleSheet CSS
    wp_enqueue_style( 'custom-style', plugins_url( 'public/css/custom.css' , __FILE__ ));
    // Register the script like this for a plugin:
    wp_enqueue_script( 'custom-script', plugins_url( 'public/js/bootstrap.js', __FILE__ ));
    // Register the script like this for a plugin:
    wp_enqueue_script( 'custom-script', plugins_url( 'public/js/custom.js', __FILE__ ));

 }

add_action( 'wp_enqueue_scripts', 'wp_external_lib' );

/**
 *  Post popularity counter
 */
function pp_popular_post_views($postID) {

  $total_key = 'views';

  // Get Current 'views' field
  $total = get_post_meta($postID, $total_key, true);

  // If Current 'views' field is empty, Set it to Zero
  if($total == '') {
      delete_post_meta($postID, $total_key);
      add_post_meta($postID, $total_key, '1');
  } else {
  // If Current 'views' field has a value, add 1 to that value
     $total++;
     update_post_meta($postID, $total_key, $total);
  }
}

/**
 *  Dynamically inject counter into single post
 */
function pp_count_popular_posts($post_id) {
  // Check that this is a single post and that the user is a visitor
  if (!is_single()) return;

  if (!is_user_logged_in()) {
    // Get the post ID
    if(empty($post_id)) {
        global $post;
        $post_id = $post->ID;
    }
    // Run Post Popularity Counter on post
    pp_popular_post_views($post_id);
  }
}

add_action('wp_head', 'pp_count_popular_posts');

/**
 * Adds GB Popular Post Widget
 */
class popular_posts extends WP_Widget {

	/**
	 * Register widget with WordPress
	 */
  public function __construct(){
      parent::__construct(
          'popular_posts',
          'GB Popular Posts',
          array(
              'description' => 'Displays most popular posts',
          )
      );
  }

	/**
	 * Front-end display of widget
	 */
	public function widget( $args, $instance ) {
    $title = apply_filters('widget_title', $instance['title']);
    echo $args['before_widget'];
    if(!empty($title)) echo $args['before_title'] . $title . $args['after_title'];

    // Query Arguments
    $query_args = array(
       'post_type' => 'post',
       'posts_per_page' => $instance['posts'],
       'title_string_range' => $instance['title_string_range'],
       'counter_view' => $instance['title_string_range'],
       'meta_key' => 'views',
       'orderby' => 'meta_value_num',
       'order' => 'DESC',
       'ignore_sticky_posts' => true
    );

    // Query to retrive data from source
    $the_query = new WP_Query( $query_args );

    // Checking the posts existance
    if ( $the_query->have_posts() ) {
    	echo '<ul class="list-group">';
      // WP Query to retrive all popular posts
    	while ( $the_query->have_posts() ) {
    		$the_query->the_post();
        echo '<a class= "post_title_permalink" href="'. get_the_permalink() .'" rel = "bookmark">';
    		echo '<li class="list-group-item post_list">';
        if( 'on' == $instance[ 'show_thumb' ] ) {
             echo get_the_post_thumbnail( null, $size, $attr );
        } else {
             echo '';
        }
        $string = get_the_title();
        $limit = $instance['title_string_range'];
        if(str_word_count($string, 0) > $limit) {
          $words = str_word_count($string, 2);
          $pos = array_keys($words);
          $title = substr($string, 0, $pos[$limit]) . '...';
          echo '<span class="title_style">'.$title.'</span>';
        } else {
          echo '<span class="title_style">'.get_the_title().'</span>';
        }
        echo '<br>';
        echo get_the_date();
        if( 'on' == $instance[ 'counter_view' ] ) {
          echo '<span class="badge">';
          echo  get_post_meta(get_the_ID(), 'views', true);
          echo '</span>';
        } else {
          echo '';
        }
        echo '</li>';
        echo '</a>';
    	}
    	echo '</ul>';
    	/* Restore original Post Data */
    	wp_reset_postdata();
    } else {
      echo '<div class="alert alert-info">';
      echo '<strong>Sorry!</strong> No post has been found or Please check in Custom Fields in any post.';
      echo '</div>';
    }
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form
	 */
	public function form( $instance ) {
    if(isset($instance['title'])) $title = $instance['title']; else $title = "GB Popular Post Plugin";
    if(isset($instance['posts'])) $posts = $instance['posts']; else $posts = "5";
    if(isset($instance['title_string_range'])) $title_string_range = $instance['title_string_range']; else $title_string_range = "5";
    if(isset($instance['counter_view'])) $counter_view = "checked"; else $counter_view = "";
    if(isset($instance['show_thumb'])) $show_thumb = "checked"; else $show_thumb = "";
		?>
		<p>
    <label for='<?php echo $this->get_field_id('title'); ?>'><?php echo "Title ";?></label>
    <input class='widefat' id='<?php echo $this->get_field_id('title'); ?>' name='<?php echo $this->get_field_name('title'); ?>' type='text' value='<?php echo $title; ?>'>
		</p>
    <p>
    <label for='<?php echo $this->get_field_id('posts'); ?>'><?php echo "Number of Posts to Display ";?></label>
    <input class='widefat' id='<?php echo $this->get_field_id('posts'); ?>' name='<?php echo $this->get_field_name('posts'); ?>' type='text' value='<?php echo $posts; ?>'>
		</p>
    <p>
    <label for='<?php echo $this->get_field_id('title_string_range'); ?>'><?php echo "Post Title String Range ";?></label>
    <input class='widefat' id='<?php echo $this->get_field_id('title_string_range'); ?>' name='<?php echo $this->get_field_name('title_string_range'); ?>' type='text' value='<?php echo $title_string_range; ?>'>
		</p>
    <p>
    <input type="checkbox" class='checkbox' id='<?php echo $this->get_field_id('counter_view'); ?>' name='<?php echo $this->get_field_name('counter_view'); ?>' <?php checked( $instance[ 'counter_view' ], 'on' ); ?>>
    <label for='<?php echo $this->get_field_id('counter_view'); ?>'><?php echo "View Counter ? ";?></label>
    </p>
    <p>
    <input type="checkbox" class='checkbox' id='<?php echo $this->get_field_id('show_thumb'); ?>' name='<?php echo $this->get_field_name('show_thumb'); ?>' <?php checked( $instance[ 'show_thumb' ], 'on' ); ?>>
    <label for='<?php echo $this->get_field_id('show_thumb'); ?>'><?php echo "Show Thumbline Photo ? ";?></label>
    </p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['posts'] = ( ! empty( $new_instance['posts'] ) ) ? strip_tags( $new_instance['posts'] ) : '';
    $instance['title_string_range'] = ( ! empty( $new_instance['title_string_range'] ) ) ? strip_tags( $new_instance['title_string_range'] ) : '';
    $instance['counter_view'] = ( ! empty( $new_instance['counter_view'] ) ) ? strip_tags( $new_instance['counter_view'] ) : '';
    $instance['show_thumb'] = ( ! empty( $new_instance['show_thumb'] ) ) ? strip_tags( $new_instance['show_thumb'] ) : '';
		return $instance;
	}

} // class popular_posts

// register Foo_Widget widget
function pp_register_popular_posts_widget() {
    register_widget( 'popular_posts' );
}

add_action( 'widgets_init', 'pp_register_popular_posts_widget' );
