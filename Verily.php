<?php

namespace Verily;

use Exception;
use Micro\Cookie;
use Micro\ORM;
use Micro\Service;
use Micro\Validation;

class Verily
{

	/**
	 * A service object to hold our objects
	 * @var Service
	 */
	protected static $service;

	/**
	 * @var bool
	 */
	protected static $logged_in = null;

	/**
	 * @var ORM
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
				throw new Exception( $message );
			}
			$service = new Service();
			$service->verily = function()
					{
						return new Verily();
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

	protected static function set_auth_cookie( ORM $user, $remember = false )
	{
		self::$current_user = $user;
		$userKey = $user->key();
		$password_property = config( 'Verily' )->password_property;
		$expiration = time() + ( 86400 * ( $remember ? 14 : 1 ) );
		$logged_in_data = array(
			'user' => $userKey,
			'hash' => $user->{$password_property},
			'expiration' => $expiration,
		);
		Cookie::set( 'verilyAuth', $logged_in_data );
		self::$logged_in = true;
	}

	protected static function clear_auth_cookie()
	{
		self::$current_user = null;
		self::$logged_in = false;
		Cookie::set( 'verilyAuth', false );
	}

	/**
	 * Creates the login form.
	 *
	 * @param Validation $validation
	 * @return View The login form
	 */
	public static function form( Validation $validation = null )
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
		$redirectToDefault = empty( $_SERVER['HTTP_REFERER'] ) ? DOMAIN . PATH : $_SERVER['HTTP_REFERER'];
		$redirectTo = get( 'redirect_to', $redirectToDefault, true );
		$form->redirectTo->input( 'hidden' )->value( $redirectTo );
		$content = new View( config( 'Verily' )->form_view );
		$form = event( 'verily.login_form', $form );
		$content->set( array( 'form' => $form ) );
		return $content;
	}

	/**
	 * Attempt to log the user in using post values. If successful, page is redirected
	 * and execution halted. Otherwise, a login form with validation messages is returned.
	 *
	 * @return View
	 */
	public function log_in()
	{
		self::maybeSetUp();
		$data = array(
			'dummy' => '',
			'name' => post( 'name', '', true ),
			'password' => post( 'password', '', true ),
		);
		$validationClass = config( 'Verily' )->validation_class;
		/** @var Validation $validation */
		$validation = new $validationClass( $data );
		$validation->field( 'name' )->required( 'The name field is required.' );
		$validation->field( 'password' )->required( 'The password field is required.' );
		if( !$validation->errors() )
		{
			/** @var ORM $model */
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
		return self::form( $validation );
	}

	/**
	 * Log the user out.
	 *
	 * @return string
	 */
	public function log_out()
	{
		self::maybeSetUp();
		self::clear_auth_cookie();
		return 'Successfully logged out!';
	}

	/**
	 * Retrieve the current logged in user, if any
	 *
	 * @return ORM|bool The user object if logged in, false if not logged in
	 */
	public static function current_user()
	{
		self::maybeSetUp();
		return self::is_logged_in() ? self::$current_user : false;
	}

	/**
	 * Check whether the user is logged in or not.
	 *
	 * @return boolean
	 */
	public static function is_logged_in()
	{
		self::maybeSetUp();
		if( !is_null( self::$logged_in ) )
		{
			return (bool)self::$logged_in;
		}
		$data = Cookie::get( 'verilyAuth' );
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
		if( $user->{$passwordProp} === $hash )
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
		self::maybeSetUp();
		return self::$service->hasher()->HashPassword( $password );
	}

}
