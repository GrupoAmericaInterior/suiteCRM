<?php
/**
 * WhatsApp Webhook Handler
 * Receives incoming messages from whatsapp-api and stores them in SuiteCRM
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/api/SugarApi.php';

class WhatsAppWebhookApi extends SugarApi
{
    public function registerApiRest()
    {
        return array(
            'whatsappWebhook' => array(
                'reqType' => 'POST',
                'path' => array('WhatsApp', 'webhook'),
                'pathVars' => array('', ''),
                'method' => 'handleWebhook',
                'shortHelp' => 'WhatsApp message webhook endpoint',
                'longHelp' => 'Receives webhook POST from whatsapp-api when a message arrives',
            ),
        );
    }

    public function handleWebhook($api, $args)
    {
        try {
            // Validate webhook token
            $token = $this->_getWebhookToken();
            $headers = apache_request_headers();
            $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

            if (!$this->_validateToken($authHeader, $token)) {
                return array(
                    'status' => 'error',
                    'message' => 'Unauthorized',
                    'code' => 401,
                );
            }

            // Parse incoming JSON payload
            $payload = json_decode(file_get_contents('php://input'), true);

            if (!$payload) {
                return array(
                    'status' => 'error',
                    'message' => 'Invalid JSON payload',
                    'code' => 400,
                );
            }

            // Process the message
            $result = $this->_processMessage($payload);

            return array(
                'status' => 'success',
                'message' => 'Message processed',
                'whatsapp_message_id' => $result['id'],
            );

        } catch (Exception $e) {
            $GLOBALS['log']->error('WhatsApp Webhook Error: ' . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 500,
            );
        }
    }

    /**
     * Process incoming message and create/update Contact and WhatsAppMessages record
     */
    private function _processMessage($payload)
    {
        global $db, $current_user;

        // Extract message data
        $phone = isset($payload['from']) ? preg_replace('/\D/', '', $payload['from']) : null;
        $message_text = isset($payload['body']) ? $payload['body'] : '';
        $timestamp = isset($payload['timestamp']) ? $payload['timestamp'] : date('Y-m-d H:i:s');
        $whatsapp_message_id = isset($payload['id']) ? $payload['id'] : uniqid('wa_');

        if (!$phone) {
            throw new Exception('Missing "from" phone number in payload');
        }

        // Check for duplicate message (via whatsapp_message_id)
        $existing = $db->query(
            "SELECT id FROM whatsapp_messages WHERE whatsapp_message_id = '" .
            $db->quote($whatsapp_message_id) . "' LIMIT 1"
        );

        if ($db->getRowCount($existing) > 0) {
            return array('id' => $whatsapp_message_id, 'status' => 'duplicate');
        }

        // Find or create Contact
        $contact = $this->_findOrCreateContact($phone, $payload);

        // Create WhatsAppMessages record
        $msg = new WhatsAppMessages();
        $msg->contact_id = $contact->id;
        $msg->whatsapp_phone = $phone;
        $msg->message_text = $message_text;
        $msg->message_direction = 'incoming';
        $msg->message_status = 'received';
        $msg->whatsapp_message_id = $whatsapp_message_id;
        $msg->timestamp = $timestamp;
        $msg->name = 'Message from ' . $phone . ' - ' . substr($message_text, 0, 50);
        $msg->created_by = $current_user->id;
        $msg->save();

        // Log the activity
        $GLOBALS['log']->info('WhatsApp message stored: ' . $whatsapp_message_id . ' from ' . $phone);

        return array(
            'id' => $msg->id,
            'contact_id' => $contact->id,
            'status' => 'created',
        );
    }

    /**
     * Find Contact by phone or create one
     */
    private function _findOrCreateContact($phone, $payload)
    {
        global $db, $current_user;

        // Try to find existing contact by phone
        $contact = new Contact();
        $result = $db->query(
            "SELECT id FROM contacts WHERE phone_mobile LIKE '%" .
            $db->quote($phone) . "%' OR phone_other LIKE '%" .
            $db->quote($phone) . "%' LIMIT 1"
        );

        if ($db->getRowCount($result) > 0) {
            $row = $db->fetchByAssoc($result);
            $contact->retrieve($row['id']);
            return $contact;
        }

        // Create new Contact
        $contact->first_name = isset($payload['senderName']) ? $payload['senderName'] : 'WhatsApp';
        $contact->last_name = $phone;
        $contact->phone_mobile = $phone;
        $contact->phone_work = $phone;
        $contact->assigned_user_id = $current_user->id;
        $contact->save();

        $GLOBALS['log']->info('Created new Contact from WhatsApp: ' . $contact->id);

        return $contact;
    }

    /**
     * Get webhook token from environment or config
     */
    private function _getWebhookToken()
    {
        $token = getenv('WHATSAPP_WEBHOOK_TOKEN');

        if (!$token && isset($GLOBALS['sugar_config']['whatsapp_webhook_token'])) {
            $token = $GLOBALS['sugar_config']['whatsapp_webhook_token'];
        }

        return $token ?: 'default_token'; // Fallback for local testing
    }

    /**
     * Validate Bearer token
     */
    private function _validateToken($authHeader, $expectedToken)
    {
        if (!$authHeader) {
            return false;
        }

        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            return hash_equals($token, $expectedToken);
        }

        return false;
    }
}
