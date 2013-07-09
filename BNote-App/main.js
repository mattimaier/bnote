// stores the main application data
var appdata;
var pin;

function rehearsal_participate(rid, part) {
	if(part == 0) {
		$('input[name=rid]').val(rid);
		window.location = '#reason';
	}
	else {
		saveParticipation(rid, 1);
	}
}

function saveParticipation(rid, part, reason) {
	var freason = encodeURIComponent(reason);
	jQuery.ajax({
		type : "GET",
		url : srv_url + srv_location,
		data: "pin=" + pin + "&func=setParticipation&rehearsal=" + rid + "&participation=" + part + "&reason=" + freason,
		dataType: 'text',
		error: function(jqXHR, textStatus, errorThrown) {
			alert(errorThrown);
		},
		success: function(data) {
			// remove buttons
			$('#btn_accept_' + rid).remove();
			$('#btn_deny_' + rid).remove();
			
			// write participation
			var msg = 'Du nimmst an der Probe ';
			if(part == 0) msg += 'nicht ';
			msg += 'teil.';
			
			$('#rehearsal_' + rid).append('<p>' + msg + '</p>');
			
			window.location = '#rehearsals';
		}
	});
}

function getLocation(lid) {
	for(i in appdata.locations) {
		if(appdata.locations[i].id == lid) return appdata.locations[i];
	}
	return "";
}

function getAddress(aid) {
	for(i in appdata.addresss) {
		if(appdata.addresss[i].id == aid) return appdata.addresss[i];
	}
	return "";
}

function getContact(cid) {
	for(i in appdata.contacts) {
		if(appdata.contacts[i].id == cid) return appdata.contacts[i];
	}
	return "";
}

function formatDateTime(ufdate) {
	// ufdate format : yyyy-mm-dd hh:ii:ss
	var year = ufdate.substr(0, 4);
	var month = ufdate.substr(5, 2);
	var day = ufdate.substr(8, 2);
	var hour = ufdate.substr(11, 2);
	var min = ufdate.substr(14, 2);
	return day + "." + month + "." + year + " " + hour + ":" + min + " Uhr";
}

function addRehearsals() {
	for(i in appdata.rehearsals) {
		var r = appdata.rehearsals[i];
		var l = getLocation(r.location);
		var a = getAddress(l.address);
		var out = '<div class="rehearsal_box" id="rehearsal_' + r.id + '">';
		out += '<span class="rehearsal_datum">' + formatDateTime(r.begin) + '</span><br/>'
			+ '<span class="rehearsal_location">im ' + l.name + '</span><br/>'
			+ '<span class="rehearsal_address">' + a.street + ', ' + a.zip + ' ' + a.city + '</span><br/>'
			+ '<span class="rehearsal_notes">' + r.notes + '</span><br/>';

		if(r.participate == "") {
			out += '<a id="btn_accept_' + r.id + '" data-role="button" data-inline="true" data-icon="check" class="rehearsal_button_accept" onClick="rehearsal_participate(' + r.id + ', 1);">Teilnehmen</a>'
				+ '<a id="btn_deny_' + r.id + '" data-role="button" data-inline="true" data-icon="delete" class="rehearsal_button_reject" onClick="rehearsal_participate(' + r.id + ', 0);">Absagen</a>';
		}
		else if(r.participate == "0") {
			// user already chose not to participate
			out += '<span class="rehearsal_participation">Du nimmst nicht an der Probe teil.</span>';
		}
		else if(r.participate == "1") {
			// user already chose to participate
			out += '<span class="rehearsal_participation">Du nimmst an der Probe teil.</span>';
		}
		out += '</div>';
		$('#rehearsal_boxes').append(out);
	}
}

function addConcerts() {
	for(i in appdata.concerts) {
		var c = appdata.concerts[i];
		var l = getLocation(c.location);
		var a = getAddress(l.address);
		var p = getContact(c.contact);
		var out = '<div class="concert_box">'
			+ '<span class="concert_info"><strong>' + formatDateTime(c.begin) + '</strong></span><br/>'
			+ '<span class="concert_info">im ' + l.name + '</span><br/>'
			+ '<span class="concert_info">' + a.street + ', ' + a.zip + ' ' + a.city + '</span><br/>'
			+ '<span class="concert_info">Ansprechpartner ' + p.name + ' ' + p.surname + '</span><br/>'
			+ '<span class="concert_info">' + c.notes + '</span>'
			+ '</div>';
		$('#concert_boxes').append(out);
	}
}

function addContacts() {
	for(i in appdata.contacts) {
		var c = appdata.contacts[i];
		var a = getAddress(c.address);
		var out = '<div class="contact_box">'
			+ ' <span class="contact_info"><strong>' + c.surname + ', ' + c.name + '</strong></span><br/>'
			+ ' <span class="contact_info">Privat: <a href="tel:' + c.phone + '">' + c.phone + '</a></span><br/>'
			+ ' <span class="contact_info">Mobil: <a href="tel:' + c.mobile + '">' + c.mobile + '</a></span><br/>'
			+ ' <span class="contact_info">Geschäftlich: <a href="tel:' + c.business + '">' + c.business + '</a></span><br/>'
			+ ' <span class="contact_info">eMail: ' + c.email + '</span><br/>'
			+ ' <span class="contact_info">Adresse: ' + a.street + ', ' + a.zip + ' ' + a.city + '</span>'
			+ '</div>';
		$('#contact_boxes').append(out);
	}
}

function fillAppWithContent() {
	// add content to pages
	addRehearsals();
	addConcerts();
	addContacts();
}

$(document).ready(function() {
	$('#login_form').submit(function() {
		pin = $('input[name=pin]').val();
		
		jQuery.ajax({
			type : "GET",
			url : srv_url + srv_location,
			data: "pin=" + pin + "&func=getAll",
			dataType: 'json',
			error: function(jqXHR, textStatus, errorThrown) {
				alert(errorThrown);
			},
			success: function(data) {
				appdata = data;
				fillAppWithContent();
				window.location = '#rehearsals';
			}
		});
		
	});
	
	$('#reason_form').submit(function() {
		var reason = $('input[name=reason]').val();
		saveParticipation($('input[name=rid]').val(), 0, reason);
	});
});