<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{	
	use \mysqli;
	
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
		*  If we're suppose to use mysqli class  
		*  @property {Boolean} _useMysqli
		*  @private
		*/
		private $_useMysqli;
		
		/**
		*  The cache object for the database, must implement IDatabaseCache
		*  @property {IDatabaseCache} _cache
		*  @private
		*/
		private $_cache = null;
		
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
		*  Abstract database connection. Use's the mysqli API (with a fall back to the mysql
		*  procedural methods, which'll be deprecated in PHP 5.5.0).
		*
		*	$db = new Database('localhost', 'root', '12341234', 'my_database');
		*  
		*  @class Database
		*  @constructor
		*  @param {String} host The database host
		*  @param {String} username The database user
		*  @param {String} password The database password
		*  @param {Dictionary|String} databases The database name aliases as key=>names, or a single database name string
		*  @param {Boolean} [useMysqli=true] If we should use mysqli API
		*/
	    public function __construct($host, $username, $password, $databases, $useMysqli=true)
	    {		
			$this->_useMysqli = $useMysqli;
		
			// If we're trying to use mysqli but don't have it installed
			// then fallback to mysql
			if ($this->_useMysqli && !class_exists('mysqli'))
			{
				$this->_useMysqli = false;
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
			$this->_databases = is_array($databases) ? $databases : array('default'=>$databases);
			
	    	// connects to the db server and selects a database
			if ($this->_useMysqli)
			{
				$this->_connection = @new mysqli($host, $username, $password, $this->_databases['default']);
				
				if (mysqli_connect_error()) 
				{
				    throw new DatabaseError(
						DatabaseError::CONNECTION_FAILED, 
						mysqli_connect_error() . '('.mysqli_connect_errno().')'
					);
				}
			}
			else
			{
				if (!$this->_connection = @mysql_connect($host, $username, $password))
				{
					throw new DatabaseError(
						DatabaseError::CONNECTION_FAILED, 
						mysql_error() . '('.mysql_errno().')'
					);
				}
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
		
			if ($this->_useMysqli)
			{
				if (!$this->_connection->select_db($this->_databases[$alias]))
				{
					throw new DatabaseError(
						DatabaseError::INVALID_DATABASE, 
						$this->_databases[$alias]
					);
				}
			}
			else
			{
				if (!mysql_select_db($this->_databases[$alias], $this->_connection))
				{
					throw new DatabaseError(
						DatabaseError::INVALID_DATABASE, 
						$this->_databases[$alias]
					);
				}
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
		*  Execute a query
		*  @method execute
		*  @param {String} sql The SQL query to execute
		*  @return {resource|mysqli_result} Either the mysqli query object or the mysql_query resource
		*/
	    public function execute($sql)
	    {
	    	// $this->_connection is a valid DB resource.
	    	// $sql is the sql query to run.
	    	// Success will return a valid result set
	    	// Failure will return false
	        // Optionally, pass in an array of querys to run
	        if (is_array($sql)) 
	        {
				$i = 0;
	            foreach ($sql as $query) 
	            {
					$i += $this->execute($query) ? 1 : 0;
	            }
	            return $i;
	        }
	        else 
	        {
				// Start profiling if we have a custom function
				if ($this->profilerStart) call_user_func($this->profilerStart, $sql);
								
				if ($this->_useMysqli)
				{
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
				}
				else
				{
					$res = @mysql_query($sql);
					if (!$res)
					{
						throw new DatabaseError(
							DatabaseError::EXECUTE,
							mysqli_error($this->_connection)
							. ' ('.mysqli_errno($this->_connection).') '
							. $sql 
						);
					}
				}
				
				// Stop profiling if we have a custom function
				if ($this->profilerStop) call_user_func($this->profilerStop);
				
	            return $res;
	        }
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
		*  Fetch an array of associate array results for a query
		*  @method getArray
		*  @param {String} sql The SQL query to get array results for
		*  @param {Boolean} [cache=false] If we should cache the result (default is false)
		*  @return {Array} The array of results or null (if invalid result)
		*/
	    public function getArray($sql, $cache=false) 
	    {
			if ($data = $this->read(__METHOD__, $sql, $cache))
			{
				return $data;
			}
			
			$res = $this->execute($sql);
			$total = $this->getLengthInternal($res);
			
	        if ($total)
	       	{
	            $data = array();
	            
	            for ($i=0; $i<$total; $i++) 
	            {
					if ($this->_useMysqli)
					{
						$data[] = $res->fetch_assoc();
					}
					else
					{
						$data[] = mysql_fetch_assoc($res);
					}
	            }
			
				// If we have some data and we request a cache
				if ($data)
				{
					$this->save(__METHOD__, $sql, $data, $cache);
				}
	            return $data;
	        }
	        return null;
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
		*  Count the number of return rows for a sql query
		*  @method getLength
		*  @param {String} sql The SQL query
		*  @param {Boolean} [cache=false] If this should be cached
		*  @return {int} Number of rows
		*/
	    public function getLength($sql, $cache=false)
	    {
			if ($data = $this->read(__METHOD__, $sql, $cache))
			{
				return $data;
			}
			
			$data = $this->getLengthInternal($this->execute($sql));
			
			// If we have some data and we request a cache
			$this->save(__METHOD__, $sql, $data, $cache);
		
			return $data;
	    }
   
		/**
		*  Fetch a single value from an sql call
		*  @method getResult
		*  @param {String} sql The SQL query
		*  @param {String} field The name of the field
		*  @param {int} [position=0] The position of the row to return, default is first row
		*  @param {Boolean} [cache=false] If this should be cached
		*  @return {mixed} The value of the field or 0
		*/
	    public function getResult($sql, $field, $position=0, $cache=false)
	    {
			if ($data = $this->read(__METHOD__, $sql, $cache))
			{
				return $data;
			}
		
	    	// $res is a valid result set.
	    	// $row is an integer value of the row which 
	    	// contains the result to be fetched.
	    	// $field is the name of the field to be returned.
			$data = '';
			$res = $this->execute($sql);
			$total = $this->getLengthInternal($res);
			
			if (!$total) return 0;
			
			if ($this->_useMysqli)
			{
				$i=0;
				$res->data_seek(0);
				while ($row = $res->fetch_array(MYSQLI_BOTH))
				{
					if ($i == $position) $data = $row[$field];
					$i++;
				}
			}
			else
			{
		    	$data = ( !$data = mysql_result($res, $position, $field) ) ? 0 : $data;
			}
			
			unset($res);
		
			// If we have some data and we request a cache
			if ($data !== 0)
			{
				$this->save(__METHOD__, $sql, $data, $cache);
			}
			return $data;
	    }
	
		/**
		*  Get the length of a resource
		*  @method getLengthInternal
		*  @private
		*  @param {mysqli_result|resource} res The database resource, either mysql_result resource or mysqli_result object
		*/
		private function getLengthInternal($res)
		{
			if (!$res) return 0;
			
			return ($this->_useMysqli) ? $res->num_rows : mysql_num_rows($res);
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
			if ($data = $this->read(__METHOD__, $sql, $cache))
			{
				return $data;
			}
		
	    	// $res is a valid result set.
	    	// $row is an integer value of the row which 
	    	// contains the result to be fetched.
	    	// $field is the name of the field to be returned.
			$data = array();
	    	$res = $this->execute($sql);

			if ($this->_useMysqli)
			{
		    	$data = ( !$data = $res->fetch_row() ) ? 0 : $data;
			}
			else
			{
		    	$data = ( !$data = mysql_fetch_row($res) ) ? 0 : $data;
			}
			
			unset($res);
			
			// If we have some data and we request a cache
			if ($data !== 0)
			{
				$this->save(__METHOD__, $sql, $data, $cache);
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
			$args = func_num_args() == 0 ? array($properties) : func_get_args();
			return new SelectQuery($this, is_array($properties) ? $properties : $args);
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
			return ($this->_useMysqli) ?
				$this->_connection->real_escape_string($value):
				mysql_real_escape_string($value);
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
				if ($this->_useMysqli)
				{
					$this->_connection->close();
				}
				else
				{
					mysql_close($this->_connection);
				}
				$this->_connection = null;
			}
	    }
	}
}