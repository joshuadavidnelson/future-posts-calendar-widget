<?php
/**
 * Plugin Name: Future Posts Calendar Widget
 * Plugin URI: http://joshuadnelson.com
 * Description: A calendar widget and archive short code for displaying future posts
 * Version: 1.0.0
 * Author: Joshua Nelson
 * Author URI: http://joshuadnelson.com
 * GitHub Plugin URI: https://github.com/joshuadavidnelson/future-posts-calendar-widget
 * GitHub Branch: master
 * License: GPL v2.0
 *
 * @package 	Future_Posts_Calendar
 * @author 		Joshua David Nelson
 * @version 	1.0.0
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

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
if( ! class_exists( 'Future_Posts_Calendar' ) ) {
	class Future_Posts_Calendar {
		
		// Main instance variable
		var $instance;
		
 		/**
 		 * Start the engine
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
 		function __construct() {
			$this->instance =& $this;
		
			$this->setup_constants();
			$this->includes();
 		}
		
 		/**
 		 * Setup plugin constants
 		 *
 		 * @since 1.0.0
 		 * @access private
 		 * @return void
 		 */
 		private function setup_constants() {

 			// Plugin version
 			if ( ! defined( 'FPC_VERSION' ) ) {
 				define( 'FPC_VERSION', '1.0.0' );
 			}

 			// Plugin Folder Path
 			if ( ! defined( 'FPC_DIR' ) ) {
 				define( 'FPC_DIR', plugin_dir_path( __FILE__ ) );
 			}

 			// Plugin Folder URL
 			if ( ! defined( 'FPC_URL' ) ) {
 				define( 'FPC_URL', plugin_dir_url( __FILE__ ) );
 			}

 			// Plugin Text Domain - for internationalization
 			if ( ! defined( 'FPC_DOMAIN' ) ) {
 				define( 'FPC_DOMAIN', 'future-posts-calendar' );
 			}
			
 		}

 		/**
 		 * Include required files and starts the plugin
 		 *
 		 * @since 1.0.0
 		 * @access private
 		 * @return void
 		 */
 		private function includes() {
			// run
 			add_action( 'plugins_loaded', array( $this, 'init' ) );
			// include widget
			require_once( FPC_DIR . '/includes/widgets/widget-future-posts-calendar.php' );
 		}
		
 		/**
 		 * Initialize the plugin hooks
 		 *
 		 * @since 1.0.0
 		 * @return void
 		 */
 		function init() {
			add_shortcode( 'future-posts-archive', array( $this, 'future_post_archive_shortcode' ) );
 		}
		
 		/**
 		 * The Future archive shortcode
 		 *
 		 * @since 1.0.0
 		 * @access public
 		 * @return shortcode
		 * @param array $atts
 		 */
		public function future_post_archive_shortcode( $atts ) {
			$atts = shortcode_atts( array(
				'post_type' => 'post',
				'post_status' => 'future',
				'category' => '',
			), $atts, 'future-posts-archive' );
			
			$args = array(
				'post_type' => $atts['post_type'],
				'post_status' => $atts['post_status'],
				'posts_per_page' => -1,
				'ignore_sticky_posts' => true,
				'orderby' => 'date',
				'order' => 'ASC',
			);
			if( !empty( $category ) ) {
				if( is_numeric( $category ) ) {
					$args['cat'] = $atts['category'];
				} elseif( is_string( $category ) ) {
					$args['category_name'] = $atts['category'];
				}
			}
			
			$the_query = new WP_Query( $args );
			$future_posts = '';
			
			// The Loop
			if ( $the_query->have_posts() ) :
				$day = current_time( 'mdy' );
				$future_posts = '<div class="future-posts">';
				while ( $the_query->have_posts() ) : $the_query->the_post();
					global $post;
					
					// Set the day anchors
					if( $the_query->current_post == 0 || get_the_date( 'mdy', get_the_ID() ) !== $day) {
						$day = get_the_date( 'mdy', get_the_ID() );
						$future_posts .= '<a name="' . $day . '"> </a>';
					}
				
					$future_posts .= '<div class="post" id="post-' . get_the_ID() . '"><h3 class="entry-title">' . get_the_title( get_the_ID() ) . '</h3><p class="entry-meta">' . get_the_date( 'F j, Y', $post->ID ) . '</p><div class="entry-content excerpt">' . get_the_excerpt() . '</div></div>';
					
				endwhile;
				$future_posts .= '</div>';
			endif;

			// Reset Post Data
			wp_reset_postdata();
			
			return $future_posts;
		}
	}
	global $_future_posts_calendar;
	$_future_posts_calendar = new Future_Posts_Calendar();
}

