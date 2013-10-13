#CanteenDatabase

Abstract mysqli library for use with the Canteen Framework.

##Setup

```php
use Canteen\Database;
$db = new Database(
	'localhost', 	// MySQL host
	'root', 		// MySQL username
	'12341234', 	// MySQL user's password
	'my_database'	// Database name
);
```

##Sample Usage

Create SQL queries with a simple, intuitive, object-oriented API. 

```php
// Create a select query of a properties
// on a user's table of all active users
$users = $db->select('user_id', 'first_name', 'last_name')
	->from('users')
	->where('active=1')
	->results();
```

##Documentation

For more information on queries, please refer to the documentation in the docs folder.