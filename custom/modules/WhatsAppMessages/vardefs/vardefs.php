<?php
$dictionary['whatsapp_messages'] = array(
    'table' => 'whatsapp_messages',
    'comment' => 'WhatsApp Messages Integration',
    'audited' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'vname' => 'LBL_ID',
            'type' => 'id',
            'required' => true,
            'reportable' => false,
        ),
        'name' => array(
            'name' => 'name',
            'vname' => 'LBL_SUBJECT',
            'type' => 'name',
            'dbType' => 'varchar',
            'len' => '255',
        ),
        'date_entered' => array(
            'name' => 'date_entered',
            'vname' => 'LBL_DATE_ENTERED',
            'type' => 'datetime',
            'required' => true,
            'audited' => false,
        ),
        'date_modified' => array(
            'name' => 'date_modified',
            'vname' => 'LBL_DATE_MODIFIED',
            'type' => 'datetime',
            'required' => true,
            'audited' => false,
        ),
        'created_by' => array(
            'name' => 'created_by',
            'vname' => 'LBL_CREATED_BY',
            'type' => 'assigned_user_name',
            'table' => 'users',
        ),
        'modified_user_id' => array(
            'name' => 'modified_user_id',
            'vname' => 'LBL_MODIFIED_BY_ID',
            'type' => 'assigned_user_name',
        ),
        'contact_id' => array(
            'name' => 'contact_id',
            'vname' => 'LBL_CONTACT_ID',
            'type' => 'id',
            'required' => true,
        ),
        'whatsapp_phone' => array(
            'name' => 'whatsapp_phone',
            'vname' => 'LBL_WHATSAPP_PHONE',
            'type' => 'varchar',
            'len' => '20',
            'required' => true,
        ),
        'message_text' => array(
            'name' => 'message_text',
            'vname' => 'LBL_MESSAGE_TEXT',
            'type' => 'text',
        ),
        'message_direction' => array(
            'name' => 'message_direction',
            'vname' => 'LBL_MESSAGE_DIRECTION',
            'type' => 'enum',
            'options' => 'whatsapp_direction_list',
            'default' => 'incoming',
        ),
        'message_status' => array(
            'name' => 'message_status',
            'vname' => 'LBL_MESSAGE_STATUS',
            'type' => 'enum',
            'options' => 'whatsapp_status_list',
            'default' => 'received',
        ),
        'whatsapp_message_id' => array(
            'name' => 'whatsapp_message_id',
            'vname' => 'LBL_WHATSAPP_MESSAGE_ID',
            'type' => 'varchar',
            'len' => '255',
            'unique' => true,
        ),
        'timestamp' => array(
            'name' => 'timestamp',
            'vname' => 'LBL_TIMESTAMP',
            'type' => 'datetime',
        ),
    ),
    'relationships' => array(
        'whatsapp_messages_contact' => array(
            'lhs_module' => 'Contacts',
            'lhs_table' => 'contacts',
            'lhs_key' => 'id',
            'rhs_module' => 'WhatsAppMessages',
            'rhs_table' => 'whatsapp_messages',
            'rhs_key' => 'contact_id',
            'relationship_type' => 'one-to-many',
        ),
    ),
    'indices' => array(
        array(
            'name' => 'idx_contact_id',
            'type' => 'index',
            'fields' => array('contact_id'),
        ),
        array(
            'name' => 'idx_whatsapp_phone',
            'type' => 'index',
            'fields' => array('whatsapp_phone'),
        ),
        array(
            'name' => 'idx_whatsapp_message_id',
            'type' => 'unique',
            'fields' => array('whatsapp_message_id'),
        ),
    ),
);
