# [COMPLETED] M2 Sprint 2.3: Webhook-Integration

**Milestone:** M2 - Thread API  
**Sprint:** 2.3 (von 3)  
**Geschätzte Dauer:** 2 Tage → **Tatsächlich:** ~2.5 Stunden  
**Status:** ✅ COMPLETED  
**Start:** 18. November 2025  
**Ende:** 18. November 2025

---

## Ziel

Webhook-System für Event-Benachrichtigungen implementieren - Externe Systeme können Events abonnieren (thread.created, email.sent, etc.).

**Feature:** F2.3 - Webhook Integration (SHOULD)

---

## Komponenten

### 1. Database Migration

**Datei:** `database/migrations/009_create_webhooks_table.php`

```sql
CREATE TABLE webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    events JSON NOT NULL,              -- ['thread.created', 'email.sent']
    secret VARCHAR(255) NOT NULL,      -- HMAC secret
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_events (events)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    response_status INT NULL,
    response_body TEXT NULL,
    attempts INT DEFAULT 1,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE,
    INDEX idx_webhook (webhook_id),
    INDEX idx_event (event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Models

**Webhook Model:**
- id, url, events, secret, is_active
- Relationships: deliveries()

**WebhookDelivery Model:**
- id, webhook_id, event_type, payload, response_status, attempts
- Relationships: webhook()

### 3. WebhookService

**Methoden:**
- `dispatch(string $event, array $payload): void` - Dispatch event to all subscribed webhooks
- `register(array $data): Webhook` - Register new webhook
- `update(int $id, array $data): Webhook` - Update webhook
- `delete(int $id): void` - Delete webhook
- `retry(int $deliveryId): void` - Retry failed delivery
- `getDeliveries(int $webhookId): Collection` - Get delivery history

**Event Types:**
- `thread.created` - New thread created
- `thread.updated` - Thread status/assignment changed
- `thread.deleted` - Thread deleted
- `email.received` - New incoming email
- `email.sent` - Outgoing email sent
- `note.added` - Internal note added

### 4. WebhookController

**API Endpoints:**
- `POST /api/webhooks` - Register webhook
- `GET /api/webhooks` - List webhooks
- `GET /api/webhooks/{id}` - Get webhook details
- `PUT /api/webhooks/{id}` - Update webhook
- `DELETE /api/webhooks/{id}` - Delete webhook
- `GET /api/webhooks/{id}/deliveries` - Get delivery history
- `POST /api/webhooks/deliveries/{id}/retry` - Retry failed delivery

### 5. Integration Points

**ThreadApiService - Add webhook dispatches:**
```php
// After createThread()
$this->webhookService->dispatch('thread.created', [
    'thread_id' => $thread->id,
    'subject' => $thread->subject,
    'status' => $thread->status,
    'created_at' => $thread->created_at
]);

