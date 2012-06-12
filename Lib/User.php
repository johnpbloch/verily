<?php

namespace Verily\Lib;

class User
{

	/**
	 * @var bool
	 */
	protected static $logged_in = null;

	/**
	 * @var \Core\ORM
	 */
	protected static $current_user;

	public static function set_auth_cookie( \Core\ORM $user, $remember = false )
	{
		self::$current_user = $user;
		$userKey = $user->key();
		$password_property = config( 'Verily' )->password_property;
		$userPass = substr( (string)$user->{$password_property}, 8, 4 );
		$expiration = time() + ( 86400 * ( $remember ? 14 : 1 ) );
		$key = hash_hmac( 'md5', $userKey . $userPass . '|' . $expiration, config( 'Verily' )->auth_salt );
		$hash = hash_hmac( 'md5', $userKey . $userPass . '|' . $expiration, $key );
		$logged_in_data = array(
			'user' => $userKey,
			'hash' => $hash,
			'expiration' => $expiration,
		);
		\Core\Cookie::set( 'verilyAuth', $logged_in_data );
		self::$logged_in = true;
	}

	public static function clear_auth_cookie()
	{
		self::$current_user = null;
		self::$logged_in = false;
		\Core\Cookie::set( 'verilyAuth', false );
	}

	public static function current()
	{
		return self::is_logged_in() ? self::$current_user : false;
	}

	public static function is_logged_in()
	{
		if( !is_null( self::$logged_in ) )
		{
			return (bool)self::$logged_in;
		}
		$data = \Core\Cookie::get( 'verilyAuth' );
		if( !$data )
		{
			self::$logged_in = false;
			self::$current_user = null;
			return false;
		}
		extract( $data );
		if( $expiration < time() )
		{
			self::clear_auth_cookie();
			return false;
		}
		$userModel = config( 'Verily' )->user_model;
		$user = new $userModel( $user );
		if( !$user->key() )
		{
			self::clear_auth_cookie();
			return false;
		}
		$passwordProp = config( 'Verily' )->password_property;
		$pass_fragment = substr( $user->{$passwordProp}, 8, 4 );
		$blob = $user->key() . $pass_fragment . '|' . $expiration;
		$key = hash_hmac( 'md5', $blob, config( 'Verily' )->auth_salt );
		$testHash = hash_hmac( 'md5', $blob, $key );
		if( $testHash === $hash )
		{
			self::$logged_in = true;
			if( !self::$current_user )
			{
				self::$current_user = $user;
			}
			return true;
		}
		self::clear_auth_cookie();
		return false;
	}

	public static function hashPassword( $password )
	{
		$passwordHasher = config( 'Verily' )->hasher;
		if( !in_array( 'Verily\Lib\PasswordHasher', class_implements( $passwordHasher ) ) )
		{
			$passwordHasher = '\Verily\Lib\PasswordHash';
		}
		if( !($passwordHasher instanceof \Verily\Lib\PasswordHasher) )
		{
			$passwordHasher = new $passwordHasher( config( 'Verily' )->hasher_iterations, config( 'Verily' )->use_portable_hashes );
		}
		return $passwordHasher->HashPassword( $password );
	}

}
