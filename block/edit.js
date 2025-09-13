/**
 * Speed Dial Block Editor Script
 */

(function(blocks, element, blockEditor, components, i18n) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	
	// Register block
	blocks.registerBlockType('speed-dial/phone', {
		title: __('Speed Dial', 'speed-dial'),
		icon: 'phone',
		category: 'widgets',
		attributes: {
			digits: {
				type: 'string',
				default: ''
			},
			auto_focus: {
				type: 'boolean',
				default: true
			}
		},
		
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			
			return [
				// Inspector Controls
				el(InspectorControls, {},
					el(PanelBody, {
						title: __('Speed Dial Settings', 'speed-dial'),
						initialOpen: true
					},
						el(TextControl, {
							label: __('Pre-filled Digits', 'speed-dial'),
							value: attributes.digits,
							onChange: function(value) {
								// Only allow digits
								value = value.replace(/\D/g, '');
								setAttributes({ digits: value });
							},
							help: __('Enter digits to pre-fill in the dialer', 'speed-dial')
						}),
						el(ToggleControl, {
							label: __('Auto Focus', 'speed-dial'),
							checked: attributes.auto_focus,
							onChange: function(value) {
								setAttributes({ auto_focus: value });
							},
							help: __('Automatically focus the dialer when page loads', 'speed-dial')
						})
					)
				),
				
				// Block Preview
				el('div', { className: props.className },
					el('div', { 
						style: { 
							padding: '20px',
							background: '#f0f0f1',
							borderRadius: '4px',
							textAlign: 'center'
						}
					},
						el('div', {
							style: {
								fontSize: '48px',
								marginBottom: '10px'
							}
						}, '📱'),
						el('h3', {}, __('Speed Dial Phone', 'speed-dial')),
						el('p', {}, __('Nokia-style dialer will appear here on the frontend', 'speed-dial')),
						attributes.digits && el('p', {
							style: {
								fontFamily: 'monospace',
								fontSize: '18px',
								marginTop: '10px'
							}
						}, __('Pre-filled: ', 'speed-dial') + attributes.digits)
					)
				)
			];
		},
		
		save: function() {
			// Server-side render
			return null;
		}
	});
	
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);