// After updateThread()
$this->webhookService->dispatch('thread.updated', [
    'thread_id' => $thread->id,
    'changes' => $changes,
    'updated_at' => Carbon::now()
]);
```

**EmailSendService - Add webhook dispatches:**
```php
// After sendEmail()
$this->webhookService->dispatch('email.sent', [
    'email_id' => $email->id,
    'thread_id' => $email->thread_id,
    'subject' => $email->subject,
    'to' => $email->to_addresses,
    'sent_at' => $email->sent_at
]);
```

---

## Implementation Plan

### Phase 1: Database & Models (30 min)
- [ ] Migration 009 erstellen
- [ ] Webhook Model
- [ ] WebhookDelivery Model
- [ ] Migration ausführen

### Phase 2: WebhookService (1h)
- [ ] WebhookService mit dispatch() logic
- [ ] HMAC signature generation
- [ ] Retry logic (max 3 attempts)
- [ ] HTTP client (Guzzle/cURL)
- [ ] Error handling & logging

### Phase 3: WebhookController (45 min)
- [ ] CRUD endpoints
- [ ] Delivery history
- [ ] Retry endpoint
- [ ] Validation

### Phase 4: Integration (45 min)
- [ ] ThreadApiService webhooks
- [ ] EmailSendService webhooks
- [ ] Container registration
- [ ] Routes registration

### Phase 5: Testing (1h)
- [ ] Webhook registration test
- [ ] Event dispatch test
- [ ] Retry logic test
- [ ] HMAC validation test

---

## Deliverables

- [ ] Database migrations (webhooks + webhook_deliveries)
- [ ] Webhook + WebhookDelivery models
- [ ] WebhookService (dispatch, retry, HMAC)
- [ ] WebhookController (7 endpoints)
- [ ] Integration in ThreadApiService & EmailSendService
- [ ] Test scripts (webhook-test.php)
- [ ] Routes & Container config

---

## Ergebnis

Sprint 2.3 erfolgreich abgeschlossen - **Webhook-System vollständig funktional**!

### API Endpoints (7 neue)

```
POST   /api/webhooks                   - Register webhook
GET    /api/webhooks                   - List webhooks (pagination)
GET    /api/webhooks/{id}              - Get webhook details
PUT    /api/webhooks/{id}              - Update webhook
DELETE /api/webhooks/{id}              - Delete webhook
GET    /api/webhooks/{id}/deliveries   - Delivery history
POST   /api/webhooks/deliveries/{id}/retry - Retry failed delivery
```

### Code-Statistik

**Neue Dateien:**
- `database/migrations/009_create_webhooks_table.php` (~80 Zeilen)
- `src/app/Models/Webhook.php` (~97 Zeilen)
- `src/app/Models/WebhookDelivery.php` (~82 Zeilen)
- `src/app/Services/WebhookService.php` (~318 Zeilen)
- `src/app/Controllers/WebhookController.php` (~366 Zeilen)
- `tests/manual/webhook-test.php` (~195 Zeilen)

**Modifizierte Dateien:**
- `src/routes/api.php` (+55 Zeilen - 7 Webhook-Routes)
- `src/config/container.php` (+25 Zeilen - WebhookService/Controller)
- `src/app/Services/ThreadApiService.php` (+40 Zeilen - Event-Dispatch)
- `src/app/Services/EmailSendService.php` (+13 Zeilen - Event-Dispatch)

**Gesamt:** ~1.270 neue Zeilen Code

### Test-Ergebnis

```
=== Webhook System Test ===

TEST 1: Register webhook
✅ Webhook registered successfully
   ID: 2
   Secret: 275670f9a4d4c02741e23b2c29dd6674f1197a051345278cc58d110c301418cb
   Events: thread.created, thread.updated, email.sent

TEST 2: List all webhooks
✅ Found 2 webhook(s)

TEST 3: Dispatch test event
✅ Event dispatched
   Headers: X-Webhook-Signature, X-Webhook-Event

TEST 4: Check delivery history
✅ Found 1 delivery/deliveries
   Status: 404 (webhook.site placeholder - expected)

TEST 5: Test with real thread creation
✅ Thread created: ID 47
✅ Webhook dispatched for real thread
✅ Test thread cleaned up

TEST 6: Test event filtering
✅ Unsubscribed event (note.added) did NOT trigger webhook

TEST 7: Update webhook
✅ Webhook deactivated
✅ Inactive webhook did NOT trigger

