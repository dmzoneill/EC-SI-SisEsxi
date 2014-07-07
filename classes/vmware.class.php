<?php

class Vmware
{
	private $identification = "";
	private $whoami;
	private $log;
	private $user;

	public function __construct( $user )
	{
		$this->user = $user;
		
		if( $this->user != "root" )
		{
			$this->log = fopen( "/srv/www/sieesxi.ir.intel.com/content/log.txt" , 'a' );
		}

		$this->whoami = trim( shell_exec( "whoami" ) );
		$this->identification = "/srv/www/sieesxi.ir.intel.com/content/.key/identification";
	}

	private function remotecall( $host , $command )
	{
		$command = "/usr/bin/ssh -x -q -oStrictHostKeyChecking=no -i " . $this->identification . " root@" . $host . ".ir.intel.com \"" . $command . "\" 2>&1";
	
		$result = shell_exec( $command );

		return $result;
	}

	public function reboot( $host , $vmid )
	{
		return $this->remotecall( $host , "vim-cmd vmsvc/power.reset " . $vmid );
	}

        public function poweron( $host , $vmid )
        {
                return $this->remotecall( $host , "vim-cmd vmsvc/power.on " . $vmid );
        }

        public function poweroff( $host , $vmid )
        {
                return $this->remotecall( $host , "vim-cmd vmsvc/power.off " . $vmid );
        }

	public function gethostvms( $host )
	{
		return $this->remotecall( $host , 'vim-cmd vmsvc/getallvms | grep -e \'^[0-9].*$\' | awk \'{ print \$1 }\'' );		
	}

	public function getvmsummary( $host , $vmid )
	{
		return $this->remotecall( $host , "vim-cmd vmsvc/get.summary " . $vmid );
	}

	public function getvmdevices( $host , $vmid )
	{
		return $this->remotecall( $host , "vim-cmd vmsvc/device.getdevices " . $vmid );
	}

	public function getvmstate( $host , $vmid )
	{
		return $this->remotecall( $host , "vim-cmd vmsvc/power.getstate " . $vmid );
	}	

	public function gethosthardware( $host )
	{
		return $this->remotecall( $host , "vim-cmd hostsvc/hosthardware" );
	}

	public function gethostnetwork( $host )
	{
		return $this->remotecall( $host , "vim-cmd hostsvc/net/config" );
	}

	public function getvmuserwwid( $host , $vmid , $vmx )
	{
	}

	public function setAnnotation( $host , $vmx , $annotation , $vmid )
	{
		$vmxnotes = $this->remotecall( $host , "cat $vmx | grep annotation" );

		if( stristr( $vmxnotes , "annotation" ) )
		{
			$this->remotecall( $host , "sed -r -i 's/annotation = \\\"(.*?)\\\"/annotation = \\\"$annotation\\\"/g' $vmx" );			
			return $this->remotecall( $host , "vim-cmd vmsvc/reload " . $vmid );
		}
		else
		{
			$this->remotecall( $host , "echo 'annotation = \\\"$annotation\\\"' >> $vmx" );
			return $this->remotecall( $host , "vim-cmd vmsvc/reload " . $vmid );
		}
	}

	public function getversion( $host )
	{
		$version = $this->remotecall( $host , "vmware -l -v" );
		preg_match( "/VMware ESXi ([0-9]\.[0-9]\.[0-9])/i" , $version , $esx );
		preg_match( "/VMware ESXi.*?build-([0-9]+)/i" , $version , $build );
		preg_match( "/.*?Update (.*?)$/i" , $version , $update );
		$version = "VMware ESXi";
		$version .= isset( $esx[1] ) ? $esx[1] : "";
		$version .= " ";
		$version .= isset( $update[1] ) ? $update[1] : "";
		$version .= " ";
		$version .= isset( $build[1] ) ? $build[1] : "";

		return $version;
	}
}
