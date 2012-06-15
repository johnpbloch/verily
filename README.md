Verily
==

Verily is an authentication module for the [MicroMVC PHP Framework](https://github.com/Xeoncross/micromvc) by David Pennington. It handles all aspects of authentication: login form, checking user credentials, and validating authenticity of the user's authentication cookie.

By default, Verily uses secure PHP Hashes for password storage and authentication.

Use
--

To use Verily, use the `\Verily\Verily` class. To fetch the login form:

    $form = \Verily\Verily::form();

This returns a View object, which can then be used in your code.

To handle login form submissions:

    $form = \Verily\Verily::log_in();

This will attempt to authenticate the user using `POST` values. If successful, the page will be redirected (determined by `$_GET['redirect_to']`) and execution stopped. If authentication fails, the login form will be returned with validation errors.

To log a user out:

    $logOutMessage = \Verily\Verily::log_out();

This returns a short confirmation message that the user was logged out.

Installation
--

To install Verily, add it as a `git submodule` from your main project and run the installation script:

    git submodule add https://github.com/johnpbloch/verily.git Class/Verily
    git submodule init
    git submodule update
    php Class/Verily/install.php

The installation script will prompt you for several configuration options. Most options have default values that many users will not need to change. The configuration details are:

    hasher                 Default Value: \Verily\Lib\PasswordHash

The class to use for hashing passwords and comparing those hashes with user submitted passwords. The class must exist, should be fully qualified, and must implement the `\Verily\Lib\PasswordHasher` interface.

    hasher_iterations      Default Value: 8

The number of iterations to use when hashing passwords. Must be greater than 3 and less than 32
    
    use_portable_hashes    Default Value: yes

Whether to use portable hashes. Using portable hashes is the default because of variations in PHP environments. Turning this option off (`false`) is recommended if your PHP environment has Blowfish encryption available.
    
    form_view              Default Value: Form

This is the `View` file that will be used for the display of the login form. The value should never have the `.php` extension; it should be a relative path, relative to the root directory of the MicroMVC installation, relative to `Class/Verily/Views` (as the default is), or relative to the default views folder, `Views`.

    form_class             Default Value: \Core\Form

This is the class used to create the login form's fields. The default is to use the core forms class (`\Core\Form`), but any class that extends `\Core\Form` may be used as well.

    validation_class       Default Value: \Core\Validation

This is the class used to validate the login form's fields. The default is to use the core validation class (`\Core\Validation`), but any class that extends `\Core\Validation` may be used as well.

    user_model             No Default

This is the `Model` used for authentication. There is no default, so a value must be provided. The model class must exist and it must extend `\Core\ORM`. This is necessary for Verily to be able to look up users who are trying to log in.

    username_property      Default Value: username

This is the property Verily will use to get the username from an object of the model type defined above. Verily needs to be able to look users up in the database based on this value, so it should correspond to a uniquely indexed column of the database table associated with the user model.

    password_property      Default Value: password

This is the property Verily will use to get the stored password hash from an object of the model type defined above. It should correspond to a column of the database table associated with the user model.

    auth_salt              No Default

If you do not provide a salt, the installation script will generate a random one for you. This salt has two purposes: first, it makes the authentication cookie more secure; second, it can be changed to force all accounts to be logged out without changing passwords.

License
--

Verily is licensed for use under the [GNU GPL Version 3](http://www.gnu.org/licenses/gpl.html) or later.