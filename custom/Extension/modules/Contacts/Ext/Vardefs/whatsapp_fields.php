<?php
$dictionary['Contact']['fields']['whatsapp_phone'] = array(
    'name' => 'whatsapp_phone',
    'vname' => 'LBL_WHATSAPP_PHONE',
    'type' => 'varchar',
    'len' => '20',
    'comment' => 'WhatsApp phone number for this contact',
);

$dictionary['Contact']['fields']['whatsapp_last_message'] = array(
    'name' => 'whatsapp_last_message',
    'vname' => 'LBL_WHATSAPP_LAST_MESSAGE',
    'type' => 'datetime',
    'comment' => 'Timestamp of last WhatsApp message from this contact',
);

$dictionary['Contact']['relationships']['contact_whatsapp_messages'] = array(
    'lhs_module' => 'Contacts',
    'lhs_table' => 'contacts',
    'lhs_key' => 'id',
    'rhs_module' => 'WhatsAppMessages',
    'rhs_table' => 'whatsapp_messages',
    'rhs_key' => 'contact_id',
    'relationship_type' => 'one-to-many',
);
