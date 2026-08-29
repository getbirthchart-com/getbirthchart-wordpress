(function () {
	"use strict";

	function text(value) {
		return document.createTextNode(value == null ? "" : String(value));
	}

	function el(tag, className, children) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		(children || []).forEach(function (child) {
			if (typeof child === "string") {
				node.appendChild(text(child));
			} else if (child) {
				node.appendChild(child);
			}
		});
		return node;
	}

	function formatDegree(degree) {
		if (typeof degree !== "number" || !isFinite(degree)) {
			return "";
		}
		return " " + degree.toFixed(2).replace(/\.?0+$/, "") + "°";
	}

	function placementLine(label, item) {
		if (!item || !item.sign) {
			return null;
		}
		return el("li", "", [label + ": " + item.sign + formatDegree(item.degree)]);
	}

	function renderResult(container, result, i18n) {
		container.replaceChildren();
		if (!result) {
			return;
		}

		var list = el("ul", "getbirthchart-result-list");
		var sun = placementLine(i18n.sun, result.sun);
		var moonItem = result.moon;
		var rising = placementLine(i18n.rising, result.rising);

		if (sun) {
			list.appendChild(sun);
		}
		if (moonItem) {
			if (moonItem.uncertain && !moonItem.sign) {
				var moonText = i18n.moon + ": " + (moonItem.note || i18n.checkBirth);
				if (moonItem.possible_signs && moonItem.possible_signs.length) {
					moonText += " (" + moonItem.possible_signs.join(", ") + ")";
				}
				list.appendChild(el("li", "", [moonText]));
			} else if (moonItem.sign) {
				var moonLine = i18n.moon + ": " + moonItem.sign;
				if (!moonItem.uncertain && typeof moonItem.degree === "number") {
					moonLine += formatDegree(moonItem.degree);
				}
				list.appendChild(el("li", "", [moonLine]));
				if (moonItem.note) {
					list.appendChild(el("li", "getbirthchart-note", [moonItem.note]));
				}
			}
		}
		if (rising) {
			list.appendChild(rising);
		}

		if (list.childNodes.length) {
			container.appendChild(list);
		}

		if (Array.isArray(result.planets) && result.planets.length) {
			container.appendChild(el("p", "getbirthchart-result-title", [i18n.planets]));
			var planets = el("ul", "getbirthchart-planets");
			result.planets.forEach(function (planet) {
				if (!planet || !planet.name || !planet.sign) {
					return;
				}
				planets.appendChild(
					el("li", "", [planet.name + ": " + planet.sign + formatDegree(planet.degree)])
				);
			});
			container.appendChild(planets);
		}

		if (result.birth_time_known === false) {
			container.appendChild(el("p", "getbirthchart-note", [i18n.unknownTimeNote]));
		}
	}

	function bindCalculator(root, config) {
		var form = root.querySelector("form");
		var timeInput = root.querySelector('input[name="time"]');
		var unknownInput = root.querySelector('input[name="unknown_time"]');
		var submit = root.querySelector(".getbirthchart-submit");
		var errorNode = root.querySelector(".getbirthchart-error");
		var resultNode = root.querySelector(".getbirthchart-result");
		var requiresTime = root.getAttribute("data-requires-time") === "1";
		var type = root.getAttribute("data-type") || "birth-chart";
		var i18n = config.i18n || {};

		function setError(message) {
			if (!errorNode) {
				return;
			}
			errorNode.replaceChildren(text(message));
			errorNode.hidden = !message;
		}

		function setLoading(isLoading) {
			if (!submit) {
				return;
			}
			submit.disabled = isLoading;
			submit.replaceChildren(text(isLoading ? i18n.calculating : i18n.calculate));
		}

		if (unknownInput && timeInput && !requiresTime) {
			unknownInput.addEventListener("change", function () {
				timeInput.disabled = unknownInput.checked;
				if (unknownInput.checked) {
					timeInput.value = "";
					timeInput.removeAttribute("required");
					timeInput.setAttribute("aria-required", "false");
				} else {
					timeInput.setAttribute("aria-required", "false");
				}
			});
		}

		if (!form) {
			return;
		}

		form.addEventListener("submit", function (event) {
			event.preventDefault();
			setError("");
			if (resultNode) {
				resultNode.replaceChildren();
			}

			var unknownTime = !!(unknownInput && unknownInput.checked);
			if (requiresTime && (unknownTime || !timeInput || !timeInput.value)) {
				setError(i18n.risingRequiresTime);
				return;
			}

			var dateInput = form.querySelector('input[name="date"]');
			var placeInput = form.querySelector('input[name="place"]');
			var payload = {
				type: type,
				date: dateInput ? dateInput.value : "",
				place: placeInput ? placeInput.value : "",
				unknown_time: unknownTime,
			};
			if (!unknownTime && timeInput && timeInput.value) {
				payload.time = timeInput.value;
			}

			setLoading(true);
			fetch(config.restUrl, {
				method: "POST",
				credentials: "same-origin",
				headers: {
					"Content-Type": "application/json",
					Accept: "application/json",
					"X-WP-Nonce": config.nonce || "",
				},
				body: JSON.stringify(payload),
			})
				.then(function (response) {
					return response.json().then(function (body) {
						return { ok: response.ok, body: body };
					});
				})
				.then(function (payloadResponse) {
					var body = payloadResponse.body || {};
					if (!payloadResponse.ok || !body.ok) {
						var message =
							body.error && body.error.message
								? body.error.message
								: i18n.unable;
						setError(message);
						return;
					}
					renderResult(resultNode, body.result, i18n);
				})
				.catch(function () {
					setError(i18n.unable);
				})
				.then(function () {
					setLoading(false);
				});
		});
	}

	function init() {
		var config = window.getbirthchartFrontend;
		if (!config || !config.restUrl) {
			return;
		}
		document.querySelectorAll("[data-getbirthchart-calculator]").forEach(function (root) {
			bindCalculator(root, config);
		});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
