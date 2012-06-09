<?php

namespace Verily\Lib;

interface PasswordHasher {

	public function HashPassword( $password );

	public function CheckPassword( $password, $stored_hash );

}
