CTC Full Calendar (Plugin)
==========================

A WordPress plugin that provides a full calendar display for users of the [Church Theme Content Plugin](http://wordpress.org/plugins/church-theme-content/)

Description
-----------

CTC Full Calendar is a a plugin for a plugin. It is designed to allow users of the [Church Theme Content Plugin](http://wordpress.org/plugins/church-theme-content/) to display a full calendar view of events in their posts. This offers an "at a glance" view of all the events, including recurring ones. The view can also be customized to be by month (standard calendar view), by week, or by day. The display is also responsive with customizable breakpoints.  

Installation
------------

Please see [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) in the WordPress Codex for general installation instructions.

CTC Full Calendar requires the [Church Theme Content Plugin](http://wordpress.org/plugins/church-theme-content/) as well, so make sure it is also installed.

Usage
-----

Use the shortcode `[ctc-fullcalendar]` in your posts or pages. 

The calendar display works even without a CTC-compatible theme. However, without a compatible theme, the event page, accessible by clicking on the event title on the calendar will not display all of the rich event information, such as date, time, venue, etc., that is available with the CTC Plugin. To create a compatible theme, please see the [CTC Developer Guide](http://churchthemes.com/guides/developer/church-theme-content/).

Options
-------

### Calendar view ###
By default the calendar is responsive, using a full month calendar view at large screens, and shrinking down to weekly and daily views at intermediate and small viewport, respectively. The view can be fixed to a single one by using the `view` option in the shortcode. 

Syntax: `[ctc-fullcalendar view='{_responsive_ | month | week | basicWeek | agendaWeek | agendaDay}']`

The responsive view uses a basicDay view at small viewports, a basicWeek view at intermediate viewports, and a month display for larger ones. 

### Responsive behavior ###
When the `view` option is left out or set to `responsive`, the calendar view adjusts to the container viewport. To adjust the location of the responsive breaks, use the `breaks` option. This is a comma=separated list of numbers with the widths at which the view changes must be made. The first value specifies the change from `month` view to `basicWeek` view; the second one gives the width for changing from `basicWeek` to `basicDay`. The default breakpoints are 450 and 300.

Syntax: `[ctc-fullcalendar breaks='{med_viewport},{small_viewport}']`

### Number of events ###
Use the shortcode option `max_events` to adjust how many events are displayed in the calendar. Note that this number does not include recurrences of an event. Default value is 100.

Syntax: `[ctc-fullcalendar max_events='10']`

### Number of recurring events ###
Use the shortcode option `max_recur` to adjust how many instances of a recurring event are displayed in the calendar. Note that this number adjusts how many  recurrences are displayed in addition to the first event. For instance, setting this value to 0, still displays the first event; setting it to 1 displays 2: the first event and the first recurrence. Default value is 12.

Syntax: `[ctc-fullcalendar max_recur='10']`

Notes
-----

CTC Full Calendar only handles the full calendar display, not the backend related to the events and their recurrence. To request features related to the event functionality in the CTC plugin, go to the [support](http://wordpress.org/plugin/support/church-theme-content/) page for the CTC Plugin.

I have created a fork of the CTC plugin which adds additional recurrance features, such as daily recurrence and Nth day/week/month/year recurrence to the CTC Plugin. You can find it on [GitHub](http://github.com/serranoabq/church-theme-content/develop). Note that this is an unsupported fork. Steven Gliebe and ChurchThemes.com are not associated with this fork.

Changelog
---------

* 0.1 - Initial version
* 0.2 - Initial shortcode blocks
* 0.3 - Added daily recurrence, with changes to CTC
* 0.4 - Added every n recurrence, with changes to CTC
* 0.9 - Added filter to ctc_move_date_forward and add daily recurrence scheduling. Added to GitHub
* 0.9.1 - Removed filter for ctc_move_date_forward since it's insufficient for daily and every-n recurrence in CTC.
* 0.9.2 - Made calendar code compatible with PHP 5.2 (previous version required PHP 5.3)
* 0.9.3 - Fix previous shift in day
