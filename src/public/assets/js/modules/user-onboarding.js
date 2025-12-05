/**
 * User Onboarding Module
 * 
 * Provides an interactive tour/guide for new users.
 * Highlights UI elements and explains features step-by-step.
 * 
 * @module UserOnboarding
 */

const UserOnboarding = (function() {
    'use strict';

    // State
    let currentTour = null;
    let currentStep = 0;
    let overlay = null;
    let tooltip = null;

    // Tour definitions
    const tours = {
        inbox_welcome: {
            id: 'inbox_welcome',
            name: 'Willkommen in CI-Inbox',
            autoStart: true, // Start on first visit
            steps: [
                {
                    title: 'Willkommen bei CI-Inbox! 👋',
                    content: 'Lass uns gemeinsam die wichtigsten Funktionen entdecken.',
                    target: null, // No specific target = center modal
                    position: 'center'
                },
                {
                    title: 'Posteingang',
                    content: 'Hier siehst du alle eingehenden E-Mails. Klicke auf einen Thread, um ihn zu öffnen.',
                    target: '.c-thread-list',
                    position: 'right'
                },
                {
                    title: 'Thread-Details',
                    content: 'Wenn du einen Thread auswählst, werden hier alle E-Mails der Konversation angezeigt.',
                    target: '.c-inbox__thread-detail',
                    position: 'left'
                },
                {
                    title: 'Neue E-Mail',
                    content: 'Klicke hier, um eine neue E-Mail zu verfassen.',
                    target: '#new-email-btn',
                    position: 'bottom'
                },
                {
                    title: 'Suche',
                    content: 'Durchsuche alle E-Mails mit der globalen Suche. Tipp: Drücke <kbd>/</kbd> für Schnellzugriff!',
                    target: '#global-search',
                    position: 'bottom'
                },
                {
                    title: 'Filter',
                    content: 'Filtere Threads nach Status, Labels oder zugewiesenen Benutzern.',
                    target: '.c-sidebar__nav',
                    position: 'right'
                },
                {
                    title: 'Tastenkürzel',
                    content: 'CI-Inbox unterstützt viele Tastenkürzel. Drücke <kbd>?</kbd> für eine Übersicht!',
                    target: null,
                    position: 'center'
                },
                {
                    title: 'Los geht\'s! 🚀',
                    content: 'Du bist bereit! Bei Fragen findest du Hilfe unter dem <kbd>?</kbd> Menü.',
                    target: null,
                    position: 'center'
                }
            ]
        },
        
        compose_tour: {
            id: 'compose_tour',
            name: 'E-Mail Verfassen',
            autoStart: false,
            steps: [
                {
                    title: 'E-Mail Composer',
                    content: 'Hier kannst du neue E-Mails verfassen, antworten oder weiterleiten.',
                    target: '.c-email-composer',
                    position: 'top'
                },
                {
                    title: 'Empfänger',
                    content: 'Gib hier die E-Mail-Adressen der Empfänger ein. Trenne mehrere mit Komma.',
                    target: '.c-composer__to',
                    position: 'bottom'
                },
                {
                    title: 'Betreff',
                    content: 'Ein aussagekräftiger Betreff hilft beim Organisieren.',
                    target: '.c-composer__subject',
                    position: 'bottom'
                },
                {
                    title: 'Senden',
                    content: 'Klicke hier zum Senden. Tipp: <kbd>Ctrl+Enter</kbd> sendet sofort!',
                    target: '.c-composer__send',
                    position: 'top'
                }
            ]
        },
        
        labels_tour: {
            id: 'labels_tour',
            name: 'Labels verstehen',
            autoStart: false,
            steps: [
                {
                    title: 'Labels',
                    content: 'Labels helfen dir, Threads zu kategorisieren und wiederzufinden.',
                    target: null,
                    position: 'center'
                },
                {
                    title: 'Label hinzufügen',
                    content: 'Drücke <kbd>l</kbd> oder klicke auf das Label-Icon, um Labels zu verwalten.',
                    target: '.c-thread-item__meta',
                    position: 'top'
                },
                {
                    title: 'Nach Labels filtern',
                    content: 'Klicke hier, um nur Threads mit bestimmten Labels anzuzeigen.',
                    target: '#labels-filter-toggle',
                    position: 'right'
                }
            ]
        }
    };

    /**
     * Initialize onboarding module
     */
    function init() {
        createOverlay();
        createTooltip();
        
        // Check if user should see welcome tour
        checkAutoStart();
        
        console.log('[Onboarding] Initialized');
    }

    /**
     * Create overlay element
     */
    function createOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'c-onboarding-overlay';
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                // Click outside tooltip = skip
                skipTour();
            }
        });
        document.body.appendChild(overlay);
    }

    /**
     * Create tooltip element
     */
    function createTooltip() {
        tooltip = document.createElement('div');
        tooltip.className = 'c-onboarding-tooltip';
        tooltip.innerHTML = `
            <div class="c-onboarding-tooltip__header">
                <span class="c-onboarding-tooltip__step">1/8</span>
                <button class="c-onboarding-tooltip__close" aria-label="Tour beenden">&times;</button>
            </div>
            <h3 class="c-onboarding-tooltip__title"></h3>
            <p class="c-onboarding-tooltip__content"></p>
            <div class="c-onboarding-tooltip__footer">
                <button class="c-onboarding-tooltip__skip">Überspringen</button>
                <div class="c-onboarding-tooltip__nav">
                    <button class="c-onboarding-tooltip__prev">Zurück</button>
                    <button class="c-onboarding-tooltip__next">Weiter</button>
                </div>
            </div>
        `;
        
        // Event listeners
        tooltip.querySelector('.c-onboarding-tooltip__close').addEventListener('click', skipTour);
        tooltip.querySelector('.c-onboarding-tooltip__skip').addEventListener('click', skipTour);
        tooltip.querySelector('.c-onboarding-tooltip__prev').addEventListener('click', prevStep);
        tooltip.querySelector('.c-onboarding-tooltip__next').addEventListener('click', nextStep);
        
        document.body.appendChild(tooltip);
    }

    /**
     * Check if any tour should auto-start
     */
    async function checkAutoStart() {
        // Check localStorage for completed tours
        const completedTours = JSON.parse(localStorage.getItem('ci_inbox_completed_tours') || '[]');
        
        // Check if welcome tour should start
        if (!completedTours.includes('inbox_welcome')) {
            // Wait a bit for page to fully load
            setTimeout(() => {
                startTour('inbox_welcome');
            }, 1000);
        }
    }

    /**
     * Start a tour
     * @param {string} tourId - Tour ID from tours object
     */
    function startTour(tourId) {
        const tour = tours[tourId];
        if (!tour) {
            console.error('[Onboarding] Tour not found:', tourId);
            return;
        }
        
        currentTour = tour;
        currentStep = 0;
        
        // Show overlay
        overlay.classList.add('is-active');
        
        // Show first step
        showStep(0);
        
        console.log('[Onboarding] Started tour:', tourId);
    }

    /**
     * Show a specific step
     * @param {number} stepIndex - Step index
     */
    function showStep(stepIndex) {
        if (!currentTour || stepIndex < 0 || stepIndex >= currentTour.steps.length) {
            return;
        }
        
        currentStep = stepIndex;
        const step = currentTour.steps[stepIndex];
        
        // Update tooltip content
        tooltip.querySelector('.c-onboarding-tooltip__step').textContent = 
            `${stepIndex + 1}/${currentTour.steps.length}`;
        tooltip.querySelector('.c-onboarding-tooltip__title').textContent = step.title;
        tooltip.querySelector('.c-onboarding-tooltip__content').innerHTML = step.content;
        
        // Update navigation buttons
        const prevBtn = tooltip.querySelector('.c-onboarding-tooltip__prev');
        const nextBtn = tooltip.querySelector('.c-onboarding-tooltip__next');
        
        prevBtn.style.visibility = stepIndex > 0 ? 'visible' : 'hidden';
        nextBtn.textContent = stepIndex === currentTour.steps.length - 1 ? 'Fertig' : 'Weiter';
        
        // Position tooltip
        positionTooltip(step);
        
        // Highlight target element
        highlightTarget(step.target);
        
        // Show tooltip
        tooltip.classList.add('is-visible');
    }

    /**
     * Position tooltip relative to target
     * @param {object} step - Step configuration
     */
    function positionTooltip(step) {
        const target = step.target ? document.querySelector(step.target) : null;
        
        // Reset position classes
        tooltip.className = 'c-onboarding-tooltip is-visible';
        
        if (!target || step.position === 'center') {
            // Center on screen
            tooltip.classList.add('c-onboarding-tooltip--center');
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
            return;
        }
        
        const targetRect = target.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const padding = 16;
        
        let top, left;
        
        switch (step.position) {
            case 'top':
                top = targetRect.top - tooltipRect.height - padding;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                tooltip.classList.add('c-onboarding-tooltip--top');
                break;
                
            case 'bottom':
                top = targetRect.bottom + padding;
                left = targetRect.left + (targetRect.width / 2) - (tooltipRect.width / 2);
                tooltip.classList.add('c-onboarding-tooltip--bottom');
                break;
                
            case 'left':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.left - tooltipRect.width - padding;
                tooltip.classList.add('c-onboarding-tooltip--left');
                break;
                
            case 'right':
                top = targetRect.top + (targetRect.height / 2) - (tooltipRect.height / 2);
                left = targetRect.right + padding;
                tooltip.classList.add('c-onboarding-tooltip--right');
                break;
        }
        
        // Keep within viewport
        top = Math.max(padding, Math.min(top, window.innerHeight - tooltipRect.height - padding));
        left = Math.max(padding, Math.min(left, window.innerWidth - tooltipRect.width - padding));
        
        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
        tooltip.style.transform = 'none';
    }

    /**
     * Highlight target element
     * @param {string} selector - CSS selector for target
     */
    function highlightTarget(selector) {
        // Remove previous highlights
        document.querySelectorAll('.c-onboarding-highlight').forEach(el => {
            el.classList.remove('c-onboarding-highlight');
        });
        
        if (!selector) return;
        
        const target = document.querySelector(selector);
        if (target) {
            target.classList.add('c-onboarding-highlight');
            
            // Scroll into view if needed
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Go to next step
     */
    function nextStep() {
        if (!currentTour) return;
        
        if (currentStep >= currentTour.steps.length - 1) {
            // Last step - complete tour
            completeTour();
        } else {
            showStep(currentStep + 1);
        }
    }

    /**
     * Go to previous step
     */
    function prevStep() {
        if (currentStep > 0) {
            showStep(currentStep - 1);
        }
    }

    /**
     * Skip/end tour without completing
     */
    function skipTour() {
        endTour(false);
    }

    /**
     * Complete tour successfully
     */
    function completeTour() {
        endTour(true);
    }

    /**
     * End tour
     * @param {boolean} completed - Whether tour was completed
     */
    function endTour(completed) {
        if (!currentTour) return;
        
        const tourId = currentTour.id;
        
        // Save to localStorage
        const completedTours = JSON.parse(localStorage.getItem('ci_inbox_completed_tours') || '[]');
        if (!completedTours.includes(tourId)) {
            completedTours.push(tourId);
            localStorage.setItem('ci_inbox_completed_tours', JSON.stringify(completedTours));
        }
        
        // Save to server (optional)
        saveProgress(tourId, completed);
        
        // Hide overlay and tooltip
        overlay.classList.remove('is-active');
        tooltip.classList.remove('is-visible');
        
        // Remove highlights
        document.querySelectorAll('.c-onboarding-highlight').forEach(el => {
            el.classList.remove('c-onboarding-highlight');
        });
        
        console.log('[Onboarding] Tour ended:', tourId, completed ? '(completed)' : '(skipped)');
        
        // Reset state
        currentTour = null;
        currentStep = 0;
        
        // Show completion message if completed
        if (completed && typeof KeyboardShortcuts !== 'undefined') {
            KeyboardShortcuts.showToast('Tour abgeschlossen! 🎉', 'success');
        }
    }

    /**
     * Save progress to server
     */
    async function saveProgress(tourId, completed) {
        try {
            await fetch('/api/user/onboarding/progress', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tour_id: tourId,
                    completed,
                    current_step: currentStep
                })
            });
        } catch (error) {
            console.warn('[Onboarding] Failed to save progress:', error);
        }
    }

    /**
     * Reset all tours (for testing)
     */
    function resetAllTours() {
        localStorage.removeItem('ci_inbox_completed_tours');
        console.log('[Onboarding] All tours reset');
    }

    /**
     * Check if a tour has been completed
     */
    function isTourCompleted(tourId) {
        const completedTours = JSON.parse(localStorage.getItem('ci_inbox_completed_tours') || '[]');
        return completedTours.includes(tourId);
    }

    // ==========================================================================
    // PUBLIC API
    // ==========================================================================

    return {
        init,
        startTour,
        skipTour,
        nextStep,
        prevStep,
        isTourCompleted,
        resetAllTours,
        
        // Expose available tours
        tours: Object.keys(tours)
    };
})();

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    UserOnboarding.init();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UserOnboarding;
}
