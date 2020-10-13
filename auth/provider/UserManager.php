<?php

namespace noud\saml2\auth\provider;

class UserManager
{
    private $groupManager;

    /**
     * @param GroupManager $groupManager
     */
    public function __construct(GroupManager $groupManager)
    {
        $groupManager = $this->groupManager;
    }

    /**
     * @param ClaimsUser $claimsUser
     * @param $allFields
     * @return array
     * @throws Exception
     */
    function lookup($attributes, $allFields = false)
    {
        if (!isset($attributes->username[0])) {
            throw new \Exception("SQL Lookup of User failed.");
        }

        global $db;

        if ($allFields) {
            $fields = '*';
        } else {
            $fields = 'user_id, username, user_password, user_passchg, user_email, user_type, user_login_attempts';
        }
        $sql = 'SELECT top 1 ' . $fields . '
       		FROM ' . USERS_TABLE . "
               WHERE user_email = '" . $db->sql_escape($attributes->email[0]) . "'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }

    /**
     * @param $attributes 
     */
    public function create($attributes)
    {
        if (!isset($attributes)) {
            throw new \Exception("Creation of user failed");
        }
        
        $builder = new UserRowBuilder();
        $user_row = $builder->build($attributes);

        // all the information has been compiled, add the user
        // tables affected: users table, profile_fields_data table, groups table, and config table.
        if (!function_exists('user_add')) {
            $phpbb_root_path = __DIR__ . '/../../../../../';
            $phpEx = substr(strrchr(__FILE__, '.'), 1);
            include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
        }

        $user_id = user_add($user_row);
    }

    /**
     * @param $attributes 
     */
    public function sync($attributes)
    {
        if (!isset($attributes)) {
            throw new \Exception("Syncing User failed.");
        }
    }

    /**
     * @param $attributes 
     */
    public function syncGroups($attributes)
    {
        if (!isset($claimsUser)) {
            throw new \Exception("Syncing groups failed.");
        }
        //TODO Implement this
    }

    /**
     * @param $attributes 
     */
    public function syncProfile($attributes)
    {
        if (!isset($attributes)) {
            throw new \Exception("Syncing Profile failed.");
        }

        $data = array();

        if (isset($attributes->email[0])) {
            $item = array('user_email' => $attributes->email[0]);
            array_push($data, $item);
        }

        array_push($data, array('user_type' => $attributes->userType()));
        array_push($data, array('group_id' => (int)$attributes->getDefaultGroupId()));
        array_push($data, array('user_lang' => $attributes->getPreferredLanguage()));

        global $db;

        $sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $data) . ' WHERE user_email = ' . $db->sql_escape($attributes->username[0]) . "'";

        $result = $db->sql_query($sql);
        $db->sql_freeresult($result);
    }
}
