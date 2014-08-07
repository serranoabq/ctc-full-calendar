 <?php
/**
* Plugin Name: CTC Full Calendar 
* Description: Apply a full calendar view for Church Theme Content Events. Use the shortcode [ctc_fullcalendar] in any post to display a full calendar of events.
* Version: 0.9
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
			$posts->the_post();
			
			// Get event information
			$startdate = get_post_meta( get_the_ID(), '_ctc_event_start_date', true );
			$enddate   = get_post_meta( get_the_ID(), '_ctc_event_end_date', true );
			$time  = get_post_meta( get_the_ID(), '_ctc_event_time', true );
			
			// Fix things a bit
			$enddate   = $enddate =='' ? $startdate : $enddate;
			$time  = str_replace(' ','',$time);
			// nominal time validation
			$hastime = preg_match("/(\d|[0-1]\d|2[0-3]):([0-5]\d)(am|pm|AM|PM)*(-(\d|[0-1]\d|2[0-3]):([0-5]\d)(am|pm|AM|PM)*)?/i", $time);
			$time = $hastime ? $time : '';
			
			$start = $startdate;
			$end   = $enddate;
			$allday = false;
			
			$times = explode('-',$time);
			
			$starttime = 'T';
			$endtime = 'T';
			if( count($times) > 0 )
				$starttime .=  date('H:i:s',strtotime($times[0]));
			if( count($times) > 1 ) 
				$endtime .= date('H:i:s',strtotime($times[1]));
			
			// Check for all day events: no start time, or different start/end dates and no end time
			$allday = ($starttime == 'T') OR ($start != $end AND $endtime == 'T');
			$start .= $allday ? '' : $starttime;
			$end   .= $endtime == 'T' ? '' : $endtime;
			
			// append to event array	
			$events[] = array(
				'id' 		=> get_the_ID(),
				'title' => get_the_title(),
				'allDay' => $allday,
				'start' => $start,
				'end' 	=> $end,
				'url'   => get_permalink()
			);
			
			// CTC only has a single event and recurrence is updated as the event lapses.
			// Therefore recurrence must be processed in code to display properly
			$recurrence 		  = get_post_meta( get_the_ID(), '_ctc_event_recurrence', true );
			$recurrence_end 	= get_post_meta( get_the_ID(), '_ctc_event_recurrence_end_date', true );
			
			// Recurrence period is not part of the CTC plugin (see readme.txt for instructions on how to add it)
			$recurrence_period 	= get_post_meta( get_the_ID(), '_ctc_event_recurrence_period', true );
			
			// Display recurrences
			if ($recurrence != 'none') {
				$n = $recurrence_period != '' ? (int) $recurrence_period : 1;
				for ($i=1 ; $i<=$max_recur ; $i++) {
					switch ($recurrence) {
						
						// Daily is not a default option in the CTC plugin (see readme.txt for instructions on how to add it)
						case 'daily':
							$interval = date_interval_create_from_date_string($i . ' days');
							break;
						case 'weekly':
							// same day of the week (eg, Sun-Sat)
							$interval = date_interval_create_from_date_string($n * $i. ' weeks');
							break;
						case 'monthly':
							// same day of the month (eg., the 13th)
							$interval = date_interval_create_from_date_string($n * $i . ' months');
							break;
						case 'yearly':
							// same day of the year (eg., the 27th of October)
							$interval = date_interval_create_from_date_string($n * $i . ' years');
							break;
					}
					
					// Get the new date based on the interval
					$newdate = date_add( new DateTime($startdate), $interval );
					
					if($recurrence == 'monthly' AND date_format(new DateTime($startdate), 'd') != date_format($newdate , 'd') ) {
						// Check for a missing day (e.g., 29, 30 or 31). This primarily happens with monthly recurrence, which 
						// moves the next date into one month past (e.g., May 31, July 1, skipping June)
						// The solution is to shift the new date back 10 days, and get the last day of the month
						$recurdate = date_format(date_sub( $newdate, date_interval_create_from_date_string('10 days') ), 'Y-m-t');
						$dateshift = date_diff($newdate, new DateTime($recurdate));						
					} else 
						$recurdate = date_format(date_add( new DateTime($startdate), $interval), 'Y-m-d');
					
					// create new date based on previous one
					$start     = $recurdate . $starttime;
					
					// stop if new date is past the recurrence end date
					if(strtotime($recurdate) > strtotime($recurrence_end)) break;
					
					// shift end dates as well
					if ($end != ''){
						$recurenddate = date_format(date_add( new DateTime($enddate), $interval), 'Y-m-d');
						
						if($recurrence == 'monthly' AND date_format(new DateTime($startdate), 'd') != date_format($newdate , 'd') ) 
							$recurenddate = date_format(date_add( new DateTime($recurenddate), $dateshift),'Y-m-d');
						
						$end 		= $recurenddate . $endtime;
					}
					
					// append to event array
					$events[] = array(
						'id' 		=> get_the_ID(),
						'title' => get_the_title(),
						'allDay' => $allday,
						'start' => $start,
						'end' 	=> $end,
						'url'   => get_permalink()
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


/**
 * Move date forward
 *
 * Move date forward by a day week, month or year until it is not in past (in case wp cron misses a beat).
 *
 * @since 0.9
 * @param string $date Date to move into the future
 * @param string $increment 'daily', 'weekly', 'monthly' or 'yearly'
 * @return string Future date
 */
function ctcfc_increment_future_date( $date, $increment ) {

	// In case no change could be made
	$new_date = $date;

	// Get month, day and year, increment if date is valid
	list( $y, $m, $d ) = explode( '-', $date );
	if ( checkdate( $m, $d, $y ) ) {

		// Increment
		switch ( $increment ) {

			// Daily
			case 'daily' :
				// Add 1 day
				list( $y, $m, $d ) = explode( '-', date( 'Y-m-d', strtotime( $date ) + DAY_IN_SECONDS ) );
				break;
			// Weekly
			case 'weekly' :
				// Add 7 days
				list( $y, $m, $d ) = explode( '-', date( 'Y-m-d', strtotime( $date ) + WEEK_IN_SECONDS ) );
				break;
			// Monthly
			case 'monthly' :
				// Move forward one month
				if ( $m < 12 ) { // same year
					$m++; // add one month
				} else { // next year (old month is December)
					$m = 1; // first month of year
					$y++; // add one year
				}	
				break;
			// Yearly
			case 'yearly' :
				// Move forward one year
				$y++;
				break;
		}

		// Day does not exist in month
		// Example: Make "November 31" into 30 or "February 29" into 28 (for non-leap year)
		$days_in_month = date( 't', mktime( 0, 0, 0, $m, 1, $y ) );
		if ( $d > $days_in_month ) {
			$d = $days_in_month;
		}

		// Form the date string
		$new_date = date( 'Y-m-d', mktime( 0, 0, 0, $m, $d, $y ) ); // pad day, month with 0

		// Is new date in past? Increment until it is not (automatic correction in case wp-cron misses a beat)
		$today_ts = strtotime( date_i18n( 'Y-m-d' ) ); // localized
		$new_date_ts = strtotime( $new_date );
		while ( $new_date_ts < $today_ts ) {
			$new_date = ctcfc_increment_future_date( $new_date, $increment );
			$new_date_ts = strtotime( $new_date );
		}

	}

}
add_filter('ctc_move_date_forward',ctcfc_increment_future_date,10,2);

?>
