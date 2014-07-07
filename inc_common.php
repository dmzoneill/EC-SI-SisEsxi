<?php

include( "classes/ldapuser.class.php" );
include( "classes/ldapgroup.class.php" );
include( "classes/ldap.class.php" );
include( "classes/vmware.class.php" );
include( "inc_functions.php" );

$vmconsole = isset( $vmconsole ) ? $vmconsole : false;
$vmcontrol = false;

$reset = isset(  $_GET[ 'reset' ] ) ? $_GET[ 'reset' ] : false;
$poweron = isset( $_GET[ 'poweron' ] ) ? $_GET[ 'poweron' ] : false;
$poweroff = isset( $_GET[ 'poweroff' ] ) ? $_GET[ 'poweroff' ] : false;
$vmoptions = ( $reset || $poweron || $poweroff ) ? true : false;

$host = isset( $_GET['host'] ) ? base64_decode( $_GET['host'] ) : false;
$vmpath = isset( $_GET['vmpath'] ) ? base64_decode( $_GET['vmpath'] ) : false;
$vmid = isset( $_GET['vmid'] ) ? $_GET['vmid'] : false;

$checkname = isset( $_GET['checkname'] ) ? $_GET['checkname'] : false;
$ntlm_user = $_SERVER[ 'PHP_AUTH_USER' ];
$ituser = false;
$itgroup = "CN=SIE IT,OU=Delegated,OU=Groups,DC=ger,DC=corp,DC=intel,DC=com";

$vmware = new Vmware( $ntlm_user );
$ldap = new Ldap();
$current_user = $ldap->getldapuser( $ntlm_user );

$ituser = ( in_array( $itgroup , $current_user->memberof ) ) ? true : false;

if( $vmoptions && $ituser )
{
        if( $reset )
        {
                print $vmware->reboot( $host , $vmid );
        }

        if( $poweron )
        {
                print $vmware->poweron( $host , $vmid );
        }

        if( $poweroff )
        {
                print $vmware->poweroff( $host , $vmid );
        }

        exit;
}

if( $vmconsole && !$ituser )
{
	print "You are not a member of SIE.IT";
        exit;
}

if( $checkname )
{
        $cuser = $ldap->getldapuser( $checkname );
        print ( $cuser->samaccountname ) ? $cuser->userprincipalname : 1;
        exit;
}

if( isset( $_POST['pproject'] ) && $ituser )
{
        $project = isset( $_POST['pproject'] ) ? $_POST['pproject'] : false;
        $os = isset( $_POST['pos'] ) ? $_POST['pos'] : false;
        $version = isset( $_POST['pversion'] ) ? $_POST['pversion'] : false;
        $arch = isset( $_POST['parch'] ) ? $_POST['parch'] : false;
        $contact = isset( $_POST['puser'] ) ? $_POST['puser'] : false;
        $contactcon = isset( $_POST['pusercon'] ) ? $_POST['pusercon'] : false;
        $host = isset( $_POST['phost'] ) ? $_POST['phost'] : false;
        $vmx = isset( $_POST['ppath'] ) ? $_POST['ppath'] : false;
        $vmid = isset( $_POST['pvmid'] ) ? $_POST['pvmid'] : false;
        $annotation = "Project : $project |0AOS : $os |0AVersion : $version |0AArch : $arch |0ACustomer : $contact|0AEmail : $contactcon";

        $vmware->setAnnotation( $host , $vmx , $annotation , $vmid );
        $vminfo = $vmware->getvmsummary( $host , $vmid );
        $fp_info = fopen( "$libdir/downloaded/$host.$vmid.info" , 'w' );
        fwrite( $fp_info , $vminfo );
        fclose( $fp_info );
        exit;
}


session_start();

header( "Pragma: nocache" );
header( "cache-Control: no-cache; must-revalidate" );
header( "Expires: Mon, 26 Jul 1993 00:00:00 GMT" );

#if(!ob_start("ob_gzhandler")) ob_start();

