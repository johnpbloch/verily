<?php

// Installer for Verily. Run anywhere inside a MicroMVC directory tree:
//     $ php Class/Verily/install.php
// Make sure we're in the cli
if( PHP_SAPI !== 'cli' )
	die( 1 );

echo "\n\n";

$vpath = rtrim( getcwd(), '/' ) . '/';

// Find the bootstrap file from MicroMVC
do
{
	if( file_exists( "{$vpath}Bootstrap.php" ) )
	{
		require "{$vpath}Bootstrap.php";
	}
	if( $vpath == '/' )
	{
		echo "\033[0;31mCould not find bootstrap file! Please make sure you're in a MicroMVC directory tree!\033[0m" . "\n";
		exit( 1 );
	}
	$vpath = dirname( rtrim( $vpath, '/' ) ) . '/';
}
while( !defined( 'SP' ) );

define( 'VPATH', SP . 'Class/Verily/' );

/**
 * Check if classes exist without trying to autoload
 * 
 * This function will also load any classes that do exist so we can check their attributes too.
 * @param string $class The class to check
 * @return boolean
 */
function safeClassExists( $class )
{
	if( class_exists( $class, false ) )
		return true;
	$fileName = '';
	$namespace = '';

	if( $lastNsPos = strripos( $class, '\\' ) )
	{
		$namespace = substr( $class, 0, $lastNsPos );
		$class = substr( $class, $lastNsPos + 1 );
		$fileName = str_replace( '\\', DS, $namespace ) . DS;
	}

	$fileName .= str_replace( '_', DS, $class ) . '.php';
	$toReturn = file_exists( SP . 'Class/' . $fileName );
	if( $toReturn )
	{
		require SP . 'Class/' . $fileName;
	}
	return $toReturn;
}

$config = config();

// If we're already installed, don't do anything!
if( !empty( $config->verilyIsInstalled ) )
{
	echo colorize( 'Verily is already installed!', 'red' ) . "\n";
	exit( 0 );
}

// We need to be able to write to the config directory. Bail if we can't.
if( !\Core\Directory::usable( SP . 'Config' ) )
{
	echo colorize( 'The config directory is not writeable!', 'red' ) . "\n";
	exit( 1 );
}

require VPATH . 'Config/Verily.php';

echo "Verily has a few options to configure. Let's get started.\n";
echo "If a default value is available (in brackets before colon), hit enter to select it.\n\n";


// Get the hasher class. Don't stop until we have one that exists
// and implements \Verily\Lib\PasswordHasher.
do
{
	if( isset( $hasher ) )
	{
		if( safeClassExists( $hasher ) )
		{
			echo colorize( 'That class does not implement \\Verily\\Lib\\PasswordHasher!', 'red' ) . "\n";
		}
		else
		{
			echo colorize( 'That class does not exist!', 'red' ) . "\n";
		}
	}
	echo colorize( 'Password hasher', 'cyan', true ) . ' [' . $config['hasher'] . ']: ';
	$hasher = trim( fgets( STDIN ) );
	if( empty( $hasher ) )
	{
		$hasher = $config['hasher'];
	}
}
while( !safeClassExists( $hasher ) || !in_array( 'Verily\Lib\PasswordHasher', class_implements( $hasher ), false ) );
$config['hasher'] = $hasher;
unset( $hasher );


// How many times should the hasher iterate? Correct if not between 4 and 31.
echo colorize( 'Hasher iterations', 'cyan', true ) . ' [8]: ';
$iterations = (int)trim( fgets( STDIN ) );
if( $iterations < 4 || $iterations > 31 )
	$iterations = 8;
$config['hasher_iterations'] = $iterations;
unset( $iterations );


// Should we use portable hashes?
echo colorize( 'Use portable hashes?', 'cyan', true ) . ' [yes]: ';
$use_portable = strtolower( trim( fgets( STDIN ) ) );
if( empty( $use_portable ) )
{
	$config['use_portable_hashes'] = true;
}
else
{
	$config['use_portable_hashes'] = ($use_portable[0] == 'y');
}
unset( $use_portable );


// What view file should we use for the login form?
echo colorize( 'Login form View:', 'cyan', true ) . ' [Class/Verily/Views/Form]: ';
$view = trim( fgets( STDIN ) );
if( empty( $view ) )
	$view = 'Form';
$config['form_view'] = $view;
unset( $view );


