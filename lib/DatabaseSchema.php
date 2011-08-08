<?php
/**
 * Helper class to retrieve and store database and table information.
 *
 * @package ZodaPop
 */
class DatabaseSchema {
   const base_URL = 'https://reportsapi.zoho.com/api/';
   private $db_name = '';
   private $tables = array();
   private $db;
   
   public function __construct() {
      $this->db_name = Connection::$db;
      $this->db = Connection::instance();
      $URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . '?ZOHO_ACTION=DATABASEMETADATA';
      $config_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $this->db->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
      $data = array( 'ZOHO_METADATA' => 'ZOHO_CATALOG_INFO' );
      $post_data = array_merge( $config_data, $data );
      
      $res = Connection::send_request( $URL, 'POST', $post_data );
      $xml = $res['body'];
      $xml_data = new SimpleXMLIterator( $xml );
      $xml_data_keys = (array) $xml_data;
      
      if (array_key_exists( 'error', $xml_data_keys )) {
         trigger_error( "Database error: " . (string) $xml_data->error->message );
         throw new Exception( 'Non existent database or table' );
      } else {
         $this->setTables( $xml_data );
      }
   }
   
   public function getTables() {
      return $this->tables;
   }
   
   private function setTables( $xml_data ) {
      
      $views = $xml_data->xpath( "//ZCATALOG/ZVIEW" );
      foreach ($views as $view) {
         if (((string) $view['TABLE_TYPE']) == 'TABLE') {
            $table = new Table();
            $table->setTableName( (string) $view['TABLE_NAME'] );
            $columns = $view->ZCOLUMNS;
            $table->setColumns( $columns );
            $this->tables[] = $table;
         }
      }
   }
}

?>