<?php

namespace Verily;

class Controller extends \Core\Controller {

	public function initialize() {
		$globalConfig = config();
		if( empty( $globalConfig->verilyIsInstalled ) ) {
			$message = 'Verily is not installed! Please follow the instructions in INSTALL.txt!';
			log_message( $message );
			throw new \Exception( $message, 'not-installed' );
		}
	}

	public function run() {
		
	}

	public function log_in() {
		
	}

	public function log_out() {
		
	}

}
