<?php namespace Session;
/**
 * Session handler
 ** 
 *
 * @package		ClanCatsFramework
 * @author		Mario Döring <mario@clancats.com>
 * @version		2.0
 * @copyright 	2010 - 2014 ClanCats GmbH
 *
 */
class CCSession 
{
	/**
	 * Get an instance of a session manager
	 *
	 * @param string 			$manager
	 * @return Session\Manager
	 */
	public static function manager( $manager = null )
	{
		return Manager::create( $manager );
	}
	
	/**
	 * Get a value from the session
	 *
	 * @param string				$key
	 * @param string 			$default
	 * @param string				$manager
	 * @return Session\Manager
	 */
	public static function get( $key, $default, $manager = null )
	{
		return Manager::create( $manager )->get( $key, $default );
	}
	
	/**
	 * Set a value on the session
	 *
	 * @param string				$key
	 * @param string 			$value
	 * @param string				$manager
	 * @return Session\Manager
	 */
	public static function set( $key, $value, $manager = null )
	{
		return Manager::create( $manager )->set( $key, $value );
	}
	
	/**
	 * Has a value on the session
	 *
	 * @param string				$key
	 * @param string				$manager
	 * @return Session\Manager
	 */
	public static function has( $key, $manager = null )
	{
		return Manager::create( $manager )->has( $key );
	}
	
	/**
	 * Delete a value on the session
	 *
	 * @param string				$key
	 * @param string				$manager
	 * @return Session\Manager
	 */
	public static function delete( $key, $manager = null )
	{
		return Manager::create( $manager )->delete( $key );
	}
}