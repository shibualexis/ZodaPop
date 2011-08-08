<?php
/**
 * Helper class to store database table specific information.
 *
 * @package ZodaPop
 */

class Table {
   private $table_name;
   private $column_attributes = array();
   
   public function getTableName() {
      return $this->table_name;
   }
   
   public function setTableName( $name ) {
      $this->table_name = $name;
   }
   
   public function getColumns() {
      return $this->column_attributes;
   }
   
   public function setColumns( $columns ) {
      foreach ($columns->ZCOLUMN as $column) {
         $this->column_attributes[(string) $column['COLUMN_NAME']] = $column;
      }
   }
}

?>