<?php

namespace Verily;

class Controller extends \Core\Controller
{

	/**
	 * The password hasher to encrypt passwords.
	 * @var \Verily\Lib\PasswordHasher
	 */
	protected $hasher;

	/**
	 * A login form
	 * @var \Core\Form
	 */
	protected $form;

	/**
	 * The display-able content
	 * @var \Verily\View
	 */
	public $content;

	public function initialize()
	{
		$this->dependencies();
	}

	protected function dependencies()
	{
		$globalConfig = config();
		if( empty( $globalConfig->verilyIsInstalled ) )
		{
			$message = 'Verily is not installed! Please follow the instructions in INSTALL.txt!';
			log_message( $message );
			throw new \Exception( $message );
		}
		$passwordHasher = config( 'Verily' )->hasher;
		if( !in_array( 'Verily\Lib\PasswordHasher', class_implements( $passwordHasher ) ) )
		{
			$passwordHasher = '\Verily\Lib\PasswordHash';
		}
		if( !($passwordHasher instanceof \Verily\Lib\PasswordHasher) )
		{
			$passwordHasher = new $passwordHasher( config( 'Verily' )->hasher_iterations, config( 'Verily' )->use_portable_hashes );
		}
		$this->hasher = $passwordHasher;
		if( !class_exists( config( 'Verily' )->user_model ) || !in_array( 'Core\ORM', class_parents( config( 'Verily' )->user_model ) ) )
		{
			$message = 'Please specify a model to use to authenticate users!';
			log_message( $message );
			throw new \Exception( $message );
		}
		$salt = config( 'Verily' )->auth_salt;
		if( empty( $salt ) )
		{
			$message = 'Please set a secure salt!';
			log_message( $message );
			throw new \Exception( $message );
		}
	}

	public function run()
	{
		
	}

	public function form( \Core\Validation $validation = null )
	{
		if( !$validation ) $validation = new \Core\Validation( array( ) );
		$this->form = new \Core\Form( $validation );
		$this->form->dummy->input( 'hidden' );
		$this->form->name->input( 'text' )->label( 'Name' )->wrap( 'p' );
		$this->form->password->input( 'password' )->label( 'Password' )->wrap( 'p' );
		$this->form->rememberMe->input( 'checkbox' )->value( '1' )->label( 'Remember Me' );
		$this->form->submit->input( 'submit' )->value( 'Log In' )->wrap( 'p' );
		$redirectTo = get( 'redirect_to', DOMAIN . DS . PATH, true );
		$this->form->redirectTo->input( 'hidden' )->value( $redirectTo );
		$content = new View( config( 'Verily' )->default_form_view );
		$content->set( array( 'form' => $this->form ) );
		$this->content = $content;
	}

	public function log_in()
	{
		$data = array(
			'dummy' => '',
			'name' => post( 'name', '', true ),
			'password' => post( 'password', '', true ),
		);
		$validation = new \Core\Validation( $data );
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
				if( $this->hasher->CheckPassword( $data['password'], $stored_hash ) )
				{
					\Verily\Lib\User::set_auth_cookie( $user, post( 'rememberMe', false ) );
					redirect( post( 'redirectTo', DOMAIN, true ) );
					exit;
				}
				$validation->field( 'dummy' )->required( 'That password did not match our records!' );
			}
		}
		$this->form( $validation );
	}

	public function log_out()
	{
		\Verily\Lib\User::clear_auth_cookie();
		$this->content = 'Successfully logged out!';
	}

}
