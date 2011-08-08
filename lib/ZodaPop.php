<?php
define( 'STRING_TYPES', 'zoho_string_types' );
$zoho_string_types = array( "Multi Line Text", "Yes\/No Decision", "Looked Up Column", "Percent", "Plain Text", "Currency", "Date", "E-Mail", "URL" );
define( 'DECIMAL_NUMBER_TYPES', 'zoho_decimal_types' );
$zoho_decimal_types = array( "Number", "Positive Number", "Decimal Number" );
define( 'INTEGER_NUMBER_TYPES', 'zoho_integer_types' );
$zoho_integer_types = array( "Auto Number" );

/**
 * PHP library wraps the raw HTTP based API of Zoho Reports and CloudSQL provided by Zoho (zohoreportsapi.wiki.zoho.com).
 *
 * Usage:
 *
 * <?php
 *
 * require_once __DIR__ . '/ZodaPop/ZodaPop.php';
 *
 * Connection::setLoginName( 'your login name' );
 * Connection::setPassword( 'your password' );
 * Connection::setAPIKey( '0123456789ABCabcXYZxyz' ); // API key given by Zoho reports
 * Connection::setDatabase( 'some db' ); // set the database to be query on
 * Connection::setTable( 'some table' ); // set the database table to be query on
 *
 * $active_record = new ZodaPop();
 *
 * // creates new row in cloud database, $new_row equals true if row was created
 * // keys in data array must correspond to actual names of table columns
 * $new_row = $active_record->create( array( 'Campaign' => 'New advertising campaign', 'AdGroup' => 'Techies' ) );
 *
 * // find a particular row by primary key 'id' in table
 * // $target_row is an ZodaPop instance that contains the rows data if record is found
 * $target_row = ZodaPop::find( array( "id" => '4537' ) );
 *
 * // retrieving values of particular columns in record
 * // all the getter and getter functions are dynamically created
 * // corresponding to the given database table schema information
 *
 * $campaign_name = $target_row->Campaign; // returns string
 * $ad_group = $target_row->AdGroup; // returns string
 * $number_of_clicks = $target_row->Clicks; // returns double
 *
 *
 * // updating values of particular columns in record
 * $target_row->Campaign = "Updated advertising campaign";
 * $target_row->AdGroup = "superceded ad group";
 * $target_row->Clicks = 1000;
 *
 * // do an update on existing record on cloud database
 * // $existing_row_updated equals true if columns were updated
 * $existing_row_updated = $target_row->save();
 *
 * // find the row we've created above
 * $destroy_me = ZodaPop::find( array( 'Campaign' => 'Updated advertising campaign', 'AdGroup' => 'superceded ad group' ) );
 *
 * // destroy this particular row in cloud database
 * // $existing_record_deleted is true if record was deleted
 * $existing_record_deleted = $destroy_me->destroy();
 *
 * // finding with dynamic finders
 * $found_by_id = ZodaPop::find_by_id( 1867 );
 * $found_by_Campaign = ZodaPop::find_by_Campaign( 'some campaign' );
 * $found_by_num_clicks = ZodaPop::find_by_Clicks( 89059 );
 * ?>
 *
 */
class ZodaPop {
   /**
    * base url
    */
   const base_URL = 'https://reportsapi.zoho.com/api/';
   /**
    * Set to the name of the database this Model's table is in.
    *
    * @var Connection
    */
   private $conn;
   /**
    * Set to the name of the database this Model's table is in.
    *
    * @var Connection
    */
   private $db_name;
   /**
    * Set this to explicitly specify the model's table name.
    *
    * @var string
    */
   private $table_name;
   /**
    * The data of the current object, accessed via the anonymous get/set methods.
    * Contains model values as column_name => value corresponding to tables columns in cloud db
    *
    * @var array
    */
   private $_data = array();
   /**
    * Flag that determines if a call to save() should issue an insert or an update call
    *
    * @var boolean
    */
   private $__new_record = true;
   
   /**
    * Constructs a model.
    *
    * When a user instantiates a new object
    * then @var $attributes will be mapped according to the schema's defaults.
    *
    * <code>
    * new ZodaPop();
    * </code>
    *
    * @param boolean $new_record Set to true if this should be considered a new record
    * @param string $db_name name of database
    * @param string $table_name name of table
    * @return ZodaPop
    */
   public function __construct( $new_record = true, $db_name = null, $table_name = null ) {
      $this->__new_record = $new_record;
      $this->conn = Connection::instance();
      if ($db_name == null)
         $db_name = Connection::$db;
      $this->db_name = $db_name;
      if ($table_name == null)
         $table_name = Connection::$table;
      $this->table_name = $table_name;
   }
   
