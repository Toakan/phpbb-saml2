<?php

namespace noud\saml2\auth\provider;

use Exception;

if (!defined('IN_PHPBB')) {
    exit;
}

require_once(__DIR__.'/../../../../../simplesaml/lib/_autoload.php');

class auth_simplesaml extends \phpbb\auth\provider\base
{
    /** @var \phpbb\db\driver\driver_interface $db */
    protected $db;

    /**
     * Database Authentication Constructor
     *
     * @param \phpbb\db\driver\driver_interface $db
     */
    public function __construct(\phpbb\db\driver\driver_interface $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function login($username, $password)
    {
        return $this->aLogin();
    }

 	/**
	* {@inheritdoc}
	*/
	public function autologin()
    {
        return $this->aLogin(true);
    }


    public function aLogin($auto = false)
    {
        try {
        // Get the SAML Credentials from SimpleSAML
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        $as->requireAuth();
        $attrib = $as->getAttributes();
        $attributes = (object) $attrib;

        // Check if they are an authenticated portal user, otherwise log them in anonymously.
        if (!isset($attributes->is_portal_user) || !isset($attributes->is_portal_user[0])) {
            return array(
                'status' => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg' => 'LOGIN_ERROR_EXTERNAL_AUTH_SS',
                'user_row' => array('user_id' => ANONYMOUS),
            );
        }
        
        // Setup the PHPBB Auth System to start verification.
        $groupManager = new GroupManager();
        $userManager = new UserManager($groupManager);
        $row = $userManager->lookup($attributes);

        
        if (isset($row) && $row !== false) {
            // User is found. Sync user attributes, get updated row, and login user using updated row.
            $userManager->sync($attributes);
            $row = $userManager->lookup($attributes, $auto);
        } else {
            // User not found. Creating user in Forums.
            $userManager->create($attributes);
            $row = $userManager->lookup($attributes, $auto);
        }


        if (!isset($row) || $row == false) {
            // Something messed up along the way, log them in as unauthed Anonymous.
            return array(
                'status' => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg' => 'LOGIN_ERROR_EXTERNAL_AUTH_SS',
                'user_row' => array('user_id' => ANONYMOUS),
            );
        }

        if ($auto) {
            return $row;
        } else {
            return $this->get_login_array($row);
        }


        } catch (Exception $e) {
            throw new \phpbb\exception\http_exception(403,'SAML_MAP_CREATION_FAILED',array(
                'status' => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg' => 'LOGIN_ERROR_EXTERNAL_AUTH_SS',
                'user_row' => array('user_id' => ANONYMOUS)
            ));
        }

    }

	public function logout($data, $new_session)
    {
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        $as->logout("/");
    }

    function get_login_array(array $row)
    {
        if (!isset($row)) {
            throw new \Exception("Row is null");
        }
        return array(
            'status' => LOGIN_SUCCESS,
            'error_msg' => false,
            'user_row' => $row,
        );
    }
}
