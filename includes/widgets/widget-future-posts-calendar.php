<?php
/**
 * Future Posts Calendar Widget
 *
 * @package 	Future_Posts_Calendar
 * @subpackage 	Widget
 * @author 		Joshua David Nelson
 * @since	 	1.0.0
 * @license 	http://www.gnu.org/licenses/gpl-2.0.html GPLv2.0+
 */

/**
 * Exit if accessed directly.
 *
 * Prevent direct access to this file. 
 *
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Widget Class
class Future_Post_Calendar_Widget extends WP_Widget {
	
    /**
     * Constructor
     *
     * @return void
     **/
	function Future_Post_Calendar_Widget() {
		$widget_ops = array( 'classname' => 'widget_future_post_calendar widget_calendar', 'description' => __( 'A calendar of your site&#8217;s post and future posts.' ) );
		$this->WP_Widget( 'future-post-calendar', __( 'Future Post Calendar' ), $widget_ops );
	}

    /**
     * Outputs the HTML for this widget.
     *
     * @param array  An array of standard parameters for widgets in this theme 
     * @param array  An array of settings for this widget instance 
     * @return void Echoes it's output
     **/
	public function widget( $args, $instance ) {
		if( function_exists( 'get_future_posts_calendar' ) ) {
			
			extract( $args, EXTR_SKIP );
			/** This filter is documented in wp-includes/default-widgets.php */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : strip_tags( $instance['title'] ), $instance, $this->id_base );
			$future = boolval( $instance['future'] );
			
			// Set category
			if( isset( $instance['category'] ) && is_numeric( $instance['category'] ) ) {
				$cateogry_id = intval( $instance['category'] );
			} else {
				$cateogry_id = null;
			}
			
			// If future posts are included, pass the page id, otherwise set to false 
			if( $future ) {
				$page_id = intval( $instance['page'] );
			} else {
				$page_id = null;
			}
			
			// Build widget
			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			echo '<div id="calendar_wrap">';
			get_future_posts_calendar( true, true, $page_id, $cateogry_id );
			echo '</div>';
			echo $args['after_widget'];
		}
	}

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array  An array of new settings as submitted by the admin
     * @param array  An array of the previous settings 
     * @return array The validated and (if necessary) amended settings
     **/
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['category'] = intval( $new_instance['category'] );
		$instance['page'] = intval( $new_instance['page'] );
		$instance['future'] = intval( $new_instance['future'] );
		
		// Clear cache
		global $m, $monthnum, $year;
		$key = md5( $m . $monthnum . $year );
		if ( $cache = wp_cache_get( 'get_future_posts_calendar', 'calendar' ) ) {
			if( is_array($cache) && isset( $cache[ $key ] ) )
				wp_cache_delete( $key, 'calendar' );
		}
		
		return $instance;
	}
	
    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array  An array of the current settings for this widget
     * @return void Echoes it's output
     **/
	function form( $instance ) {
		
		$defaults = array( 
			'title' => '',
			'category' => '',
			'page' => '',
			'future' => 1,
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		$title = strip_tags( $instance['title'] );
		$category = intval( $instance['category'] );
		$page = intval( $instance['page'] );
		$future = intval( $instance['future'] );
		
		$cat_args = array(
			'hide_empty' => true,
			'id' => $this->get_field_id('category'),
			'name' => $this->get_field_name('category'),
			'show_option_none' => 'Select A Category',
			'option_none_value' => 0,
		);
		if( isset( $category ) )
			$cat_args['selected'] = $category;
		
		$page_args = array(
			'name' => $this->get_field_name('page'),
			'id' => $this->get_field_id('page'),
			'show_option_none' => 'Select A Page',
			'option_none_value' => 0,
		);
		if( isset( $page ) )
			$page_args['selected'] = $page;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category (optional):'); ?></label><br/>
		<?php wp_dropdown_categories( $cat_args )?>
		
		<p><label for="<?php echo $this->get_field_id('future'); ?>">Show Future Posts</label> <input class="checkbox" type="checkbox" value="1" <?php checked($future, 1 ); ?> id="<?php echo $this->get_field_id('future'); ?>" name="<?php echo $this->get_field_name('future'); ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id('page'); ?>"><?php _e('Archive Page (required for future posts):'); ?></label><br/>
		<?php wp_dropdown_pages( $page_args )?>
<?php	
	}
}

// Register the Widget
function fpc_register_contact_widget() {
	register_widget( 'Future_Post_Calendar_Widget' );
}
add_action( 'widgets_init', 'fpc_register_contact_widget' );