/**
 * Display calendar with days that have posts as links. Future posts are shown, optionally.
 *
 * The calendar is cached, which will be retrieved, if it exists. If there are
 * no posts for the month, then it will not be displayed.
 *
 * @since 1.0.0
 *
 * @param bool $initial Optional, default is true. Use initial calendar names.
 * @param bool $echo Optional, default is true. Set to false for return.
 * @param bool $future Optional, default set to true. Set to false to only return past posts.
 * @param int $future_archive_page_id Required for future posts, links all future posts to this page
 * @param bool $category If it will be filtered by a category
 * @param int $category_id ID of cateogry to be filterd by, required if $category is true
 * @return string|null String when retrieving, null when displaying.
 */
if( !function_exists( 'get_future_posts_calendar' ) ) {
	function get_future_posts_calendar( $initial = true, $echo = true, $future = true, $future_archive_page_id = null, $category = true, $category_id = null ) {
		
		// If it's just a past posts and no taxonomy filter, return the original WP function
		if( !$future && ( !$category || is_null( $category_id ) ) && !is_null( $future_archive_page_id ) ) {
			return get_calendar( false );
		}
		
		// if taxonomy, set it
		if( $category && is_numeric( $category_id ) ) {
			$category_id = intval( $category_id );
		} elseif( $category ) {
			$category = false;
		}
		
		global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

		$key = md5( $m . $monthnum . $year );
		if ( $cache = wp_cache_get( 'get_future_posts_calendar', 'calendar' ) ) {
			if ( is_array($cache) && isset( $cache[ $key ] ) ) {
				if ( $echo ) {
					// This filter is documented in wp-includes/general-template.php 
					echo apply_filters( 'get_future_posts_calendar', $cache[$key] );
					return;
				} else {
					// This filter is documented in wp-includes/general-template.php
					return apply_filters( 'get_future_posts_calendar', $cache[$key] );
				}
			}
		}

		if ( !is_array($cache) )
			$cache = array();
		
		// Quick check. If we have no posts at all, abort!
		if ( !$posts ) {
			if( $category ) {
				$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts INNER JOIN {$wpdb->term_relationships} calendar_term_relationship ON calendar_term_relationship.object_id={$wpdb->posts}.ID
	INNER JOIN {$wpdb->term_taxonomy} calendar_term_taxonomy ON calendar_term_taxonomy.term_taxonomy_id=calendar_term_relationship.term_taxonomy_id
	INNER JOIN {$wpdb->terms} calendar_term ON calendar_term.term_id=calendar_term_taxonomy.term_id
	WHERE calendar_term_taxonomy.taxonomy='category' AND calendar_term.slug=$category AND post_type = 'post' AND post_status = 'publish' OR post_status = 'future' LIMIT 1");
			} else {
				$gotsome = $wpdb->get_var("SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' OR post_status = 'future' LIMIT 1");
			}
			
			if ( !$gotsome ) {
				$cache[ $key ] = '';
				wp_cache_set( 'get_future_posts_calendar', $cache, 'calendar' );
				return;
			}
		}
		
		if ( isset($_GET['w']) )
			$w = ''.intval($_GET['w']);

		// week_begins = 0 stands for Sunday
		$week_begins = intval(get_option('start_of_week'));

		// Let's figure out when we are
		if ( !empty($monthnum) && !empty($year) ) {
			$thismonth = ''.zeroise(intval($monthnum), 2);
			$thisyear = ''.intval($year);
		} elseif ( !empty($w) ) {
			// We need to get the month from MySQL
			$thisyear = ''.intval(substr($m, 0, 4));
			$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
			$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
		} elseif ( !empty($m) ) {
			$thisyear = ''.intval(substr($m, 0, 4));
			if ( strlen($m) < 6 )
					$thismonth = '01';
			else
					$thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
		} else {
			$thisyear = gmdate('Y', current_time('timestamp'));
			$thismonth = gmdate('m', current_time('timestamp'));
		}

		$unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
		$last_day = date('t', $unixmonth);

		// Get the next and previous month and year with at least one post
		if( $category ) {
			$previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
				FROM $wpdb->posts INNER JOIN {$wpdb->term_relationships} calendar_term_relationship ON calendar_term_relationship.object_id={$wpdb->posts}.ID
			INNER JOIN {$wpdb->term_taxonomy} calendar_term_taxonomy ON calendar_term_taxonomy.term_taxonomy_id=calendar_term_relationship.term_taxonomy_id
			INNER JOIN {$wpdb->terms} calendar_term ON calendar_term.term_id=calendar_term_taxonomy.term_id
			WHERE calendar_term_taxonomy.taxonomy='category' AND calendar_term.slug=$category AND post_date < '$thisyear-$thismonth-01' AND post_type = 'post' AND post_status = 'publish'
					ORDER BY post_date DESC
					LIMIT 1");
					
			$next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
				FROM $wpdb->posts
			INNER JOIN {$wpdb->term_relationships} calendar_term_relationship ON calendar_term_relationship.object_id={$wpdb->posts}.ID
			INNER JOIN {$wpdb->term_taxonomy} calendar_term_taxonomy ON calendar_term_taxonomy.term_taxonomy_id=calendar_term_relationship.term_taxonomy_id
			INNER JOIN {$wpdb->terms} calendar_term ON calendar_term.term_id=calendar_term_taxonomy.term_id
			WHERE calendar_term_taxonomy.taxonomy='category' AND calendar_term.slug=$category AND post_date > '$thisyear-$thismonth-{$last_day} 23:59:59' AND post_type = 'post' AND post_status = 'publish' OR post_status = 'future'
					ORDER BY post_date ASC
					LIMIT 1");
			
		} else {
		
			$previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
				FROM $wpdb->posts
				WHERE post_date < '$thisyear-$thismonth-01' AND post_type = 'post' AND post_status = 'publish'
					ORDER BY post_date DESC
					LIMIT 1");
			$next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
				FROM $wpdb->posts
				WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59' AND post_type = 'post' AND post_status = 'publish' OR post_status = 'future'
					ORDER BY post_date ASC
					LIMIT 1");
		}
		
		/* translators: Calendar caption: 1: month name, 2: 4-digit year */
		$calendar_caption = _x('%1$s %2$s', 'calendar caption');
		$calendar_output = '<table id="wp-calendar">
		<caption>' . sprintf($calendar_caption, $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
		<thead>
		<tr>';

		$myweek = array();

		for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
			$myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
		}

		foreach ( $myweek as $wd ) {
			$day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
			$wd = esc_attr($wd);
			$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
		}

		$calendar_output .= '
		</tr>
		</thead>

		<tfoot>
		<tr>';

		if ( $previous ) {
			$calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a href="' . get_month_link($previous->year, $previous->month) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>';
		} else {
			$calendar_output .= "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
		}

		$calendar_output .= "\n\t\t".'<td class="pad">&nbsp;</td>';

		if ( $future_archive_page_id ) {
			$calendar_output .= "\n\t\t".'<td colspan="3" id="next"><a href="' . get_permalink( $future_archive_page_id ) . '">Future &raquo;</a></td>';
		} else {
			$calendar_output .= "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
		}

		$calendar_output .= '
		</tr>
		</tfoot>

		<tbody>
		<tr>';

		// Get days with posts
		if( $category ) {
			$dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
				FROM $wpdb->posts INNER JOIN {$wpdb->term_relationships} calendar_term_relationship ON calendar_term_relationship.object_id={$wpdb->posts}.ID
			INNER JOIN {$wpdb->term_taxonomy} calendar_term_taxonomy ON calendar_term_taxonomy.term_taxonomy_id=calendar_term_relationship.term_taxonomy_id
			INNER JOIN {$wpdb->terms} calendar_term ON calendar_term.term_id=calendar_term_taxonomy.term_id
			WHERE calendar_term_taxonomy.taxonomy='category' AND calendar_term.slug='$category' AND post_type = 'post' AND post_status = 'publish' OR post_status = 'future'", ARRAY_N);
		} else {
			$dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
			FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' OR post_status = 'future'", ARRAY_N);
		}
		if ( $dayswithposts ) {
			foreach ( (array) $dayswithposts as $daywith ) {
				$daywithpost[] = $daywith[0];
			}
		} else {
			$daywithpost = array();
		}

		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
			$ak_title_separator = "\n";
		else
			$ak_title_separator = ', ';

		$ak_titles_for_day = array();
		
		if( $category ) {
			$ak_post_titles = $wpdb->get_results( "SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
				."FROM $wpdb->posts INNER JOIN {$wpdb->term_relationships} calendar_term_relationship ON calendar_term_relationship.object_id={$wpdb->posts}.ID
INNER JOIN {$wpdb->term_taxonomy} calendar_term_taxonomy ON calendar_term_taxonomy.term_taxonomy_id=calendar_term_relationship.term_taxonomy_id
INNER JOIN {$wpdb->terms} calendar_term ON calendar_term.term_id=calendar_term_taxonomy.term_id
WHERE calendar_term_taxonomy.taxonomy='category' AND calendar_term.slug=$category AND post_type = 'post' AND post_status = 'publish' OR post_status = 'future'"
			);
		} else {
			$ak_post_titles = $wpdb->get_results( "SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
				."FROM $wpdb->posts "
				."WHERE post_type = 'post' AND post_status = 'publish' OR post_status = 'future'"
			);
		}
		if ( $ak_post_titles ) {
			foreach ( (array) $ak_post_titles as $ak_post_title ) {
				/** This filter is documented in wp-includes/post-template.php */
				$post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );

				if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
					$ak_titles_for_day['day_'.$ak_post_title->dom] = '';
				if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
					$ak_titles_for_day["$ak_post_title->dom"] = $post_title;
				else
					$ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
			}
		}

		// See how much we should pad in the beginning
		$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
		if ( 0 != $pad )
			$calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';

		$daysinmonth = intval( date( 't', $unixmonth ) );
		for ( $day = 1; $day <= $daysinmonth; ++$day ) {
			if ( isset( $newrow ) && $newrow )
				$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
			$newrow = false;

			if ( $day == gmdate('j', current_time('timestamp') ) && $thismonth == gmdate('m', current_time('timestamp') ) && $thisyear == gmdate('Y', current_time('timestamp') ) )
				$calendar_output .= '<td id="today">';
			else
				$calendar_output .= '<td>';

			if ( in_array( $day, $daywithpost ) && isset( $ak_titles_for_day[ $day ] ) ) { // any posts today?
				if( $day > gmdate('j', current_time('timestamp') ) ) {
					$calendar_output .= '<a href="' . get_permalink( $future_archive_page_id ) . '#' . date( 'mdy', mktime(0, 0 , 0, $thismonth, $day, $thisyear) ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
				} else {
					$calendar_output .= '<a href="' . get_day_link( $thisyear, $thismonth, $day ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
				}
			} else {
				$calendar_output .= $day;
			}
			
			$calendar_output .= '</td>';

			if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
				$newrow = true;
		}

		$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
		if ( $pad != 0 && $pad != 7 )
			$calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';

		$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

		$cache[ $key ] = $calendar_output;
		wp_cache_set( 'get_future_posts_calendar', $cache, 'calendar' );

		if ( $echo ) {
			/**
			 * Filter the HTML calendar output.
			 *
			 * @since 1.0.0
			 *
			 * @param string $calendar_output HTML output of the calendar.
			 */
			echo apply_filters( 'get_future_posts_calendar', $calendar_output );
		} else {
			/** This filter is documented in wp-includes/general-template.php */
			return apply_filters( 'get_future_posts_calendar', $calendar_output );
		}

	}
}