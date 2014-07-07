<?php

class LdapGroup
{
	private $connection = null;
	private $users = array();
	
	public function __construct( $params )
	{
		$this->connection = $params[0];
		
		if( $params[1] != false )
		{
			$this->populate( $params[1] );	
		}
	}
	

	private function populate( $group )
	{		
		$group = stristr( $group , "DC=corp,DC=intel,DC=com" ) ? $group : "CN=" . $group . ",OU=Delegated,OU=Groups,DC=ger,DC=corp,DC=intel,DC=com";
			
		$search_attributes = array( "*" );	
		$filter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=*)(memberof:1.2.840.113556.1.4.1941:=$group))";
		$user_obj = ldap_search( $this->connection , "OU=Workers,DC=ger,DC=corp,DC=intel,DC=com" , $filter , $search_attributes );
		$objs = ldap_get_entries( $this->connection , $user_obj );	
		
		foreach( $objs as $person )
		{
			$this->users[] = new LdapUser( array( $this->connection , $person['distinguishedname'][0] ) );
		}
	}
	
	
	public function add( $user )
	{	
		$this->users[] = $user;			
	}
	
	public function size()
	{
		return count( $this->users );
	}
	
	public function get( $index )
    {
        return $this->users[ $index ];
    }
}