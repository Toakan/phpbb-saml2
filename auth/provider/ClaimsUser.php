<?php

namespace noud\saml2\auth\provider;

class ClaimsUser
{
    private $attributes;
    private $groups;

    private $validUser = true;

    public $userName;
    public $email;

    function __construct($attributes)
    {
        $this->attributes = $attributes;
        $this->setUserName($attributes);
        $this->trySetEmail($attributes);
        $this->is_portal_user($attributes);
    }

    private function setUserName($attributes)
    {
        if (!isset($attributes->username) || !isset($attributes->username[0])) {
            $this->validUser = false;
        }

        $userName = $attributes->username[0];

        if(strlen($userName) > 20)
        {
            $val = strtolower($userName);
            $crc64 = ( '0x' . hash('crc32', $val) . hash('crc32b', $val) );
            $this->userName = $crc64;
        }
        else
        {
            $this->username = strtolower($userName);
        }
    }

    private function trySetEmail($attributes)
    {
        if (!isset($attributes->email) || !isset($attributes->email[0])) {
            $this->email = '';
            return;
        }
        $email = $attributes->email[0];
        $this->email = strtolower($email);
    }

    private function is_portal_user($attributes){
        if(!isset($attributes->is_portal_user) || !isset($attributes->is_portal_user[0])){
            $this->validUser = false;
            return;
        }


    }

    public function isFounder()
    {
        return $this->isUserInRole(GroupSchema::getFoundersGroupName());
    }

    public function isAdministrator()
    {
        if ($this->isFounder()) {
            return true;
        }

        return $this->isUserInRole(GroupSchema::getAdministratorsGroupName());
    }

    public function isModerator()
    {
        if ($this->isAdministrator()) {
            return true;
        }

        return $this->isUserInRole(GroupSchema::getModeratorsGroupName());
    }

    public function isRegistered()
    {
        if ($this->isModerator()) {
            return true;
        }
        return $this->isUserInRole(GroupSchema::getRegisteredUsersGroupName());
    }

    private function isUserInRole($role)
    {
        if (!isset($this->attributes[$this->map->groupType])) {
            return false;
        }

        $r = strtolower($role);
        $groups = $this->attributes[$this->map->groupType];
        foreach ($groups as $group)
        {
            $g = strtolower($group);
            if ($g == $r) {
                return true;
            }
        }

        return false;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function userType()
    {
        if ($this->isFounder()) {
            return USER_FOUNDER;
        }

        return USER_NORMAL;
    }

    public function getPreferredLanguage()
    {
        // If no preferredLanguage claim is present, we default to English
        if (!isset($this->attributes[$this->map->preferredLanguage])) {
            return 'en';
        }

        // Languages supported by the phpBB installation
        $supportedLanguages = array('en');
        $lang = $this->attributes[$this->map->preferredLanguage][0];

        if (!isset($lang)) {
            return 'en';
        }

        // Comparer the supplied claim value with the list of supported languages
        foreach ($supportedLanguages as $supported) {
            if (strtolower($lang) == strtolower($supported)) {
                // Return the supported language if a match is found
                return $supported;
            }
        }

        // Fallback to British English, if the preferred language is not supported
        return 'en';
    }

    /**
     * @return bool
     */
    public function isValidUser()
    {
        return $this->validUser;
    }
}
