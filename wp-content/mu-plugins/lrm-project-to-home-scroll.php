<?php
/**
 * Plugin Name: LRM Project Bottom Redirect
 * Description: Redirects visitors from project pages back to the homepage when they reach the bottom of the page.
 * Version: 1.1.0
 * Author: LRM
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Print redirect script globally on frontend pages.
 *
 * Project-page checks happen in JS so this works with AJAX-style navigation
 * where page transitions do not trigger a full PHP render.
 *
 * Kept as a MU plugin so theme updates do not overwrite the behavior.
 */
function lrm_project_bottom_redirect_script() {
    // Load on all frontend pages so behavior survives AJAX-style page transitions
    // where scripts are not re-injected on each virtual page view.
    $home_url = home_url('/');
    ?>
    <script>
        (function () {
            var redirectUrl = <?php echo wp_json_encode($home_url); ?>;
            var thresholdPx = 24;
            var promptDistanceRatio = 0.05;
            var hasRedirected = false;
            var lastPathname = window.location.pathname;
            var promptShown = false;
            var promptShownAt = 0;
            var promptId = 'lrm-scroll-home-prompt';
            var lastScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
            var isScrollingUp = false;

            function isLikelyProjectPage() {
                var body = document.body;
                if (!body) {
                    return false;
                }

                var classes = body.className || '';

                return (
                    classes.indexOf('single-project') !== -1 ||
                    classes.indexOf('post-type-project') !== -1 ||
                    classes.indexOf('type-project') !== -1 ||
                    classes.indexOf('lay-project') !== -1 ||
                    classes.indexOf('single-portfolio') !== -1
                );
            }

            function atBottomOfPage() {
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                var documentHeight = Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.offsetHeight,
                    document.body.clientHeight,
                    document.documentElement.clientHeight
                );

                return (scrollTop + viewportHeight) >= (documentHeight - thresholdPx);
            }

            function remainingDistanceToBottom() {
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                var documentHeight = Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.offsetHeight,
                    document.body.clientHeight,
                    document.documentElement.clientHeight
                );

                return Math.max(0, documentHeight - (scrollTop + viewportHeight));
            }

            function shouldShowPromptEarly() {
                var documentHeight = Math.max(
                    document.body.scrollHeight,
                    document.documentElement.scrollHeight,
                    document.body.offsetHeight,
                    document.documentElement.offsetHeight,
                    document.body.clientHeight,
                    document.documentElement.clientHeight
                );
                var earlyPromptDistance = documentHeight * promptDistanceRatio;

                return remainingDistanceToBottom() <= earlyPromptDistance;
            }

            function getPromptElement() {
                return document.getElementById(promptId);
            }

            function ensurePromptElement() {
                var existingPrompt = getPromptElement();
                if (existingPrompt) {
                    return existingPrompt;
                }

                var prompt = document.createElement('div');
                prompt.id = promptId;
                prompt.className = '_ParagraphCustom_tidy lrm-scroll-home-prompt';
                prompt.setAttribute('aria-hidden', 'true');
                prompt.textContent = 'Scroll Back to Project Overview';
                document.body.appendChild(prompt);

                return prompt;
            }

            function showPrompt() {
                if (promptShown) {
                    return;
                }

                var prompt = ensurePromptElement();
                prompt.classList.add('is-visible');
                promptShown = true;
                promptShownAt = Date.now();
            }

            function hidePrompt() {
                var prompt = getPromptElement();

                if (prompt) {
                    prompt.classList.remove('is-visible');
                }

                promptShown = false;
                promptShownAt = 0;
            }

            function triggerRedirect() {
                if (hasRedirected) {
                    return;
                }

                hasRedirected = true;
                hidePrompt();

                document.documentElement.classList.add('lrm-transitioning-home');
                document.body.classList.add('lrm-transitioning-home');

                window.setTimeout(function () {
                    window.location.assign(redirectUrl);
                }, 320);
            }

            function handleScrollIntent(deltaY) {
                if (deltaY <= 0 || hasRedirected || !promptShown || !atBottomOfPage()) {
                    return;
                }

                // Ignore tiny/inertial events right after prompt appears.
                if ((Date.now() - promptShownAt) < 180) {
                    return;
                }

                triggerRedirect();
            }

            function handleScrollDirection() {
                var currentScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
                isScrollingUp = currentScrollTop < lastScrollTop;

                if (isScrollingUp) {
                    hidePrompt();
                }

                lastScrollTop = currentScrollTop;
            }

            function maybeShowPrompt() {
                var currentPathname = window.location.pathname;

                if (currentPathname !== lastPathname) {
                    hasRedirected = false;
                    lastPathname = currentPathname;
                    hidePrompt();
                }

                if (!isLikelyProjectPage()) {
                    hidePrompt();
                    return;
                }

                if (isScrollingUp) {
                    hidePrompt();
                    return;
                }

                if (hasRedirected) {
                    return;
                }

                if (shouldShowPromptEarly()) {
                    showPrompt();
                    return;
                }

                hidePrompt();
            }

            window.addEventListener('wheel', function (event) {
                maybeShowPrompt();
                handleScrollIntent(event.deltaY || 0);
            }, { passive: true });

            window.addEventListener('touchmove', function () {
                maybeShowPrompt();
            }, { passive: true });

            window.addEventListener('scroll', function () {
                handleScrollDirection();
                maybeShowPrompt();
            }, { passive: true });
            window.addEventListener('resize', maybeShowPrompt);
            window.addEventListener('popstate', maybeShowPrompt);

            window.addEventListener('keydown', function (event) {
                var key = event.key;
                var isForwardScrollKey = (
                    key === 'ArrowDown' ||
                    key === 'PageDown' ||
                    key === 'End' ||
                    key === ' '
                );

                maybeShowPrompt();

                if (isForwardScrollKey) {
                    handleScrollIntent(1);
                }
            });

            document.addEventListener('DOMContentLoaded', maybeShowPrompt);
            window.setInterval(maybeShowPrompt, 300);
            window.setTimeout(maybeShowPrompt, 200);
        })();
    </script>
    <style>
        .lrm-transitioning-home body,
        body.lrm-transitioning-home {
            transition: opacity 320ms ease;
            opacity: 0;
        }

        .lrm-scroll-home-prompt {
            position: fixed;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%) translateY(16px);
            padding: 18vh 0;
            text-align: center;
            opacity: 0;
            transition: opacity 260ms ease, transform 260ms ease;
            pointer-events: none;
            z-index: 10000;
        }

        .lrm-scroll-home-prompt.is-visible {
            opacity: 0.5;
            transform: translateX(-50%) translateY(0);
        }
    </style>
    <?php
}
add_action('wp_footer', 'lrm_project_bottom_redirect_script', 99);
