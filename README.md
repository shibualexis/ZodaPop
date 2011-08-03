# ZodaPop #

Version 0.0.1 Alpha

by Thane Vo

<vothane@gmail.com>

## Introduction ##

A brief summarization of what ZodaPop is:

> A basic PHP implementation of ActiveRececord, lighty sweetened with syntatic sugar, for the Zoho Reports cloud database API.
> Active Record is an implementation of the object-relational mapping (ORM) pattern by the same name described by Martin Fowler.
> Active Record attempts to provide a coherent wrapper as a solution for the inconvenience that is object-relational mapping.
> A database table or view is wrapped into a class, thus an object instance is tied to a single row in the table. 
> After creation of an object, a new row is added to the table upon save. Any object loaded gets its information from the database; 
> when an object is updated, the corresponding row in the table is also updated. The wrapper class implements accessor methods or 
> properties for each column in the table.

Zoho Reports cloud database API can be found [here](http://zohoreportsapi.wiki.zoho.com/).
More details can be found [here](http://en.wikipedia.org/wiki/Active_record_pattern).

## Minimum Requirements ##

- PHP 5.3+
- Account with Zoho Reports with generated API key.
- All Tables must have a column named 'id' (primary key) and set to auto-number in Zoho dashboard.
- Tables columns names must not have spaces or funky characters
 
# Features ##

- OO CRUD operations
- Finder methods
- Dynamic finder methods
- Dynamic setter and getter methods

## Basic CRUD ##

### Retrieve ###
These are your basic methods to find and retrieve records from your database.

	// find a particular row by primary key 'id' in table
	// $activerecord_instance is an ZodaPop instance that contains the rows data if record is found
	$activerecord_instance = ZodaPop::find( array( "id" => '15' ) );
	echo $activerecord_instance->subject;  // 'Design Patterns'
	echo $activerecord_instance->chapters; // 10

	# finding using dynamic finders
	$activerecord_instance = ZodaPop::find_by_subject('ActiveRecord');
	activerecord_instance = ZodaPop::find_by_authur('Martin Fowler');

### Create ###
Here we create a new post by instantiating a new object and then invoking the save() method.

	$activerecord_instance = new ZodaPop();
	$activerecord_instance->subject = 'DataMapper'
	$activerecord_instance->title = 'PHP ActiveRecord';
	$activerecord_instance->save();

### Update ###
To update you would just need to find a record first and then change one of its attributes.

	$activerecord_instance = ZodaPop::find_by_subject('Singleton');
	echo $activerecord_instance->title; // 'Best Practices'
	$activerecord_instance->title = 'Better Practice';
	$activerecord_instance->save();

### Destroy ###
Destroy a record will not *destroy* the object instance. This means that it will call the API to delete
the record in your cloud database but you can still use the object if you need to.

	$activerecord_instance = ZodaPop::find_by_author('Thane Vo');
	$activerecord_instance->destroy();
	
	// bring back from the dead
    $activerecord_instance->save();
	
## Example Usage ##

require_once __DIR__ . '/ZodaPop/ZodaPop.php';

Connection::setLoginName( 'Your Login Name Here' );
Connection::setPassword( 'Your Password Here' );
Connection::setAPIKey( 'Your API key Here' );
Connection::setDatabase( 'name of database here' );
Connection::setTable( 'name of database table here' );

$active_record = new ZodaPop();

// creates new row in cloud database, $new_row equals true if row was created
// keys in data array must correspond to actual names of table columns
$new_row = $active_record->create( array( 'Campaign' => 'New advertising campaign', 'AdGroup' => 'Techies' ) );

// find a particular row by primary key 'id' in table
// $target_row is an ZodaPop instance that contains the rows data if record is found
$target_row = ZodaPop::find( array( "id" => '4540' ) );

// retrieving values of particular columns in record
// all the getter and getter functions are dynamically created
// corresponding to the given database table schema information

$campaign_name = $target_row->Campaign; // returns string
$ad_group = $target_row->AdGroup; // returns string
$number_of_clicks = $target_row->Clicks; // returns double

// updating values of particular columns in record
$target_row->Campaign = "Updated advertising campaign";
$target_row->AdGroup = "superceded ad group";
$target_row->Clicks = 1000;

// do an update on existing record on cloud database
// $existing_row_updated equals true if columns were updated
$existing_row_updated = $target_row->save();

// find the row we've created above
$destroy_me = ZodaPop::find( array( 'Campaign' => 'Updated advertising campaign', 'AdGroup' => 'superceded ad group' ) );

// destroy this particular row in cloud database
// $existing_record_deleted is true if record was deleted
$existing_record_deleted = $destroy_me->destroy();

// finding with dynamic finders
$found_by_id = ZodaPop::find_by_id( 1867 );
$found_by_Campaign = ZodaPop::find_by_Campaign( 'some campaign' );
$found_by_num_clicks = ZodaPop::find_by_Clicks( 89059 );

## TODOs ##

- Testing
- Major Refactoring 
- Relationships
- Validations
- Callbacks