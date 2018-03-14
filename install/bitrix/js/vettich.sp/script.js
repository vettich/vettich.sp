if(typeof Vettich == 'undefined') {
	Vettich = {};
}
// SP is SocialPosting
Vettich.SP = {};

Vettich.SP.OpenWin = null;
Vettich.SP.Open = function(url) {
	if(Vettich.SP.OpenWin) {
		Vettich.SP.OpenWin.close();
	}
	Vettich.SP.OpenWin = window.open(url, 'VettichSP');
}

Vettich.SP.AjaxMethod = function (method, params, callback) {
	// console.log(method, params, callback);
	params = params || "";
	if(params.length) {
		params = "&" + params;
	}
	// console.log('AjaxMethod', method, params);
	$.get('/bitrix/tools/vettich.sp/ajax.php?method=' + method + params, callback);
}

Vettich.SP.MenuSend = function(squery) {
	var show = BX.showWait('adm-workarea');
	Vettich.SP.AjaxMethod('publishIblockElem', squery, function(data) {
		BX.closeWait('adm-workarea', show);
		try {
			data = JSON.parse(data);
		} catch (err) {
			// console.log(err);
		}
		new BX.CDialog({
			title: data.title || 'Result',
			// head: 'Head',
			content: data.content || 'Success',
			width: data.width || 500,
			height: data.height || 200,
			buttons: [BX.CDialog.prototype.btnClose]
		}).Show();
	});
}

Vettich.SP.MenuSendIndividual = function(squery) {
	Vettich.SP.Open('/bitrix/admin/vettich.sp.posts_popup.php?'+squery);
}

Vettich.SP.MenuGroupSend = function(squery) {
	var show = BX.showWait('adm-workarea');
	var elems = [];
	var sections = [];
	$('.adm-list-table-checkbox input:checked').each(function(i, val) {
		var name = $(val).attr('name');
		var value = $(val).val();
		if(name && name.length && value.length > 1) {
			if(value[0] == 'E') {
				elems.push(value.substr(1));
			} else if(value[0] == 'S') {
				sections.push(value.substr(1));
			} else {
				elems.push(value);
			}
		}
	});
	if(elems.length) {
		if(squery.length) {
			squery += '&';
		}
		squery += 'ELEMS=' + elems.join(',');
	}
	if(sections.length) {
		if(squery.length) {
			squery += '&';
		}
		squery += 'SECTIONS=' + sections.join(',');
	}
	Vettich.SP.AjaxMethod('publishIblockElems', squery, function(data) {
		BX.closeWait('adm-workarea', show);
		try {
			data = JSON.parse(data);
		} catch (err) {
			// console.log(err);
		}
		new BX.CDialog({
			title: data.title || 'Result',
			// head: 'Head',
			content: data.content || 'Success',
			width: data.width || 500,
			height: data.height || 200,
			buttons: [BX.CDialog.prototype.btnClose]
		}).Show();
	});
}

Vettich.SP.PublishFromQueue = function(id) {
	var show = BX.showWait('adm-workarea');
	Vettich.SP.AjaxMethod('publishFromQueue', 'id=' + id, function(data) {
		if(typeof sTableID != 'undefined'
			&& !$('#form_sTableID .adm-list-table-cell input[type=text]').length) {
			sTableID.GetAdminList('/bitrix/admin/vettich.sp.queue.php', function() {
				BX.closeWait('adm-workarea', show);
			})
		} else {
			BX.closeWait('adm-workarea', show);
		}
	});
}

Vettich.SP.RemoveSocial = function(queueId, accId) {
	var show = BX.showWait('adm-workarea');
	Vettich.SP.AjaxMethod(
		'removeSocial',
		'queueId=' + queueId + '&accId=' + accId,
		function() {
			BX.closeWait('adm-workarea', show);
			Vettich.Devform.Refresh();
		}
	);
}

Vettich.SP.getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

Vettich.SP.UpdateNextPublishAtLock = false;
Vettich.SP.UpdateNextPublishAt = function(selector) {
	selector = selector || '#nextPublishDatetime';
	if(Vettich.SP.UpdateNextPublishAtLock) {
		return;
	}
	Vettich.SP.UpdateNextPublishAtLock = true;
	var last = $(selector).text();
	$(selector).text('Please, wait...');
	var postID = Vettich.SP.getUrlParameter('ID');
	Vettich.SP.AjaxMethod('updateNextPublishAt', 'postID=' + postID, function(data) {
		try {
			data = JSON.parse(data);
		} catch (err) {
			// console.log(err);
		}
		if(data && data.success) {
			$(selector).text(data.datetime);
			Vettich.SP.UpdateNextPublishAtLock = false;
		} else {
			$(selector).text('Error updating!');
			setTimeout(function() {
				$(selector).text(last);
				Vettich.SP.UpdateNextPublishAtLock = false;
			})
		}
	});
}
