(function () {
	"use strict";

	function init() {
		var button = document.getElementById("getbirthchart-test-connection");
		var result = document.getElementById("getbirthchart-test-result");
		var config = window.getbirthchartAdmin;
		if (!button || !result || !config) {
			return;
		}

		button.addEventListener("click", function () {
			button.disabled = true;
			result.replaceChildren(document.createTextNode(config.i18n.testing));
			var body = new URLSearchParams();
			body.set("action", config.action);
			body.set("nonce", config.nonce);
			fetch(config.ajaxUrl, {
				method: "POST",
				credentials: "same-origin",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
				},
				body: body.toString(),
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					var message =
						payload && payload.data && payload.data.message
							? payload.data.message
							: config.i18n.test;
					if (payload && payload.data && payload.data.request_id) {
						message += " (" + payload.data.request_id + ")";
					}
					result.replaceChildren(document.createTextNode(message));
				})
				.catch(function () {
					result.replaceChildren(document.createTextNode(config.i18n.test));
				})
				.then(function () {
					button.disabled = false;
					button.replaceChildren(document.createTextNode(config.i18n.test));
				});
		});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
