/**
 * Centralized Error Handler Module
 * 
 * Provides standardized error handling and user feedback for all API operations.
 * Integrates with UiComponents toast notifications.
 * 
 * @module ErrorHandler
 * @since 2025-12-06 (M3 Production Readiness)
 */

const ErrorHandler = (function() {
    'use strict';

    // ============================================================================
    // ERROR TYPES
    // ============================================================================
    
    const ErrorTypes = {
        NETWORK: 'network',
        API: 'api',
        VALIDATION: 'validation',
        AUTHENTICATION: 'authentication',
        PERMISSION: 'permission',
        NOT_FOUND: 'not_found',
        SERVER: 'server',
        UNKNOWN: 'unknown'
    };

    // ============================================================================
    // ERROR MESSAGES (User-Friendly)
    // ============================================================================
    
    const DefaultMessages = {
        [ErrorTypes.NETWORK]: 'Netzwerkfehler. Bitte prüfe deine Internetverbindung.',
        [ErrorTypes.API]: 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.',
        [ErrorTypes.VALIDATION]: 'Ungültige Eingabe. Bitte überprüfe deine Daten.',
        [ErrorTypes.AUTHENTICATION]: 'Sitzung abgelaufen. Bitte melde dich erneut an.',
        [ErrorTypes.PERMISSION]: 'Keine Berechtigung für diese Aktion.',
        [ErrorTypes.NOT_FOUND]: 'Die angeforderte Ressource wurde nicht gefunden.',
        [ErrorTypes.SERVER]: 'Serverfehler. Bitte versuche es später erneut.',
        [ErrorTypes.UNKNOWN]: 'Ein unbekannter Fehler ist aufgetreten.'
    };

    // ============================================================================
    // ERROR DETECTION
    // ============================================================================
    
    /**
     * Detect error type from error object or HTTP status
     * @param {Error|Response|object} error - Error object
     * @returns {string} Error type from ErrorTypes
     */
    function detectErrorType(error) {
        // Network error (fetch failed)
        if (error instanceof TypeError && error.message.includes('fetch')) {
            return ErrorTypes.NETWORK;
        }
        
        // HTTP Response error
        if (error.status) {
            if (error.status === 401) return ErrorTypes.AUTHENTICATION;
            if (error.status === 403) return ErrorTypes.PERMISSION;
            if (error.status === 404) return ErrorTypes.NOT_FOUND;
            if (error.status === 422) return ErrorTypes.VALIDATION;
            if (error.status >= 500) return ErrorTypes.SERVER;
            return ErrorTypes.API;
        }
        
        // API error response
        if (error.error) {
            if (error.error.includes('auth') || error.error.includes('token')) {
                return ErrorTypes.AUTHENTICATION;
            }
            if (error.error.includes('permission') || error.error.includes('forbidden')) {
                return ErrorTypes.PERMISSION;
            }
            if (error.error.includes('not found')) {
                return ErrorTypes.NOT_FOUND;
            }
            if (error.error.includes('validation') || error.error.includes('invalid')) {
                return ErrorTypes.VALIDATION;
            }
        }
        
        return ErrorTypes.UNKNOWN;
    }

    /**
     * Extract user-friendly error message
     * @param {Error|object} error - Error object
     * @param {string} defaultMessage - Fallback message
     * @returns {string} User-friendly error message
     */
    function getUserMessage(error, defaultMessage = null) {
        // API error with message
        if (error.error) {
            return error.error;
        }
        
        // API error with message field
        if (error.message && typeof error.message === 'string') {
            // Skip technical error messages
            if (!error.message.includes('fetch') && 
                !error.message.includes('undefined') &&
                !error.message.includes('null')) {
                return error.message;
            }
        }
        
        // Use default message for error type
        const errorType = detectErrorType(error);
        if (defaultMessage) {
            return defaultMessage;
        }
        
        return DefaultMessages[errorType];
    }

    // ============================================================================
    // ERROR HANDLING
    // ============================================================================
    
    /**
     * Handle API error with user feedback
     * @param {Error|object} error - Error object
     * @param {object} options - Handling options
     * @param {string} options.context - Context description (e.g., "Thread laden")
     * @param {string} options.message - Custom user message
     * @param {boolean} options.silent - Don't show toast (default: false)
     * @param {boolean} options.retry - Show retry button (default: false)
     * @param {function} options.onRetry - Retry callback
     * @param {boolean} options.logToConsole - Log to console (default: true)
     * @returns {object} Processed error info
     */
    function handleError(error, options = {}) {
        const {
            context = '',
            message: customMessage = null,
            silent = false,
            retry = false,
            onRetry = null,
            logToConsole = true
        } = options;

        // Detect error type
        const errorType = detectErrorType(error);
        const userMessage = customMessage || getUserMessage(error);
        
        // Build full message with context
        const fullMessage = context 
            ? `${context}: ${userMessage}` 
            : userMessage;

        // Log to console for debugging
        if (logToConsole) {
            const logPrefix = context ? `[${context}]` : '[ErrorHandler]';
            console.error(logPrefix, 'Error:', error);
            console.error(logPrefix, 'Type:', errorType);
            console.error(logPrefix, 'Message:', userMessage);
        }

        // Show user feedback (unless silent)
        if (!silent) {
            // Check if UiComponents is available
            if (typeof window.UiComponents !== 'undefined') {
                window.UiComponents.showErrorMessage(fullMessage);
            } else if (typeof window.showErrorMessage !== 'undefined') {
                window.showErrorMessage(fullMessage);
            } else {
                // Fallback to alert
                alert(`Fehler: ${fullMessage}`);
            }
        }

        // Handle authentication errors (redirect to login)
        if (errorType === ErrorTypes.AUTHENTICATION && !options.noRedirect) {
            setTimeout(() => {
                window.location.href = '/login.php?expired=1';
            }, 2000);
        }

        // Return processed error info
        return {
            type: errorType,
            message: userMessage,
            fullMessage: fullMessage,
            originalError: error
        };
    }

    /**
     * Handle API fetch error (convenience wrapper)
     * @param {Response} response - Fetch response
     * @param {string} context - Context description
     * @returns {Promise<object>} Processed error
     */
    async function handleFetchError(response, context = '') {
        try {
            const data = await response.json();
            return handleError(data, { context });
        } catch (parseError) {
            // JSON parse failed, use status text
            return handleError({
                error: response.statusText || 'Server error',
                status: response.status
            }, { context });
        }
    }

    /**
     * Wrap async function with automatic error handling
     * @param {function} asyncFn - Async function to wrap
     * @param {object} options - Error handling options
     * @returns {function} Wrapped function
     */
    function wrapAsync(asyncFn, options = {}) {
        return async function(...args) {
            try {
                return await asyncFn(...args);
            } catch (error) {
                handleError(error, options);
                throw error; // Re-throw for caller to handle if needed
            }
        };
    }

    /**
     * Show validation errors for form fields
     * @param {object} errors - Field errors object {field: message}
     * @param {string} formSelector - Form selector
     */
    function showValidationErrors(errors, formSelector = null) {
        if (!errors || typeof errors !== 'object') return;

        // Find form element
        const form = formSelector 
            ? document.querySelector(formSelector)
            : null;

        // Show each field error
        Object.entries(errors).forEach(([field, message]) => {
            // Try to find input field
            const input = form 
                ? form.querySelector(`[name="${field}"], #${field}`)
                : document.querySelector(`[name="${field}"], #${field}`);

            if (input) {
                // Add error class
                input.classList.add('has-error');
                
                // Create/update error message
                let errorEl = input.parentElement.querySelector('.c-input-error');
                if (!errorEl) {
                    errorEl = document.createElement('div');
                    errorEl.className = 'c-input-error';
                    input.parentElement.appendChild(errorEl);
                }
                errorEl.textContent = message;

                // Remove error on input
                input.addEventListener('input', function removeError() {
                    input.classList.remove('has-error');
                    if (errorEl) errorEl.remove();
                    input.removeEventListener('input', removeError);
                }, { once: true });
            } else {
                // Field not found, show as toast
                if (typeof window.showErrorMessage !== 'undefined') {
                    window.showErrorMessage(`${field}: ${message}`);
                }
            }
        });
    }

    // ============================================================================
    // ERROR RECOVERY
    // ============================================================================
    
    /**
     * Retry operation with exponential backoff
     * @param {function} operation - Async operation to retry
     * @param {object} options - Retry options
     * @returns {Promise} Operation result
     */
    async function retryWithBackoff(operation, options = {}) {
        const {
            maxRetries = 3,
            initialDelay = 1000,
            maxDelay = 10000,
            backoffMultiplier = 2,
            context = 'Operation'
        } = options;

        let lastError;
        
        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                return await operation();
            } catch (error) {
                lastError = error;
                
                // Don't retry authentication/permission errors
                const errorType = detectErrorType(error);
                if (errorType === ErrorTypes.AUTHENTICATION || 
                    errorType === ErrorTypes.PERMISSION) {
                    throw error;
                }

                if (attempt < maxRetries) {
                    const delay = Math.min(
                        initialDelay * Math.pow(backoffMultiplier, attempt),
                        maxDelay
                    );
                    
                    console.warn(`[ErrorHandler] ${context} failed, retrying in ${delay}ms (attempt ${attempt + 1}/${maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                } else {
                    console.error(`[ErrorHandler] ${context} failed after ${maxRetries} retries`);
                }
            }
        }

        throw lastError;
    }

    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Constants
        ErrorTypes,
        
        // Error handling
        handleError,
        handleFetchError,
        wrapAsync,
        
        // Helpers
        detectErrorType,
        getUserMessage,
        showValidationErrors,
        
        // Recovery
        retryWithBackoff
    };
})();

// Make globally available
window.ErrorHandler = ErrorHandler;
