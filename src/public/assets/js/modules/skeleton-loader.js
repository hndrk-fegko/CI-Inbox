/**
 * Skeleton Loading Module
 * 
 * Provides functions to show/hide skeleton loading states
 * for improved perceived performance.
 */

const SkeletonLoader = (function() {
    'use strict';

    /**
     * Generate thread skeleton HTML
     * @param {number} count - Number of skeleton items
     * @returns {string} HTML string
     */
    function generateThreadSkeletons(count = 5) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="c-skeleton-thread">
                    <div class="c-skeleton-thread__header">
                        <div class="c-skeleton c-skeleton-thread__sender"></div>
                        <div class="c-skeleton c-skeleton-thread__time"></div>
                    </div>
                    <div class="c-skeleton c-skeleton-thread__subject"></div>
                    <div class="c-skeleton-thread__meta">
                        <div class="c-skeleton c-skeleton-thread__badge"></div>
                        <div class="c-skeleton c-skeleton-thread__badge"></div>
                    </div>
                </div>
            `;
        }
        return html;
    }

    /**
     * Generate thread detail skeleton HTML
     * @returns {string} HTML string
     */
    function generateDetailSkeleton() {
        return `
            <div class="c-skeleton-detail">
                <div class="c-skeleton-detail__header">
                    <div class="c-skeleton c-skeleton-detail__title"></div>
                    <div class="c-skeleton-detail__meta">
                        <div class="c-skeleton c-skeleton-thread__badge"></div>
                        <div class="c-skeleton c-skeleton-thread__badge"></div>
                    </div>
                </div>
                
                <div class="c-skeleton-email">
                    <div class="c-skeleton-email__header">
                        <div class="c-skeleton c-skeleton--circle c-skeleton-email__avatar"></div>
                        <div class="c-skeleton-email__from">
                            <div class="c-skeleton c-skeleton-email__name"></div>
                            <div class="c-skeleton c-skeleton-email__date"></div>
                        </div>
                    </div>
                    <div class="c-skeleton-email__body">
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                    </div>
                </div>
                
                <div class="c-skeleton-email">
                    <div class="c-skeleton-email__header">
                        <div class="c-skeleton c-skeleton--circle c-skeleton-email__avatar"></div>
                        <div class="c-skeleton-email__from">
                            <div class="c-skeleton c-skeleton-email__name"></div>
                            <div class="c-skeleton c-skeleton-email__date"></div>
                        </div>
                    </div>
                    <div class="c-skeleton-email__body">
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                        <div class="c-skeleton c-skeleton-email__line"></div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Show skeleton loading in a container
     * @param {HTMLElement|string} container - Container element or selector
     * @param {string} type - Type of skeleton ('thread-list', 'thread-detail')
     * @param {object} options - Additional options
     */
    function show(container, type = 'thread-list', options = {}) {
        const el = typeof container === 'string' 
            ? document.querySelector(container) 
            : container;
        
        if (!el) {
            console.warn('[SkeletonLoader] Container not found:', container);
            return;
        }

        // Store original content
        if (!el.dataset.originalContent) {
            el.dataset.originalContent = el.innerHTML;
        }

        // Set skeleton class
        el.classList.add('is-loading');

        // Generate appropriate skeleton
        let skeletonHtml = '';
        switch (type) {
            case 'thread-list':
                skeletonHtml = generateThreadSkeletons(options.count || 5);
                break;
            case 'thread-detail':
                skeletonHtml = generateDetailSkeleton();
                break;
            default:
                // Generic skeleton
                skeletonHtml = `<div class="c-skeleton" style="width: 100%; height: ${options.height || '100px'};"></div>`;
        }

        el.innerHTML = skeletonHtml;
    }

    /**
     * Hide skeleton loading
     * @param {HTMLElement|string} container - Container element or selector
     * @param {string} newContent - Optional new content to display
     */
    function hide(container, newContent = null) {
        const el = typeof container === 'string' 
            ? document.querySelector(container) 
            : container;
        
        if (!el) return;

        el.classList.remove('is-loading');

        if (newContent !== null) {
            el.innerHTML = newContent;
        } else if (el.dataset.originalContent) {
            el.innerHTML = el.dataset.originalContent;
            delete el.dataset.originalContent;
        }
    }

    /**
     * Wrap an async function with skeleton loading
     * @param {HTMLElement|string} container - Container element or selector
     * @param {Function} asyncFn - Async function to execute
     * @param {string} type - Skeleton type
     * @returns {Promise} Result of asyncFn
     */
    async function withLoading(container, asyncFn, type = 'thread-list') {
        show(container, type);
        try {
            const result = await asyncFn();
            hide(container);
            return result;
        } catch (error) {
            hide(container);
            throw error;
        }
    }

    /**
     * Create inline loading indicator
     * @returns {HTMLElement} Loading indicator element
     */
    function createInlineLoader() {
        const loader = document.createElement('div');
        loader.className = 'c-inline-loader';
        loader.innerHTML = `
            <svg class="c-inline-loader__spinner" viewBox="0 0 50 50">
                <circle class="c-inline-loader__path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
            </svg>
        `;
        return loader;
    }

    /**
     * Show loading state on a button
     * @param {HTMLElement|string} button - Button element or selector
     * @param {string} loadingText - Optional loading text
     */
    function showButtonLoading(button, loadingText = 'Wird geladen...') {
        const el = typeof button === 'string' 
            ? document.querySelector(button) 
            : button;
        
        if (!el) return;

        el.dataset.originalText = el.innerHTML;
        el.disabled = true;
        el.classList.add('is-loading');
        el.innerHTML = `
            <svg class="c-button__spinner" viewBox="0 0 50 50" width="16" height="16">
                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5" stroke-dasharray="80" stroke-linecap="round">
                    <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/>
                </circle>
            </svg>
            ${loadingText}
        `;
    }

    /**
     * Hide loading state on a button
     * @param {HTMLElement|string} button - Button element or selector
     */
    function hideButtonLoading(button) {
        const el = typeof button === 'string' 
            ? document.querySelector(button) 
            : button;
        
        if (!el) return;

        el.disabled = false;
        el.classList.remove('is-loading');
        if (el.dataset.originalText) {
            el.innerHTML = el.dataset.originalText;
            delete el.dataset.originalText;
        }
    }

    // Public API
    return {
        show,
        hide,
        withLoading,
        createInlineLoader,
        showButtonLoading,
        hideButtonLoading,
        generateThreadSkeletons,
        generateDetailSkeleton
    };
})();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SkeletonLoader;
}
