<?php

class LdapUser
{
	private $connection = null;
	private $data = null;
	
	public function __construct( $params )
	{		
		$this->data = array();
		$this->connection = $params[ 0 ];
		$this->getuser( $params[ 1 ] );
	}

	public function __get( $property )
    {		
        if( array_key_exists( $property , $this->data ) ) 
		{
			if( $this->data[ $property ][ 'count' ] > 1 )
			{
				return $this->data[ $property ];
			}
			
            return $this->data[ $property ][ 0 ];
        }
		
        return false;
    }
	
	public function getimage()
	{	
		if( $this->thumbnailphoto )
		{		
			$img = imagecreatefromstring( $this->thumbnailphoto );
			
			imagejpeg( $img , "images/users/" . $this->data['samaccountname'][0] . ".jpg" );
			
			return true;	
		}
		
		return false;
	}
	
	public function getemployees()
	{
		if( $this->ismanager == "TRUE" )
		{
			$ldapgroup = new LdapGroup( array( $this->connection , false ) );
			
			for( $k = 0; $k < $this->directreports['count']; $k++ )
			{
				$ldapgroup->add( new LdapUser( array( $this->connection , $this->directreports[$k] ) ) );
			}			
			
			return $ldapgroup;
		}
		
		return new LdapGroup( array( false , false ) );
	}
	
	public function dump()
	{
		print "<pre>";
		print_r( $this->data );
		print "</pre>";
	}
	
	private function getuser( $user )
	{		
		if( !stristr( $user , "DC=corp,DC=intel,DC=com" ) )
		{
			$search_attributes = array( "*" );
			$filter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=$user))"; 
			$user_obj = ldap_search( $this->connection , "DC=corp,DC=intel,DC=com" , $filter , $search_attributes );
			$user_properties = ldap_get_entries( $this->connection , $user_obj );
						
			if( is_array( $user_properties ) )
			{
				if( $user_properties['count'] > 0 )
				{
					$this->data = $user_properties[ 0 ];
				}
				else
				{
				
				}
			}
			else
			{
			
			}
		}
		else
		{
			$search_attributes = array( "*" );
			$filter = "(&(objectClass=user)(objectCategory=person))"; 
			$user_obj = ldap_read( $this->connection , $user , $filter , $search_attributes );
			$user_properties = ldap_get_entries( $this->connection , $user_obj );
			
			if( is_array( $user_properties ) )
			{
				if( $user_properties['count'] > 0 )
				{
					$this->data = $user_properties[ 0 ];
				}
				else
				{
				
				}
			}
			else
			{
			
			}
		}
	}
	
	public function __toString()
	{
		return $this->data['samaccountname'][0];
	}
}
