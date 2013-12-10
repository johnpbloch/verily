Verily
==

Verily is an authentication module for the [MicroMVC PHP Framework](https://github.com/Xeoncross/micromvc) by David Pennington. It handles all aspects of authentication: login form, checking user credentials, and validating authenticity of the user's authentication cookie.

By default, Verily uses secure PHP Hashes for password storage and authentication.

Use
--

To use Verily, use the `\Verily\Verily` class. To fetch the login form:

    $form = \Verily\Verily::form();

This returns a View object, which can then be used in your code.
____
To handle login form submissions:

    $form = \Verily\Verily::log_in();

This will attempt to authenticate the user using `POST` values. If successful, the page will be redirected (determined by `$_GET['redirect_to']`) and execution stopped. If authentication fails, the login form will be returned with validation errors.
____
To log a user out:

    $logOutMessage = \Verily\Verily::log_out();

This returns a short confirmation message that the user was logged out.
____
To check if there is a currently logged in user:

    if( \Verily\Verily::is_logged_in() ) { /* Do something */ }

____
To retrieve the currently logged in user:

    $user = \Verily\Verily::current_user();

This returns an object of the user model type if there is a user logged in, and `false` if no user is logged in.
____
To hash a password (e.g. when creating new users):

    $passwordHash = \Verily\Verily::hash_password();

Installation
--

To install Verily, add it as a requirement in your `composer.json` file and run the installation script:

```
composer require johnpbloch/verily:~0.1
php vendor/bin/install.php
```

The installation script will prompt you for several configuration options. Most options have default values that many users will not need to change. The configuration details are:

    hasher                 Default Value: \Verily\Lib\PasswordHash

The class to use for hashing passwords and comparing those hashes with user submitted passwords. The class must exist, should be fully qualified, and must implement the `\Verily\Lib\PasswordHasher` interface.

    hasher_iterations      Default Value: 8

The number of iterations to use when hashing passwords. Must be greater than 3 and less than 32
    
    use_portable_hashes    Default Value: yes

Whether to use portable hashes. Using portable hashes is the default because of variations in PHP environments. Turning this option off (`false`) is recommended if your PHP environment has Blowfish encryption available.
    
    form_view              Default Value: Form

This is the `View` file that will be used for the display of the login form. The value should never have the `.php` extension; it should be a relative path, relative to the root directory of the MicroMVC installation, relative to `Verily/Views` (as the default is), or relative to the default views folder, `Views`.

    form_class             Default Value: \Micro\Form

This is the class used to create the login form's fields. The default is to use the core forms class (`\Micro\Form`), but any class that extends `\Micro\Form` may be used as well.

    validation_class       Default Value: \Micro\Validation

This is the class used to validate the login form's fields. The default is to use the core validation class (`\Micro\Validation`), but any class that extends `\Micro\Validation` may be used as well.

    user_model             No Default

This is the `Model` used for authentication. There is no default, so a value must be provided. The model class must exist and it must extend `\Micro\ORM`. This is necessary for Verily to be able to look up users who are trying to log in.

    username_property      Default Value: username

This is the property Verily will use to get the username from an object of the model type defined above. Verily needs to be able to look users up in the database based on this value, so it should correspond to a uniquely indexed column of the database table associated with the user model.

    password_property      Default Value: password

This is the property Verily will use to get the stored password hash from an object of the model type defined above. It should correspond to a column of the database table associated with the user model.

    auth_salt              No Default

If you do not provide a salt, the installation script will generate a random one for you. This salt has two purposes: first, it makes the authentication cookie more secure; second, it can be changed to force all accounts to be logged out without changing passwords.

License
--

Verily is licensed for use under the [GNU GPL Version 3](http://www.gnu.org/licenses/gpl.html) or later.
