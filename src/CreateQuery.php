<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	class CreateQuery extends Query
	{
		/**
		*  The MySQL table engine to use
		*  @property {String} engine
		*  @default 'MyISAM'
		*  @private
		*/
		private $engine = 'MyISAM';

		/**
		*  The MySQL character set to use
		*  @property {String} charset
		*  @default 'latin1'
		*  @private
		*/
		private $charset = 'latin1';

		/**
		*  The MySQL collate set to use
		*  @property {String} collate
		*  @default 'latin1_general_ci'
		*  @private
		*/
		private $collate = 'latin1_general_ci';

		/**
		*  The name of the table to create
		*  @property {String} table
		*  @private
		*/
		private $table;

		/**
		*  The starting auto increment amount
		*  @property {int} autoIncrement
		*  @property
		*  @default null
		*/
		private $autoIncrement = null;

		/**
		*  The collection of fields to add
		*  @property {Array} fields
		*  @private
		*/
		private $fields = [];

		/**
		*  The collection of keys to add
		*  @property {Array} keys
		*  @private
		*/
		private $keys = [];
		
		/**
		*  Create a new truncate query
		*  
		*	$tables = $db->create('users')
		*		->fields(
		*			"`msgid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT",
		*			"`parent` mediumint(8) unsigned NOT NULL DEFAULT '0'",
  		*			"`subject` varchar(255) NOT NULL DEFAULT ''",
		*		)
		*		->primaryKey('msgid')
		*		->fulltextKey('subject')
		*		->result();
		*  
		*  @class CreateQuery
		*  @extends Query
		*  @constructor
		*  @param {Database} db Reference to the database
		*  @param {String} table The name of the table to add
		*/
		public function __construct(Database $db, $table)
		{
			parent::__construct($db);
			$this->table = $table;
		}

		/**
		*  Set the engine type
		*  @method engine
		*  @param {String} engine The name of the engine to use for talbe
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function engine($engine)
		{
			$this->engine = $engine;
			return $this;
		}

		/**
		*  Define the character set to use on table
		*  @method charset
		*  @param {String} charset The name of the charset
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function charset($charset)
		{
			$this->charset = $charset;
			return $this;
		}

		/**
		*  Define the character set collate to use on table
		*  @method collate
		*  @param {String} collate The name of the collate
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function collate($collate)
		{
			$this->collate = $collate;
			return $this;
		}

		/**
		*  Add a collection of fields
		*  @method fields
		*  @param {String} fields* Add a n-number of string arguments 
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function fields($fields)
		{
			$fields = is_array($fields) ? $fields : func_get_args();
			$this->fields = array_merge($this->fields, $fields);
			return $this;
		}

		/**
		*  Add a single field by name and properties
		*  @method field
		*  @param {String} fieldName The name of the field to add
		*  @param {String} The properties, including the type
		*  @param {Boolean} [isNull=true] If the value can be null
		*  @param {String} [default] The default value, if any
		*  @param {Boolean} [autoIncrement=false] If we should auto increment this field
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function field($fieldName, $properties, $isNull=true, $default=null, $autoIncrement=false)
		{
			$field = "`$fieldName` $properties";
			if ($default !== null)
			{
				$field .= " DEFAULT '$default'";
			}
			$field .= $isNull ? ' NULL' : ' NOT NULL';
			if ($autoIncrement)
			{
				$field .= ' AUTO_INCREMENT';
				$this->primaryKey($fieldName);
			}
			$this->fields[] = $field;
			return $this;
		}

		/**
		*  Add a character set field to the table
		*  @method set
		*  @param {String} fieldName
		*  @param {Array} value The collection of string values
		*  @param {Boolean} [isNull=true] If the value can be null
		*  @param {String} [default] The default value, if any
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function set($fieldName, array $values, $isNull=true, $default=null)
		{
			$properties = "set('".implode("','", $values)."')";
			return $this->field($fieldName, $properties, $isNull, $default);
		}

		/**
		*  Internal method to create a key on the query
		*  @method internalKey
		*  @private
		*  @param {String} fieldName The name of the field
		*  @param {String} [type=''] Either UNIQUE, FULLTEXT or empty
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		private function internalKey($fieldName, $type='')
		{
			$this->keys[] = $type."KEY `{$fieldName}` (`{$fieldName}`)";
		}

		/**
		*  Create a key on the table, this is non-unique and non-primary
		*  @method key
		*  @param {String} fieldName The name of the field
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function key($fieldName)
		{
			$this->internalKey($fieldName);
			return $this;
		}

		/**
		*  Create a unique key on the table, this is unique and non-primary
		*  @method uniqueKey
		*  @param {String} fieldName The name of the field
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function uniqueKey($fieldName)
		{
			$this->internalKey($fieldName, 'UNIQUE ');
			return $this;
		}

		/**
		*  Create a primary key on the table, this is unique and primary
		*  @method primaryKey
		*  @param {String} fieldName The name of the field
		*  @param {int} [startingValue=1] The starting auto increment value
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function primaryKey($fieldName, $startingValue=1)
		{
			$this->autoIncrement = $startingValue;
			$this->keys[] = "PRIMARY KEY (`{$fieldName}`)";
			return $this;
		}

		/**
		*  Create a fulltext key on the table
		*  @method fulltextKey
		*  @param {String} fieldName The name of the field
		*  @return {CreateQuery} Instance of this call for chaining
		*/
		public function fulltextKey($fieldName)
		{
			$this->internalKey($fieldName, 'FULLTEXT ');
			return $this;
		}

		/**
		*  Represent the query as a SQL statement
		*  @method __toString
		*  @return {String} The query in SQL string form 
		*/
		public function __toString()
		{
			$sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (";
			$sql .= implode(', ', $this->fields) . ', ' . implode(', ', $this->keys);
			$sql .= ') ENGINE='.$this->engine.' DEFAULT CHARSET='.$this->charset;

			if ($this->collate)
				$sql .= ' COLLATE='.$this->collate;

			if ($this->autoIncrement)
				$sql .= ' AUTO_INCREMENT='.$this->autoIncrement;

			return $sql;
		}
	}
}