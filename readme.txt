=== CTC Full Calendar ===
Contributors: serranoabq
Tags: church, churches, events, calendar
Requires at least: 3.6
Tested up to: 4.7.5
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

Use the shortcode option 'view', as in [ctc-fullcalendar view='month']. Available views are 'month','week','basicWeek','basicDay','agendaWeek', 'agendaDay', 'listDay', 'listWeek', 'listMonth', and 'listYear'. If the view is not specified, the calendar view is adjusted depending on the width of the container. The responsive view uses a basicDay view at small viewports, a basicWeek view at intermediate viewports, and a month display for larger ones. List style views (listDay, listWeek, listMonth, listYear) are not responsive. 

= How do adjust the breakpoints for responsive views? =

Use the shortcode option 'breaks', as in [ctc-fullcalendar breaks='450,300']. The breaks are specified as a comma-separated list of numbers with the widths at which the view changes must be made. The first value specifies the change from large/month view to basicWeek view; the second one gives the width for changing from basicWeek to basicDay. The default breakpoints are 768 (tablet) and 568. 

= Can I change the number of events loaded into the calendar? =

Yes. Use the shortcode option 'max_events', as in [ctc-fullcalendar max_events='10']. Note that this number does not include recurrences of an event. Default value is 100.

= Can I change how many recurrences of an event are displayed? =

Yes. Use the shortcode option 'max_recur', as in [ctc-fullcalendar max_recur='5']. Note that this number adjusts how many  recurrences are displayed in addition to the first event. For instance, setting this value to 0, still displays the first event; setting it to 1 displays 2: the first event and the first recurrence.  

= Can you add a daily recurrence feature to the plugin? =

CTC Full Calendar only handles the full calendar display, not the backend related to the events and their recurrence. To request features related to the event functionality in the CTC plugin, go to the [support](http://wordpress.org/support/church-theme-content/) page for the CTC Plugin. I have created a fork of the CTC plugin which adds this feature and which CTC Full Calendar supports. You can find it on [GitHub](http://github.com/serranoabq/church-theme-content/develop). Note that this is an unsupported fork. Steven Gliebe and ChurchThemes.Net are not associated with this version.

= Can you add Nth day/week/month/year recurrence to the plugin? =

CTC Full Calendar only handles the full calendar display, not the backend related to the events and their recurrence. To request features related to the event functionality in the CTC plugin, go to the [support](http://wordpress.org/support/church-theme-content/) page for the CTC Plugin. I have created a fork of the CTC plugin which adds this feature. You can find it on [GitHub](http://github.com/serranoabq/church-theme-content/develop). Note that this is an unsupported fork. Steven Gliebe and ChurchThemes.Net are not associated with this version.

= Can I use this plugin with any theme? =

Yes, the calendar display works even without a CTC-compatible theme. However, without a compatible theme, the event page, accessible by clicking on the event title on the calendar will not display all of the rich event information, such as date, time, venue, etc., that is available with the CTC Plugin. To create a compatible theme, please see the [CTC Developer Guide](http://churchthemes.com/guides/developer/church-theme-content/).

== Screenshots ==


== Changelog ==

0.1 - Initial version
0.2 - Initial shortcode blocks
0.3 - Added daily recurrence, with changes to CTC
0.4 - Added every n recurrence, with changes to CTC
0.9 - Added filter to ctc_move_date_forward and add daily recurrence scheduling. Added to GitHub
0.9.1 - Removed filter for ctc_move_date_forward since it's insufficient for daily and every-n recurrence in CTC.
0.9.2 - Made calendar code compatible with PHP 5.2 (previous version required PHP 5.3)
1.2.1 - Updated to v3.4.0 of fullcalendar.js and fixed various bugs