   /**
    * Save the model to the database.
    *
    * This function will automatically determine if an INSERT or UPDATE needs to occur.
    *
    * @return boolean True if the model was saved to the database otherwise false
    */
   public function save() {
      if (empty( $this->_data ))
         throw new Exception( "Cannot save: no data" );
      
      if ($this->isNewRecord()) {
         $save_URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . Connection::$table . '?ZOHO_ACTION=ADDROW';
         $config_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $this->conn->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
         $post_data = array_merge( $config_data, $this->_data );
         
         $response_data = Connection::send_request( $save_URL, 'POST', $post_data ); // create
         $xml = $response_data['body'];
         $xml_data = new SimpleXMLIterator( $xml );
         $xml_data_keys = (array) $xml_data;
         
         if (array_key_exists( 'error', $xml_data_keys )) {
            trigger_error( "Save error: " . (string) $xml_data->error->message );
         } else if (array_key_exists( 'result', $xml_data_keys )) {
            return true;
         } else {
            trigger_error( "General Error" );
         }
         return false;
      } else {
         $updated = $this->update();
         return $updated;
      }
   }
   
   /**
    * Issue an UPDATE call for an already existing record (i.e. instantiated by a find).
    *
    * @return boolean true if the model was saved to the database otherwise false
    */
   public function update() {
      
      $update_URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . Connection::$table . '?ZOHO_ACTION=UPDATE';
      $config_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $this->conn->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
      $post_data = array_merge( $config_data, $this->_data );
      $post_data['ZOHO_CRITERIA'] = "(\"id\" = '{$this->_data['id']}')";
      unset( $post_data['id'] );
      
      $response_data = Connection::send_request( $update_URL, 'POST', $post_data );
      $xml = $response_data['body'];
      $xml_data = new SimpleXMLIterator( $xml );
      $xml_data_keys = (array) $xml_data;
      
      if (array_key_exists( 'error', $xml_data_keys )) {
         trigger_error( "Update error: " . (string) $xml_data->error->message );
      } else if (array_key_exists( 'result', $xml_data_keys )) {
         return true;
      } else {
         trigger_error( "General Error" );
      }
      return false;
   }
   
   /**
    * create a new row in database table
    *
    * @param array $data associate array with keys as coulumn names and values as column values.
    * @return boolean true if the model was saved to the database otherwise false
    */
   public static function create( $data ) {
      
      $db = Connection::instance();
      
      $create_URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . Connection::$table . '?ZOHO_ACTION=ADDROW';
      $config_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $db->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
      
      $post_data = array_merge( $config_data, $data );
      $response_data = Connection::send_request( $create_URL, 'POST', $post_data );
      $xml = $response_data['body'];
      $xml_data = new SimpleXMLIterator( $xml );
      $xml_data_keys = (array) $xml_data;
      
      if (array_key_exists( 'error', $xml_data_keys )) {
         trigger_error( "Create error: " . (string) $xml_data->error->message );
      } else if (array_key_exists( 'result', $xml_data_keys )) {
         return true;
      } else {
         trigger_error( "General Error" );
      }
      return false;
   }
   
   /**
    * Deletes this model from the database and returns true if successful.
    *
    * @return boolean if row was deleted in database table; false otherwise.
    */
   public function destroy() {
      
      if (!$this->isNewRecord()) {
         $destroy_URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . Connection::$table . '?ZOHO_ACTION=DELETE';
         $post_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $this->conn->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
         
         $post_data['ZOHO_CRITERIA'] = '(' . '"id"=' . '\'' . $this->_data['id'] . '\'' . ')';
         $response_data = Connection::send_request( $destroy_URL, 'POST', $post_data );
         $xml = $response_data['body'];
         $xml_data = new SimpleXMLIterator( $xml );
         $xml_data_keys = (array) $xml_data;
         
         if (array_key_exists( 'error', $xml_data_keys )) {
            trigger_error( "Destroy error: " . (string) $xml_data->error->message );
         } else if (array_key_exists( 'result', $xml_data_keys )) {
            unset( $this->_data['id'] );
            $this->__new_record = true;
            return true;
         } else {
            trigger_error( "General Error" );
         }
      } else {
         trigger_error( "Cannot delete a new record that is not in database table." );
         return false;
      }
   }
   
