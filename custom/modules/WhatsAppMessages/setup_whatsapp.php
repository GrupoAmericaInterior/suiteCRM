<?php
/**
 * WhatsApp Integration Setup Script
 * Run this to create WhatsAppMessages module and tables
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line');
}

require_once 'include/entryPoint.php';

$db = DBManagerFactory::getInstance();

// Create WhatsAppMessages table
$sql = "CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255),
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    created_by VARCHAR(36),
    modified_user_id VARCHAR(36),
    contact_id VARCHAR(36) NOT NULL,
    whatsapp_phone VARCHAR(20) NOT NULL,
    message_text LONGTEXT,
    message_direction ENUM('incoming', 'outgoing') DEFAULT 'incoming',
    message_status ENUM('received', 'sent', 'delivered', 'read', 'failed') DEFAULT 'received',
    whatsapp_message_id VARCHAR(255) UNIQUE,
    timestamp DATETIME,
    deleted TINYINT DEFAULT 0,
    KEY idx_contact_id (contact_id),
    KEY idx_whatsapp_phone (whatsapp_phone),
    KEY idx_whatsapp_message_id (whatsapp_message_id),
    KEY idx_date_entered (date_entered)
);";

try {
    $db->query($sql);
    echo "[OK] whatsapp_messages table created/verified\n";
} catch (Exception $e) {
    echo "[ERROR] Failed to create whatsapp_messages table: " . $e->getMessage() . "\n";
    exit(1);
}

// Add WhatsApp fields to contacts table
$alter_sql = "ALTER TABLE contacts ADD COLUMN IF NOT EXISTS whatsapp_phone VARCHAR(20),
              ADD COLUMN IF NOT EXISTS whatsapp_last_message DATETIME,
              ADD INDEX idx_whatsapp_phone (whatsapp_phone);";

try {
    $db->query($alter_sql);
    echo "[OK] Contacts table extended with WhatsApp fields\n";
} catch (Exception $e) {
    echo "[ERROR] Failed to alter contacts table: " . $e->getMessage() . "\n";
    exit(1);
}

// Create config entry for webhook token (if not exists)
$config_file = 'config.php';
if (file_exists($config_file)) {
    $config = include $config_file;

    if (!isset($config['whatsapp_webhook_token'])) {
        $token = bin2hex(random_bytes(32));
        $config['whatsapp_webhook_token'] = $token;

        // Write back config
        $config_content = '<?php' . "\n";
        $config_content .= '$sugar_config = ' . var_export($config, true) . ";\n";

        file_put_contents($config_file, $config_content);
        echo "[OK] Generated and stored webhook token in config.php\n";
        echo "    Webhook Token: " . $token . "\n";
    }
}

echo "\n[SUCCESS] WhatsApp integration setup complete!\n";
echo "Webhook endpoint: POST /api/v8/WhatsApp/webhook\n";
echo "Required header: Authorization: Bearer <token>\n";
