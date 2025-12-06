/**
 * Loading State Manager
 * 
 * Unified API for showing/hiding loading indicators across the application.
 * Integrates with Accessibility module for screen reader announcements.
 * 
 * @module LoadingStateManager
 * @since 2025-12-06 (M3 Production Readiness)
 */

const LoadingStateManager = (function() {
    'use strict';

    // ============================================================================
    // STATE TRACKING
    // ============================================================================
    
    const activeLoadingStates = new Set();
    let globalOverlay = null;

    // ============================================================================
    // LOADING SPINNER
    // ============================================================================
    
    /**
     * Show loading state on element
     * @param {string|HTMLElement} target - Element selector or element
     * @param {object} options - Loading options
     * @param {string} options.message - Loading message
     * @param {boolean} options.overlay - Show overlay (default: true)
     * @param {string} options.size - Spinner size: 'sm', 'md', 'lg' (default: 'md')
     * @returns {string} Loading state ID
     */
    function show(target, options = {}) {
        const {
            message = 'Lädt...',
            overlay = true,
            size = 'md'
        } = options;

        const element = typeof target === 'string' 
            ? document.querySelector(target)
            : target;

        if (!element) {
            console.warn('[LoadingState] Element not found:', target);
            return null;
        }

        // Generate unique ID
        const id = 'loading-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Add loading class
        element.classList.add('is-loading');
        element.setAttribute('data-loading-id', id);
        element.setAttribute('aria-busy', 'true');

        // Store state
        activeLoadingStates.add(id);

        // Announce to screen readers
        if (typeof window.Accessibility !== 'undefined') {
            window.Accessibility.announceStatus(message);
        }

        console.log('[LoadingState] Show:', id, message);

        return id;
    }

    /**
     * Hide loading state from element
     * @param {string|HTMLElement} target - Element selector, element, or loading ID
     * @param {string} successMessage - Optional success message
     */
    function hide(target, successMessage = null) {
        let element;
        let id;

        if (typeof target === 'string') {
            // Could be selector or loading ID
            if (target.startsWith('loading-')) {
                // It's a loading ID
                id = target;
                element = document.querySelector(`[data-loading-id="${id}"]`);
            } else {
                // It's a selector
                element = document.querySelector(target);
                id = element?.getAttribute('data-loading-id');
            }
        } else {
            element = target;
            id = element?.getAttribute('data-loading-id');
        }

        if (!element) {
            console.warn('[LoadingState] Element not found:', target);
            return;
        }

        // Remove loading class
        element.classList.remove('is-loading');
        element.removeAttribute('data-loading-id');
        element.setAttribute('aria-busy', 'false');

        // Remove from state
        if (id) {
            activeLoadingStates.delete(id);
        }

        // Announce success if provided
        if (successMessage) {
            if (typeof window.Accessibility !== 'undefined') {
                window.Accessibility.announceStatus(successMessage);
            }
        }

        console.log('[LoadingState] Hide:', id || 'unknown');
    }

    // ============================================================================
    // BUTTON LOADING STATE
    // ============================================================================
    
    /**
     * Show loading state on button
     * @param {string|HTMLElement} button - Button selector or element
     * @param {string} loadingText - Optional loading text (default: button text)
     * @returns {function} Cleanup function
     */
    function showButtonLoading(button, loadingText = null) {
        const element = typeof button === 'string' 
            ? document.querySelector(button)
            : button;

        if (!element) {
            console.warn('[LoadingState] Button not found:', button);
            return () => {};
        }

        // Save original state
        const originalText = element.textContent;
        const originalDisabled = element.disabled;

        // Apply loading state
        element.classList.add('is-loading');
        element.disabled = true;
        element.setAttribute('aria-busy', 'true');

        if (loadingText) {
            element.textContent = loadingText;
        }

        // Return cleanup function
        return () => {
            element.classList.remove('is-loading');
            element.disabled = originalDisabled;
            element.setAttribute('aria-busy', 'false');
            element.textContent = originalText;
        };
    }

    // ============================================================================
    // GLOBAL OVERLAY
    // ============================================================================
    
    /**
     * Show global loading overlay
     * @param {string} message - Loading message
     * @returns {function} Hide function
     */
    function showOverlay(message = 'Lädt...') {
        // Remove existing overlay if any
        if (globalOverlay) {
            globalOverlay.remove();
        }

        // Create overlay
        globalOverlay = document.createElement('div');
        globalOverlay.className = 'c-loading-overlay';
        globalOverlay.innerHTML = `
            <div class="c-loading-overlay__spinner"></div>
            <div class="c-loading-overlay__text">${message}</div>
        `;

        document.body.appendChild(globalOverlay);

        // Announce to screen readers
        if (typeof window.Accessibility !== 'undefined') {
            window.Accessibility.announce(message, 'polite');
        }

        console.log('[LoadingState] Overlay shown:', message);

        // Return hide function
        return () => hideOverlay();
    }

    /**
     * Hide global loading overlay
     */
    function hideOverlay() {
        if (globalOverlay) {
            globalOverlay.remove();
            globalOverlay = null;
            console.log('[LoadingState] Overlay hidden');
        }
    }

    // ============================================================================
    // PROGRESS BAR
    // ============================================================================
    
    /**
     * Show progress bar
     * @param {string|HTMLElement} container - Container selector or element
     * @param {number} progress - Progress value (0-100), or null for indeterminate
     * @returns {object} Progress bar API
     */
    function showProgress(container, progress = null) {
        const element = typeof container === 'string' 
            ? document.querySelector(container)
            : container;

        if (!element) {
            console.warn('[LoadingState] Container not found:', container);
            return { update: () => {}, hide: () => {} };
        }

        // Create progress bar
        const progressEl = document.createElement('div');
        progressEl.className = 'c-progress' + (progress === null ? ' c-progress--indeterminate' : '');
        progressEl.innerHTML = `<div class="c-progress__bar" style="width: ${progress || 0}%"></div>`;

        element.appendChild(progressEl);

        const bar = progressEl.querySelector('.c-progress__bar');

        // Return API
        return {
            update: (newProgress) => {
                if (progressEl.classList.contains('c-progress--indeterminate')) {
                    progressEl.classList.remove('c-progress--indeterminate');
                }
                bar.style.width = `${Math.min(100, Math.max(0, newProgress))}%`;
            },
            hide: () => {
                progressEl.remove();
            }
        };
    }

    // ============================================================================
    // SKELETON LOADER
    // ============================================================================
    
    /**
     * Show skeleton loader
     * @param {string|HTMLElement} container - Container selector or element
     * @param {string} type - Skeleton type: 'thread-list', 'thread-detail', 'card'
     * @param {number} count - Number of skeleton items (default: 3)
     */
    function showSkeleton(container, type = 'thread-list', count = 3) {
        const element = typeof container === 'string' 
            ? document.querySelector(container)
            : container;

        if (!element) {
            console.warn('[LoadingState] Container not found:', container);
            return;
        }

        // Clear container
        element.innerHTML = '';

        // Generate skeleton items
        for (let i = 0; i < count; i++) {
            const skeleton = createSkeletonItem(type);
            element.appendChild(skeleton);
        }

        element.setAttribute('aria-busy', 'true');
        element.setAttribute('aria-label', 'Inhalt wird geladen');
    }

    /**
     * Create skeleton item based on type
     * @param {string} type - Skeleton type
     * @returns {HTMLElement} Skeleton element
     */
    function createSkeletonItem(type) {
        const skeleton = document.createElement('div');

        switch (type) {
            case 'thread-list':
                skeleton.className = 'c-thread-skeleton';
                skeleton.innerHTML = `
                    <div class="c-thread-skeleton__avatar"></div>
                    <div class="c-thread-skeleton__content">
                        <div class="c-thread-skeleton__line"></div>
                        <div class="c-thread-skeleton__line"></div>
                        <div class="c-thread-skeleton__line"></div>
                    </div>
                `;
                break;

            case 'card':
                skeleton.className = 'c-skeleton c-skeleton--card';
                break;

            case 'text':
                skeleton.className = 'c-skeleton c-skeleton--text';
                break;

            default:
                skeleton.className = 'c-skeleton c-skeleton--card';
        }

        return skeleton;
    }

    /**
     * Hide skeleton loader
     * @param {string|HTMLElement} container - Container selector or element
     */
    function hideSkeleton(container) {
        const element = typeof container === 'string' 
            ? document.querySelector(container)
            : container;

        if (!element) {
            console.warn('[LoadingState] Container not found:', container);
            return;
        }

        element.removeAttribute('aria-busy');
        element.removeAttribute('aria-label');
        // Content will be replaced by actual data
    }

    // ============================================================================
    // UTILITIES
    // ============================================================================
    
    /**
     * Check if element is in loading state
     * @param {string|HTMLElement} target - Element selector or element
     * @returns {boolean} True if loading
     */
    function isLoading(target) {
        const element = typeof target === 'string' 
            ? document.querySelector(target)
            : target;

        return element?.classList.contains('is-loading') || false;
    }

    /**
     * Get all active loading states
     * @returns {Array<string>} Array of loading IDs
     */
    function getActiveStates() {
        return Array.from(activeLoadingStates);
    }

    /**
     * Clear all loading states
     */
    function clearAll() {
        // Hide overlay
        hideOverlay();

        // Remove all loading classes
        activeLoadingStates.forEach(id => {
            const element = document.querySelector(`[data-loading-id="${id}"]`);
            if (element) {
                hide(element);
            }
        });

        activeLoadingStates.clear();
        console.log('[LoadingState] All states cleared');
    }

    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Basic loading
        show,
        hide,
        
        // Button loading
        showButtonLoading,
        
        // Global overlay
        showOverlay,
        hideOverlay,
        
        // Progress bar
        showProgress,
        
        // Skeleton loader
        showSkeleton,
        hideSkeleton,
        
        // Utilities
        isLoading,
        getActiveStates,
        clearAll
    };
})();

// Make globally available
window.LoadingStateManager = LoadingStateManager;