// What class should we use for the login form? Must either be \Core\Form
// or extend it. Keep trying until we get a match.
do
{
	if( isset( $form ) )
	{
		if( safeClassExists( $form ) )
		{
			echo colorize( 'Class must either be \\Core\\Form or extend it!', 'red' ) . "\n";
		}
		else
		{
			echo colorize( 'Class does not exist!', 'red' ) . "\n";
		}
	}
	echo colorize( 'Form class to use for Forms', 'cyan', true ) . ' [\\Core\\Form]: ';
	$form = trim( fgets( STDIN ) );
	if( empty( $form ) )
		$form = '\Core\Form';
}
while( !safeClassExists( $form ) || ( $form !== '\Core\Form' && !in_array( 'Core\Form', class_parents( $form ) ) ) );
$config['form_class'] = $form;
unset( $form );


// What class should we use for the form validation? Must exist and either be
// \Core\Validation or extend it. Keep trying until we get a match.
do
{
	if( isset( $validation ) )
	{
		if( safeClassExists( $validation ) )
		{
			echo colorize( 'Class must either be \\Core\\Validation or extend it!', 'red' ) . "\n";
		}
		else
		{
			echo colorize( 'Class does not exist!', 'red' ) . "\n";
		}
	}
	echo colorize( 'Validation class to use', 'cyan', true ) . ' [\\Core\\Validation]: ';
	$validation = trim( fgets( STDIN ) );
	if( empty( $validation ) )
		$validation = '\Core\Form';
}
while( !safeClassExists( $validation ) || ( $validation !== '\Core\Validation' && !in_array( 'Core\Validation', class_parents( $validation ) ) ) );
$config['validation_class'] = $validation;
unset( $validation );


// Get a Model to use as the users. Don't stop asking until we have a model
// that exists and extends \Core\ORM.
do
{
	if( isset( $userModel ) )
	{
		if( safeClassExists( $userModel ) )
		{
			echo colorize( 'That class does not exted \\Core\\ORM!', 'red', true ) . "\n";
		}
		else
		{
			echo colorize( 'That class does not exist!', 'red', true ) . "\n";
		}
	}
	echo colorize( 'Model to use for Users', 'cyan', true ) . ' (non-namespaced classes will be assumed to be in the \\Model namespace): ';
	$userModel = trim( fgets( STDIN ) );
	if( !empty( $userModel ) && $userModel[0] != '\\' )
	{
		$userModel = "\Model\\$userModel";
	}
}
while( !safeClassExists( $userModel ) || !in_array( 'Core\ORM', class_parents( $userModel ) ) );
$config['user_model'] = $userModel;
unset( $userModel );


// A property of the above model to use for usernames.
echo colorize( 'Model property for username', 'cyan', true ) . ' [username]: ';
$un = trim( fgets( STDIN ) );
if( empty( $un ) )
	$un = 'username';
$config['username_property'] = $un;
unset( $un );

// A property of the above model to use for passwords.
echo colorize( 'Model property for hashed password', 'cyan', true ) . ' [password]: ';
$pw = trim( fgets( STDIN ) );
if( empty( $pw ) )
	$pw = 'password';
$config['password_property'] = $pw;
unset( $pw );


// Get a unique password salt. Generate a random one if the user doesn't specify one.
echo colorize( 'Unique password salt', 'cyan', true ) . ' [' . colorize( 'Randomly Generated', 'green' ) . ']: ';
$salt = trim( fgets( STDIN ) );
if( empty( $salt ) )
{
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
	$salt = '';
	for( $i = 0; $i < 72; $i++ )
	{
		$salt .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
	}
}
$config['auth_salt'] = $salt;
unset( $salt, $chars, $i );

echo "Installing configuration profile...\n";


// Write the new configuration to the config file
$installationValues = <<<EOF
<?php

\$config = array(
EOF;

foreach( $config as $key => $value )
{
	$installationValues .= "\n\t";
	$installationValues .= "'$key' => ";
	$q = ($key == 'hasher_iterations' || $key == 'use_portable_hashes') ? '' : "'";
	$value = ($key != 'use_portable_hashes') ? $value : ($value ? 'true' : 'false');
	$installationValues.="$q$value$q,";
}
$installationValues .= "\n);\n";

$verilyConfig = fopen( SP . 'Config/Verily.php', 'w' );
fwrite( $verilyConfig, $installationValues );
fclose( $verilyConfig );
unset( $config, $installationValues, $key, $value, $q, $verilyConfig );


// Give ourselves a flag in the main config file to let us know that it's been installed.
echo "Registering Verily installation in global config...\n";

$mainConfigFile = file_get_contents( SP . 'Config/Config.php' );
$mainConfigFile = preg_replace( '@\?>\s*$@', '', $mainConfigFile );
$mainConfigFile .= "\n";
$mainConfigFile .= '$config[\'verilyIsInstalled\'] = true;';
$mainConfigFile .= "\n";

$cfgFile = fopen( SP . 'Config/Config.php', 'w' );
fwrite( $cfgFile, $mainConfigFile );
fclose( $cfgFile );

echo "All done!\n\n";
exit( 0 );
