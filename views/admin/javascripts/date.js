jQuery(document).bind("omeka:elementformload", function() {

	var $ = jQuery; // use noConflict version of jQuery as the short $ within this block

	$("input[data-type='date']").each(function() {

		var thisHere=$(this);

		var isSpan=(thisHere.val().indexOf(" - ")!=-1);

		thisHere.closest(".input-block").css("width","100%");
		thisHere.css("width","45%");

		var format = thisHere.attr("data-format") || "yy-mm-dd";
		thisHere.calendarsPicker({
			dateFormat: format,
			firstDay: 1,
			yearRange: 'any',
			rangeSelect: isSpan,
			calendar: $.calendars.instance('gregorian', elTypesLocale),
			showOnFocus: false,
		});

		var gredJulId = "gregJul"+thisHere.attr("id");
		var timespanId = "timespan"+thisHere.attr("id");

		thisHere.parent().children("#"+gredJulId).remove();

		var isChecked = (isSpan ? "checked" : "");
		thisHere.parent().append("<span id='"+gredJulId+"'> "+
															"<button type='button' class='editGregLink'>"+
																				elTypesGregorian.substr(0,1)+"</button>"+
															"<button type='button' class='editJuliLink'>"+
																				elTypesJulian.substr(0,1)+"</button>"+
															"<input type='checkbox' id='"+timespanId+"' "+isChecked+"> "+elTypesTimeSpan+
															"<br><strong>"+elTypesConvert+":</strong> "+
															"<a href='#' class='convGregLink'>→ ["+elTypesGregorian+"]</a> "+
															"<a href='#' class='convJuliLink'>→ ["+elTypesJulian+"]</a>"+
															"</span>");

		var thisDateEdit = thisHere.parent().find(".is-calendarsPicker");

		$("#"+gredJulId+" .editGregLink").click( function() { return editGregJuli("gregorian"); } );
		$("#"+gredJulId+" .editJuliLink").click( function() { return editGregJuli("julian"); } );

		$("#"+timespanId).change(switchTimespan);

		$("#"+gredJulId+" .convGregLink").click( function() { return convGregJuli("gregorian"); } );
		$("#"+gredJulId+" .convJuliLink").click( function() { return convGregJuli("julian"); } );

		// -----------------------------

		function setGregJuli(to) {
			var myCalendar = $.calendars.instance(to, elTypesLocale);
			thisDateEdit.calendarsPicker("option", { calendar: myCalendar } );
		}

		// -----------------------------

		function editGregJuli(what) {
			setGregJuli(what);
			thisDateEdit.calendarsPicker("show");
			return false;
		}

		// -----------------------------

		function switchTimespan() {
			var isSpan = $(this).is(':checked');
			thisDateEdit.calendarsPicker("option", { rangeSelect: isSpan } );

			var curValue=thisDateEdit.val();

			if (curValue) {
				var curSep=curValue.indexOf(" - ");
				var currentlySpan=(curSep!=-1);
	
				if (currentlySpan!=isSpan) {
					if (isSpan) { thisDateEdit.val(curValue+" - "+curValue); }
					else { thisDateEdit.val(curValue.substr(0,curSep)); }
				}
			}
		}

		// -----------------------------

		function convGregJuli(to) {
			var from=(to=='gregorian' ? 'julian' : 'gregorian');

			setGregJuli(from);
			thisDateEdit.calendarsPicker("show");

			var curdate=thisDateEdit.calendarsPicker('getDate');

			var toCalendar=$.calendars.instance(to, elTypesLocale);
			var newdate=new Array();

			for (var i = 0; i < curdate.length; i++) {
				newdate[i] = toCalendar.fromJD(curdate[i].toJD());
			}

			setGregJuli(to);                                   
			thisDateEdit.calendarsPicker('setDate', newdate);

			return false;
		}

		// -----------------------------

	});
});
