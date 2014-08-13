 <?php
/**
* Plugin Name: CTC Full Calendar 
* Description: Apply a full calendar view for Church Theme Content Events. Use the shortcode [ctc_fullcalendar] in any post to display a full calendar of events.
* Version: 0.9.2
*/

// No direct access
if ( !defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'ctcfc_fullcalendar_init' );
function ctcfc_fullcalendar_init(){
	if( !class_exists('Church_Theme_Content') ) return;
	add_shortcode('ctc_fullcalendar', 'ctcfc_fullcalendar_shortcode');
}

/**
 * Prep scripts and styles
 *
 * @since 0.1
 */
function ctcfc_scripts_styles(){
	wp_register_script( 'moment', 
		plugins_url( '/fullcalendar/moment.min.js' , __FILE__ ), array() );
	wp_register_script( 'fullcalendar', 
		plugins_url( '/fullcalendar/fullcalendar.min.js' , __FILE__ ), array('jquery','moment') );
	wp_register_script( 'ctc-fullcalendar', 
		plugins_url( '/js/ctc-fullcalendar.js' , __FILE__ ), array('fullcalendar') );
	wp_register_style( 'fullcalendar-css', 
		plugins_url( '/fullcalendar/fullcalendar.css' , __FILE__ ) );
	wp_register_style( 'ctc-fullcalendar', 
		plugins_url( '/css/ctc-fullcalendar.css' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'ctcfc_scripts_styles' );

/**
 * Parse shortcode and insert calendar
 * Usage: [ctc_fullcalendar] 
 *   Optional parameters:
 *     view = 'responsive','month','week','basicWeek','basicDay','agendaWeek', or 'agendaDay'
 *       Type of calendar to display. See http://arshaw.com/fullcalendar/ for more details. If empty or 'responsive'
 *       the calendar is responsive and changes from month => basicWeek => basicDay views
 *     breaks = 'med_break, small_break' 
 *       Widths to change from month view to basicWeek and from basicWeek to basicDay when $view is empty or 'responsive'
 *       Default is '400,350'
 *     max_recur = (integer)
 *       Maximum number of recurrences to display. 0 only shows the first event. Default is 12
 *     max_events = (integer)
 *       Maximum events to display, not counting recurrences. Default is 100
 *     before=''
 *       Markup to insert before the calendar
 *     after = ''
 *       Markup to insert after the calendar
 * @since 0.2
 * @param string $attr Shortcode options
 * @return string Full calendar display
 */
function ctcfc_fullcalendar_shortcode($attr){

	$output = apply_filters( 'ctcfc_fullcalendar_shortcode', '', $attr);
	if ( $output != '' ) return $output;
	extract( shortcode_atts( array(
		'view'			=>  '',  
		'breaks' 		=>  '400,350',  
		'max_recur'	=>  12,
		'max_events'=>  100,
		'before'    =>  '',
		'after'     =>  '',
		), $attr ) );
	
	// Clean up things a bit
	if($breaks) $breaks = json_decode('['.$breaks.']',true);
	$views =array('responsive','month', 'week', 'basicWeek', 'basicDay', 'agendaWeek', 'agendaDay');
	if(!in_array($view, $views)) $view = '';
	
	// insert scripts and styles
	wp_enqueue_script( 'moment' );
	wp_enqueue_script( 'fullcalendar' );
	wp_enqueue_script( 'ctc-fullcalendar' );
	wp_enqueue_style( 'fullcalendar-css' );
	wp_enqueue_style( 'ctc-fullcalendar' );
	
	// calendar div
	$result = '<div id="ctc-fullcalendar"></div>';
	
	// do query 
	$query = array(
			'post_type' => 'ctc_event', 
			'posts_per_page' => $max_events, 
			'order' => 'ASC'
		) ; 
	$posts = new WP_Query();
	$posts->query($query); 
	if ($posts->have_posts()){
		$events = array();
		while ($posts->have_posts()) :
			$posts		-> the_post();
			$post_id 	= get_the_ID();
			$title 		= get_the_title();
			$url 			= get_permalink();
			
			// Get event information
			$startdate 	= get_post_meta( $post_id, '_ctc_event_start_date', true );
			$enddate   	= get_post_meta( $post_id, '_ctc_event_end_date', true );
			$time  			= get_post_meta( $post_id, '_ctc_event_time', true );
			
			// Fix things a bit
			$enddate   	= $enddate =='' ? $startdate : $enddate;
			$time  			= str_replace(' ','',$time);
			// nominal time validation
			$hastime = preg_match("/(\d|[0-1]\d|2[0-3]):([0-5]\d)(am|pm|AM|PM)*(-(\d|[0-1]\d|2[0-3]):([0-5]\d)(am|pm|AM|PM)*)?/i", $time);
			$time = $hastime ? $time : '';
			
			$start  = $startdate;
			$end    = $enddate;
			$allday = false;
			
			$times = explode('-',$time);
			
			$starttime 	= '';
			$endtime 		= '';
			if( count($times) > 0 )
				$starttime .=  'T' . date('H:i:s',strtotime($times[0]));
			if( count($times) > 1 ) 
				$endtime .= 'T' . date('H:i:s',strtotime($times[1]));
			
			// Check for all day events: no start time, or different start/end dates and no end time
			$allday = ($starttime == 'T') OR ($start != $end AND $endtime == 'T');
			$start .= $allday ? '' : $starttime;
			$end   .= $endtime;

			// append to event array	
			$events[] = array(
				'id' 		 => $post_id,
				'title'  => $title,
				'allDay' => $allday,
				'start'  => $start,
				'end' 	 => $end,
				'url'    => $url
			);
			
			// CTC only has a single event and recurrence is updated as the event lapses.
			// Therefore recurrence must be processed in code to display properly
			$recurrence 		  = get_post_meta( $post_id, '_ctc_event_recurrence', true );
			$recurrence_end 	= get_post_meta( $post_id, '_ctc_event_recurrence_end_date', true );
			
			// NOTE: Recurrence period is not part of the CTC plugin
			$recurrence_period 	= get_post_meta( $post_id, '_ctc_event_recurrence_period', true );
			
			// Display recurrences
			if ($recurrence != 'none') {
				$n = $recurrence_period != '' ? (int) $recurrence_period : 1;
				for ($i=1 ; $i<=$max_recur ; $i++) {
					list( $y, $m, $d ) = explode( '-', $startdate );
					switch ($recurrence) {
						// NOTE: Daily is not an option in the CTC plugin 
						case 'daily':
							list( $y, $m, $d ) = explode( '-', date( 'Y-m-d', strtotime( $startdate ) + $i * $n * DAY_IN_SECONDS ) );
							break;
						case 'weekly':
							// same day of the week (eg, Sun-Sat)
							list( $y, $m, $d ) = explode( '-', date( 'Y-m-d', strtotime( $startdate ) + $i * $n * WEEK_IN_SECONDS ) );
							break;
						case 'monthly':
							// same day of the month (eg., the 13th)
							$m += $i * $n;							
							$ny = floor(($m-1)/12);
							$m -= 12*$ny;
							$y += $ny;
							break;
						case 'yearly':
							// same day of the year (eg., the 27th of October)
							$y+=$i * $n;
							break;
					}
					
					// Get the new date based on the interval
					$t = date( 't', mktime( 0, 0, 0, $m, 1, $y )) ;
					if($d > $t) {
						$recurdate = date('Y-m-t', mktime( 0, 0, 0, $m, 1, $y ));
					} else {
						$recurdate = date('Y-m-d', mktime( 0, 0, 0, $m, $d, $y ));
					}
					
					// Figure out the shift needed to apply to the end date & time
					$dateshift = strtotime($recurdate)-strtotime($startdate);
					
					// Create next event date & time
					$start     = $recurdate . $starttime;
					
					// stop if new date is past the recurrence end date
					if(strtotime($recurdate) > strtotime($recurrence_end)) break;
					
					// shift end dates as well
					if ($end != ''){
						$recurenddate = date('Y-m-d', strtotime($enddate) + $dateshift);
						$end 		= $recurenddate . $endtime;
					}
					
					// append to event array
					$events[] = array(
						'id' 		=> $post_id,
						'title' => $title,
						'allDay' => $allday,
						'start' => $start,
						'end' 	=> $end,
						'url'   => $url
					);
				}
			}
		endwhile; 
	}
	wp_reset_query();
	
	// The event data is loded as a json object 
	$before .= '<script>';
	$before .= 'var events = '. json_encode($events) .';';
	$before .= 'var fixedView = '. json_encode($view) . ';';
	$before .= 'var breaks = '. json_encode($breaks) . ';';
	$before .= '</script>';
	
	return $before . $result . $after;	
}
?>
