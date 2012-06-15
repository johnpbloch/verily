<?php

namespace Verily;

class Verily
{

	/**
	 * A service object to hold our objects
	 * @var \Core\Service 
	 */
	protected static $service;

	/**
	 * @var bool
	 */
	protected static $logged_in = null;

	/**
	 * @var \Core\ORM
	 */
	protected static $current_user;

	protected static function maybeSetUp()
	{
		if( !self::$service )
		{
			$globalConfig = config();
			if( empty( $globalConfig->verilyIsInstalled ) )
			{
				$message = 'Verily is not installed! Please execute the installation script!';
				log_message( $message );
				throw new \Exception( $message );
			}
			$service = new \Core\Service();
			$service->verily = function()
					{
						return new \Verily\Verily();
					};
			$service->hasher = function()
					{
						$hasherClass = config( 'Verily' )->hasher;
						$iterations = config( 'Verily' )->hasher_iterations;
						$use_portable = config( 'Verily' )->use_portable_hashes;
						return new $hasherClass( $iterations, $use_portable );
					};
			self::$service = $service;
		}
	}

	protected static function set_auth_cookie( \Core\ORM $user, $remember = false )
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

	protected static function clear_auth_cookie()
	{
		self::$current_user = null;
		self::$logged_in = false;
		\Core\Cookie::set( 'verilyAuth', false );
	}

	/**
	 * Creates the login form.
	 *
	 * @param \Core\Validation $validation
	 * @return \Verily\View The login form
	 */
	public static function form( \Core\Validation $validation = null )
	{
		self::maybeSetUp();
		if( !$validation )
		{
			$validationClass = config( 'Verily' )->validation_class;
			$validation = new $validationClass( array( ) );
		}
		$formClass = config( 'Verily' )->form_class;
		$form = new $formClass( $validation );
		$form->dummy->input( 'hidden' );
		$form->name->input( 'text' )->label( 'Name' )->wrap( 'p' );
		$form->password->input( 'password' )->label( 'Password' )->wrap( 'p' );
		$form->rememberMe->input( 'checkbox' )->value( '1' )->label( 'Remember Me' );
		$form->submit->input( 'submit' )->value( 'Log In' )->wrap( 'p' );
		$redirectTo = get( 'redirect_to', DOMAIN . DS . PATH, true );
		$form->redirectTo->input( 'hidden' )->value( $redirectTo );
		$content = new View( config( 'Verily' )->form_view );
		$content->set( array( 'form' => $form ) );
		return $content;
	}

	/**
	 * Attempt to log the user in using post values. If successful, page is redirected
	 * and execution halted. Otherwise, a login form with validation messages is returned.
	 * 
	 * @return \Verily\View 
	 */
	public function log_in()
	{
		$data = array(
			'dummy' => '',
			'name' => post( 'name', '', true ),
			'password' => post( 'password', '', true ),
		);
		$validationClass = config( 'Verily' )->validation_class;
		$validation = new $validationClass( $data );
		$validation->field( 'name' )->required( 'The name field is required.' );
		$validation->field( 'password' )->required( 'The password field is required.' );
		if( !$validation->errors() )
		{
			$model = config( 'Verily' )->user_model;
			$usernameField = config( 'Verily' )->username_property;
			$passwordField = config( 'Verily' )->password_property;
			$user = $model::row(
							array(
								$usernameField => $data['name']
							)
			);
			if( !$user || !$user->key() )
			{
				$validation->field( 'dummy' )->required( 'That user does not exist!' );
			}
			else
			{
				$user->load();
				$stored_hash = $user->{$passwordField};
				if( self::$service->hasher()->CheckPassword( $data['password'], $stored_hash ) )
				{
					self::set_auth_cookie( $user, post( 'rememberMe', false ) );
					redirect( post( 'redirectTo', DOMAIN, true ) );
					exit;
				}
				$validation->field( 'dummy' )->required( 'That password did not match our records!' );
			}
		}
		return self::form( '', $validation );
	}

	/**
	 * Log the user out.
	 *
	 * @return string
	 */
	public function log_out()
	{
		self::clear_auth_cookie();
		return 'Successfully logged out!';
	}

	/**
	 * Retrieve the current logged in user, if any
	 *
	 * @return \Core\ORM|bool The user object if logged in, false if not logged in
	 */
	public static function current_user()
	{
		return self::is_logged_in() ? self::$current_user : false;
	}

	/**
	 * Check whether the user is logged in or not.
	 *
	 * @return boolean 
	 */
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

	/**
	 * Hash a password using the same method Verily will use to authenticate.
	 * @param string $password
	 * @return string The hashed password 
	 */
	public static function hash_password( $password )
	{
		return self::$service->hasher()->HashPassword( $password );
	}

}
