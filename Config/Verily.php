<?php

$config = array(
	'hasher' => '\Verily\Lib\PasswordHash',
	'hasher_iterations' => 8,
	'use_portable_hashes' => true,
	'default_form_view' => 'Form',
	'user_model' => '',
	'username_property' => 'username',
	'password_property' => 'password',
	'auth_salt' => '',
);