   /**
    * Find records in the database.
    *
    * @return mixed An array of ZodaPop objects if more than one record is found
    * Single ZodaPop object if only one record was found.
    * false if no record was found
    */
   public static function find( $conditions ) {
      
      $db = Connection::instance();
      $find_URL = self::base_URL . Connection::$login_id . '/' . Connection::$db . '/' . Connection::$table . '?ZOHO_ACTION=EXPORT';
      $post_data = array( 'ZOHO_API_KEY' => Connection::$api_key, 'ticket' => $db->getSession(), 'ZOHO_API_VERSION' => '1.0', 'ZOHO_ERROR_FORMAT' => 'XML', 'ZOHO_OUTPUT_FORMAT' => 'XML' );
      
      $query_string_params = array();
      foreach ($conditions as $k => $v) {
         $query_string_params[] = '"' . $k . '"' . '=' . '\'' . $v . '\'';
      }
      
      $post_data['ZOHO_CRITERIA'] = '(' . implode( ' and ', $query_string_params ) . ')';
      
      $response_data = Connection::send_request( $find_URL, 'POST', $post_data );
      $xml = $response_data['body'];
      $xml_data = new SimpleXMLIterator( $xml );
      
      $xml_data_keys = (array) $xml_data;
      if (array_key_exists( 'error', $xml_data_keys )) {
         trigger_error( "Find error: " . (string) $xml_data->error->message );
      } else if (array_key_exists( 'result', $xml_data_keys )) {
         $results = array();
         $rows = $xml_data->xpath( "//result/rows/row" );
         foreach ($rows as $row) {
            $zoho_reports_data_model = new self( false );
            $columns = $row->xpath( "//column" );
            foreach ($columns as $column) {
               $column_name = (string) $column['name'];
               $column_value = (string) $column;
               $dynamic_setter = '$zoho_reports_data_model->' . $column_name . ' = "' . $column_value . '";';
               eval( $dynamic_setter );
            }
            $results[] = $zoho_reports_data_model;
         }
         if (count( $results ) == 1) {
            return $results[0];
         } else if (count( $results ) > 1) {
            return $results;
         } else {
            return false;
         }
      } else {
         trigger_error( "General Error" );
      }
      return false;
   }
   
   /**
    * Enables the use of dynamic finders when calling on instantiated objects of ZodaPop.
    *
    * Dynamic finders are just an easy way to do queries quickly without having to
    * specify an options array with conditions in it.
    *
    * <code>
    * $some_instance = new ZodaPop()
    * $some_instance->find_by_first_name('Tito');
    * </code>
    *
    *
    * @param string $method Name of method
    * @param mixed $args Method args
    * @return ZodaPop
    */
   public function __call( $method, $args ) {
      if (substr( $method, 0, 7 ) === 'find_by') {
         $attribute = substr( $method, 8 );
         $query_params = array();
         $query_params[$attribute] = $args[0];
         
         return $this->find( $query_params );
      }
      trigger_error( "Call to undefined instance method: $method" );
      return false;
   }
   
   /**
    * Enables the use of dynamic finders when calling on statically.
    * @see __call
    *
    * <code>
    * ZodaPop::find_by_first_name('Tito');
    * </code>
    *
    * @param string $method Name of method
    * @param mixed $args Method args
    * @return ZodaPop
    */
   public static function __callStatic( $method, $args ) {
      if (substr( $method, 0, 7 ) === 'find_by') {
         $attribute = substr( $method, 8 );
         $query_params = array();
         $query_params[$attribute] = $args[0];
         
         return self::find( $query_params );
      }
      trigger_error( "Call to undefined static method: $method" );
      return false;
   }
   
   /**
    * Getter for internal object data.
    */
   public function __get( $name ) {
      if (isset( $this->_data[$name] )) {
         return $this->_data[$name];
      }
      
      $trace = debug_backtrace();
      trigger_error( 'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE );
      return null;
   }
   
   /**
    * Magic allows un-defined attributes to be set via $attributes.
    * Maps directly to column names, will not allow setting if column name
    * in table and name of instance variable do not match.
    *
    * <code>
    * $some_instance = ZodaPop();
    * $some_instance->name = 'John'; // instance variable must be a column name
    * </code>
    *
    * @param string $column_name Name of attribute, must correspond to a database column name
    * @param mixed $value value
    * @return true if the instance attribute is set, otherwise false
    */
   public function __set( $column_name, $value ) {
      $columns = $this->conn->getTableColumns( Connection::$db, Connection::$table );
      
      if (array_key_exists( $column_name, $columns )) {
         $data_type = (string) $columns[$column_name];
         $type_caster = '';
         
         if (in_array( $data_type, $GLOBALS[STRING_TYPES] )) {
            $type_caster = '(string) ';
         } else if (in_array( $data_type, $GLOBALS[DECIMAL_NUMBER_TYPES] )) {
            $type_caster = '(double) ';
         } else if (in_array( $data_type, $GLOBALS[INTEGER_NUMBER_TYPES] )) {
            $type_caster = '(integer) ';
         }
         
         $dynamic_setter = '$this->_data [$column_name] = ' . $type_caster . "'" . $value . "';";
         eval( $dynamic_setter );
         return true;
      }
      trigger_error( "not a column" );
      return false;
   }
   
   /**
    * Determine if the model is a new record.
    *
    * @return boolean
    */
   public function isNewRecord() {
      return $this->__new_record;
   }
}

?>