<?php
/**
 * Singleton to manage any and all database connections.
 * Manages reading and writing to a Zoho database table.
 */
class Connection {
   const login_URL = 'https://accounts.zoho.com';
   /**
    * login name for your Zoho Reports account
    * @var string
    */
   public static $login_id = null;
   /**
    * password credentials for your Zoho Reports account
    * @var string
    */
   public static $password = null;
   /**
    * name of cloud database for connection to Zoho Reports
    * @var string
    */
   public static $db = null;
   /**
    * name of cloud database table Zoho Reports account
    * @var string
    */
   public static $table = null;
   /**
    * current instance of Connection a Singleton
    * @var Connection
    */
   private static $_instance = null;
   /**
    * your Zoho Reports API key
    * @var string
    */
   public static $api_key = null;
   /**
    * Ticket is a session specific ID that is to be
    * generated and passed along with every API call.
    * @var string
    */
   private $session_ticket;
   /**
    * schemas of databases that includes column names of table
    * and column value data types
    * @var mixed
    */
   private $db_schemas = array();
   /**
    * look up table to access column names
    * @var array
    */
   private $table_columns = array();
   
   /**
    * Constructs a Connection with Zoho Reports HTTP session ticket.
    *
    * Instantiated only once, this is a Singleton implemented class.
    * @return void
    */
   private function __construct() {
      $this->session_ticket = $this->__login();
   }
   
   /**
    * If $name is null then the default connection will be returned.
    *
    * @return Connection
    */
   final public static function instance() {
      if (!(self::$_instance instanceof self)) {
         self::$_instance = new self();
         self::$_instance->setDBSchema();
      }
      return self::$_instance;
   }
   
   /**
    * Sets schema info for a particular database.
    *
    * @return void
    */
   private function setDBSchema() {
      try {
         $this->db_schemas[self::$db] = new DatabaseSchema();
      } catch (Exception $e) {
         trigger_error( 'Caught exception: ' . $e->getMessage() . "\n" );
      }
   }
   
   /**
    * Singleton objects should not be cloned.
    *
    * @return void
    */
   final private function __clone() {
   }
   
   /**
    * Retrieves Zoho HTTP API session ticket code.
    *
    * @return void
    */
   public function getSession() {
      return $this->session_ticket;
   }
   
   /**
    * Sends request to Zoho API.
    *
    * @return XML
    */
   public static function send_request( $url, $method, $data = array(), $responseHandler = null ) {
      $params = '';
      
      foreach ($data as $k => $v) {
         $params .= '&' . $k . '=' . rawurlencode( $v );
      }
      
      $res = HttpPHP::send( $url, $method, $params );
      list( $headers, $body ) = explode( "\r\n\r\n", $res, 2 );
      
      if (!$responseHandler) {
         $response = array( 'headers' => $headers, 'body' => $body );
      } else {
         $response = $responseHandler( $headers, $body );
      }
      return $response;
   }
   
   /**
    * Login to Zoho API with credentials and retrieve session ticket number.
    *
    * @return string
    */
   private function __login() {
      $login_base_url = 'https://accounts.zoho.com';
      $login_url = $login_base_url . '/login?servicename=ZohoReports&FROM_AGENT=true&LOGIN_ID=' . self::$login_id . '&PASSWORD=' . self::$password;
      $res = HttpPHP::send( $login_url, 'GET' );
      preg_match( '/TICKET=[a-zA-Z0-9]+/', $res, $matches );
      return substr( $matches[0], 7 );
   }
   
   /**
    * Sets account login name for Zoho API.
    *
    * @return void
    */
   public static function setLoginName( $name ) {
      self::$login_id = $name;
   }
   
   /**
    * Sets account login password for Zoho API.
    *
    * @return void
    */
   public static function setPassword( $pass ) {
      self::$password = $pass;
   }
   
   /**
    * Sets account API key for accessing Zoho API.
    *
    * @return void
    */
   public static function setAPIKey( $key ) {
      self::$api_key = $key;
   }
   
   /**
    * Sets the name of the database that the connection will be using.
    *
    * @return void
    */
   public static function setDatabase( $DBname ) {
      self::$db = $DBname;
      if (self::$_instance instanceof self) {
         if (!array_key_exists( self::$db, self::$_instance->getDBSchemas() )) {
            self::$_instance->setDBSchema();
         }
      }
   }
   
   /**
    * Sets the name of the database table that the connection will be using.
    *
    * @return void
    */
   public static function setTable( $table ) {
      self::$table = $table;
   }
   
   /**
    * Gets schema info of tables for current database.
    *
    * @return object
    */
   public function getDBSchemas() {
      return $this->db_schemas;
   }
   
   /**
    * Gets column names of a database table.
    *
    * @return array
    */
   public function getTableColumns( $db_name, $table_name ) {
      if (!isset( $this->table_columns[$db_name][$table_name]['columns'] )) {
         $this->setTableColumns( $db_name, $table_name );
      }
      return $this->table_columns[$db_name][$table_name]['columns'];
   }
   
   /**
    * Sets column names and their coreesponding data type (string, double or integer) of a database table.
    *
    * @return void
    */
   private function setTableColumns( $db_name, $table_name ) {
      $data = $this->db_schemas[$db_name];
      $tables = $data->getTables();
      $column_attributes = null;
      
      foreach ($tables as $table) {
         if ($table->getTableName() == $table_name) {
            $column_attributes = $table->getColumns();
            break;
         }
      }
      
      $column_names = array();
      foreach ($column_attributes as $column_name => $attributes) {
         $column_names[$column_name] = $attributes['TYPE_NAME'];
      }
      
      $this->table_columns[$db_name][$table_name]['columns'] = $column_names;
   }
}

?>