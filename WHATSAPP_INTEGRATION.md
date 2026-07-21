# WhatsApp Integration - SuiteCRM 7.15.1

## Overview
This module integrates SuiteCRM with the WhatsApp-API service to capture incoming WhatsApp messages and associate them with Contacts for follow-up and tracking.

**Architecture:**
- WhatsApp-API (Node.js) sends webhook POST to SuiteCRM when messages arrive
- SuiteCRM receives webhook, validates token, finds/creates Contact
- Messages stored in `whatsapp_messages` table linked to Contact
- Automatic contact tracking via phone number matching

## Configuration

### 1. Environment Variables

Set these in your `.env` file (or docker-compose for local development):

```bash
# WhatsApp API Integration
WHATSAPP_API_URL=https://wa-api.grupoamericainterior.com.ar
WHATSAPP_WEBHOOK_TOKEN=your_secure_token_here
WHATSAPP_WEBHOOK_SECRET=your_webhook_secret_here

# SuiteCRM Instance (for whatsapp-api to reach back)
SUITECRM_URL=https://crm.grupoamericainterior.com.ar

# Log level
WHATSAPP_LOG_LEVEL=info
```

### 2. Setup WhatsApp Module

Run the setup script to create tables and configure webhook:

```bash
# Local (inside container)
docker exec suitecrm_app php custom/modules/WhatsAppMessages/setup_whatsapp.php

# Or directly from CLI
cd /path/to/suiteCRM
php custom/modules/WhatsAppMessages/setup_whatsapp.php
```

Output will include:
```
[OK] whatsapp_messages table created/verified
[OK] Contacts table extended with WhatsApp fields
[OK] Generated and stored webhook token in config.php
    Webhook Token: abc123def456...
```

### 3. Configure WhatsApp-API Webhook

In the whatsapp-api `.env` file, set:

```bash
WEBHOOK_URL=https://crm.grupoamericainterior.com.ar/api/v8/WhatsApp/webhook
WEBHOOK_TOKEN=abc123def456...  # Same token from step 2
```

## Data Flow

```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│  WhatsApp Message Arrives                                      │
│  │                                                             │
│  ▼                                                             │
│  WhatsApp-API Instance (wa-api.grupoamericainterior.com.ar)   │
│  │                                                             │
│  ├─> Detect incoming message                                  │
│  ├─> Extract: from (phone), body (text), id (msg_id)          │
│  ├─> POST to webhook with Bearer token                        │
│  │                                                             │
│  ▼                                                             │
│  SuiteCRM API Endpoint (crm.grupoamericainterior.com.ar)      │
│  /api/v8/WhatsApp/webhook                                     │
│  │                                                             │
│  ├─> Validate Bearer token                                    │
│  ├─> Check for duplicate (whatsapp_message_id)                │
│  ├─> Find Contact by phone number                             │
│  │   └─> If not found: Create new Contact                     │
│  ├─> Create whatsapp_messages record                          │
│  ├─> Link to Contact                                          │
│  │                                                             │
│  ▼                                                             │
│  Database (suitecrm)                                          │
│  │                                                             │
│  ├─> Table: whatsapp_messages                                 │
│  │   - contact_id (FK to contacts)                            │
│  │   - whatsapp_phone                                         │
│  │   - message_text                                           │
│  │   - whatsapp_message_id (unique, dedup)                    │
│  │   - timestamp                                              │
│  │   - message_direction: 'incoming'                          │
│  │   - message_status: 'received'                             │
│  │                                                             │
│  └─> Table: contacts (extended)                               │
│      - whatsapp_phone (indexed)                               │
│      - whatsapp_last_message (timestamp)                      │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

## Webhook Payload Format

WhatsApp-API sends POST with this structure:

```json
{
  "from": "+541234567890",
  "body": "Hello, this is a test message",
  "id": "wa_msg_123456789",
  "timestamp": "2026-07-21T14:30:00Z",
  "senderName": "John Doe"
}
```

### Expected Response (from SuiteCRM)

Success (200 OK):
```json
{
  "status": "success",
  "message": "Message processed",
  "whatsapp_message_id": "wa_msg_123456789"
}
```

Error (401 Unauthorized):
```json
{
  "status": "error",
  "message": "Unauthorized",
  "code": 401
}
```

## Testing the Integration

### 1. Test Webhook Token Generation

Verify token is stored:
```bash
# Inside container
docker exec suitecrm_app php -r "include 'config.php'; echo \$sugar_config['whatsapp_webhook_token'];"
```

### 2. Test Webhook Endpoint

Send a test message to the webhook:

```bash
curl -X POST https://crm.grupoamericainterior.com.ar/api/v8/WhatsApp/webhook \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token_from_step_1>" \
  -d '{
    "from": "+541234567890",
    "body": "Test message",
    "id": "test_msg_001",
    "timestamp": "2026-07-21T14:30:00Z",
    "senderName": "Test User"
  }'
