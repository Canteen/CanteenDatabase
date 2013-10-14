#Canteen Database

Abstract mysqli library for use with the Canteen Framework. For documentation of the codebase, please see [Canteen Database docs](http://canteen.github.io/CanteenDatabase/).

##Installation

Install is available using [Composer](http://getcomposer.org).

```bash
composer require canteen/database dev-master
```

Including using the Composer autoloader in your index.

```php
require 'vendor/autoload.php';
```

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


###Rebuild Documentation

This library is auto-documented using [YUIDoc](http://yui.github.io/yuidoc/). To install YUIDoc, run `sudo npm install yuidocjs`. Also, this requires the project [CanteenTheme](http://github.com/Canteen/CanteenTheme) be checked-out along-side this repository. To rebuild the docs, run the ant task from the command-line. 

```bash
ant docs
```

##License##

Copyright (c) 2013 [Matt Karl](http://github.com/bigtimebuddy)

Released under the MIT License.