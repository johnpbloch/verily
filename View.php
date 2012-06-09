<?php

namespace Verily;

class View extends \Core\View {

	private $__view = null;

	public function __construct( $file ) {
		if( file_exists( SP . $file . EXT ) ) {
			$this->__view = $file;
		} elseif( file_exists( __DIR__ . "/Views/$file" . EXT ) ) {
			$this->__view = str_replace( SP, '', __DIR__ ) . "/Views/$file";
		} else {
			$this->__view = "View/$file";
		}
	}

	public function __toString() {
		try {
			ob_start();
			extract( (array)$this );
			require SP . $this->__view . EXT;
			return ob_get_clean();
		} catch( \Exception $exc ) {
			\Core\Error::exception( $exc );
			return '';
		}
	}

}