```

Expected response:
```json
{
  "status": "success",
  "message": "Message processed",
  "whatsapp_message_id": "test_msg_001"
}
```

### 3. Verify Data in SuiteCRM

- Log in to SuiteCRM admin panel
- Navigate to: **WhatsApp Messages** (in menu)
- Should see the incoming test message
- Click on the message to view linked Contact details

## Module Structure

```
custom/modules/WhatsAppMessages/
├── WhatsAppMessages.php              # Bean definition
├── WhatsAppWebhookApi.php             # Webhook handler (API endpoint)
├── setup_whatsapp.php                 # Database setup script
├── vardefs/
│   └── vardefs.php                    # Field definitions
├── language/
│   └── en_us.lang.php                 # UI labels
└── metadata/                          # UI views (optional)

custom/Extension/modules/Contacts/Ext/Vardefs/
└── whatsapp_fields.php                # Extended Contact fields
```

## Database Schema

### `whatsapp_messages` Table

| Column | Type | Notes |
|--------|------|-------|
| id | VARCHAR(36) | Primary key, UUID |
| name | VARCHAR(255) | Subject/display name |
| date_entered | DATETIME | When record created in SuiteCRM |
| date_modified | DATETIME | Last modification |
| created_by | VARCHAR(36) | User who logged the message |
| contact_id | VARCHAR(36) | FK to contacts.id |
| whatsapp_phone | VARCHAR(20) | Phone number (indexed) |
| message_text | LONGTEXT | Message body |
| message_direction | ENUM | 'incoming' or 'outgoing' |
| message_status | ENUM | 'received', 'sent', 'delivered', 'read', 'failed' |
| whatsapp_message_id | VARCHAR(255) | Unique ID from WhatsApp-API (for dedup) |
| timestamp | DATETIME | Original timestamp from WhatsApp |

### Extended `contacts` Fields

| Column | Type | Notes |
|--------|------|-------|
| whatsapp_phone | VARCHAR(20) | Indexed for fast lookup |
| whatsapp_last_message | DATETIME | Last message timestamp |

## Security Notes

1. **Webhook Token:** Generated automatically, store securely in `.env` or config.php
2. **Bearer Auth:** All webhook requests must include `Authorization: Bearer <token>`
3. **Token Validation:** Uses constant-time comparison (`hash_equals`) to prevent timing attacks
4. **HTTPS Only:** Production must use HTTPS (enforced via domain names)
5. **Message Deduplication:** `whatsapp_message_id` unique constraint prevents duplicate processing

## Troubleshooting

### Webhook not receiving messages

1. Check webhook URL in whatsapp-api `.env`:
   ```bash
   WEBHOOK_URL=https://crm.grupoamericainterior.com.ar/api/v8/WhatsApp/webhook
   ```

2. Verify token matches:
   ```bash
   # In SuiteCRM
   echo \$sugar_config['whatsapp_webhook_token']
   
   # In whatsapp-api
   WHATSAPP_WEBHOOK_TOKEN=<value>
   ```

3. Check SuiteCRM logs:
   ```bash
   tail -f /var/www/html/cache/suitecrm.log
   ```

### Contact Not Found / Created as New

- Check phone number format (should be normalized to digits only)
- Verify Contact's `phone_mobile` or `phone_other` matches the incoming phone

### Duplicate Messages

- `whatsapp_message_id` should be globally unique from WhatsApp-API
- If receiving duplicates, check your webhook retry logic

## Future Enhancements

1. **Outgoing Messages:** Add ability to send messages from SuiteCRM back to WhatsApp
2. **Message Status Tracking:** Update message_status based on WhatsApp delivery/read status
3. **Conversation View:** Dashboard showing last N messages with each Contact
4. **Auto-Response:** Template-based automatic replies
5. **CRM Integration:** Link messages to Opportunities, Cases, Activities
6. **Sentiment Analysis:** Process message text with AI for Lead scoring
