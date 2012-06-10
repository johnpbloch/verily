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
			throw new \Exception( $message, 'not-installed' );
		}
		$passwordHasher = config( 'Verily' )->hasher;
		if( !in_array( 'PasswordHasher', class_implements( $passwordHasher ) ) )
		{
			$passwordHasher = '\Verily\Lib\PasswordHash';
		}
		if( !($passwordHasher instanceof \Verily\Lib\PasswordHasher) )
		{
			$passwordHasher = new $passwordHasher( config( 'Verily' )->hasher_iterations, config( 'Verily' )->use_portable_hashes );
		}
		$this->hasher = $passwordHasher;
	}

	public function run()
	{
		
	}

	public function form( \Core\Validation $validation = null )
	{
		if( !$validation ) $validation = new \Core\Validation( array( ) );
		$this->form = new \Core\Form( $validation );
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
		
	}

	public function log_out()
	{
		
	}

}
