<?php namespace Core;
/**
 * Validator
 * Input validation engine
 ** 
 *
 * @package		ClanCatsFramework
 * @author		Mario Döring <mario@clancats.com>
 * @version		2.0
 * @copyright 	2010 - 2014 ClanCats GmbH
 *
 */
class CCValidator 
{
	/**
	 * Rule extensions
	 *
	 * @var array[callbacks]
	 */
	protected static $rules = array();
	
	/**
	 * Add new rule to the validator
	 *
	 * @param string			$name
	 * @param callback		$callback
	 * @return void
	 */
	public static function rule( $name, $callback ) 
	{
		static::$rules[$name] = $callback;
	}
	
	/**
	 * Create a new validator object
	 *
	 * @param array 				$data
	 * @return CCValidator
	 */
	public static function create( $data = array() ) 
	{
		return new static( $data );
	}
	
	/**
	 * Create a new validator from post data
	 *
	 * @param array 				$data
	 * @return CCValidator
	 */
	public static function post( $data = array() ) 
	{
		return new static( array_merge( CCIn::$_instance->POST, $data ) );
	}
	
	/**
	 * Data container
	 *
	 * @var array
	 */
	private $data = null;
	
	/**
	 * Failed tests container
	 *
	 * @var array
	 */
	private $failed = array();

	/**
	 * validation success
	 *
	 * @var bool
	 */
	private $success = true;
	
	/**
	 * Validator constructor
	 *
	 * @param array 			$data
	 * @return void
	 */
	public function __construct( $data = array() ) 
	{
		$this->data = $data;
	}
	
	/**
	 * Did the input pass the validation
	 *
	 * @return bool
	 */
	public function success()
	{
		return $this->success;
	}
	
	/**
	 * Did the input not pass the validation
	 *
	 * @return bool
	 */
	public function failure()
	{
		return !$this->success;
	}
	
	/**
	 * Return the failed tests
	 *
	 * @return array
	 */
	public function failed()
	{
		return $this->failed;
	}
	
	/** 
	 * Get the current validator's data
	 * Wehn the key is not set this will simply return all data
	 *
	 * @param string 		$key
	 * @return array
	 */
	public function data( $key = null )
	{
		if ( !is_null( $key ) )
		{
			if ( array_key_exists( $key, $this->data ) )
			{
				return $this->data[$key];
			}
			return null;
		}
		return $this->data;
	}
	
	/**
	 * Apply multiple rules to one attribute
	 *
	 * @param ...string
	 * @return bool
	 */
	public function rules()
	{
		$args = func_get_args();
		
		$key = array_shift( $args );
		
		if ( !is_array( reset( $args ) ) )
		{
			$rules = $args;
		}
		else 
		{
			$rules = array_shift( $args );
		}
		
		$success = true;
		
		foreach( $rules as $rule )
		{
			$rule = explode( ':', $rule );
			$params = array();
			
			if ( array_key_exists( 1, $rule ) )
			{
				$params = explode( ',', $rule[1] );
			}
			
			$rule = reset( $rule );
			
			array_unshift( $params, $key );
			
			if ( !call_user_func_array( array( $this, $rule ), $params ) )
			{
				$success = false;
			}
		}
		
		return $success;
	}
	
	/**
	 * Dynamic function calls 
	 *
	 * @param string 	$method
	 * @param array 		$params
	 * @return mixed
	 */
	public function __call( $method, $params )
	{
		if ( array_key_exists( $method, static::$rules ) )
		{
			return $this->apply_rule( $method, static::$rules[$method], $params );
		}
		
		if ( method_exists( $this, 'rule_'.$method ) )
		{
			return $this->apply_rule( $method, array( $this, 'rule_'.$method ), $params );
		}
		
		throw new \BadMethodCallException( "CCValidator - Invalid rule or method '".$method."'." );
	}
	
	/**
	 * Proof a single result and update the success property
	 *
	 * @param string		$rule
	 * @param string 	$key
	 * @param array 		$result
	 * @return bool
	 */ 
	protected function proof_result( $rule, $key, $result )
	{	
		if ( $result === false )
		{
			$this->failed[$key][] = $rule;
		}
		
		if ( $this->success === true )
		{
			return $this->success = $result;
		}
		
		return $result;
	}
	
	/**
	 * Apply an rule executes the rule and runs the result proof
	 *
	 * @param string 		$rule
	 * @param callback		$callback
	 * @param array 			$params
	 * @return bool
	 */
	protected function apply_rule( $rule, $callback, $params )
	{
		$data_key = array_shift( $params );
		
		// In case of that the requested data set does not exist
		// we always set the test as failure.
		if ( !array_key_exists( $data_key, $this->data ) )
		{
			return $this->proof_result( $rule, $data_key, false );
		}
		
		$call_arguments = array( $data_key, $this->data[$data_key] );
		
		// add the other params to our call parameters
		$call_arguments = array_merge( $call_arguments, $params );
		
		return $this->proof_result( $rule, $data_key, (bool) call_user_func_array( $callback, $call_arguments ) );
	}
	
