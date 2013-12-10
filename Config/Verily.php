<?php

$config = array(
	'hasher' => '\Verily\Lib\PasswordHash',
	'hasher_iterations' => 8,
	'use_portable_hashes' => true,
	'form_view' => 'Form',
	'form_class' => '\Micro\Form',
	'validation_class' => '\Micro\Validation',
	'user_model' => '',
	'username_property' => 'username',
	'password_property' => 'password',
	'auth_salt' => '',
);
