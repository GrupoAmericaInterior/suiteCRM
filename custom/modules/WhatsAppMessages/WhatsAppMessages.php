<?php
class WhatsAppMessages extends SugarBean
{
    public $module_dir = 'WhatsAppMessages';
    public $object_name = 'WhatsAppMessages';
    public $table_name = 'whatsapp_messages';
    public $new_schema = true;

    public function __construct()
    {
        parent::__construct();
        $this->setup_custom_fields('WhatsAppMessages');
    }
}
