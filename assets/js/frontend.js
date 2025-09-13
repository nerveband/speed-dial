/**
 * Speed Dial Frontend JavaScript
 * Handles phone dialer interactions
 */

(function() {
	'use strict';

	// Wait for DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSpeedDial);
	} else {
		initSpeedDial();
	}

	function initSpeedDial() {
		// Find all Speed Dial instances
		const phones = document.querySelectorAll('.sd-phone');

		phones.forEach(phone => {
			new SpeedDialPhone(phone);
		});
	}

	// Speed Dial Phone Class
	class SpeedDialPhone {
		constructor(element) {
			this.element = element;
			this.digits = [];
			this.maxDigits = SDN.maxDigits || 16;

			// Get elements
			this.readout = element.querySelector('.sd-readout');
			this.clearBtn = element.querySelector('.sd-clear');
			this.callBtn = element.querySelector('.sd-call');
			this.keys = element.querySelectorAll('.sd-key');
			this.hiddenInput = element.querySelector('.sd-hidden-input');
			this.overlay = element.querySelector('.sd-overlay');
			this.result = element.querySelector('.sd-result');
			this.screen = element.querySelector('.sd-screen');

			// Initialize
			this.init();
		}

		init() {
			// Pre-fill digits if provided
			const presetDigits = this.element.dataset.digits;
			if (presetDigits) {
				this.digits = presetDigits.split('');
				this.updateDisplay();
			}

			// Bind events
			this.bindEvents();

			// Auto focus if requested
			if (this.element.dataset.autoFocus === 'true') {
				this.hiddenInput.focus();
			}
		}

		bindEvents() {
			// Key buttons
			this.keys.forEach(key => {
				key.addEventListener('click', () => {
					const digit = key.dataset.key;
					this.addDigit(digit);
				});
			});

			// Clear button
			this.clearBtn.addEventListener('click', () => {
				this.clear();
			});

			// Long press clear
			let clearTimer;
			this.clearBtn.addEventListener('mousedown', () => {
				clearTimer = setTimeout(() => {
					this.clearAll();
				}, 500);
			});

			this.clearBtn.addEventListener('mouseup', () => {
				clearTimeout(clearTimer);
			});

			this.clearBtn.addEventListener('mouseleave', () => {
				clearTimeout(clearTimer);
			});

			// Call button
			this.callBtn.addEventListener('click', () => {
				this.call();
			});

			// Keyboard input
			document.addEventListener('keydown', (e) => {
				// Only handle if this phone has focus
				if (!this.element.contains(document.activeElement)) {
					return;
				}

				if (e.key >= '0' && e.key <= '9') {
					e.preventDefault();
					this.addDigit(e.key);
				} else if (e.key === 'Backspace') {
					e.preventDefault();
					this.clear();
				} else if (e.key === 'Enter') {
					e.preventDefault();
					this.call();
				} else if (e.key === 'Escape') {
					e.preventDefault();
					this.clearAll();
				}
			});

			// Hidden input for mobile
			this.hiddenInput.addEventListener('input', (e) => {
				const value = e.target.value.replace(/\D/g, '');
				this.digits = value.split('');
				this.updateDisplay();
			});
		}

		addDigit(digit) {
			if (this.digits.length >= this.maxDigits) {
				return;
			}

			this.digits.push(digit);
			this.updateDisplay();

			// Play sound if enabled
			if (SDN.soundEnabled && typeof playDTMF === 'function') {
				playDTMF(digit);
			}

			// Vibrate if enabled
			if (SDN.vibrationEnabled && navigator.vibrate) {
				navigator.vibrate(20);
			}
		}

		clear() {
			if (this.digits.length > 0) {
				this.digits.pop();
				this.updateDisplay();
			}
		}

		clearAll() {
			this.digits = [];
			this.updateDisplay();
			this.hideOverlay();
			this.hideResult();
		}

		updateDisplay() {
			const display = this.digits.join('') || '<span class="sd-cursor">_</span>';
			this.readout.innerHTML = display;
			this.hiddenInput.value = this.digits.join('');
		}

		async call() {
			if (this.digits.length === 0) {
				// Flash readout
				this.readout.style.opacity = '0';
				setTimeout(() => {
					this.readout.style.opacity = '1';
				}, 200);
				return;
			}

			const number = this.digits.join('');

			// Show connecting overlay
			this.showOverlay(SDN.connectText);

			// Play connect sound if available
			if (SDN.soundEnabled && typeof playConnectSound === 'function') {
				playConnectSound();
			}

			try {
				// Try REST API first
				const response = await this.lookupNumber(number);

				if (response.found) {
					this.showResult(response);
				} else {
					this.showNotFound();
				}
			} catch (error) {
				console.error('Speed Dial lookup error:', error);
				this.showError();
			}
		}

		async lookupNumber(number) {
			// Try REST API
			if (SDN.restUrl) {
				try {
					const response = await fetch(SDN.restUrl + 'lookup?number=' + encodeURIComponent(number), {
						method: 'GET',
						headers: {
							'X-WP-Nonce': SDN.restNonce
						}
					});

					if (response.ok) {
						return await response.json();
					}
				} catch (e) {
					// Fall back to AJAX
				}
			}

			// AJAX fallback
			return new Promise((resolve, reject) => {
				const xhr = new XMLHttpRequest();
				xhr.open('GET', SDN.ajaxUrl + '?action=sd_lookup&number=' + encodeURIComponent(number) + '&nonce=' + SDN.nonce);

				xhr.onload = function() {
					if (xhr.status === 200) {
						try {
							const data = JSON.parse(xhr.responseText);
							if (data.success) {
								resolve(data.data);
							} else {
								resolve({ found: false });
							}
						} catch (e) {
							reject(e);
						}
					} else {
						reject(new Error('Request failed'));
					}
				};

				xhr.onerror = function() {
					reject(new Error('Network error'));
				};

				xhr.send();
			});
		}

		showOverlay(message) {
			const messageEl = this.overlay.querySelector('.sd-message');
			if (messageEl) {
				messageEl.textContent = message;
			}
			this.overlay.style.display = 'flex';
		}

		hideOverlay() {
			this.overlay.style.display = 'none';
		}

		showResult(data) {
			this.hideOverlay();

			// Update result elements
			const titleEl = this.result.querySelector('.sd-result-title');
			const urlEl = this.result.querySelector('.sd-result-url');
			const visitBtn = this.result.querySelector('.sd-result-visit');

			if (titleEl) titleEl.textContent = data.title;
			if (urlEl) {
				urlEl.href = data.url;
				urlEl.textContent = data.url;
			}

			// Show result
			this.result.style.display = 'block';
			this.readout.style.display = 'none';

			// Handle visit button
			if (visitBtn) {
				visitBtn.onclick = () => {
					window.open(data.url, '_blank');
				};
			}

			// Auto redirect if enabled
			if (SDN.autoRedirect) {
				setTimeout(() => {
					this.showOverlay(SDN.i18n.redirecting || 'Redirecting...');
					window.location.href = data.url;
				}, SDN.redirectDelay || 1200);
			}
		}

		showNotFound() {
			this.hideOverlay();

			// Show not found message
			const messageEl = document.createElement('div');
			messageEl.className = 'sd-not-found';
			messageEl.textContent = SDN.notFoundText;
			messageEl.style.padding = '1rem';
			messageEl.style.textAlign = 'center';
			messageEl.style.color = '#666';

			this.screen.appendChild(messageEl);

			// Remove after delay
			setTimeout(() => {
				messageEl.remove();
				this.clearAll();
			}, 2000);
		}

		showError() {
			this.hideOverlay();

			// Show error message
			const messageEl = document.createElement('div');
			messageEl.className = 'sd-error';
			messageEl.textContent = SDN.i18n.error || 'An error occurred';
			messageEl.style.padding = '1rem';
			messageEl.style.textAlign = 'center';
			messageEl.style.color = '#d00';

			this.screen.appendChild(messageEl);

			// Remove after delay
			setTimeout(() => {
				messageEl.remove();
			}, 3000);
		}

		hideResult() {
			this.result.style.display = 'none';
			this.readout.style.display = 'block';
		}
	}

	// Expose to global scope if needed
	window.SpeedDialPhone = SpeedDialPhone;
})();
