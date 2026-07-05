/**
 * Shared sound + haptics for alerts (kitchen chime, waiter/customer "ready").
 *
 * Mobile browsers block audio until a user gesture, so we keep ONE AudioContext
 * and unlock it on the first tap anywhere. Vibration works on Android; iOS
 * Safari doesn't support it and we simply no-op there.
 *
 * Exposed as window.mtAlerts so any module can use it without import ordering.
 */
let ctx = null;
let unlocked = false;

function getCtx() {
    if (!ctx) {
        try {
            ctx = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            ctx = null;
        }
    }
    return ctx;
}

function unlock() {
    const c = getCtx();
    if (!c) return;
    if (c.state === 'suspended' && c.resume) {
        c.resume();
    }
    // A one-sample silent buffer fully unlocks audio on iOS Safari.
    try {
        const b = c.createBuffer(1, 1, 22050);
        const s = c.createBufferSource();
        s.buffer = b;
        s.connect(c.destination);
        s.start(0);
    } catch (e) {
        /* ignore */
    }
    unlocked = true;
    document.dispatchEvent(new CustomEvent('mt-audio-unlocked'));
}

function isUnlocked() {
    return unlocked;
}

/**
 * Play a short chime. kind: 'order' (3 notes, new ticket) | 'ready' (2 notes)
 * | 'ping' (1 note).
 */
function chime(kind) {
    const c = getCtx();
    if (!c) return;
    if (c.state === 'suspended' && c.resume) {
        c.resume();
    }
    const notes = kind === 'order' ? [660, 880, 1175]
        : kind === 'ready' ? [880, 1175]
        : [880];
    notes.forEach(function (freq, i) {
        const osc = c.createOscillator();
        const gain = c.createGain();
        osc.type = 'sine';
        osc.frequency.value = freq;
        osc.connect(gain);
        gain.connect(c.destination);
        const t = c.currentTime + i * 0.16;
        gain.gain.setValueAtTime(0.0001, t);
        gain.gain.exponentialRampToValueAtTime(0.35, t + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
        osc.start(t);
        osc.stop(t + 0.36);
    });
}

function buzz(pattern) {
    if (navigator.vibrate) {
        try {
            navigator.vibrate(pattern || [200, 100, 200]);
        } catch (e) {
            /* ignore */
        }
    }
}

function alertUser(kind, pattern) {
    chime(kind);
    buzz(pattern);
}

// Arm auto-unlock on the first user gesture anywhere on the page.
(function armUnlock() {
    const handler = function () {
        unlock();
        ['pointerdown', 'touchstart', 'keydown', 'click'].forEach(function (ev) {
            window.removeEventListener(ev, handler);
        });
    };
    ['pointerdown', 'touchstart', 'keydown', 'click'].forEach(function (ev) {
        window.addEventListener(ev, handler, { passive: true });
    });
})();

window.mtAlerts = { unlock, isUnlocked, chime, buzz, alertUser };

export { unlock, isUnlocked, chime, buzz, alertUser };
