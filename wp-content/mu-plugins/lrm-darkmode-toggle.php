<?php
/**
 * Plugin Name: LRM Dark Mode Toggle
 * Description: Adds a breathing circle toggle next to the site title to switch dark mode.
 * Version: 1.3.0
 * Author: LRM
 */

if (!defined('ABSPATH')) {
    exit;
}

function lrm_darkmode_toggle_markup() {
    ?>
    <script>
        (function () {
            var rootClass = 'lrm-darkmode-enabled';
            var toggleId = 'lrm-darkmode-toggle';
            var toggleWrapId = 'lrm-darkmode-toggle-wrap';
            var storageKey = 'lrm-darkmode-enabled';
            var observer = null;
            var animationFrameId = null;

            function getTitleElement() {
                var selectors = [
                    'a.sitetitle',
                    '.site-title a',
                    '.lay-site-title a',
                    '.site-title',
                    '.lay-site-title',
                    '.sitetitle',
                    '[class*="site-title"]'
                ];

                for (var i = 0; i < selectors.length; i += 1) {
                    var titleElement = document.querySelector(selectors[i]);

                    if (titleElement) {
                        return titleElement;
                    }
                }

                return null;
            }

            function getToggleWrap() {
                return document.getElementById(toggleWrapId);
            }

            function isDarkModeEnabled() {
                return document.documentElement.classList.contains(rootClass);
            }

            function persistDarkMode(enabled) {
                try {
                    window.localStorage.setItem(storageKey, enabled ? '1' : '0');
                } catch (error) {
                    // Ignore storage errors (private mode, browser restrictions).
                }
            }

            function readStoredMode() {
                try {
                    return window.localStorage.getItem(storageKey) === '1';
                } catch (error) {
                    return false;
                }
            }

            function updateToggleA11y() {
                var toggle = document.getElementById(toggleId);

                if (!toggle) {
                    return;
                }

                var enabled = isDarkModeEnabled();
                toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
                toggle.setAttribute('aria-label', enabled ? 'Disable dark mode' : 'Enable dark mode');
            }

            function setDarkMode(enabled, shouldPersist) {
                document.documentElement.classList.toggle(rootClass, enabled);

                if (document.body) {
                    document.body.classList.toggle(rootClass, enabled);
                }

                updateToggleA11y();

                if (shouldPersist !== false) {
                    persistDarkMode(enabled);
                }
            }

            function toggleDarkMode(event) {
                event.preventDefault();
                event.stopPropagation();
                setDarkMode(!isDarkModeEnabled(), true);
            }

            function createToggle() {
                var toggle = document.createElement('a');
                toggle.id = toggleId;
                toggle.href = '#';
                toggle.setAttribute('role', 'button');
                toggle.className = 'lrm-darkmode-toggle';

                toggle.addEventListener('click', toggleDarkMode);
                toggle.addEventListener('mousedown', function (event) {
                    event.stopPropagation();
                });
                toggle.addEventListener('keydown', function (event) {
                    if (event.key === ' ') {
                        toggleDarkMode(event);
                    }
                });

                return toggle;
            }

            function positionToggleWrap() {
                var titleElement = getTitleElement();
                var wrap = getToggleWrap();

                if (!titleElement || !wrap) {
                    return;
                }

                var rect = titleElement.getBoundingClientRect();
                var dotSize = 8;
                var gap = 8;
                var top = rect.top + (rect.height / 2) - (dotSize / 2);
                var left = rect.right + gap;

                wrap.style.top = top + 'px';
                wrap.style.left = left + 'px';
            }

            function startPositionSync() {
                if (animationFrameId !== null) {
                    return;
                }

                function tick() {
                    positionToggleWrap();
                    animationFrameId = window.requestAnimationFrame(tick);
                }

                animationFrameId = window.requestAnimationFrame(tick);
            }

            function ensureToggleAttached() {
                var titleElement = getTitleElement();

                if (!titleElement || !document.body) {
                    return;
                }

                var wrap = getToggleWrap();

                if (!wrap) {
                    wrap = document.createElement('span');
                    wrap.id = toggleWrapId;
                    wrap.className = 'lrm-darkmode-toggle-wrap';
                    document.body.appendChild(wrap);
                } else if (!document.body.contains(wrap)) {
                    document.body.appendChild(wrap);
                }

                if (!document.getElementById(toggleId)) {
                    wrap.appendChild(createToggle());
                }

                updateToggleA11y();
                positionToggleWrap();
            }

            function startObserver() {
                if (observer || !document.body || !window.MutationObserver) {
                    return;
                }

                observer = new MutationObserver(function () {
                    ensureToggleAttached();
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }

            function init() {
                setDarkMode(readStoredMode(), false);
                ensureToggleAttached();
                startObserver();
                startPositionSync();
                window.addEventListener('resize', positionToggleWrap, { passive: true });
                window.addEventListener('scroll', positionToggleWrap, { passive: true });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
    <style>
        #lrm-darkmode-toggle-wrap.lrm-darkmode-toggle-wrap {
            position: fixed;
            z-index: 9999;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 8px;
            height: 8px;
            line-height: 1;
            pointer-events: auto;
        }

        .lrm-darkmode-toggle {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            border: 0;
            padding: 0;
            margin: 0;
            cursor: pointer;
            background: #000000;
            animation: lrm-darkmode-breathing 2.2s ease-in-out infinite;
            transition: background-color 220ms ease;
            text-decoration: none;
            display: inline-block;
        }

        .lrm-darkmode-toggle:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.65);
            outline-offset: 3px;
        }

        .lrm-darkmode-enabled .lrm-darkmode-toggle,
        .lrm-darkmode-toggle[aria-pressed='true'] {
            background: #ffffff;
        }

        html.lrm-darkmode-enabled,
        html.lrm-darkmode-enabled body,
        body.lrm-darkmode-enabled,
        html.lrm-darkmode-enabled #content,
        html.lrm-darkmode-enabled .site,
        html.lrm-darkmode-enabled .site-content,
        html.lrm-darkmode-enabled .site-main,
        html.lrm-darkmode-enabled .main,
        html.lrm-darkmode-enabled .content,
        html.lrm-darkmode-enabled .entry-content,
        html.lrm-darkmode-enabled .lay-content,
        html.lrm-darkmode-enabled .lay-content-wrapper,
        html.lrm-darkmode-enabled .container,
        html.lrm-darkmode-enabled .page,
        html.lrm-darkmode-enabled .page-wrapper,
        html.lrm-darkmode-enabled main,
        html.lrm-darkmode-enabled article,
        html.lrm-darkmode-enabled section,
        html.lrm-darkmode-enabled header,
        html.lrm-darkmode-enabled footer,
        html.lrm-darkmode-enabled nav,
        html.lrm-darkmode-enabled aside,
        html.lrm-darkmode-enabled div:not(.title._ProjectTitle):not(.title._ProjectTitle *):not(.titlewrap-on-image):not(.titlewrap-on-image *),
        html.lrm-darkmode-enabled [class*='wrap']:not(.title._ProjectTitle):not(.title._ProjectTitle *):not(.titlewrap-on-image):not(.titlewrap-on-image *),
        html.lrm-darkmode-enabled [class*='container']:not(.title._ProjectTitle):not(.title._ProjectTitle *) {
            background: #111111 !important;
            background-color: #111111 !important;
            background-image: none !important;
        }

        html.lrm-darkmode-enabled body,
        html.lrm-darkmode-enabled p,
        html.lrm-darkmode-enabled span:not(._ProjectTitle):not(._ProjectTitle *),
        html.lrm-darkmode-enabled a:not(._ProjectTitle):not(._ProjectTitle *),
        html.lrm-darkmode-enabled li,
        html.lrm-darkmode-enabled h1,
        html.lrm-darkmode-enabled h2,
        html.lrm-darkmode-enabled h3,
        html.lrm-darkmode-enabled h4,
        html.lrm-darkmode-enabled h5,
        html.lrm-darkmode-enabled h6,
        html.lrm-darkmode-enabled strong,
        html.lrm-darkmode-enabled em,
        html.lrm-darkmode-enabled small,
        html.lrm-darkmode-enabled figcaption {
            color: #ffffff !important;
        }


        html.lrm-darkmode-enabled .titlewrap-on-image,
        html.lrm-darkmode-enabled .titlewrap-on-image *,
        html.lrm-darkmode-enabled .lay-textformat-parent.titlewrap-on-image,
        html.lrm-darkmode-enabled .lay-textformat-parent.titlewrap-on-image * {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }



        html.lrm-darkmode-enabled .nav-pill.nav-opacity-pill-transition.nav-pill-transition,
        html.lrm-darkmode-enabled .nav-pill,
        body.lrm-darkmode-enabled .nav-pill.nav-opacity-pill-transition.nav-pill-transition,
        body.lrm-darkmode-enabled .nav-pill {
            background: #dbdbdb !important;
            background-color: #dbdbdb !important;
            background-image: none !important;
        }


        html.lrm-darkmode-enabled #lrm-scroll-home-prompt,
        body.lrm-darkmode-enabled #lrm-scroll-home-prompt {
            color: #ffffff !important;
            background: transparent !important;
            background-color: transparent !important;
            opacity: 0 !important;
        }

        html.lrm-darkmode-enabled .tablepress,
        html.lrm-darkmode-enabled .tablepress *,
        html.lrm-darkmode-enabled .tablepress th,
        html.lrm-darkmode-enabled .tablepress td,
        html.lrm-darkmode-enabled .tablepress thead th,
        html.lrm-darkmode-enabled .tablepress tbody td,
        html.lrm-darkmode-enabled .tablepress a {
            color: #ffffff !important;
        }

        html.lrm-darkmode-enabled .tablepress,
        html.lrm-darkmode-enabled .tablepress thead th,
        html.lrm-darkmode-enabled .tablepress tbody td,
        html.lrm-darkmode-enabled .tablepress tfoot th,
        html.lrm-darkmode-enabled .tablepress tr {
            background: #111111 !important;
            background-color: #111111 !important;
            background-image: none !important;
        }

        html.lrm-darkmode-enabled ._ProjectTitle,
        html.lrm-darkmode-enabled ._ProjectTitle * {
            color: inherit !important;
            background: inherit !important;
            background-color: inherit !important;
        }

        .site-title,
        .site-title a,
        .lay-site-title,
        .lay-site-title a {
            color: #ffffff !important;
        }

        @keyframes lrm-darkmode-breathing {
            0%,
            100% {
                transform: scale(0.84);
            }

            50% {
                transform: scale(1.16);
            }
        }

        @media (max-width: 1024px) {
            #lrm-darkmode-toggle-wrap.lrm-darkmode-toggle-wrap {
                display: none !important;
            }
        }
    </style>
    <?php
}
add_action('wp_footer', 'lrm_darkmode_toggle_markup', 98);
