<?php
/*
    Plugin Name: CTC Full Calendar
    Description: Plugin to display Church Theme Content Events in a responsive, full calendar view in your pages and post using the <code>[ctc_fullcalendar]</code> shortcode. Requires <strong>Church Theme Content</strong> plugin.
    Version: 1.0
    Author: Justin R. Serrano
*/

// No direct access
if ( !defined( 'ABSPATH' ) ) exit;

require_once( sprintf( "%s/ctc-full-calendar-class.php", dirname(__FILE__) ) );

if( class_exists( 'CTC_FullCalendar' ) ) {
	$CTCFC = new CTC_FullCalendar();
}
