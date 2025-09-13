/**
 * DTMF (Dual-Tone Multi-Frequency) Sound Generator
 * Generates authentic phone dial tones using WebAudio API
 */

(function(window) {
	'use strict';
	
	let audioContext = null;
	let isUnlocked = false;
	
	// DTMF frequency mappings
	const DTMF_FREQUENCIES = {
		'1': [697, 1209],
		'2': [697, 1336],
		'3': [697, 1477],
		'4': [770, 1209],
		'5': [770, 1336],
		'6': [770, 1477],
		'7': [852, 1209],
		'8': [852, 1336],
		'9': [852, 1477],
		'*': [941, 1209],
		'0': [941, 1336],
		'#': [941, 1477]
	};
	
	// Initialize audio context
	function initAudioContext() {
		if (!audioContext) {
			const AudioContext = window.AudioContext || window.webkitAudioContext;
			if (AudioContext) {
				audioContext = new AudioContext();
			}
		}
		return audioContext;
	}
	
	// Unlock audio context on first user interaction
	function unlockAudioContext() {
		if (!isUnlocked && audioContext) {
			// Create silent buffer to unlock
			const buffer = audioContext.createBuffer(1, 1, 22050);
			const source = audioContext.createBufferSource();
			source.buffer = buffer;
			source.connect(audioContext.destination);
			source.start(0);
			
			// Resume if suspended
			if (audioContext.state === 'suspended') {
				audioContext.resume();
			}
			
			isUnlocked = true;
		}
	}
	
	// Play DTMF tone
	function playDTMF(key, duration = 120) {
		// Check if sounds are enabled
		if (!window.SDN || !window.SDN.soundEnabled) {
			return;
		}
		
		const ctx = initAudioContext();
		if (!ctx) {
			return;
		}
		
		// Unlock on first play
		if (!isUnlocked) {
			unlockAudioContext();
		}
		
		const frequencies = DTMF_FREQUENCIES[key];
		if (!frequencies) {
			return;
		}
		
		const now = ctx.currentTime;
		const endTime = now + (duration / 1000);
		
		// Create oscillators for dual tones
		const oscillator1 = ctx.createOscillator();
		const oscillator2 = ctx.createOscillator();
		
		// Create gain node for volume control
		const gainNode = ctx.createGain();
		
		// Set frequencies
		oscillator1.frequency.value = frequencies[0];
		oscillator2.frequency.value = frequencies[1];
		
		// Set sine wave type
		oscillator1.type = 'sine';
		oscillator2.type = 'sine';
		
		// Connect oscillators to gain
		oscillator1.connect(gainNode);
		oscillator2.connect(gainNode);
		
		// Connect gain to output
		gainNode.connect(ctx.destination);
		
		// Set volume
		gainNode.gain.value = 0.2;
		
		// Fade in/out to prevent clicks
		gainNode.gain.setValueAtTime(0, now);
		gainNode.gain.linearRampToValueAtTime(0.2, now + 0.01);
		gainNode.gain.setValueAtTime(0.2, endTime - 0.01);
		gainNode.gain.linearRampToValueAtTime(0, endTime);
		
		// Start and stop oscillators
		oscillator1.start(now);
		oscillator2.start(now);
		oscillator1.stop(endTime);
		oscillator2.stop(endTime);
	}
	
	// Play connect sound (chirp)
	function playConnectSound() {
		// Check if sounds are enabled
		if (!window.SDN || !window.SDN.soundEnabled) {
			return;
		}
		
		const ctx = initAudioContext();
		if (!ctx) {
			return;
		}
		
		// Unlock on first play
		if (!isUnlocked) {
			unlockAudioContext();
		}
		
		const now = ctx.currentTime;
		const duration = 0.3;
		
		// Create oscillator
		const oscillator = ctx.createOscillator();
		const gainNode = ctx.createGain();
		
		oscillator.connect(gainNode);
		gainNode.connect(ctx.destination);
		
		// Frequency sweep from 800Hz to 1200Hz
		oscillator.frequency.setValueAtTime(800, now);
		oscillator.frequency.exponentialRampToValueAtTime(1200, now + duration / 2);
		oscillator.frequency.exponentialRampToValueAtTime(1000, now + duration);
		
		// Volume envelope
		gainNode.gain.setValueAtTime(0, now);
		gainNode.gain.linearRampToValueAtTime(0.3, now + 0.05);
		gainNode.gain.exponentialRampToValueAtTime(0.01, now + duration);
		
		// Start and stop
		oscillator.start(now);
		oscillator.stop(now + duration);
	}
	
	// Play error sound
	function playErrorSound() {
		// Check if sounds are enabled
		if (!window.SDN || !window.SDN.soundEnabled) {
			return;
		}
		
		const ctx = initAudioContext();
		if (!ctx) {
			return;
		}
		
		// Unlock on first play
		if (!isUnlocked) {
			unlockAudioContext();
		}
		
		const now = ctx.currentTime;
		
		// Create two beeps
		for (let i = 0; i < 2; i++) {
			const startTime = now + (i * 0.15);
			const oscillator = ctx.createOscillator();
			const gainNode = ctx.createGain();
			
			oscillator.connect(gainNode);
			gainNode.connect(ctx.destination);
			
			oscillator.frequency.value = 400;
			oscillator.type = 'square';
			
			gainNode.gain.value = 0.1;
			
			oscillator.start(startTime);
			oscillator.stop(startTime + 0.1);
		}
	}
	
	// Initialize on first user interaction
	if (typeof document !== 'undefined') {
		const initEvents = ['click', 'touchstart', 'keydown'];
		
		function handleFirstInteraction() {
			initAudioContext();
			unlockAudioContext();
			
			// Remove listeners after first interaction
			initEvents.forEach(event => {
				document.removeEventListener(event, handleFirstInteraction);
			});
		}
		
		initEvents.forEach(event => {
			document.addEventListener(event, handleFirstInteraction, { once: true });
		});
	}
	
	// Export functions
	window.playDTMF = playDTMF;
	window.playConnectSound = playConnectSound;
	window.playErrorSound = playErrorSound;
	
})(window);