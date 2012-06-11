<?php

namespace Verily;

class Controller extends \Core\Controller {

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
	protected static $logged_in = null;

	public function initialize() {
		$this->dependencies();
	}

	protected function dependencies() {
		$globalConfig = config();
		if( empty( $globalConfig->verilyIsInstalled ) ) {
			$message = 'Verily is not installed! Please follow the instructions in INSTALL.txt!';
			log_message( $message );
			throw new \Exception( $message, 'not-installed' );
		}
		$passwordHasher = config( 'Verily' )->hasher;
		if( !in_array( 'Verily\Lib\PasswordHasher', class_implements( $passwordHasher ) ) ) {
			$passwordHasher = '\Verily\Lib\PasswordHash';
		}
		if( !($passwordHasher instanceof \Verily\Lib\PasswordHasher) ) {
			$passwordHasher = new $passwordHasher( config( 'Verily' )->hasher_iterations, config( 'Verily' )->use_portable_hashes );
		}
		$this->hasher = $passwordHasher;
		if( !class_exists( config( 'Verily' )->user_model ) || !in_array( 'Core\ORM', class_parents( config( 'Verily' )->user_model ) ) ) {
			$message = 'Please specify a model to use to authenticate users!';
			log_message( $message );
			throw new \Exception( $message, 'no-model-specified' );
		}
		$salt = config( 'Verily' )->auth_salt;
		if( empty( $salt ) ) {
			$message = 'Please set a secure salt!';
			log_message( $message );
			throw new \Exception( $message, 'no-auth-salt' );
		}
	}

	public function run() {
		
	}

	public function form( \Core\Validation $validation = null ) {
		if( !$validation )
			$validation = new \Core\Validation( array( ) );
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

	public function log_in() {
		$data = array(
			'dummy' => '',
			'name' => post( 'name', '', true ),
			'password' => post( 'password', '', true ),
		);
		$validation = new \Core\Validation( $data );
		$validation->field( 'name' )->required( 'The name field is required.' );
		$validation->field( 'password' )->required( 'The password field is required.' );
		if( !$validation->errors() ) {
			$model = config( 'Verily' )->user_model;
			$usernameField = config( 'Verily' )->username_property;
			$passwordField = config( 'Verily' )->password_property;
			$user = $model::row(
							array(
								$usernameField => $data['name']
							)
			);
			$user->load();
			if( !$user->key() ) {
				$validation->field( 'dummy' )->required( 'That user does not exist!' );
			} else {
				$stored_hash = $user->{$passwordField};
				if( $this->hasher->CheckPassword( $data['password'], $stored_hash ) ) {
					$this->set_auth_cookie( $user->key(), $stored_hash, post( 'rememberMe', false ) );
					redirect( post( 'redirectTo', DOMAIN, true ) );
					exit;
				}
				$validation->field( 'dummy' )->required( 'That password did not match our records!' );
			}
		}
		$this->form( $validation );
	}

	protected function set_auth_cookie( $userKey, $userPass, $remember = false ) {
		$userPass = substr( $userPass, 8, 4 );
		$expiration = time() + ( 86400 * ( $remember ? 14 : 1 ) );
		$key = hash_hmac( 'md5', $userKey . $userPass . '|' . $expiration, config( 'Verily' )->auth_salt );
		$hash = hash_hmac( 'md5', $userKey . $userPass . '|' . $expiration, $key );
		$logged_in_data = array(
			'user' => $userKey,
			'hash' => $hash,
			'expiration' => $expiration,
		);
		\Core\Cookie::set( 'verilyAuth', $logged_in_data );
	}

	public static function logged_in() {
		if( !is_null( self::$logged_in ) ) {
			return (bool)self::$logged_in;
		}
		$data = \Core\Cookie::get( 'verilyAuth' );
		if( !$data ) {
			self::$logged_in = false;
			return false;
		}
		extract( $data );
		if( $expiration < time() ) {
			self::clear_auth_cookie();
			return false;
		}
		$userModel = config( 'Verily' )->user_model;
		$user = new $userModel( $user );
		if( !$user->key() ) {
			self::clear_auth_cookie();
			return false;
		}
		$passwordProp = config( 'Verily' )->password_property;
		$pass_fragment = substr( $user->{$passwordProp}, 8, 4 );
		$blob = $user->key() . $pass_fragment . '|' . $expiration;
		$key = hash_hmac( 'md5', $blob, config( 'Verily' )->auth_salt );
		$testHash = hash_hmac( 'md5', $blob, $key );
		if( $testHash === $hash ) {
			self::$logged_in = true;
			return true;
		}
		self::clear_auth_cookie();
		return false;
	}

	protected function clear_auth_cookie() {
		\Core\Cookie::set( 'verilyAuth', '' );
		self::$logged_in = false;
	}

	public function log_out() {
		$this->clear_auth_cookie();
		$this->content = 'Successfully logged out!';
	}

}
