<?php

/**
*  @module Canteen\Database
*/
namespace Canteen\Database
{
	/**
	*  The object when doing multiple queries or pooling
	*  @class QueryResult
	*  @constructor 
	*  @param {Query|String} sql The query to execute
	*  @param {String} The type of query 'getLength', 'getRow', 'getResult', 'getArray', 'execute'
	*  @param {String} [field] Optional field name for getResult type only
	*  @param {int} [position] Optional position for getResult type only
	*/
	class QueryResult
	{
		/**
		*  The type of result
		*  @property {String} type
		*/
		public $type;

		/** 
		*  The SQL Query object or query string
		*  @property {Query|String} sql
		*/
		public $sql;

		/**
		*  The result of the data
		*  @property {mixed} content
		*/
		public $content;

		/**
		*  The field name for getResult type
		*  @property {String} field
		*/
		public $field;

		/**
		*  The position number for getResult type
		*  @property {int} position
		*/
		public $position;

		/**
		*  Constructor
		*/
		public function __construct($sql, $type, $field=null, $position=null)
		{
			$this->sql = $sql;
			$this->type = $type;
			$this->field = $field;
			$this->position = $position;
		}
	}
}