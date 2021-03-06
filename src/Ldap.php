<?php

namespace DPRMC\Ldap;

use DPRMC\Ldap\Exceptions\AuthenticationFailed;
use DPRMC\Ldap\Exceptions\LdapBindFailed;
use DPRMC\Ldap\Exceptions\UnableToConnectToLdapServer;
use DPRMC\Ldap\Exceptions\UnableToReachLdapServer;

/**
 * Class Ldap
 * @package DPRMC\Ldap
 */
class Ldap {

    protected $ldapHost;
    protected $ldapPort;
    protected $timeout;
    protected $ldapVersion;

    protected $errno;
    protected $errstr;

    /**
     * Ldap constructor.
     *
     * @param string $ldapHost
     * @param int    $ldapPort
     * @param float  $timeout
     * @param int    $ldapVersion
     */
    public function __construct( string $ldapHost, int $ldapPort = 389, float $timeout = 1.0, int $ldapVersion = 3 ) {
        $this->ldapHost    = $ldapHost;
        $this->ldapPort    = $ldapPort;
        $this->timeout     = $timeout;
        $this->ldapVersion = $ldapVersion;
        @fsockopen( $this->ldapHost, $this->ldapPort, $this->errno, $this->errstr, $this->timeout );
    }

    /**
     * I have PHPUnit ignoring a few exception blocks, because it's just not necessary to build tests around those.
     *
     * @param string $rdn
     * @param string $password
     *
     * @return bool
     * @throws \DPRMC\Ldap\Exceptions\AuthenticationFailed
     * @throws \DPRMC\Ldap\Exceptions\LdapBindFailed
     * @throws \DPRMC\Ldap\Exceptions\UnableToConnectToLdapServer
     * @throws \DPRMC\Ldap\Exceptions\UnableToReachLdapServer
     */
    public function authenticate( string $rdn, string $password ): bool {
        $filePointer = @fsockopen( $this->ldapHost, $this->ldapPort, $this->errno, $this->errstr, $this->timeout );

        // @codeCoverageIgnoreStart
        if ( false === $filePointer ):
            throw new UnableToReachLdapServer( "Unable to reach the ldap server you tried at: " . $this->ldapHost . ':' . $this->ldapPort . " with a timeout of " . $this->timeout . " seconds." );
        endif;
        // @codeCoverageIgnoreEnd

        $ldapLinkIdentifier = ldap_connect( $this->ldapHost, $this->ldapPort );

        // @codeCoverageIgnoreStart
        if ( false === $ldapLinkIdentifier ):
            throw new UnableToConnectToLdapServer( "Unable to reach the ldap server you tried at: " . $this->ldapHost . ':' . $this->ldapPort );
        endif;
        // @codeCoverageIgnoreEnd

        ldap_set_option( $ldapLinkIdentifier, LDAP_OPT_PROTOCOL_VERSION, $this->ldapVersion );

        try {
            $ldapIsBound = ldap_bind( $ldapLinkIdentifier, $rdn, $password );
        } catch ( \Exception $exception ) {
            throw new AuthenticationFailed( "Login failed. Your username and/or password were incorrect.", 0, $exception );
        }

        // @codeCoverageIgnoreStart
        if ( false === $ldapIsBound ):
            throw new LdapBindFailed( "The ldap_bind() failed." );
        endif;

        // @codeCoverageIgnoreEnd

        return true;
    }
}