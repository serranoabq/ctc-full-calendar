/*
* Church Theme Content Full Calendar
*
*/
jQuery(document).ready( function() {
	var ww = jQuery( window ).width();
	
	if ( typeof breaks !== 'undefined' ) {
		
		if  (breaks.length == 0 ) breaks = [ 768, 558 ];
		
		if ( breaks.length == 1 ) breaks[1] = breaks[0];
		
		jQuery( '#ctc-fullcalendar' ).fullCalendar({
			
			defaultView: fixedView ? fixedView : (ww < breaks[0] ? (ww < breaks[1] ? 'basicDay' : 'basicWeek' ) : 'month' ),
			
			events: events,
			
			eventRender: function( event, element ){
				element.find( '.fc-title' ).html( event.title );
				element.find( '.fc-list-item-title' ).html( event.title );
			},
			
			height: 'auto',
			
			windowResize: function( view ){
				
				if ( fixedView ) return;
				
				var ww = jQuery( '#ctc-fullcalendar' ).width();
				
				var view = ww < breaks[0] ? 'basicWeek' : 'month';
				view = ww < breaks[1] ? 'basicDay' : view;
				
				var currentView = jQuery( '#ctc-fullcalendar' ).fullCalendar( 'getView' );
				
				if( view != currentView.name) {
					jQuery( '#ctc-fullcalendar' ).fullCalendar( 'changeView', view );
				}
			}
		});
	}
	
	
	ctc_accordion();
	jQuery( '#ctc-cal-week' ).each( function(){ jQuery( this ).responsiveTabs(); } );
	jQuery( window ).resize( function(){ ctc_accordion(); } );
	
} );

function ctc_accordion(){
	jQuery( '#ctc-cal-week' ).each( function(){ 
		cww = jQuery( this ).width();
		if ( cww < 512 ) 
			jQuery(  this ).addClass( 'accordion' );
		else
			jQuery( this ).removeClass( 'accordion' );
	} );
}
