/* ============================================================================
 * Romoly — Guided Tour / User Guide engine (Driver.js)
 * ----------------------------------------------------------------------------
 * Membaca konfigurasi dari <body data-tour-*> + window.TOUR_I18N (dari lang/tour.php).
 * - Langkah didefinisikan di lang/{id,en}/tour.php (sumber tunggal).
 * - Auto-run di halaman yang belum pernah dilihat; tidak mengulang.
 * - Filter langkah: RBAC (roles) + elemen yang tidak ada/ tersembunyi dibuang.
 * - Progress disimpan ke DB via /api/tour/* (CSRF).
 * - Tombol "Panduan": window.RomolyTour.replay()  |  Reset: window.RomolyTour.reset()
 * ========================================================================== */
(function () {
    'use strict';

    function getDriver() {
        return (window.driver && window.driver.js && window.driver.js.driver)
            ? window.driver.js.driver
            : null;
    }

    function safeParse(str, fallback) {
        try { return str ? JSON.parse(str) : fallback; } catch (e) { return fallback; }
    }

    var body         = document.body;
    var ROUTE        = body.getAttribute('data-tour-route') || '';
    var SEEN         = safeParse(body.getAttribute('data-tour-seen'), []);
    var WELCOME_DONE = safeParse(body.getAttribute('data-tour-welcome'), false);
    var ROLES        = safeParse(body.getAttribute('data-user-roles'), []);
    var I18N         = window.TOUR_I18N || {};
    var COMMON       = I18N.common || {};

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function jsonHeaders() {
        return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() };
    }
    function reduceMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }
    function isSuperadmin() { return ROLES.indexOf('superadmin') !== -1; }

    function isVisible(el) {
        if (!el) return false;
        return el.offsetParent !== null || el.getClientRects().length > 0;
    }

    function roleOk(step) {
        if (!step.roles || !step.roles.length) return true;
        if (isSuperadmin()) return true;
        for (var i = 0; i < step.roles.length; i++) {
            if (ROLES.indexOf(step.roles[i]) !== -1) return true;
        }
        return false;
    }

    // Bangun langkah Driver.js dari definisi i18n; buang yang role-nya tak cocok / elemen tak ada.
    function buildSteps(key) {
        var raw = I18N[key] || [];
        var out = [];
        for (var i = 0; i < raw.length; i++) {
            var s = raw[i];
            if (!roleOk(s)) continue;
            var el = document.querySelector('[data-tour="' + s.tour + '"]');
            if (!isVisible(el)) continue;
            out.push({ element: el, popover: { title: s.title || '', description: s.body || '' } });
        }
        return out;
    }

    function markSeen(key) {
        fetch('/api/tour/seen', { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ key: key }) })
            .catch(function () {});
    }
    function markWelcome() {
        fetch('/api/tour/welcome', { method: 'POST', headers: jsonHeaders() }).catch(function () {});
    }

    function modalOpen() { return !!document.querySelector('.modal.show'); }

    /**
     * Jalankan tour untuk sebuah key.
     * opts: { markType: 'welcome'|'seen'|null, onDone: fn }
     * return true bila tour benar-benar dijalankan.
     */
    function startTour(key, opts) {
        opts = opts || {};
        var Driver = getDriver();
        var steps = Driver ? buildSteps(key) : [];
        if (!Driver || !steps.length) { if (opts.onDone) opts.onDone(); return false; }

        var inst = Driver({
            showProgress: true,
            progressText: COMMON.progress || 'Step {{current}} of {{total}}',
            nextBtnText:  COMMON.next || 'Next',
            prevBtnText:  COMMON.prev || 'Back',
            doneBtnText:  COMMON.done || 'Done',
            animate:      !reduceMotion(),
            allowClose:   true,
            steps:        steps,
            onHighlightStarted: function (element) {
                // Mobile: buka sidebar dulu bila target berada di dalamnya
                if (element && element.closest && element.closest('.dlabnav') && window.innerWidth <= 767) {
                    var mw = document.getElementById('main-wrapper');
                    if (mw) mw.classList.add('mobile-nav-open');
                }
            },
            onDestroyed: function () {
                var mw = document.getElementById('main-wrapper');
                if (mw && window.innerWidth <= 767) mw.classList.remove('mobile-nav-open');
                if (opts.markType === 'welcome') markWelcome();
                else if (opts.markType === 'seen') markSeen(key);
                if (opts.onDone) opts.onDone();
            }
        });

        inst.drive();
        return true;
    }

    function runDashboardIfNeeded(afterWelcome) {
        if (SEEN.indexOf('dashboard') !== -1) return;
        setTimeout(function () { startTour('dashboard', { markType: 'seen' }); }, afterWelcome ? 350 : 0);
    }

    // Auto-run berdasarkan route aktif (first-run per halaman)
    function autoRun() {
        if (modalOpen()) return; // jangan ganggu modal / flow yang sedang berjalan
        if (ROUTE === 'dashboard') {
            if (!WELCOME_DONE) {
                startTour('welcome', { markType: 'welcome', onDone: function () { runDashboardIfNeeded(true); } });
            } else {
                runDashboardIfNeeded(false);
            }
        } else if (ROUTE && SEEN.indexOf(ROUTE) === -1) {
            startTour(ROUTE, { markType: 'seen' });
        }
    }

    // API publik: tombol "Panduan" (replay) & reset dari Settings
    window.RomolyTour = {
        replay: function () {
            if (ROUTE === 'dashboard') {
                startTour('welcome', { markType: 'welcome', onDone: function () { startTour('dashboard', { markType: 'seen' }); } });
            } else if (ROUTE) {
                startTour(ROUTE, { markType: 'seen' });
            }
        },
        reset: function () {
            var msg = COMMON.reset_confirm || 'Reset all guides?';
            if (!window.confirm(msg)) return;
            fetch('/api/tour/reset', { method: 'DELETE', headers: jsonHeaders() })
                .then(function () { window.location.href = '/dashboard'; })
                .catch(function () {});
        }
    };

    // Beri jeda agar widget/chart selesai render sebelum spotlight
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(autoRun, 450); });
    } else {
        setTimeout(autoRun, 450);
    }
})();
