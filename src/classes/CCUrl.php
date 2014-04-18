<?php namespace Core;
/**
 * ClanCats Url
 * @todo if have to rewrite this shitty class some day..
 ** 
 *
 * @package		ClanCatsFramework
 * @author		Mario Döring <mario@clancats.com>
 * @version		2.0
 * @copyright 	2010 - 2014 ClanCats GmbH
 *
 */
class CCUrl 
{
	/**
	 * The configured path offset
	 *
	 * @var string
	 */
	private static $path_offset = null;
	
	/**
	 * static CCUrl initialisation
	 */
	public static function _init() 
	{
		static::$path_offset = ClanCats::$config->get( 'url.path', '/' );
		
		if ( empty( static::$path_offset ) )
		{
			static::$path_offset = '/';
		}
		
		if ( substr( static::$path_offset, -1 ) != '/' )
		{
			static::$path_offset .= '/';
		}
	}

	/**
	 * Generate an url
	 *
	 * @param string			$uri
	 * @param array			$params
	 * @param bool			$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function to( $uri = '', $params = array(), $retain = false ) 
	{
		// To avoid // urls we check for a single slash.
		if ( $uri === '/' ) 
		{
			$uri = '';
		}
		
		// When the uri starts with an @ sign we handle the uri as route alias.
		if ( substr( $uri, 0, 1 ) == '@' ) 
		{
			return static::alias( substr( $uri, 1 ), $params, $retain );
		}
		
		// Are there already parameters in the uri? Parse them
		// and merge them with current argument parameters
		if ( strpos( $uri, '?' ) !== false )
		{
			$parts = explode( '?', $uri );
			
			$uri = $parts[0];
			
			if ( isset( $parts[1] ) )
			{
				parse_str( $parts[1], $old_params );
				
				$params = array_merge( $old_params, $params );
			}
		}
		
		
		// When the uri contains a protocoll or starts with a slash we assume
		// a full url is given and we don't have to add a path offest.
		if ( strpos( $uri, '://' ) === false && substr( $uri, 0, 1 ) !== '/' ) 
		{
			$uri = static::$path_offset.$uri;
		}
		
		// Try to replace parameters in the uri and remove them from
		// the array so we can append them as get parameters
		foreach( $params as $key => $value )
		{
			$uri = str_replace( ':'.$key, $value, $uri, $count );
			
			if ( $count > 0 )
			{
				unset( $params[$key] );
			}
		}
		
		// Should we keep the get parameters? If retain is enabled
		// we merge the get parameter array with argument parameters
		if ( $retain )
		{
			$params = array_merge( CCIn::$_instance->GET, $params );
		}
		
		// When we still got parameters add them to the url
		if ( !empty( $params ) ) 
		{
			$uri .= '?'.http_build_query( $params );
		}

		return $uri;
	}
	
	/**
	 * Create an URL based on an router alias
	 *
	 * @param string		$alias
	 * @param array  	$params
	 * @param bool		$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function alias( $alias, $params = array(), $retain = false )
	{
		$route_params = array();
		
		// get the parameters with the numeric keys so we can 
		// pass them as route parameters like [any]-[num]-[num]/[any]/
		foreach( $params as $key => $value )
		{
			if ( is_int( $key ) )
			{
				$route_params[] = $value; unset( $params[$key] );
			}
		}
		
		return CCUrl::to( CCRouter::alias( $alias, $route_params ), $params, $retain );
	}

	/**
	 * Create the full url including protocol and hostname
	 *
	 * @param string		$uri
	 * @param array  	$params
	 * @param bool		$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function full( $uri = '', $params = array(), $retain = false ) 
	{
		return CCIn::protocol().'://'.CCIn::host().static::to( $uri, $params, $retain );
	}
	
	/**
	 * Create the url and force the https protocol
	 *
	 * @param string		$uri
	 * @param array  	$params
	 * @param bool		$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function secure( $uri = '', $params = array(), $retain = false ) 
	{
		return 'https://'.CCIn::host().static::to( $uri, $params, $retain );
	}

	/**
	 * Get the current url
	 *
	 * @param array  	$params
	 * @param bool		$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function current( $params = array(), $retain = false ) 
	{
		return static::to( CCIn::uri(), $params, $retain );
	}
	
	/**
	 * Get the url to a action of the current route
	 *
	 * @param string 	$action
	 * @param array  	$params
	 * @param bool		$retain		Should we keep the get parameters?
	 * @return string 
	 */
	public static function action( $action = '', $params = array(), $retain = false ) 
	{
		if ( $action == 'index' )
		{
			$action = '';
		}
		
		if ( CCRequest::current() && ( $route = CCRequest::current()->route ) )
		{
			$uri =  substr( $route->uri, 0, strlen( $route->action ) * -1 );
			
			if ( substr( $uri, -1 ) != '/' )
			{
				$uri .= '/';
			}
			
			return static::to( $uri.$action, $params, $retain );
		}
		
		throw new CCException( 'CCUrl::action - There has been no route executed yet.' );	
	}
	
	
}