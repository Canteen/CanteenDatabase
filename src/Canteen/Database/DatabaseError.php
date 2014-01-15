<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{		
	/**
	*  Database-specific error
	*/
	class DatabaseError extends \Exception
	{
		/**
		*  The database connection failed  
		*  @property {int} CONNECTION_FAILED
		*  @static
		*  @final
		*/
	 	const CONNECTION_FAILED = 300;
	
		/**
		*  The alias for a database is invalid  
		*  @property {int} INVALID_ALIAS
		*  @static
		*  @final
		*/
		const INVALID_ALIAS = 301;
		
		/**
		*  The database name we're trying to switch to is invalid  
		*  @property {int} INVALID_DATABASE
		*  @static
		*  @final
		*/
		const INVALID_DATABASE = 302;
		
		/**
		*  The mysql where trying to execute was a problem  
		*  @property {int} EXECUTE
		*  @static
		*  @final
		*/
		const EXECUTE = 303;
		
		/**
		*  A default name is required  
		*  @property {int} DEFAULT_REQUIRED
		*  @static
		*  @final
		*/
		const DEFAULT_REQUIRED = 304;

		/**
		*  The MySQLi PHP extension is required.
		*  @property {int} MYSQLI_REQUIRED
		*  @static
		*  @final
		*/
		const MYSQLI_REQUIRED = 305;
		
		/**
		*  Look-up for error messages
		*  @property {Dictionary} messages
		*  @private
		*  @static
		*/
		private static $messages = [
			self::CONNECTION_FAILED => 'Unable to connect',
			self::INVALID_ALIAS => 'Database alias doesn\'t exist',
			self::INVALID_DATABASE => 'Unable to find selected database',
			self::EXECUTE => 'Unable to execute query',
			self::DEFAULT_REQUIRED => 'Database name references requires a default name',
			self::MYSQLI_REQUIRED => 'The MySQLi PHP extension is required'
		];
		
		/**
		*  The label for an error that is unknown or unfound in messages  
		*  @property {String} UNKNOWN
		*  @static
		*  @final
		*/
		const UNKNOWN = 'Unknown error';
		
		/**
		*  Create the database error
		*  
		*  @class DatabaseError
		*  @extends Exception
		*  @constructor
		*  @param {int} code The code of the error
		*  @param {String} [data=''] The extra data to pass with the error
		*/
		public function __construct($code, $data='')
		{
			$messages = ifsetor($messages, self::$messages);
			$message =  ifsetor($messages[$code], self::UNKNOWN) 
				. ($data ? ' : ' . $data : $data);	
			parent::__construct($message, $code);
		}
	}
}