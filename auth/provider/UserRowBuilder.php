<?php

namespace noud\saml2\auth\provider;
// New Account Creation
class UserRowBuilder
{

    public function build($attributes)
    {

        global $phpbb_container;
        $passwords_manager = $phpbb_container->get('passwords.manager');

        $user_row = array(
            'username' => $attributes->userId[0], // Uses the USERID from SalesForce
            'user_password' => $passwords_manager->hash(PasswordFactory::generate()),
            'user_email' => $attributes->email[0], // Pulls the Email from SalesForce
            'group_id' => (int)'2', //Default new users to Registered User List
            'user_timezone' => (float)'2', // 
            'user_lang' => (string)'en', // Defaults User Lang to British English
            'user_type' => (int)'0', // Defaults User to Activated
            'user_regdate' => time(),
        );

        return $user_row;
    }

    
}
