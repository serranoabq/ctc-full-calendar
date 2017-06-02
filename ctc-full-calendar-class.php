<?php

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CTC_FullCalendar' ) ) {
	class CTC_FullCalendar {
		
		private const CTCFC_USE_CDN = true; // Default is to use CDN; change to false to change default behavior to use local files
		
		private $momentV = '2.18.1'; // CDN version of moment.js
		
		private $fullcalendarV = '3.4.0'; // CDN version of fullcalendar.js
		
		function __construct() {
			$this->version = '1.2.1'; 
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_shortcode( 'ctc_fullcalendar', array( &$this, 'shortcode' ) );
			
			// Enable shortcodes in text widgets
			add_filter('widget_text','do_shortcode');
			
		}
		
		/**
		 * Parse shortcode and insert calendar
		 * Usage: [ctc_fullcalendar] 
		 *   Optional parameters:
		 *     view = 'responsive','month','week','basicWeek','basicDay','agendaWeek',  'agendaDay', 'listDay', 'listYear', 'listMonth', 'listWeek'
		 *       Type of calendar to display. See http://fullcalendar.io for more details. 
		 *       If empty or 'responsive' the calendar is responsive and changes from 
		 *       month => basicWeek => basicDay views
		 *     breaks = '(med_break), (small_break)' 
		 *       Widths to change from month view to basicWeek and from basicWeek to basicDay when $view is empty or 'responsive'
		 *       Default is '768,576'
		 *     category = (string)
		 *       Category slug to show
		 *     max_recur = (integer)
		 *       Maximum number of recurrences to display. 0 only shows the first event. Default is 12
		 *     max_events = (integer)
		 *       Maximum events to display, not counting recurrences. Default is 100
		 *     use_cdn = CTCFC_USE_CDN (true as shipped)
		 *       Set to false to use local libraries instead of pulling them from CDNJS (default)
		 *     before=''
		 *       Markup to insert before the calendar
		 *     after = ''
		 *       Markup to insert after the calendar
		 * @since 1.2
		 * @param string $attr Shortcode options
		 * @return string Full calendar display
		 */
		function shortcode( $attr ) {
			$output = apply_filters( array( &$this, 'shortcode' ), '', $attr );
		
			if ( $output != '' ) return $output;
			
			extract( shortcode_atts( array(
				'view'			=>  '',  
				'breaks' 		=>  '768,576',  
				'category' 	=>  '',  
				'max_recur'	=>  12, // this is enough for most cases
				'max_events'=>  100,
				'use_cdn'  	=>  self::CTCFC_USE_CDN, // default is to use CDNJS  
				'before'    =>  '',
				'after'     =>  '',
				), $attr ) );
				
			$this->scripts_styles( $use_cdn );
			
			// Clean up things a bit
			if( $breaks ) $breaks = json_decode( '[' . $breaks . ']', true );
			$views =array( '', 'month', 'week', 'basicWeek', 'basicDay', 'agendaWeek', 'agendaDay', 'listDay', 'listYear', 'listMonth', 'listWeek' );
			
			if( ! in_array( $view, $views ) ) $view = '';
			
			// calendar div
			$result = '<div id="ctc-fullcalendar"></div>';
			
			// do query 
			$query = array(
				'post_type' => 'ctc_event', 
				'order' => 'ASC',
				'orderby' => 'meta_value',
				'meta_key' => '_ctc_event_start_date_start_time',
				'meta_type' => 'DATETIME',
				'posts_per_page' => $max_events, 
				'meta_query'     => array(
					array(
						'key'     => '_ctc_event_end_date',
						'value'   => date_i18n( 'Y-m-d' ), // today localized
						'compare' => '>=', // later than today
						'type'    => 'DATE',
					),
				), 
			) ; 
			
			if( ! empty( $category ) )  {
				$query[ 'tax_query' ] = array( 
					array(
						'taxonomy'  => 'ctc_event_category',
						'field'     => 'slug',
						'terms'     => $category,
					),
				);
			}
			
			$posts = new WP_Query();
			$posts -> query($query); 
			
			if ($posts->have_posts()){
				$events = array();
				
				while ($posts->have_posts()) :
					$posts		-> the_post();
					$post_id 	= get_the_ID();
					$title 		= get_the_title() ;
					$url 			= get_permalink();
					
					// Event data
					$start_date = get_post_meta( $post_id, '_ctc_event_start_date' , true ); 
					$end_date   = get_post_meta( $post_id, '_ctc_event_end_date' , true ); 
					$start_time = get_post_meta( $post_id, '_ctc_event_start_time' , true );
					$end_time   = get_post_meta( $post_id, '_ctc_event_end_time' , true );
					$start_date_start_time = str_replace( ' ', 'T', ctc_convert_to_datetime( $start_date, $start_time ) ); 
					$end_date_end_time = str_replace( ' ', 'T', ctc_convert_to_datetime( $end_date, $end_time ) ); 
					
					$allday = empty( $start_time ); // safest assumption

					$eventlen = strtotime($end_date) - strtotime($start_date);
					
					// append to event array	
					$events[] = array(
						'id'        => $post_id,
						'title'     => $title,
						'htmlTitle' => $title,
						'allDay'    => $allday,
						'start'     => $start_date_start_time,
						'end' 	    => $end_date_end_time,
						'url'       => $url
					);
					
					// Pertinent recurrence information
					$recurrence = get_post_meta( $post_id, '_ctc_event_recurrence' , true ); 
					
					// These are not default in CTC, but are included in the Harvest theme
					$recurrence_period = get_post_meta( $post_id, '_ctc_event_recurrence_period' , true );
					$recurrence_monthly_type = get_post_meta( $post_id, '_ctc_event_recurrence_monthly_type' , true );
					$recurrence_monthly_week = get_post_meta( $post_id, '_ctc_event_recurrence_monthly_week' , true );
					$recurrence_end 	= get_post_meta( $post_id, '_ctc_event_recurrence_end_date', true );
					
					// Display recurrences
					if( 'none' != $recurrence ) {
						
						$n = $recurrence_period != '' ? (int) $recurrence_period : 1;
						
						// If no end set for recurrence, set it to 12 years out
						if( empty( $recurrence_end ) ) {
							$DateTime = new DateTime( $start_date );
							$DateTime -> modify( '+12 years' );
							$recurrence_end = $DateTime->format( 'Y-m-d' );
						}
						
						list( $start_date_y, $start_date_m, $start_date_d ) = explode( '-', $start_date );
						
						for( $i = 1 ; $i <= $max_recur ; $i++ ) {
							
							list( $y, $m, $d ) = explode( '-', $start_date );
							
							switch( $recurrence ) {
								
								// NOTE: Daily is not an option in the CTC plugin 
								case 'daily':
									$DateTime = new DateTime( $start_date );
									$DateTime -> modify( '+' . $i * $n . ' days' );
									list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
									break;
									
								case 'weekly':
									// same day of the week (eg, Sun-Sat)
									$DateTime = new DateTime( $start_date );
									$DateTime -> modify( '+' . $i * $n . ' weeks' );
									list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
									break;
									
								case 'monthly':
									$DateTime = new DateTime( $start_date );
									$DateTime -> modify( '+' . $i * $n . ' months' );
									list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
									
									// On a specific week of month's day
									// 1st - 4th or Last day of week in the month
									if ( 'week' == $recurrence_monthly_type && ! empty( $recurrence_monthly_week ) ) {
										
										// What is start_date's day of the week?
										// 0 - 6 represents Sunday through Saturday
										$start_date_day_of_week = date( 'w', strtotime( $start_date ) );

										// Loop the days of this month
										$week_of_month = 1;
										$times_day_of_week_found = 0;
										$days_in_month = date( 't', mktime( 0, 0, 0, $m, 1, $y ) );

										for ( $j = 1; $j <= $days_in_month; $j++ ) {

											// Get this day's day of week (0 - 6)
											$day_of_week = date( 'w', mktime( 0, 0, 0, $m, $j, $y ) );

											// This day's day of week matches start date's day of week
											if ( $day_of_week == $start_date_day_of_week ) {
												$last_day_of_week_found = $j;
												$times_day_of_week_found++;

												// Is this the 1st - 4th day of week we're looking for?
												if ( $recurrence_monthly_week == $times_day_of_week_found ) {
													$d = $j;
													break;
												}
											}
										}

										// Are we looking for 'last' day of week in a month?
										if ( 'last' == $recurrence_monthly_week && ! empty( $last_day_of_week_found ) ) {
											$d = $last_day_of_week_found;
										}
										
									} else {
										
										if ( $d < $start_date_d ) {
											// Move back to last day of last month
											$m--;
											if ( 0 == $m ) {
												$m = 12;
												$y--;
											}
											// Get days in the prior month
											$d = date( 't', mktime( 0, 0, 0, $m, $d, $y ) );
										}
									}

									break;
									
								case 'yearly':
									$DateTime = new DateTime( $start_date );
									$DateTime->modify( '+' . $i * $n . ' years' );
									list( $y, $m, $d ) = explode( '-', $DateTime->format( 'Y-m-d' ) );
									if ( $d < $start_date_d ) {
										// Move back to last day of last month
										$m--;
										if ( 0 == $m ) {
											$m = 12;
											$y--;
										}

										// Get days in the prior month
										$d = date( 't', mktime( 0, 0, 0, $m, $d, $y ) );
									}
									break;
									
							} // switch
							
							// make the new date
							$new_start_date = date( 'Y-m-d', mktime( 0, 0, 0, $m, $d, $y ) );
							$new_end_date = date( 'Y-m-d', strtotime( $new_start_date ) + $eventlen );
							
							// stop if new date is past the recurrence end date
							if( strtotime( $new_start_date ) > strtotime( $recurrence_end ) ) break;
							
							$new_start_date_start_time = str_replace( ' ', 'T', ctc_convert_to_datetime( $new_start_date, $start_time ) );
							$new_end_date_end_time = str_replace( ' ', 'T', ctc_convert_to_datetime( $new_end_date, $end_time ) );
														
							// append to event array
							$events[] = array(
								'id' 		    => $post_id,
								'title'     => $title,
								'htmlTitle' => $title,
								'allDay'    => $allday,
								'start'     => $new_start_date_start_time,
								'end' 	    => $new_end_date_end_time,
								'url'       => $url
							);
						}
					}
				endwhile; 
			}
			wp_reset_query();
			
			// The event data is loded as a json object 
			$before .= '<script>';
			if( ! empty( $events ) ) $before .= 'var events = '. json_encode( $events ) . ';';
			$before .= 'var fixedView = '. json_encode( $view ) . ';';
			$before .= 'var breaks = '. json_encode( $breaks ) . ';';
			$before .= '</script>';
			
			return $before . $result . $after;
		}

	
		/**
		 * Register scripts and styles
		 *
		 * @since 1.2.1
		 */
		function scripts_styles( $use_cdn = self::CTCFC_USE_CDN ){
			
			if( $use_cdn ) {
				
				wp_enqueue_script( 'moment',  '//cdnjs.cloudflare.com/ajax/libs/moment.js/' . $this->momentV . '/moment.min.js', array() );
				wp_enqueue_script( 'fullcalendar', '//cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . $this->fullcalendarV . '/fullcalendar.min.js', array( 'jquery', 'moment' ) );				
				wp_enqueue_style( 'fullcalendar-css', '//cdnjs.cloudflare.com/ajax/libs/fullcalendar/' . $this->fullcalendarV . '/fullcalendar.min.css' );
			
			} else {
				
				wp_register_script( 'moment', 
				plugins_url( '/js/moment.min.js' , __FILE__ ), array() );
				wp_register_script( 'fullcalendar', 
				plugins_url( '/js/fullcalendar.min.js' , __FILE__ ), array('jquery','moment') );		
				wp_register_style( 'fullcalendar-css', 
				plugins_url( '/css/fullcalendar.css' , __FILE__ ) );
				
			}
			
			wp_enqueue_script( 'ctc-fullcalendar', 
				plugins_url( 'js/ctc-fullcalendar.js' , __FILE__ ), array('fullcalendar') );			
			wp_enqueue_style( 'ctc-fullcalendar', 
				plugins_url( 'css/ctc-fullcalendar.css' , __FILE__ ) );
				
		}
		
	}
	
}