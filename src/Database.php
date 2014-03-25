<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{	
	use \mysqli;
	use \mysqli_result;
	
	class Database
	{
		/**
		*  The database references 
		*  @property {Array} _databases
		*  @private
		*/
	    private $_databases;

		/**
		*  The mysql connect  
		*  @property {mysqli} _connection
		*  @private
		*/
	    private $_connection;

		/**
		*  The current alias  
		*  @property {String} _currentAlias
		*  @private
		*/
		private $_currentAlias;
	
		/**
		*  If we should cache the next sql statement  
		*  @property {Boolean} _cacheNext
		*  @private
		*/
		private $_cacheNext = false;
	
		/**
		*  If we should cache all calls, no matter what  
		*  @property {Boolean} _cacheAll
		*  @private
		*/
		private $_cacheAll = false;
		
		/**
		*  The cache object for the database, must implement IDatabaseCache
		*  @property {IDatabaseCache} _cache
		*  @private
		*/
		private $_cache = null;

		/**
		*  If we are pooling queries to execute as once
		*  @property {Boolean} _pooling
		*  @private
		*/
		private $_pooling = false;

		/**
		*  The query of things to pool
		*  @property {Array} _pool
		*  @private
		*/
		private $_pool = [];
		
		/**
		*  The main cache key for the index of cached calls  
		*  @property {String} _defaultCacheContext
		*  @private
		*/
		private $_defaultCacheContext = null;
		
		/**
		*  The optional user callback reference to start profiling  
		*  @property {Array|String} profilerStart
		*  @private
		*/
		public $profilerStart = null;
		
		/**
		*  The optional user callback reference to stop profiling  
		*  @property {Array|String} profilerStop
		*  @private
		*/
		public $profilerStop = null;

		/**
		*  Abstract database connection. Use's the mysqli API.
		*
		*	$db = new Database('localhost', 'root', '12341234', 'my_database');
		*  
		*  @class Database
		*  @constructor
		*  @param {String} host The database host
		*  @param {String} username The database user
		*  @param {String} password The database password
		*  @param {Dictionary|String} databases The database name aliases as key=>names, or a single database name string
		*/
	    public function __construct($host, $username, $password, $databases)
	    {		
			if (!class_exists('mysqli')) 
	    	{
	    		throw new DatabaseError(DatabaseError::MYSQLI_REQUIRED);
	    	}
			
			if (is_array($databases) && !isset($databases['default'])) 
	        {
	    	   throw new DatabaseError(DatabaseError::DEFAULT_REQUIRED);
	    	}
			
			define('DATE_FORMAT_MYSQL', 'Y-m-d H:i:s');
			define('DATE_FORMAT_MYSQL_SHORT', 'Y-m-d');
			
			// Create the name of the index
			$this->_defaultCacheContext = 'Canteen_Database_'.$host;
			
			// Check for a collection or a single array
			$this->_databases = is_array($databases) ? $databases : ['default'=>$databases];
			
	    	// connects to the db server and selects a database
			$this->_connection = @new mysqli($host, $username, $password, $this->_databases['default']);
			
			if (mysqli_connect_error()) 
			{
			    throw new DatabaseError(
					DatabaseError::CONNECTION_FAILED, 
					mysqli_connect_error() . '('.mysqli_connect_errno().')'
				);
			}
			$this->setDatabase();
	    }
   
		/**
		*  Check to see if the database is currently connected
		*  @method isConnected
		*  @return {Boolean} If the database is connected
		*/
	    public function isConnected() 
	    {
	        return $this->_connection ? true : false;
	    }
      
		/**
		*  Get the name of the database by an alias
		*  @method getDatabaseName
		*  @param {String} [alias='default'] The valid database alias to check by
		*  @return {String} The name of the database
		*/
		public function getDatabaseName($alias='default')
		{
			if (!isset($this->_databases[$alias]))
			{
				throw new DatabaseError(DatabaseError::INVALID_ALIAS, $alias);
			}
			return $this->_databases[$alias];
		}

		/**
		*  Turn on query pooling, this will not execute queries until stopPooling is called
		*  @method startPooling
		*/
		public function startPooling()
		{
			$this->_pooling = true;
		}

		/**
		*  Execute the pooled queries in the order in which they were added
		*  @method stopPooling
		*  @param {Boolean} [cache=false] If se should cache the result for later
		*  @return {Array} The result objects or null if no pooling
		*/
		public function stopPooling($cache=false)
		{
			// We should turn this off before we execute anything else
			$this->_pooling = false;

			// The output data
			$data = null;

			if (count($this->_pool))
			{
				// Build a collection of all SQL queries
				$sql = [];
				foreach($this->_pool as $i=>$query)
				{
					$sql[$i] = $query->sql;
				}

				// The collection of mysqli_results
				$results = $this->internalExecute($sql);

				foreach($results as $i=>$result)
				{
					$query = $this->_pool[$i];

					// Parse the contents
					$query->content = $this->parseResults(
						$result, 
						$query->type,
						$query->field,
						$query->position
					);
				}
				// Copy the pool to the output
				$data = $this->_pool;
			}

			// Clear the pool
			$this->_pool = [];

			return $data;
		}
	
		/**
		*  Get the currently selected database
		*  @method currentDatabase
		*  @return {String} Name of the actual database name (not the alias)
		*/
		public function currentDatabase()
		{
			return ifsetor($this->_databases[$this->_currentAlias]);
		}
	
		/**
		*  Get the currently selected database alias
		*  @method currentAlias
		*  @return {String} Name database alias
		*/
		public function currentAlias()
		{
			return $this->_currentAlias;
		}
		
		/**
		*  Set a cache object to use
		*  @method setCache
		*  @param {IDatabaseCache} cache The cache object, interface with IDatabaseCache
		*/
		public function setCache(IDatabaseCache $cache)
		{
			$this->_cache = $cache;
		}
	
		/**
		*  Change the database by an alias
		*  @method setDatabase
		*  @param {String} [alias='default'] The alias to change to
		*  @return {int} Return 1 if we have changed and error's if we haven't
		*/
	    public function setDatabase($alias='default') 
	    {
	        if (!isset($this->_databases[$alias]))
			{
				throw new DatabaseError(DatabaseError::INVALID_ALIAS, $alias);
			}
		
			$this->_currentAlias = $alias;
		
			if (!$this->_connection->select_db($this->_databases[$alias]))
			{
				throw new DatabaseError(
					DatabaseError::INVALID_DATABASE, 
					$this->_databases[$alias]
				);
			}		
	    	return 1;
	    }
      
		/**
		*  Check to see if a table exists
		*  @method tableExists
		*  @param {String} table The name of the table
		*  @param {String} [alias=''] to check on a specific database alias
		*  @return {Boolean} If the table exists or not
		*/
	    public function tableExists($table, $alias='') 
	    {
			$tables = $this->show($alias)->result();
					
			if (!$tables) return 0;

			foreach($tables as $t)
			{
				$t = array_values($t);
				if ($t[0] == $table) return 1;
			}
			return 0;
	    }
   
		/**
		*  Function to get the next ordered ID from a table index
		*  @method nextId
		*  @param {String} table The table to search on
		*  @param {String} field The name of the field to search
		*  @return {int} The integer for the next ID
		*/
	    public function nextId($table, $field) 
	    {
			$result = $this->select($field)
				->from($table)
				->orderBy($field, 'desc')
				->limit(1)
				->result($field);
			
			return $result ? $result + 1 : 1;
	    }

	    /**
		*  Returns the auto generated id used in the last query
		*  @method insertId
		*  @return {int} The integer for the last insert
		*/
	    public function insertId() 
	    {
			return $this->_connection->insert_id;
	    }

	    /**
		*  Execute a query
		*  @method execute
		*  @param {String|Query|Array} sql The SQL query to execute or collection of queries
		*  @return {mysqli_result} The mysqli query object
		*/
	    public function execute($sql)
	    {
	    	return $this->parseQueries($sql, 'execute');
	    }

	    /**
		*  Execute a query internally
		*  @method internalExecute
		*  @private
		*  @param {String|Query|Array} sql The SQL query to execute or collection of queries
		*  @return {Array|mysqli_result} The mysqli query object
		*/
	    private function internalExecute($sql)
	    {
	    	// We can do multiple queries at once
	        if (is_array($sql)) 
	        {
	        	$allSql = implode(';', $sql);

	        	// Start profiling if we have a custom function
				if ($this->profilerStart) call_user_func($this->profilerStart, $allSql);

	        	$res = @$this->_connection->multi_query($allSql);
	        	if (!$res)
				{
					throw new DatabaseError(
						DatabaseError::EXECUTE,
						mysqli_error($this->_connection)
						. ' ('.mysqli_errno($this->_connection).') '
						. ', Query: "'.$allSql.'"' 
					);
				}

				// The collectino of mysqli_result objects
	        	$results = [];

				do {
					/* store first result set */
					if ($res = $this->_connection->store_result())
					{
						$results[] = $res;
					}
					if (!$this->_connection->more_results()) break;
				} 
				while ($this->_connection->next_result());

				// Stop profiling if we have a custom function
				if ($this->profilerStop) call_user_func($this->profilerStop);

				return $results;
	        }
	        // Do a single query
	        else 
	        {
				// Start profiling if we have a custom function
				if ($this->profilerStart) call_user_func($this->profilerStart, $sql);
								
				$res = @$this->_connection->query($sql);
				if (!$res)
				{
					throw new DatabaseError(
						DatabaseError::EXECUTE,
						mysqli_error($this->_connection)
						. ' ('.mysqli_errno($this->_connection).') '
						. ', Query: "'.$sql.'"' 
					);
				}
				
				// Stop profiling if we have a custom function
				if ($this->profilerStop) call_user_func($this->profilerStop);
				
	            return $res;
	        }
	    }
	
		/**
		*  Fetch an array of associate array results for a query
		*  @method getArray
		*  @param {String|Array} sql The SQL query or collection of queries to get array results for
		*  @param {Boolean} [cache=false] If we should cache the result (default is false)
		*  @return {Array} The array of results or null (if invalid result)
		*/
	    public function getArray($sql, $cache=false)
	    {
	    	return $this->parseQueries($sql, 'getArray', $cache);
	    }
   
		/**
		*  Count the number of return rows for a sql query
		*  @method getLength
		*  @param {String} sql The SQL query
		*  @param {Boolean} [cache=false] If this should be cached
		*  @return {int} Number of rows
		*/
	    public function getLength($sql, $cache=false)
	    {
	    	return $this->parseQueries($sql, 'getLength', $cache);
	    }
   
		/**
		*  Fetch a single value from an sql call
		*  @method getResult
		*  @param {String} sql The SQL query
		*  @param {String} field The name of the field
		*  @param {int} [position=0] The position of the row to return, default is first row
		*  @param {Boolean} [cache=false] If this should be cached
		*  @return {mixed} The value of the field or null
		*/
	    public function getResult($sql, $field, $position=0, $cache=false)
	    {
	    	return $this->parseQueries($sql, 'getResult', $cache, $field, $position);
	    }
   
		/**
		*  Fetch a single row from the database
		*  @method getRow
		*  @param {String} sql The SQL query
		*  @param {Boolean} [cache=false] If this should be cached
		*  @return {Array} The row as a non-associative array
		*/
	    public function getRow($sql, $cache=false)
	    {
			return $this->parseQueries($sql, 'getRow', $cache);
	    }

	    /**
	    *  Parse the queries as a type
	    *  @method parseQueries
	    *  @private
	    *  @param {String|Query|Array} sql The collection or single SQL query
	    *  @param {String} type The type of parse, 'getRow', 'getResult', 'getArray', 'getLength'
	    *  @param {Boolean} [cache=false] If we should cache the result
	    *  @param {String} [field=null] The field name for getResult
	    *  @param {int} [position=null] The position number for getResult
	  	*  @return {mixed} The result data
	    */
	    private function parseQueries($sql, $type, $cache=false, $field=null, $position=null)
	    {
	    	$key = is_array($sql) ? implode(';', $sql) : $sql;

			if ($data = $this->read(__METHOD__, $key, $cache))
			{
				return $data;
			}

			// Check to see if we're pooling queries to execute later
        	if ($this->_pooling)
        	{
        		if (is_array($sql))
        		{
        			foreach($sql as $s)
	        		{
	        			$this->_pool[] = new QueryResult($s, $type, $field, $position);
	        		}
        		}
        		else
        		{
        			$this->_pool[] = new QueryResult($sql, $type, $field, $position);
        		}

        		// Don't execute further
        		// need to call stopPooling
        		return;
        	}

        	// Set the output if everything goes wrong
	    	$data = null;

	    	// Convert the query/queries into a collection of mysqli_result objects
	    	$results = $this->internalExecute($sql);

	    	// Parse the mysqli results
	    	$data = $this->parseResults($results, $type, $field, $position);

	    	// If we have some data and we request a cache
			if ($data !== null)
			{
				$this->save(__METHOD__, $key, $data, $cache);
			}
			return $data;
	    }

	    /**
	    *  Parse the queries as a type
	    *  @method parseResults
	    *  @private
	    *  @param {mysqli_result|Array} results The collection or single mysqli_result
	    *  @param {String} type The type of parse, 'getRow', 'getResult', 'getArray', 'getLength'
	    *  @param {String} [field=null] The field name for getResult
	    *  @param {int} [position=null] The position number for getResult
	  	*  @return {mixed} The result data
	    */
	    private function parseResults($results, $type, $field=null, $position=null)
	    {
	    	$data = null;

	    	// See if the results are a collection
	    	$isSingle = !is_array($results);
	    	if ($isSingle) $results = [$results];

	    	// Loop through all the results
			foreach($results as $i=>$result)
			{
				if (!$result) continue;
				
				if ($data == null) $data = [];

				switch($type)
				{
					case 'getResult' :
					{
						if ($result->num_rows)
						{

							$result->data_seek($position);
							$row = $result->fetch_array();
							$data[$i] = isset($row[$field]) ? $row[$field] : null;
						}
						else
						{
							$data[$i] = null;
						}
						break;
					}
					case 'getLength' :
					{
						$data[$i] = $result->num_rows;
						break;
					}
					case 'getRow' :
					{
						$data[$i] = $result->fetch_row();
						break;
					}
					case 'getArray' :
					{
						$total = $result->num_rows;
						$rows = [];
				        if ($total)
				        {
				        	for ($j = 0; $j < $total; $j++) 
				            {
								$rows[$j] = $result->fetch_assoc();
				            }
				        }
				        $data[$i] = $rows;
				        break;
					}
					case 'execute' :
					{
						$data[$i] = (bool)$result;
						break;
					}
					default :
					{
						$data[$i] = null;
						break;
					}
				}

				// Free memory from the result
				if ($result instanceof mysqli_result)
				{
					$result->free();
				}
			}

			// Convert the data back into a single result
			if ($isSingle && count($data) == 1)
			{
				$data = current($data);
			}

			return $data;
	    }
		
		/**
		*  Show the current tables on a database
		*  @method show
		*  @param {String} [alias=''] The optional alias, defaults to the current database
		*  @return {ShowQuery} The array of tables on this database
		*/
		public function show($alias='')
		{
			if ($alias && isset($this->_databases[$alias]))
			{
				return new ShowQuery($this, $this->_databases[$alias]);
			}
			return new ShowQuery($this);
		}
		
		/**
		*  An update function
		*  @method update 
		*  @param {String} tables* The tables as arguments e.g. update('users', 'users_other')
		*  @return {UpdateQuery} New update query
		*/
		public function update($tables)
		{
			$tables = is_array($tables) ? $tables : func_get_args();
			return new UpdateQuery($this, $tables);
		}
	
		/**
		*  A method for deleting rows from table(s)
		*  @method delete
		*  @param {String} tables* The tables as arguments e.g. delete('users', 'users_other')
		*  @return {DeleteQuery} New delete query
		*/
		public function delete($tables)
		{
			$tables = is_array($tables) ? $tables : func_get_args();
			return new DeleteQuery($this, $tables);
		}
		
		/**
		*  Insert query
		*  @method insert
		*  @param {String} tables* The tables as arguments e.g. insert('users', 'users_other')
		*  @return {InsertQuery} New insert query
		*/
		public function insert($tables)
		{
			$tables = is_array($tables) ? $tables : func_get_args();
			return new InsertQuery($this, $tables);
		}
		
		/**
		*  Create a select query
		*  @method select
		*  @param {Array|String} [properties='*'] The list of properties, can be multiple arguments
		*  @return {SelectQuery} The select query
		*/
		public function select($properties='*')
		{
			$args = func_num_args() == 0 ? [$properties] : func_get_args();
			return new SelectQuery($this, is_array($properties) ? $properties : $args);
		}

		/**
		*  Create a new table on the database
		*  @method create
		*  @param {String} table The name of the table to create
		*  @return {CreateQuery} The create query
		*/
		public function create($table)
		{
			return new CreateQuery($this, $table);
		}
		
		/**
		*  Remove all of the rows from a table
		*  @method truncate
		*  @param {String} table The name of the table to truncate
		*  @return {TruncateQuery} The new truncate query
		*/
		public function truncate($table)
		{
			return new TruncateQuery($this, $table);
		}
	
		/**
		*  Remove a table
		*  @method drop
		*  @param {String} table The name of an existing table to drop
		*  @return {DropQuery} The new drop query
		*/
		public function drop($table)
		{
			return new DropQuery($this, $table);
		}
		
		/**
		*  Escape a value to add
		*  @method escapeString
		*  @param {String|Array} value The value to add or collection of values
		*  @return {String|Array} The escaped string or collection of strings
		*/
		public function escapeString($value)
		{
			if (is_array($value))
			{
				foreach($value as $i=>$v)
				{
					$value[$i] = $this->escapeString($v);
				}
				return $value;
			}
			return $this->_connection->real_escape_string($value);
		}

		/**
		*  Cache the next select, row, result or count
		*  @method cacheNext
		*/
		public function cacheNext()
		{
			$this->_cacheNext = true;
		}
	
		/**
		*  If we want to turn on caching for all select, row, result or count
		*  @method cacheAll
		*  @param {Boolean} [enabled=true] If we should enable this defaults to true
		*/
		public function cacheAll($enabled=true)
		{
			$this->_cacheAll = $enabled;
		}
		
		/**
		*  Get the cache data if available and active
		*  @method read
		*  @private
		*  @param {String} type The name of the method being cached
		*  @param {String} sql The SQL query
		*  @param {Boolean} cache If this should be cached
		*  @return {mixed} The cached data or false
		*/
		private function read($type, $sql, $cache)
		{
			if ($this->_cache && ($this->_cacheAll || $this->_cacheNext || $cache))
			{
				$cacheId = md5('Database::'.$type.' ; ' .$sql);
				$data = $this->_cache->read($cacheId);
				$this->_cacheNext = false;
				
				if ($data !== false)
				{
					return $data;
				}
			}
			return false;
		}
		
		/**
		*  Save the cache data
		*  @method save
		*  @private
		*  @param {String} type The name of the method being cached
		*  @param {String} sql The SQL query
		*  @param {mixed} data The data to set
		*  @param {Boolean} cache If this should be cached
		*  @return {Boolean} If the cache was saved
		*/
		private function save($type, $sql, $data, $cache)
		{
			if ($this->_cache && ($this->_cacheAll || $cache))
			{
				$cacheId = md5('Database::'.$type.' ; ' .$sql);
				return $this->_cache->save($cacheId, $data, $this->_defaultCacheContext);
			}
			return false;
		}

		/**
		*  Flush the cache
		*  @method flush
		*  @return {Boolean} If flush was successful
		*/
		public function flush()
		{
			if ($this->_cache)
				$this->_cache->flushContext($this->_defaultCacheContext);
		}
   
		/**
		*  Close the database connection, if any is open
		*  @method disconnect
		*/
	    public function disconnect() 
	    {
	        //close the database
	        if ($this->_connection)
			{
				$this->_connection->close();
				$this->_connection = null;
			}
	    }
	}
}