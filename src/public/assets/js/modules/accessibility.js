/**
 * Accessibility Enhancement Module
 * 
 * Provides ARIA live regions, keyboard navigation improvements,
 * and screen reader support for CI-Inbox.
 * 
 * @module Accessibility
 * @since 2025-12-06 (M3 Production Readiness)
 */

const Accessibility = (function() {
    'use strict';

    // ============================================================================
    // ARIA LIVE REGIONS
    // ============================================================================
    
    let liveRegions = {
        polite: null,
        assertive: null,
        status: null
    };

    /**
     * Initialize ARIA live regions
     */
    function initLiveRegions() {
        // Polite announcements (non-urgent)
        if (!liveRegions.polite) {
            liveRegions.polite = createLiveRegion('polite', 'aria-live-polite');
        }

        // Assertive announcements (urgent)
        if (!liveRegions.assertive) {
            liveRegions.assertive = createLiveRegion('assertive', 'aria-live-assertive');
        }

        // Status updates
        if (!liveRegions.status) {
            liveRegions.status = createLiveRegion('polite', 'aria-live-status', 'status');
        }
    }

    /**
     * Create ARIA live region element
     * @param {string} politeness - 'polite' or 'assertive'
     * @param {string} className - CSS class name
     * @param {string} role - ARIA role (default: 'log')
     * @returns {HTMLElement} Live region element
     */
    function createLiveRegion(politeness, className, role = 'log') {
        const region = document.createElement('div');
        region.className = `sr-only ${className}`;
        region.setAttribute('aria-live', politeness);
        region.setAttribute('aria-atomic', 'true');
        region.setAttribute('role', role);
        document.body.appendChild(region);
        return region;
    }

    /**
     * Announce message to screen readers
     * @param {string} message - Message to announce
     * @param {string} priority - 'polite' or 'assertive' (default: 'polite')
     */
    function announce(message, priority = 'polite') {
        if (!liveRegions.polite) {
            initLiveRegions();
        }

        const region = priority === 'assertive' 
            ? liveRegions.assertive 
            : liveRegions.polite;

        // Clear and set message
        region.textContent = '';
        setTimeout(() => {
            region.textContent = message;
            console.log('[A11y] Announced:', message, `(${priority})`);
        }, 100);
    }

    /**
     * Announce status update
     * @param {string} status - Status message
     */
    function announceStatus(status) {
        if (!liveRegions.status) {
            initLiveRegions();
        }

        liveRegions.status.textContent = '';
        setTimeout(() => {
            liveRegions.status.textContent = status;
            console.log('[A11y] Status:', status);
        }, 100);
    }

    // ============================================================================
    // FOCUS MANAGEMENT
    // ============================================================================
    
    /**
     * Set focus to element with optional delay
     * @param {string|HTMLElement} target - Element selector or element
     * @param {number} delay - Delay in ms (default: 100)
     */
    function setFocus(target, delay = 100) {
        setTimeout(() => {
            const element = typeof target === 'string' 
                ? document.querySelector(target)
                : target;

            if (element) {
                element.focus();
                console.log('[A11y] Focus set to:', element);
            }
        }, delay);
    }

    /**
     * Trap focus within modal/dialog
     * @param {HTMLElement} container - Container element
     * @returns {function} Cleanup function
     */
    function trapFocus(container) {
        const focusableElements = container.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length === 0) return () => {};

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        const handleTab = (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        };

        container.addEventListener('keydown', handleTab);

        // Set initial focus
        firstElement.focus();

        // Return cleanup function
        return () => {
            container.removeEventListener('keydown', handleTab);
        };
    }

    /**
     * Save current focus and return restore function
     * @returns {function} Restore function
     */
    function saveFocus() {
        const activeElement = document.activeElement;
        
        return () => {
            if (activeElement && typeof activeElement.focus === 'function') {
                activeElement.focus();
            }
        };
    }

    // ============================================================================
    // KEYBOARD NAVIGATION
    // ============================================================================
    
    /**
     * Add keyboard navigation to list
     * @param {string} listSelector - List container selector
     * @param {string} itemSelector - List item selector
     * @param {object} options - Navigation options
     */
    function enableListNavigation(listSelector, itemSelector, options = {}) {
        const {
            onSelect = null,
            onEscape = null,
            wrap = true
        } = options;

        const list = document.querySelector(listSelector);
        if (!list) return;

        list.addEventListener('keydown', (e) => {
            const items = Array.from(list.querySelectorAll(itemSelector));
            const currentIndex = items.indexOf(document.activeElement);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < items.length - 1) {
                        items[currentIndex + 1].focus();
                    } else if (wrap) {
                        items[0].focus();
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        items[currentIndex - 1].focus();
                    } else if (wrap) {
                        items[items.length - 1].focus();
                    }
                    break;

                case 'Home':
                    e.preventDefault();
                    items[0].focus();
                    break;

                case 'End':
                    e.preventDefault();
                    items[items.length - 1].focus();
                    break;

                case 'Enter':
                case ' ':
                    e.preventDefault();
                    if (onSelect && document.activeElement) {
                        onSelect(document.activeElement);
                    }
                    break;

                case 'Escape':
                    if (onEscape) {
                        onEscape();
                    }
                    break;
            }
        });
    }

    // ============================================================================
    // ARIA ATTRIBUTES
    // ============================================================================
    
    /**
     * Update ARIA label
     * @param {string|HTMLElement} target - Element selector or element
     * @param {string} label - ARIA label text
     */
    function setAriaLabel(target, label) {
        const element = typeof target === 'string' 
            ? document.querySelector(target)
            : target;

        if (element) {
            element.setAttribute('aria-label', label);
        }
    }

    /**
     * Update ARIA described-by
     * @param {string|HTMLElement} target - Element selector or element
     * @param {string} descriptionId - ID of description element
     */
    function setAriaDescribedBy(target, descriptionId) {
        const element = typeof target === 'string' 
            ? document.querySelector(target)
            : target;

        if (element) {
            element.setAttribute('aria-describedby', descriptionId);
        }
    }

    /**
     * Update loading state with ARIA
     * @param {string|HTMLElement} target - Element selector or element
     * @param {boolean} isLoading - Loading state
     * @param {string} message - Loading message (optional)
     */
    function setLoadingState(target, isLoading, message = null) {
        const element = typeof target === 'string' 
            ? document.querySelector(target)
            : target;

        if (!element) return;

        element.setAttribute('aria-busy', isLoading);

        if (isLoading) {
            element.classList.add('is-loading');
            if (message) {
                announceStatus(message);
            }
        } else {
            element.classList.remove('is-loading');
            if (message) {
                announceStatus(message);
            }
        }
    }

    /**
     * Update expanded state with ARIA
     * @param {string|HTMLElement} button - Button element
     * @param {boolean} isExpanded - Expanded state
     */
    function setExpandedState(button, isExpanded) {
        const element = typeof button === 'string' 
            ? document.querySelector(button)
            : button;

        if (element) {
            element.setAttribute('aria-expanded', isExpanded);
        }
    }

    // ============================================================================
    // SEMANTIC ROLES
    // ============================================================================
    
    /**
     * Make element a button if not already
     * @param {HTMLElement} element - Element to enhance
     */
    function ensureButton(element) {
        if (!element) return;

        if (element.tagName !== 'BUTTON' && !element.hasAttribute('role')) {
            element.setAttribute('role', 'button');
            element.setAttribute('tabindex', '0');
            
            // Add keyboard support
            element.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    element.click();
                }
            });
        }
    }

    /**
     * Enhance icon-only buttons with labels
     * @param {string} containerSelector - Container to search (default: document)
     */
    function enhanceIconButtons(containerSelector = null) {
        const container = containerSelector 
            ? document.querySelector(containerSelector)
            : document;

        if (!container) return;

        // Find buttons with only SVG content (no text)
        const iconButtons = container.querySelectorAll('button:not([aria-label])');
        
        iconButtons.forEach(button => {
            const hasText = button.textContent.trim().length > 0;
            const hasSvg = button.querySelector('svg') !== null;
            
            if (!hasText && hasSvg && !button.hasAttribute('aria-label')) {
                // Try to infer label from title or class
                const title = button.getAttribute('title');
                if (title) {
                    button.setAttribute('aria-label', title);
                    console.log('[A11y] Added aria-label to button:', title);
                } else {
                    console.warn('[A11y] Icon button without aria-label:', button);
                }
            }
        });
    }

    // ============================================================================
    // SKIP NAVIGATION
    // ============================================================================
    
    /**
     * Add skip navigation links
     */
    function addSkipLinks() {
        const existing = document.querySelector('.skip-links');
        if (existing) return;

        const skipLinks = document.createElement('div');
        skipLinks.className = 'skip-links';
        skipLinks.innerHTML = `
            <a href="#main-content" class="skip-link">Zum Hauptinhalt springen</a>
            <a href="#sidebar" class="skip-link">Zur Navigation springen</a>
        `;

        document.body.insertBefore(skipLinks, document.body.firstChild);

        // Ensure target elements have IDs
        const mainContent = document.querySelector('main, .main-content, .c-inbox');
        if (mainContent && !mainContent.id) {
            mainContent.id = 'main-content';
        }

        const sidebar = document.querySelector('.sidebar, .c-sidebar');
        if (sidebar && !sidebar.id) {
            sidebar.id = 'sidebar';
        }
    }

    // ============================================================================
    // INITIALIZATION
    // ============================================================================
    
    /**
     * Initialize accessibility enhancements
     */
    function init() {
        console.log('[A11y] Initializing accessibility enhancements...');

        // Create ARIA live regions
        initLiveRegions();

        // Add skip links
        addSkipLinks();

        // Enhance icon buttons
        enhanceIconButtons();

        console.log('[A11y] Accessibility enhancements initialized');
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Announcements
        announce,
        announceStatus,
        
        // Focus management
        setFocus,
        trapFocus,
        saveFocus,
        
        // Keyboard navigation
        enableListNavigation,
        
        // ARIA attributes
        setAriaLabel,
        setAriaDescribedBy,
        setLoadingState,
        setExpandedState,
        
        // Enhancements
        ensureButton,
        enhanceIconButtons,
        addSkipLinks,
        
        // Manual init
        init
    };
})();

// Make globally available
window.Accessibility = Accessibility;
