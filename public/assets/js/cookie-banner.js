/**
 * Cookie Banner Component
 * Displays a cookie consent banner and manages cookie preferences
 */

(function() {
    'use strict';

    const COOKIE_NAME = 'cookie_consent';
    const COOKIE_EXPIRY_DAYS = 365;
    const BANNER_ID = 'cookie-banner';

    /**
     * Check if user has already given consent
     */
    function hasConsent() {
        return getCookie(COOKIE_NAME) !== null;
    }

    /**
     * Get cookie value
     */
    function getCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i];
            while (cookie.charAt(0) === ' ') {
                cookie = cookie.substring(1, cookie.length);
            }
            if (cookie.indexOf(nameEQ) === 0) {
                return cookie.substring(nameEQ.length, cookie.length);
            }
        }
        return null;
    }

    /**
     * Set cookie
     */
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + date.toUTCString();
        document.cookie = name + '=' + value + ';' + expires + ';path=/;SameSite=Lax';
    }

    /**
     * Create and show cookie banner
     */
    function showBanner() {
        // Check if banner already exists
        if (document.getElementById(BANNER_ID)) {
            return;
        }

        const banner = document.createElement('div');
        banner.id = BANNER_ID;
        banner.innerHTML = `
            <div class="cookie-banner-content">
                <div class="cookie-banner-text">
                    <h4>Cookie Consent</h4>
                    <p>We use cookies to enhance your experience, analyze site usage, and assist in our marketing efforts. By clicking "Accept All", you consent to our use of cookies. You can manage your preferences or learn more in our <a href="${getBaseUrl()}/privacy.php">Privacy Policy</a>.</p>
                </div>
                <div class="cookie-banner-actions">
                    <button id="cookie-accept-all" class="cookie-btn cookie-btn-primary">Accept All</button>
                    <button id="cookie-accept-necessary" class="cookie-btn cookie-btn-secondary">Necessary Only</button>
                    <button id="cookie-customize" class="cookie-btn cookie-btn-link">Customize</button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        // Add event listeners
        document.getElementById('cookie-accept-all').addEventListener('click', function() {
            acceptAll();
        });

        document.getElementById('cookie-accept-necessary').addEventListener('click', function() {
            acceptNecessary();
        });

        document.getElementById('cookie-customize').addEventListener('click', function() {
            showCustomizeModal();
        });

        // Animate in
        setTimeout(function() {
            banner.classList.add('show');
        }, 100);
    }

    /**
     * Accept all cookies
     */
    function acceptAll() {
        setCookie(COOKIE_NAME, JSON.stringify({
            necessary: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString()
        }), COOKIE_EXPIRY_DAYS);
        hideBanner();
    }

    /**
     * Accept only necessary cookies
     */
    function acceptNecessary() {
        setCookie(COOKIE_NAME, JSON.stringify({
            necessary: true,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString()
        }), COOKIE_EXPIRY_DAYS);
        hideBanner();
    }

    /**
     * Show customize modal
     */
    function showCustomizeModal() {
        // Simple implementation - can be enhanced with a modal
        const consent = {
            necessary: true, // Always true
            analytics: confirm('Allow analytics cookies? These help us understand how visitors use our site.'),
            marketing: confirm('Allow marketing cookies? These are used to deliver relevant advertisements.')
        };

        setCookie(COOKIE_NAME, JSON.stringify({
            ...consent,
            timestamp: new Date().toISOString()
        }), COOKIE_EXPIRY_DAYS);
        hideBanner();
    }

    /**
     * Hide banner
     */
    function hideBanner() {
        const banner = document.getElementById(BANNER_ID);
        if (banner) {
            banner.classList.remove('show');
            setTimeout(function() {
                banner.remove();
            }, 300);
        }
    }

    /**
     * Get base URL for links
     */
    function getBaseUrl() {
        const script = document.querySelector('script[src*="cookie-banner"]');
        if (script) {
            const src = script.getAttribute('src');
            const match = src.match(/^(.*?)\/assets\/js\/cookie-banner\.js/);
            if (match) {
                return match[1];
            }
        }
        return '';
    }

    /**
     * Initialize cookie banner
     */
    function init() {
        if (!hasConsent()) {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showBanner);
            } else {
                showBanner();
            }
        }
    }

    // Initialize when script loads
    init();

    // Export functions for external use if needed
    window.CookieBanner = {
        show: showBanner,
        hide: hideBanner,
        hasConsent: hasConsent,
        getConsent: function() {
            const cookie = getCookie(COOKIE_NAME);
            return cookie ? JSON.parse(cookie) : null;
        }
    };
})();

