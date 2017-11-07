<?php

use LdapTools\Configuration;
use LdapTools\LdapManager;

class Auth {

    static $timeout = 1800;

    public static function check()
    {
        $time = $_SERVER['REQUEST_TIME'];

        if (isset($_SESSION['last_seen']) && ($time - $_SESSION['last_seen']) > self::$timeout) {
          session_unset();
          session_destroy();
          session_start();
        }

        if(!isset($_SESSION['loggedin']) && $_SERVER['REQUEST_URI'] != '/login') {
            $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
            header("Location: /login");
            exit;
        }

        $_SESSION['last_seen'] = $time;

        Flight::route('/login',function() {
        	Auth::login();
        });

        Flight::route('/logout',function() {
        	Auth::logout();
        });
    }

    public static function login() {
        $errors = array();

        if (Flight::request()->method == 'POST') {

            if (self::authenticate($_POST['username'], $_POST['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $_POST['username'];
                $redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : '/';
                unset($_SESSION['redirect']);
                Flight::redirect($redirect);
            }

        }

        // $template_vars['reports'] = $reports;
        // $template_vars['report_errors'] = $errors;

        $start = microtime(true);
        echo PhpReports::render('html/login',[]);
    }

    private static function authenticate($username, $password)
    {
        $username = trim($username);
        $password = trim($password);
        if (is_null($username) || is_null($password)) {
            return false;
        }
        
        if (isset(PhpReports::$config['users_allowed'])
            && !empty(PhpReports::$config['users_allowed'])
            && !in_array($username, PhpReports::$config['users_allowed'])
        ) {
            return false;
        }

        $config = (new Configuration())->load('config/adldap.yml');
        $ldap = new LdapManager($config);

        return $ldap->authenticate($username, $password);
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
        session_start();
        Flight::redirect('/login');
    }

}
