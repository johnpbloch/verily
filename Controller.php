<?php

namespace Verily;

class Verily
{

	/**
	 * A service object to hold our objects
	 * @var \Core\Service 
	 */
	protected static $service;

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
					\Verily\Lib\User::set_auth_cookie( $user, post( 'rememberMe', false ) );
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
		\Verily\Lib\User::clear_auth_cookie();
		return 'Successfully logged out!';
	}

}
