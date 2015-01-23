<?php


if ( ! class_exists( 'CTC_FullCalendar' ) ) {
	class CTC_FullCalendar {
		
		public function __construct() {
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			add_shortcode( 'ctc_fullcalendar', array( &$this, 'shortcode' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'scripts_styles' ) );
			add_action( 'widgets_init', array( &$this, 'register_widgets' ) );			
		}
		
		/**
		 * Parse shortcode and insert calendar
		 * Usage: [ctc_fullcalendar] 
		 *   Optional parameters:
		 *     view = 'responsive','month','week','basicWeek','basicDay','agendaWeek', or 'agendaDay'
		 *       Type of calendar to display. See http://arshaw.com/fullcalendar/ for more details. 
		 *       If empty or 'responsive' the calendar is responsive and changes from 
		 *       month => basicWeek => basicDay views
		 *     breaks = '(med_break), (small_break)' 
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
		 * @since 1.0
		 * @param string $attr Shortcode options
		 * @return string Full calendar display
		 */
		public function shortcode( $attr ) {
			$output = apply_filters( array( &$this, 'shortcode' ), '', $attr );
			
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
					$origindate 	= get_post_meta( $post_id, '_ctc_event_origin_date', true );
					$startdate 	= get_post_meta( $post_id, '_ctc_event_start_date', true );
					$origindate = empty( $origindate ) ? $startdate : $origindate;
					$enddate   	= get_post_meta( $post_id, '_ctc_event_end_date', true );
					$time  			= get_post_meta( $post_id, '_ctc_event_time', true );
					
					// Fix things a bit
					$enddate   	= $enddate =='' ? $startdate : $enddate;
					$time  			= str_replace(' ','',$time);
					
					$eventlen = strtotime($enddate) - strtotime($startdate);
					
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
							list( $oy, $om, $od) = explode( '-', $origindate );
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
									// Fix: the event start day might have been shifted, so go by the 
									// original start date. Note that this will affect end date as that 
									// is relative to the original 
									$d = $d != $od ? $od: $d;
									break;
								case 'yearly':
									// same day of the year (eg., the 27th of October)
									$y+=$i * $n;
									break;
							}
							
							if ( 'monthly' == $recurrence && $d != $od ) {
								$d = $od;
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
								$recurenddate = date('Y-m-d', strtotime($startdate) + $eventlen + $dateshift);
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
	
		/**
		 * Register scripts and styles
		 *
		 * @since 1.0
		 */
		public function scripts_styles(){
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
	
		
		public function register_widgets() {
			//register_widget( 'CTCFC_CalWidget' ); // Smallish
			register_widget( 'CTCFC_WeekWidget' ); // This week
		}
	}
	
}
	function ctcfc_getEvents( $week_start, $week_end, $max_recur ){
		if ( ! isset( $max_recur ) ) $max_recur = 12;
		$query = array(
			'post_type' 			=> 'ctc_event', 
			'order' 					=> 'ASC',
			'orderby' 				=> 'meta_value',
			'meta_key' 				=> '_ctc_event_start_date',
			'meta_type' 			=> 'DATETIME',
		); 
		$posts = new WP_Query();
		$posts -> query( $query ); 
		if ( $posts -> have_posts() ){
			$events = array();
			while ($posts->have_posts()) :
				$posts		 	-> the_post();
				$post_id 	 	= get_the_ID();
				$evt_title	= get_the_title();
				$url 			 	= get_permalink();
				
				// Get event information
				$start_date 	= get_post_meta( $post_id, '_ctc_event_start_date', true );
				$start_time 	= get_post_meta( $post_id, '_ctc_event_start_time', true );
				$end_date   	= get_post_meta( $post_id, '_ctc_event_end_date', true );
				$end_time   	= get_post_meta( $post_id, '_ctc_event_end_time', true );
				$evt_len 			= strtotime( $end_date ) - strtotime( $start_date );
				
				// Skip over events outside of the week
				if( isset( $week_start ) && $start_date < $week_start ) continue;
				if( isset( $week_end ) && $start_date > $week_end ) continue;
				
				// Add to event array
				$events[] = array(
					'id' 		 			=> $post_id,
					'title'  			=> $evt_title,
					'start_date'  => $start_date,
					'start_time'  => $start_time,
					'end_date' 	 	=> $end_date,
					'end_time' 	 	=> $end_time,
					'url'    => $url
				);
				
				// Get recurrent information
				$recurrence 		  = get_post_meta( $post_id, '_ctc_event_recurrence', true );
				$recurrence_end 	= get_post_meta( $post_id, '_ctc_event_recurrence_end_date', true );
				$recurrence_period 	= get_post_meta( $post_id, '_ctc_event_recurrence_period', true );
						
				if ($recurrence != 'none') {
					$n = $recurrence_period != '' ? (int) $recurrence_period : 1;
					//$stDT = new DateTime( $start_date );
					for ( $i = 1 ; $i <= $max_recur ; $i++ ) {
						list( $y, $m, $d ) = explode( '-', $start_date );
						switch ($recurrence) {
							// NOTE: Daily is not an option in the CTC plugin 
							case 'daily':
								$stDT = new DateTime( $start_date );
								$stDT->modify('+' . $i * $n  . ' days');
								list( $y, $m, $d ) = explode( '-', $stDT->format( 'Y-m-d' ) );
								break;
							case 'weekly':
								// same day of the week (eg, Sun-Sat)
								$stDT = new DateTime( $start_date );
								$stDT -> modify('+' , $i * $n  . ' weeks');
								list( $y, $m, $d ) = explode( '-', $stDT->format( 'Y-m-d' ) );
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
								$y += $i * $n;
								break;
						} // switch
						
						// Fix for a day beyond the month's end
						$t = date( 't', mktime( 0, 0, 0, $m, 1, $y )) ;
						if($d > $t) {
							$recur_date = date( 'Y-m-t', mktime( 0, 0, 0, $m, 1, $y ));
						} else {
							$recur_date = date( 'Y-m-d', mktime( 0, 0, 0, $m, $d, $y ));
						}
						echo "<div>$i $n $recur_date $recurrence_end</div>";
						// Figure out the shift needed to apply to the end date & time
						$date_shift = strtotime( $recur_date ) - strtotime( $start_date );
						
						// stop if new date is past the recurrence end date
						if( strtotime( $recur_date ) > strtotime( $recurrence_end) ) break;
						if( isset( $week_end ) && strtotime( $recur_date ) > strtotime( $week_end ) ) break;
						
						// shift end dates as well
						$recur_end_date = date('Y-m-d', strtotime( $start_date ) + $evt_len + $date_shift);
						
						// append to event array
						$events[] = array(
							'id' 		 			=> $post_id,
							'title'  			=> $evt_title,
							'start_date'  => $recur_date,
							'start_time'  => $start_time,
							'end_date' 	 	=> $recur_end_date,
							'end_time' 	 	=> $end_time,
							'url'    			=> $url
						);
					} 
				} 
			endwhile; 
		}
		wp_reset_query();			

		return $events;
	} 
			
	class CTCFC_WeekWidget extends WP_Widget {
		
		function CTCFC_WeekWidget() {
			$widget_ops = array(
				'classname' 	=> 'widget_weekcal', 
				'description' => __( 'Weekly Calendar', 'ctc-fullcalendar' ) 
			);
			$this->WP_Widget( 'widget_weekcal', __( 'Weekly Calendar', 'ctc-fullcalendar' ), $widget_ops);
		}
		
		function widget( $args, $instance ) {
			extract( $args );
			$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Weekly Calendar', 'ctc-fullcalendar' ) : $instance['title'], $instance, id_base);
			$add_link = $instance['add_link'];
			$link = $instance['link'];
			
			$week_start = date( 'D' ) != 'Sun' ? date( 'Y-m-d', strtotime( 'last Sunday' ) ) : date( 'Y-m-d' );
			$week_end = date( 'D' ) != 'Sat' ? date( 'Y-m-d', strtotime( 'next Saturday' ) ) : date( 'Y-m-d' );
			
			/*
			// do query 
			$query = array(
				'post_type' 			=> 'ctc_event', 
				'order' 					=> 'ASC',
				'orderby' 				=> 'meta_value',
				'meta_key' 				=> '_ctc_event_start_date',
				'meta_type' 			=> 'DATETIME',
			); 
			$posts = new WP_Query();
			$posts->query($query); 
			if ($posts->have_posts()){
				$events = array();
				while ($posts->have_posts()) :
					$posts		 	-> the_post();
					$post_id 	 	= get_the_ID();
					$evt_title	= get_the_title();
					$url 			 	= get_permalink();
					
					// Get event information
					$start_date 	= get_post_meta( $post_id, '_ctc_event_start_date', true );
					$start_time 	= get_post_meta( $post_id, '_ctc_event_start_time', true );
					$end_date   	= get_post_meta( $post_id, '_ctc_event_end_date', true );
					$end_time   	= get_post_meta( $post_id, '_ctc_event_end_time', true );
					
					// Skip over events outside of the week
					if( $start_date < $week_start ) continue;
					if( $start_date > $week_end ) continue;
					// append to event array	
					
					$events[] = array(
						'id' 		 			=> $post_id,
						'title'  			=> $evt_title,
						'start_date'  => $start_date,
						'start_time'  => $start_time,
						'end_date' 	 	=> $end_date,
						'url'    => $url
					);
					
				endwhile;
			}
			wp_reset_query();
			*/
			$events = ctcfc_getEvents( $week_start, $week_end );
			
			wp_enqueue_script( 'responsive-tabs-js', 
				plugins_url( '/js/jquery.responsiveTabs.min.js' , __FILE__ ), array('jquery') );
			wp_enqueue_script( 'ctc-fullcalendar-js', 
				plugins_url( '/js/ctc-fullcalendar.js' , __FILE__ ), array('jquery') );
			wp_enqueue_style( 'ctc-fullcalendar-css', 
				plugins_url( '/css/ctc-fullcalendar.css' , __FILE__ ) );
			
			echo $before_widget;
			if ( $title ) {
				if( $add_link ) {
					wp_enqueue_style( 'font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css', array(), '4.0.3' );
					$after_title = '<a class="ctc-cal-week-title-link" href="'.$link . '"><i class="fa fa-chevron-right pull-right"></i></a>' . $after_title;
				} 
				echo $before_title . $title . $after_title;
			}
			
			echo '<div id="ctc-cal-week"><ul>';
			
			$week_startDT = new DateTime( $week_start );
			$week_endDT = new DateTime( $week_end );
			
			// Do the day tabs
			$dayN = 1;
			while( $week_startDT <= $week_endDT ) {
				$day = $week_startDT -> format( 'd' );
				$week_day = date_i18n( 'l', $week_startDT -> getTimestamp() );
				echo '<li><a href="#day-'. $dayN .'">'. $day . "<span>$week_day</span>" .'</a></li>';
				$week_startDT -> modify( '+1 day' );
				$dayN++;
			}
			echo '</ul>';
			
			// Do the daily content
			$week_startDT = new DateTime( $week_start );
			$dayN = 1;
			while( $week_startDT <= $week_endDT ) {
				$day = $week_startDT -> format( 'd' );
				$week_day = date_i18n( 'l', $week_startDT -> getTimestamp() );
				echo '<div id="day-'. $dayN . '">';
				$evt_count = 0;
				foreach( $events as $evt ){
					$evt_date = new DateTime( $evt[ 'start_date' ] );
					if( $evt_date == $week_startDT ) {
						if( $evt[ 'start_time' ] )
							echo sprintf('<p>%s &mdash; <a href="%s">%s</a></p>', $evt[ 'start_time' ], $evt[ 'url' ], $evt[ 'title' ] );
						else
							echo sprintf('<p><a href="%s">%s</a></p>', $evt[ 'url' ], $evt[ 'title' ] );							
						$evt_count ++;
					}
				}
				
				if( $evt_count == 0 ) 
					echo "<p>No events listed for $week_day</p>";
					
				echo '</div>';
				$week_startDT -> modify( '+1 day' );
				$dayN++;
			}
			/**/
			echo '</div>';
			echo $after_widget;
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			if( strip_tags( $new_instance['link'] ) != '' && $new_instance['add_link'] ){
				$instance['link'] = strip_tags($new_instance['link']);
				$instance['add_link'] = strip_tags($new_instance['add_link']);
			} else {
				$instance['link'] = '';
				$instance['add_link'] = '';
			}
			return $instance;
		}
		function form( $instance ) {
			$title = esc_attr( $instance['title'] );
			$add_link = esc_attr( $instance['add_link'] );
			$link = esc_attr( $instance['link'] );
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
				<input type="checkbox" id="<?php echo $this->get_field_id('add_link'); ?>" name="<?php echo $this->get_field_name('add_link'); ?>" <?php echo $add_link ? 'checked': ''; ?> />
				<label for="<?php echo $this->get_field_id('add_link'); ?>"><?php _e( 'Show Title Link', 'ctc-fullcalendar' ); ?></label> 
				<input type="text" class="widefat" id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" <?php echo $add_link ? '' : 'disabled'; ?> value="<?php echo $link; ?>" />
			</p>
			<script>
			<?php 
			// There's no need to have two of these on the same sidebar, so this code below
			// prevents adding a second instance to the same sidebar. This won't prevent 
			// adding it to the same page (via separate widget areas), but it's a start
			?>
				jQuery(document).ready( function($) {
					var sidebars = $('.widgets-sortables');
					sidebars.each( function() {
						var id_base = $(this).find("input.id_base");
						var me = $( this );
						if( me.attr('id').indexOf('inactive') != -1 ) return;
						var found = false;
						id_base.each( function(){
							if( $(this).val() != 'widget_weekcal') return;
							if(found) 
								$(this).closest('div.widget').remove();
							else
								found = true;
						});
					});
					$('#<?php echo $this->get_field_id('add_link'); ?>').change( function (){
							$('#<?php echo $this->get_field_id('link'); ?>').prop('disabled', ! $( this ).is(':checked') );
						
					});
				});
			</script>
	<?php 
		} 
	} 