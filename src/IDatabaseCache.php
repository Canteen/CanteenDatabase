<?php

/**
*  @module Canteen\Database 
*/
namespace Canteen\Database
{
	/**
	*  Interface for the external database cache, optional
	*  @class IDatabaseCache
	*/
	interface IDatabaseCache
	{
		/**
		*  Read an item back from the cache
		*  @method read
		*  @param {String} key The key of the item
		*  @param {Boolean} [output=false] If the file should be output directly (better memory management)
		*  @return {mixed} Return false if we can't read it, or it doesn't exist
		*/
		public function read($key, $output=false);
		
		/**
		*  Save and item to the server
		*  @method save
		*  @param {String} key The key of the item
		*  @param {mixed} key The value of the item
		*  @param {String} [context=null] The optional group context for the cache
		*  @param {int} [expires=-1] How many seconds before this expires, defaults to expiresDefault
		*  @param {Boolean} If we were able to save successfully
		*/
		public function save($key, $value, $context=null, $expires=-1);
		
		/**
		*  Delete a context (which is a group of related keys)
		*  @method flushContext
		*  @param {String} context The name of the context
		*  @return {Boolean} If we successfully deleted the context
		*/
		public function flushContext($context);
	}
}