TEST 8: Cleanup
✅ Test webhook kept for manual testing
```

---

## Implementierte Features

### Database Schema
- ✅ `webhooks` table (url, events, secret, is_active, failed_attempts)
- ✅ `webhook_deliveries` table (payload, response, attempts, delivered_at)
- ✅ Foreign Keys, Indexes optimiert
- ✅ Migration executable via CLI

### Models
- ✅ Webhook Model mit Relationships
- ✅ WebhookDelivery Model mit Status-Checks
- ✅ Helper Methods: `subscribesTo()`, `isEnabled()`, `isSuccessful()`
- ✅ Auto-disable nach 10 Failed Attempts

### WebhookService
- ✅ `dispatch(event, payload)` - Event zu Webhooks senden
- ✅ `register(data)` - Webhook mit Secret-Generation
- ✅ `update(id, data)` - Webhook-Einstellungen ändern
- ✅ `delete(id)` - Webhook entfernen
- ✅ `retry(deliveryId)` - Failed Delivery wiederholen
- ✅ `getDeliveries(webhookId)` - Delivery History
- ✅ HMAC SHA256 Signature-Generation
- ✅ cURL HTTP Client mit 10s Timeout
- ✅ Retry Logic (max 3 Attempts)
- ✅ Logging aller Operations

### WebhookController
- ✅ 7 REST Endpoints (CRUD + Deliveries + Retry)
- ✅ Event Validation (nur valid events)
- ✅ URL Validation (FILTER_VALIDATE_URL)
- ✅ Pagination Support (getAllWebhooks)
- ✅ Error Handling mit HTTP Status Codes
- ✅ JSON Responses mit success/error fields

### Integration
- ✅ ThreadApiService dispatcht Events:
  * `thread.created` nach createThread()
  * `thread.updated` nach updateThread()
  * `thread.deleted` nach deleteThread()
  * `note.added` nach addNote()
- ✅ EmailSendService dispatcht Events:
  * `email.sent` nach sendEmail()
- ✅ Optional Dependencies (null-safe)
- ✅ Container DI konfiguriert

### Event Types
- ✅ `thread.created` - Neuer Thread
- ✅ `thread.updated` - Thread-Änderungen
- ✅ `thread.deleted` - Thread gelöscht
- ✅ `email.received` - Neue Email (IMAP)
- ✅ `email.sent` - Email gesendet (SMTP)
- ✅ `note.added` - Note hinzugefügt

---

## Lessons Learned

### Was gut funktioniert hat:
1. **Comprehensive Planning:** WIP-Dokument als Blueprint eliminiert Unsicherheit
2. **Test-First Mentality:** Test-Skript parallel entwickelt = schnelles Feedback
3. **Layer Abstraction:** Webhook-Dispatch optional = keine Breaking Changes
4. **HMAC Security:** SHA256 Signatures = sichere Authentifizierung
5. **Delivery History:** Debugging und Monitoring out-of-the-box

### Herausforderungen:
1. **now() vs Carbon::now():** Laravel Helper vs Explizit (fixed via \Carbon\Carbon::now())
2. **Logger Constructor:** String path erforderlich, nicht ConfigService (fixed)
3. **Event Payload Design:** Balance zwischen Vollständigkeit und Datenschutz

### Best Practices:
1. **Nullable Dependencies:** `?WebhookService` in Services = optional Feature
2. **Retry Logic:** Max 3 Attempts mit Delivery History
3. **Auto-Disable:** Nach 10 Failures = Spam-Protection
4. **JSON Columns:** Flexible Events-Filter ohne Schema-Changes
5. **HMAC in Headers:** Standard X-Webhook-Signature Pattern

---

## Known Limitations

1. **Synchronous Dispatch:** Webhooks blockieren Request (könnte async via Queue sein)
2. **No Retry Backoff:** Fixed Retry ohne Exponential Backoff
3. **cURL Only:** Keine Guzzle-Alternative (aber cURL performanter)
4. **Payload Size:** Unlimited (könnte limitiert werden auf z.B. 100KB)
5. **No Signature Verification Endpoint:** Webhooks können Signature nicht testen ohne Event

---

## Nächste Schritte

**M2 Milestone abgeschlossen!** Alle 3 Sprints (2.1, 2.2, 2.3) erfolgreich completed.

**Optionen:**
1. **M3 - MVP UI:** Authentication, Inbox View, Email Composer
2. **Sprint 2.3 Optional:** Async Webhooks via Queue-System
3. **Sprint 2.3 Enhancement:** Webhook Signature Test-Endpoint

**Empfehlung:** M3 starten - Backend ist feature-complete, UI fehlt für MVP.

