/**
 * KAISAN Web Audio API Sound Synthesizer
 *
 * Produces clean, harmonic micro-sound effects using pure mathematical waveforms
 * (0 KB download, 0 ms latency, no external audio files required).
 */
class SoundEngine {
    constructor() {
        this.ctx = null;
        this.storageKey = 'kaisan_sfx_muted';
        this.muted = this.readMutedState();
        this.unlocked = false;

        this.initAutoUnlock();
    }

    readMutedState() {
        if (typeof window === 'undefined' || !window.localStorage) {
            return false;
        }
        return window.localStorage.getItem(this.storageKey) === 'true';
    }

    getAudioContext() {
        if (typeof window === 'undefined') {
            return null;
        }

        if (!this.ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                this.ctx = new AudioCtx();
            }
        }

        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume().catch(() => {});
        }

        return this.ctx;
    }

    initAutoUnlock() {
        if (typeof window === 'undefined') {
            return;
        }

        const unlock = () => {
            const ctx = this.getAudioContext();
            if (ctx && ctx.state === 'running') {
                this.unlocked = true;
                ['touchstart', 'touchend', 'mousedown', 'keydown'].forEach((evt) => {
                    document.removeEventListener(evt, unlock, true);
                });
            }
        };

        ['touchstart', 'touchend', 'mousedown', 'keydown'].forEach((evt) => {
            document.addEventListener(evt, unlock, true);
        });
    }

    isMuted() {
        return this.muted;
    }

    setMuted(value) {
        this.muted = Boolean(value);
        if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem(this.storageKey, this.muted ? 'true' : 'false');
            window.dispatchEvent(new CustomEvent('kaisan:sound-muted-changed', {
                detail: { muted: this.muted },
            }));
        }
    }

    toggleMute() {
        this.setMuted(!this.muted);
        if (!this.muted) {
            this.playTap();
        }
        return this.muted;
    }

    /**
     * Helper to schedule an oscillator tone
     */
    playTone({ freq, type = 'sine', duration = 0.1, startTime = 0, gain = 0.1, decay = true }) {
        if (this.muted) {
            return;
        }

        try {
            const ctx = this.getAudioContext();
            if (!ctx) {
                return;
            }

            const now = ctx.currentTime + startTime;
            const osc = ctx.createOscillator();
            const gainNode = ctx.createGain();

            osc.type = type;
            osc.frequency.setValueAtTime(freq, now);

            gainNode.gain.setValueAtTime(gain, now);
            if (decay) {
                gainNode.gain.exponentialRampToValueAtTime(0.0001, now + duration);
            }

            osc.connect(gainNode);
            gainNode.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + duration);
        } catch {
            // Audio failures should never block UI
        }
    }

    /**
     * Subtle micro-pop for UI button / radio taps
     */
    playTap() {
        if (this.muted) {
            return;
        }

        try {
            const ctx = this.getAudioContext();
            if (!ctx) {
                return;
            }

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gainNode = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(600, now);
            osc.frequency.exponentialRampToValueAtTime(120, now + 0.025);

            gainNode.gain.setValueAtTime(0.04, now);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, now + 0.025);

            osc.connect(gainNode);
            gainNode.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.025);
        } catch {}
    }

    /**
     * Ascending harmonic chime for correct answers (C5 -> E5 -> G5)
     */
    playCorrect() {
        if (this.muted) {
            return;
        }

        const notes = [
            { freq: 523.25, time: 0, dur: 0.12, gain: 0.12 }, // C5
            { freq: 659.25, time: 0.07, dur: 0.14, gain: 0.14 }, // E5
            { freq: 783.99, time: 0.14, dur: 0.22, gain: 0.16 }, // G5
        ];

        notes.forEach((n) => {
            this.playTone({
                freq: n.freq,
                type: 'sine',
                startTime: n.time,
                duration: n.dur,
                gain: n.gain,
            });
        });
    }

    /**
     * Gentle soft thud for incorrect answers (F3 -> D3)
     */
    playWrong() {
        if (this.muted) {
            return;
        }

        const notes = [
            { freq: 174.61, time: 0, dur: 0.12, gain: 0.09, type: 'triangle' }, // F3
            { freq: 146.83, time: 0.08, dur: 0.16, gain: 0.07, type: 'sine' }, // D3
        ];

        notes.forEach((n) => {
            this.playTone({
                freq: n.freq,
                type: n.type,
                startTime: n.time,
                duration: n.dur,
                gain: n.gain,
            });
        });
    }

    /**
     * Triumphant level-up fanfare arpeggio (G4 -> C5 -> E5 -> G5 -> C6)
     */
    playLevelUp() {
        if (this.muted) {
            return;
        }

        const notes = [
            { freq: 392.00, time: 0, dur: 0.12, gain: 0.10 }, // G4
            { freq: 523.25, time: 0.08, dur: 0.12, gain: 0.12 }, // C5
            { freq: 659.25, time: 0.16, dur: 0.14, gain: 0.14 }, // E5
            { freq: 783.99, time: 0.24, dur: 0.18, gain: 0.16 }, // G5
            { freq: 1046.50, time: 0.34, dur: 0.35, gain: 0.18 }, // C6
        ];

        notes.forEach((n) => {
            this.playTone({
                freq: n.freq,
                type: 'sine',
                startTime: n.time,
                duration: n.dur,
                gain: n.gain,
            });
        });
    }

    /**
     * Harmonious rich chord upon submitting an exam (C major triad)
     */
    playExamSubmit() {
        if (this.muted) {
            return;
        }

        const notes = [261.63, 329.63, 392.00, 523.25]; // C4, E4, G4, C5
        notes.forEach((freq) => {
            this.playTone({
                freq,
                type: 'sine',
                startTime: 0,
                duration: 0.65,
                gain: 0.07,
            });
        });
    }

    /**
     * Subtle reminder beep when countdown timer has 1 minute remaining
     */
    playTimerWarning() {
        if (this.muted) {
            return;
        }

        this.playTone({ freq: 740, type: 'sine', startTime: 0, duration: 0.05, gain: 0.05 });
        this.playTone({ freq: 740, type: 'sine', startTime: 0.09, duration: 0.06, gain: 0.06 });
    }
}

export const soundEngine = new SoundEngine();

if (typeof window !== 'undefined') {
    window.soundEngine = soundEngine;
}
