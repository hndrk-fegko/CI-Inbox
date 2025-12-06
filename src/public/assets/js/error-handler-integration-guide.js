/**
 * Error Handler Integration Examples
 * 
 * Shows how to integrate ErrorHandler into existing code.
 * This file is for documentation and can be deleted after integration.
 * 
 * @since 2025-12-06 (M3 Production Readiness)
 */

// =============================================================================
// PATTERN 1: Replace console.error + alert with ErrorHandler
// =============================================================================

// ❌ OLD PATTERN:
/*
try {
    const response = await fetch('/api/threads');
    const data = await response.json();
    if (!data.success) {
        console.error('[Feature] Error:', data.error);
        alert('Error: ' + data.error);
    }
} catch (error) {
    console.error('[Feature] Failed:', error);
    alert('Network error');
}
*/

// ✅ NEW PATTERN:
try {
    const response = await fetch('/api/threads');
    const data = await response.json();
    if (!data.success) {
        ErrorHandler.handleError(data, {
            context: 'Thread laden'
        });
    }
} catch (error) {
    ErrorHandler.handleError(error, {
        context: 'Thread laden',
        retry: true,
        onRetry: () => fetchThreads()
    });
}

// =============================================================================
// PATTERN 2: Wrap async functions for automatic error handling
// =============================================================================

// ❌ OLD PATTERN:
/*
async function loadData() {
    try {
        const response = await fetch('/api/data');
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load data');
        throw error;
    }
}
*/

// ✅ NEW PATTERN:
const loadData = ErrorHandler.wrapAsync(async function() {
    const response = await fetch('/api/data');
    return await response.json();
}, {
    context: 'Daten laden'
});

// =============================================================================
// PATTERN 3: Integration with existing showAlert() function
// =============================================================================

// For admin-settings.js which uses showAlert():
function showAlert(containerId, message, type) {
    // OLD: Only shows in modal/page specific location
    // Can keep for backwards compatibility
    const alertEl = document.getElementById(containerId);
    if (alertEl) {
        alertEl.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        alertEl.classList.remove('d-none');
    }
    
    // NEW: Also show toast notification for user feedback
    if (type === 'error') {
        if (typeof UiComponents !== 'undefined') {
            UiComponents.showErrorMessage(message);
        }
    } else if (type === 'success') {
        if (typeof UiComponents !== 'undefined') {
            UiComponents.showSuccessMessage(message);
        }
    }
}

// =============================================================================
// PATTERN 4: Retry with backoff for network operations
// =============================================================================

// ❌ OLD PATTERN:
/*
async function fetchWithRetry() {
    for (let i = 0; i < 3; i++) {
        try {
            return await fetch('/api/data');
        } catch (error) {
            if (i === 2) throw error;
            await new Promise(r => setTimeout(r, 1000 * (i + 1)));
        }
    }
}
*/

// ✅ NEW PATTERN:
const fetchWithRetry = () => ErrorHandler.retryWithBackoff(
    () => fetch('/api/data'),
    {
        context: 'API Verbindung',
        maxRetries: 3,
        initialDelay: 1000
    }
);

// =============================================================================
// PATTERN 5: Form validation errors
// =============================================================================

// ❌ OLD PATTERN:
/*
if (!formData.email) {
    document.getElementById('email-error').textContent = 'Email required';
    document.getElementById('email').classList.add('error');
}
*/

// ✅ NEW PATTERN:
ErrorHandler.showValidationErrors({
    email: 'Email ist erforderlich',
    password: 'Passwort muss mindestens 8 Zeichen haben'
}, '#my-form');

// =============================================================================
// PATTERN 6: API fetch with automatic error handling
// =============================================================================

// ✅ BEST PRACTICE - Complete example:
async function saveThreadStatus(threadId, status) {
    try {
        const response = await fetch(`/api/threads/${threadId}/status`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status })
        });

        if (!response.ok) {
            // Let ErrorHandler parse the error response
            await ErrorHandler.handleFetchError(response, 'Status speichern');
            return false;
        }

        const data = await response.json();
        
        if (!data.success) {
            ErrorHandler.handleError(data, {
                context: 'Status speichern'
            });
            return false;
        }

        // Success!
        UiComponents.showSuccessMessage('Status erfolgreich aktualisiert');
        return true;

    } catch (error) {
        // Network error or other exception
        ErrorHandler.handleError(error, {
            context: 'Status speichern',
            retry: true,
            onRetry: () => saveThreadStatus(threadId, status)
        });
        return false;
    }
}

// =============================================================================
// INTEGRATION CHECKLIST
// =============================================================================

/*
To integrate ErrorHandler into existing files:

1. ✅ Add script tag to HTML:
   <script src="/assets/js/modules/error-handler.js<?= asset_version() ?>"></script>
   (Load before other modules that use it)

2. ✅ Replace patterns:
   - console.error() + alert() → ErrorHandler.handleError()
   - try/catch blocks → ErrorHandler.wrapAsync() or handleError()
   - Form validation → ErrorHandler.showValidationErrors()

3. ✅ Update showAlert() function (if exists):
   Add toast notification alongside existing alert display

4. ✅ Add accessibility announcements:
   ErrorHandler automatically logs to console
   Consider adding Accessibility.announce() for screen readers

5. ✅ Test error scenarios:
   - Network failure (disconnect)
   - API errors (400, 404, 500)
   - Validation errors
   - Authentication expiry (401)
   - Permission denied (403)

6. ✅ Monitor console for errors:
   ErrorHandler logs all errors with context
   Check browser console during testing
*/
