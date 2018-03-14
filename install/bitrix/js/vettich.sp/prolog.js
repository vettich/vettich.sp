BX.ready(function() {
	var intervaRes;

	function _setInterval() {
		if(!intervaRes) {
			intervaRes = setInterval(_publish, 30000);
		}
	}

	function _clearInterval() {
		clearInterval(intervaRes);
	}

	function _publish() {
		if(window.location.pathname == '/bitrix/admin/vettich.sp.queue.php') {
			var show = BX.showWait('adm-workarea');
		}
		jQuery.get('/bitrix/tools/vettich.sp/ajax.php?method=publishFromHit', function(data) {
			console.log(data);
			var jsonData = JSON.parse(data);
			if(jsonData) {
				if(jsonData.interval) {
					_setInterval();
				} else {
					_clearInterval();
				}
			}
		}).always(function() {
			if(window.location.pathname == '/bitrix/admin/vettich.sp.queue.php') {
				if(typeof sTableID != 'undefined'
					&& !$('#form_sTableID .adm-list-table-cell input[type=text]').length) {
					sTableID.GetAdminList('/bitrix/admin/vettich.sp.queue.php', function() {
						BX.closeWait('adm-workarea', show);
					})
				} else {
					BX.closeWait('adm-workarea', show);
				}
			}
		});
	}

	_publish();
})