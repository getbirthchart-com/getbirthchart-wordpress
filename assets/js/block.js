(function (blocks, element, blockEditor, components, i18n) {
	"use strict";

	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var __ = i18n.__;

	registerBlockType("getbirthchart/calculator", {
		apiVersion: 3,
		title: __("GetBirthChart Calculator", "getbirthchart"),
		icon: "star-filled",
		category: "widgets",
		attributes: {
			type: {
				type: "string",
				default: "birth-chart",
			},
		},
		edit: function (props) {
			var blockProps = useBlockProps({ className: "getbirthchart-block-editor" });
			var options = [
				{ label: __("Birth Chart", "getbirthchart"), value: "birth-chart" },
				{ label: __("Moon Sign", "getbirthchart"), value: "moon-sign" },
				{ label: __("Rising Sign", "getbirthchart"), value: "rising-sign" },
				{ label: __("Big Three", "getbirthchart"), value: "big-three" },
			];
			var selected = options.filter(function (item) {
				return item.value === props.attributes.type;
			})[0];
			return el(
				"div",
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __("Calculator", "getbirthchart") },
						el(SelectControl, {
							label: __("Calculator type", "getbirthchart"),
							value: props.attributes.type,
							options: options,
							onChange: function (value) {
								props.setAttributes({ type: value });
							},
						})
					)
				),
				el(
					"p",
					{},
					__("GetBirthChart Calculator", "getbirthchart") +
						": " +
						(selected ? selected.label : props.attributes.type)
				)
			);
		},
		save: function () {
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
