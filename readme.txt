=== CTC Full Calendar ===
Contributors: serranoabq
Tags: church, churches, events, calendar
Requires at least: 3.6
Tested up to: 3.9.1
Stable tag: trunk
License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Provides users of the events feature in [Church Theme Content Plugin](http://wordpress.org/plugins/church-theme-content/) (CTC) a way to display their events in a full calendar view by using a shortcode. The CTC plugin must be installed and activated. Displaying content associated with events requires a  CTC-compatible theme.

== Description ==

CTC Full Calendar is intended to help individuals and churches using the events feature of the [Church Theme Content plugin](http://wordpress.org/plugins/church-theme-content/) to display a full calendar of their events from within their posts. This offers an "at a glance" view of all the events, including recurring ones. The view can also be customized to be by month (standard calendar view), by week, or by day. The display is also responsive with customizable breakpoints.  

== Installation ==

Please see [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) in the WordPress Codex.

== Frequently Asked Questions ==

= How do I display a calendar? =

Use the shortcode [ctc-fullcalendar] in your posts or pages. 

= How do I change the calendar view? =

Use the shortcode option 'view', as in [ctc-fullcalendar view='month']. Available views are 'responsive', 'month','week','basicWeek','basicDay','agendaWeek', or 'agendaDay'. If the view is 'responsive' or not specified, the calendar view is adjusted depending on the width of the container. The responsive view uses a basicDay view at small viewports, a basicWeek view at intermediate viewports, and a month display for larger ones. 

= How do adjust the breakpoints for responsive views? =

Use the shortcode option 'breaks', as in [ctc-fullcalendar breaks='450,300']. The breaks are specified as a comma-separated list of numbers with the widths at which the view changes must be made. The first value specifies the change from large/month view to basicWeek view; the second one gives the width for changing from basicWeek to basicDay. The default breakpoints are 450 and 300. 

= Can I change the number of events loaded into the calendar? =

Yes. Use the shortcode option 'max_events', as in [ctc-fullcalendar max_events='10']. Note that this number does not include recurrences of an event. Default value is 100.

= Can I change how many recurrences of an event are displayed? =

Yes. Use the shortcode option 'max_recur', as in [ctc-fullcalendar max_recur='5']. Note that this number adjusts how many  recurrences are displayed in addition to the first event. For instance, setting this value to 0, still displays the first event; setting it to 1 displays 2: the first event and the first recurrence.  

= Can you add a daily recurrence feature to the plugin? =

CTC Full Calendar only handles the full calendar display, not the backend related to the events and their recurrence. To request features related to the event functionality in the CTC plugin, go to the [support](http://wordpress.org/support/church-theme-content/) page for the CTC Plugin. 

Here is one way to add daily recurrence to the CTC plugin. Note that this feature is unsupported by the CTC plugin and will break with future updates. This requires editing files in the CTC plugin directory. 

Add the following line to wp-content\plugins\church-theme-content\includes\admin\event-fields.php. In the ctc_add_meta_box_event_date() function, edit the $meta_box 'fields' array as follows. Under '_ctc_event_recurrence', in the 'options', add the following element in the array, after 'none'
 'daily'	=> _x( 'Daily', 'event meta box', 'church-theme-content' ),


= Can you add Nth day/week/month/year recurrence to the plugin? =

CTC Full Calendar only handles the full calendar display, not the backend related to the events and their recurrence. To request features related to the event functionality in the CTC plugin, go to the [support](http://wordpress.org/support/church-theme-content/) page for the CTC Plugin. 

Here is one way to add recurrence period (equivalent to Nth day/week/month/year recurrence) to the CTC plugin. Note that this feature is unsupported by the CTC plugin and will break with future updates. This requires editing files in the CTC plugin directory. 

In 
// Recurence Period
'_ctc_event_recurrence_period' => array(
	'name'				=> __( 'Recurrence Period', 'church-theme-content' ),
	'after_name'		=> '',
	'desc'				=> _x( "Period of recurrence. It works with the Recurrence field to allow every N type recurrence. For example, choosing 'weekly' and seting this field to '2' makes the recurrence biweekly.", 'event meta box', 'church-theme-content' ),
	'type'				=> 'number', // text, textarea, checkbox, radio, select, number, upload, upload_textarea, url
	'checkbox_label'	=> '', //show text after checkbox
	'options'			=> array(), // array of keys/values for radio or select
	'upload_button'		=> '', // text for button that opens media frame
	'upload_title'		=> '', // title appearing at top of media frame
	'upload_type'		=> '', // optional type of media to filter by (image, audio, video, application/pdf)
	'default'			=> '1', // value to pre-populate option with (before first save or on reset)
	'no_empty'			=> true, // if user empties value, force default to be saved instead
	'allow_html'		=> false, // allow HTML to be used in the value (text, textarea)
	'attributes'		=> array(), // attr => value array (e.g. set min/max for number type)
	'class'				=> '', // class(es) to add to input (try try ctmb-medium, ctmb-small, ctmb-tiny)
	'field_attributes'	=> array(), // attr => value array for field container
	'field_class'		=> '', // class(es) to add to field container
	'custom_sanitize'	=> '', // function to do additional sanitization
	'custom_field'		=> '', // function for custom display of field input
),
= Can I use this plugin with any theme? =

Yes, the calendar display works even without a CTC-compatible theme. However, without a compatible theme, the event page, accessible by clicking on the event title on the calendar will not display all of the rich event information, such as date, time, venue, etc., that is available with the CTC Plugin. To create a compatible theme, please see the [CTC Developer Guide](http://churchthemes.com/guides/developer/church-theme-content/).

== Screenshots ==


== Changelog ==

0.1 - Initial version
0.2 - Initial shortcode blocks
0.3 - Added daily recurrence, with changes to CTC
0.4 - Added nth - day recurrence, with changes to CTC
0.9 - BETA. Added filter to ctc_move_date_forward and add daily recurrence scheduling