	/*
	 ** --- RULES BELOW HERE
	 */
	
	/** 
	 * Check if the field is set an not empty
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_required( $key, $value )
	{
		if ( is_null( $value ) )
		{
			return false;
		}
		elseif ( is_string( $value ) && trim( $value ) == '' )
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Is the given value numeric?
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_numeric( $key, $value )
	{
		return is_numeric( $value );
	}
	
	/**
	 * Is the given number at least some size
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_min_num( $key, $value, $min )
	{
		if ( is_numeric( $value ) )
		{
			return $value >= $min;
		}
		return false;
	}
	
	/**
	 * Is the given number max some size
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_max_num( $key, $value, $max )
	{
		if ( is_numeric( $value ) )
		{
			return $value <= $max;
		}
		return false;
	}
	
	/**
	 * Is the given number between min and max
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_between_num( $key, $value, $min, $max )
	{
		if ( !$this->rule_min_num( $key, $value, $min ) )
		{
			return false;
		}
		if ( !$this->rule_max_num( $key, $value, $max ) )
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Is the given string at least some size
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_min( $key, $value, $min )
	{
		return $this->rule_min_num( $key, strlen( $value ), $min );
	}
	
	/**
	 * Is the given string max some size
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_max( $key, $value, $max )
	{
		return $this->rule_max_num( $key, strlen( $value ), $max );
	}
	
	/**
	 * Is the given string between min and max
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_between( $key, $value, $min, $max )
	{
		return $this->rule_between_num( $key, strlen( $value ), $min, $max );
	}
	
	/**
	 * Is the given value in the given array
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @param array 			$array
	 * @return bool
	 */
	public function rule_in( $key, $value, $array )
	{
		return in_array( $value, $array );
	}
	
	/**
	 * Does the given value match another one
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @param mixed 			$other_value
	 * @return bool
	 */
	public function rule_match( $key, $value, $other_value )
	{
		return $value === $this->data( $other_value );
	}
	
	/** 
	 * Check if the value is a valid email address
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_email( $key, $value ) 
	{
		return preg_match( "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $value );
	}
	
	/** 
	 * Check if the value is a valid ip address
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_ip( $key, $value ) 
	{
		return filter_var( $value, FILTER_VALIDATE_IP ) !== false;
	}
	
	/** 
	 * Check if the value is a valid ip address
	 *
	 * @param string			$key
	 * @param string 		$value
	 * @return bool
	 */
	public function rule_url( $key, $value ) 
	{
		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}
	
	/**
	 * check if the regex matches
	 * 
	 * @param mixed		$data
	 * @param string	$regex
	 */ 
	public function rule_regex( $key, $value, $regex ) 
	{
		return preg_match( $regex, $value );
	}
		
	/**
	 * check if an array contains a string
	 *
	 * @param string	$string
	 */
	public function is_in_array( $string, $array ) {
		return $this->success( in_array( $this->data( $string ), $array ) );
	}
	
	/**
	 * reverse of is in array
	 *
	 * @param string	$string
	 */
	public function is_not_in_array( $string, $array ) {
		return $this->success( !in_array( $this->data( $string ), $array ) );
	}
	
	/**
	 * check if something is true
	 *
	 * @param string	$string
	 */
	public function is_true( $string ) {
		return $this->success( ( $this->data( $string ) === true ) ? true : false );
	}
	
	/**
	 * check if something is false
	 *
	 * @param string	$string
	 */
	public function is_false( $string ) {
		return $this->success( ( $this->data( $string ) === false ) ? true : false );
	}
	
	/**
	 * check if something is positive
	 *
	 * @param string	$string
	 */
	public function is_positive( $string ) {
		return $this->success( ( $this->data( $string ) ) ? true : false );
	}
	
	/**
	 * check if something is negative
	 *
	 * @param string	$string
	 */
	public function is_negative( $string ) {
		return $this->success( ( $this->data( $string ) ) ? false : true );
	}
	
	/**
	 * are the parameters equal?
	 *
	 * @param string	$string
	 * @param mixed		$item
	 */
	public function is_equal( $string, $item ) {
		return $this->success( $this->data( $string ) == $item );
	}
	
	/**
	 * are the parameters equal?
	 *
	 * @param string	$string
	 * @param mixed		$item
	 */
	public function is_not_equal( $string, $item ) {
		return $this->success( $this->data( $string ) != $item );
	}
	
	/**
	 * check if valid date format
	 *
	 * @param string	$string
	 * @param string	$format
	 */
	public function is_valid_date( $string, $format = 'd-m-Y' ) {
		$date = strtotime( trim( $this->data( $string ) ) );
		return $this->success( date( $format, $date ) == trim( $this->data( $string ) ) );
	}
}
