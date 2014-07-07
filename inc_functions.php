<?php

$counter = 0;
$libdir = "/srv/www/sieesxi.ir.intel.com";
$files = glob( "$libdir/content/downloaded/*.info" );
$numvms = count( $files );
$nfsdiskarea = array();
$nfsdiskfree = array();
$hnodes = array();

function ddisks()
{
	global $nfsdiskarea, $nfsdiskfree;

	$disks = array();
#	$disks[0] = "/nfs/sie/disks/SIE-EC_esxi_disk003/";
#	$disks[1] = "/nfs/sie/disks/SIE-EC_esxi_disk004/";

	foreach( $disks as $disk )
	{
		$du = shell_exec( "du -h " . $disk );
		$df  = trim( shell_exec( "df -h " . $disk . "  | tail -n 1" ) );
		$parts = preg_split( '/\s+/' , $df );
		$nfsdiskfree[] = $parts;
		array_merge( $nfsdiskarea , explode( "\n" , $du ) );		
	}
}


function memorySize( $memory )
{
	return ( $memory > 1023 ) ? round( $memory / 1024 , 2 ) . "gb" : $memory . "mb";
}


function quickList( $filter = null )
{
	global $ldap, $current_user, $counter, $libdir;

	ddisks();

	$nodes = getNodes( $filter );
	$html = "";	

	$html .= "<table width='1200'>";

	foreach( $nodes as $node )
	{
		$vms = getNodeVms( $node );
		
		#if( count( $vms ) < 1 )
		#{
		#	continue;
		#}                
 
		$html .= "<tr>";
		if( $counter == 0 ) 
		{
                	$html .= "<td colspan='14'>";
			$counter++;
		}
		else
		{
			$html .= "<td colspan='14'><br>";
		}
                $html .= "<div id='" . base64_encode( $node ) . "'><table><tr><td width='60'><img src='content/images/vm.png' border='0' align='middle' /></td>";
                $html .= "<td><a name='" . base64_encode( $node ) . "'><h2>" . $node . "</h2></a></td></tr>";
                $html .= "</table></div></td>";

                $html .= "</tr>";

                $html .= "<tr>";
                $html .= "<td colspan='3'>&nbsp;</td>";
		$html .= "<td colspan='2'><b>Hardware</b></td>";
		$html .= "<td><b>Memory</b></td>";
		$html .= "<td colspan='2'><b>OS</b></td>";
                $html .= "<td><b>IpAddress</b></td>";
                $html .= "<td colspan='3'><b>Mac</b></td>";
                $html .= "<td><b>Serial</b></td>";
                $html .= "<td>&nbsp;</td>";
                $html .= "</tr>";

                $version = file_get_contents( "$libdir/content/downloaded/" . $node . ".version" );
                $hostinfo = getHostHardwareInfo( $node );
                $netinfo = getHostNetworkInfo( $node );
		
		list( $portgroup , $mac , $ipaddr ) = $netinfo;
		list( $mem , $vendor , $model , $serial , $cpus , $cores ) = $hostinfo;

                $html .= "<tr>";
                $html .= "<td colspan='2'>&nbsp;</td>";
		$html .= "<td><img src='content/images/host.png' /></td>";
		$html .= "<td colspan='2'>" . $vendor . " " . $model . "</td>";
		$html .= "<td>" . $mem . "</td>";
                $html .= "<td colspan='2'>" . $version . "</td>";
		$html .= "<td>" . $ipaddr . "</td>";
                $html .= "<td colspan='3'>" . $mac . "</td>";
                $html .= "<td>" . $serial . "</td>";
                $html .= "<td></td>";
                $html .= "</tr>";

                $html .= "<tr>";
                $html .= "<td colspan='14'><br></td>";
                $html .= "</tr>";

                $html .= "<tr>";
		$html .= "<td colspan='3'><br></td>";
                $html .= "<td colspan='11' style='border-top:1px dashed #99f'><br></td>";
                $html .= "</tr>";

                $HOST = $node;

                $html .= "<tr>";
		$html .= "<td width='15'>&nbsp;</td>";
		$html .= "<td width='20'>&nbsp;</td>";
		$html .= "<td width='20'>&nbsp;</td>";
                $html .= "<td><b>Name</b></td>";
                $html .= "<td><b>Mac</b></td>";
                $html .= "<td><b>Memory</b></td>";
                $html .= "<td><b>CPUS</b></td>";
	        $html .= "<td><b>Project</b></td>";
        	$html .= "<td><b>OS</b></td>";
	        $html .= "<td><b>Version</b></td>";
        	$html .= "<td><b>Arch</b></td>";
	        $html .= "<td><b>Customer</b></td>";
		$html .= "<td><b>Contact</b></td>";
		$html .= "<td><b>Tools</b></td>";
        	$html .= "</tr>";

		foreach( $vms as $vm )
		{
			$missingfile = 0;

			$vmfile = explode( "." , $vm );
			
			if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" ))
			{
				$info = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" );
			}
			else
			{
				$missingfile = 1;
			}
			if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".device" ))
			{
				$device = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".device" );
			}
			else
			{
				$missingfile = 1;
			}
			if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".power" ) )
			{
				$power = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".power" );
			}
			else
			{
				$missingfile = 1;
			}

			if( $missingfile == 1 )
			{
				continue;
			}

			$vmid = $vmfile[4];		
	
			preg_match( "/overallStatus = \"(.*?)\"/" , "$info" , $status ); 		
			preg_match( "/name = \"(.*?)\",/" , "$info" , $name );
			preg_match( "/memorySizeMB = ([0-9]+),/" , "$info" , $memory );
			preg_match( "/macAddress = \"(.*?)\",/" , "$device" , $mac );
			preg_match( "/numCpu = ([0-9]+),/" , "$info" , $cpu );
			preg_match( "/annotation = \"(.*?)\",/sm" , $info , $customer );
			preg_match( "/Powered ([a-zA-Z]+)/" , $power , $powerinfo );
			preg_match( "/vmPathName = \"\[(.*?)\] (.*?)\",/" , $info , $vmpath );
			preg_match( "/toolsStatus = \"(.*?)\",/" , $info , $vtools );

			$mac[1] = ( isset( $mac[1] ) ) ? $mac[1] : "";

			$custproj = "";
			$custos = "";
			$custver = "";
			$custarch = "";
			$custcon = "";
			$cust = "";

			$pwrinf = isset( $powerinfo[1] ) ? $powerinfo[1] : "off";	
			$statusColor = ( trim( $pwrinf ) == "on" ) ? "#33AA33" : "#AA3333";

			if( stristr( $customer[1] , ":" ) )
			{
				$customer = explode( "\n" , $customer[1] );
				foreach( $customer as $detail )
				{
					$det = explode( ":" , $detail );
					
					switch( strtolower(trim($det[0])) )
					{
						case "project": $custproj = trim( $det[1] ); break;
						case "email": $custcon = trim( $det[1] ); break;
						case "customer": $cust = trim( $det[1] ); break;
						case "os": $custos = trim( $det[1] ); break;
						case "version": $custver = trim( $det[1] ); break;
						case "arch": $custarch = trim( $det[1] ); break;
					}
				} 
			} 

			$ldapuser = $ldap->getldapuser( $cust );

			$vmpath = base64_encode( $vmpath[1] . "/" . $vmpath[2] );
			$vmhost = base64_encode( $HOST );	
			$html .= "<tr>";
			$html .= "<td></td>";
			$html .= "<td><img src='content/images/vm" . $pwrinf . ".png' border='0' width='16' /></td>";
			$html .= "<td><a title='Console' class='various' data-fancybox-type='iframe' href='index_console.php?host=" . $vmhost . "&vmpath=" . $vmpath . "&vmid=" . $vmid . "'>";
			$html .= "<img src='content/images/vmconsole.png'/></a></td>";
			$html .= "<td><a title='Console' class='various' data-fancybox-type='iframe' href='index_console.php?host=" . $vmhost . "&vmpath=" . $vmpath . "&vmid=" . $vmid . "'>" . $name[1] . "</a></td>";
			$html .= "<td>" . $mac[1] . "</td>";
			$html .= "<td>" . memorySize( $memory[1] ) . "</td>";
			$html .= "<td>" . $cpu[1] . "</td>";
			$html .= "<td>" . $custproj. "</td>";
			$html .= "<td>" . $custos . "</td>";
			$html .= "<td>" . $custver . "</td>";
			$html .= "<td>" . $custarch . "</td>";
			$html .= "<td><a href='im:&lt;sip:" . $custcon . "&gt;' title='" . $custcon . "'>" . $custcon . "</a></td>";
                        $html .= "<td><a href='mailto:" . $custcon . "' style='margin-right:3px'><img src='content/images/outlook.png' border='0' width='16'/></a>";
                        /*
                                $html .= "<a class='edit' data-fancybox-type='iframe' href='index.php?edit=1&host=" . $vmhost  . "&vmpath=" . $vmpath . "&vmid=" . $vmid . "' style='margin-right:3px'>";
                                $html .= "<img src='content/images/edit.png' border='0' width='16'/></a>";
                        */
                        $html .= "<a href='im:&lt;sip:" . $custcon . "&gt;' style='margin-right:3px'><img src='content/images/lync.png' border='0' width='16'/></a>";
                        $html .= "<a href='tel:" . $ldapuser->ipphone . "' title='" . $ldapuser->ipphone . "'  style='margin-right:3px'><img src='content/images/telephone.png' border='0' width='16'/></a></td>";

			$html .= "<td><img src='content/images/" . $vtools[ 1 ] . ".png' title='" . $vtools[ 1 ] . "' alt='" . $vtools[ 1 ] . "' width='16'/></td>";
			$html .= "</tr>";
	
		}
	
		if( count( $vms ) == 0 )
		{
			$html .= "<tr>";
			$html .= "<td colspan='14' align='center'><b style='color:red;'>0 virtual machines</b></td>";
			$html .= "</tr>";
		}
	

		$html .= "<tr>";
		$html .= "<td colspan='14' style='border-bottom:1px dashed #99f'><br><center><span style='font-size:7pt'>Last polled : <b>";
		if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" ) )
		{
			$html .= date( "l jS \of F Y h:i:s A" , filemtime( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" ) ); 
		}
		$html .= "</b></style></center><br><br></td>";
		$html .= "</tr>";
	
	}	
	

	$html .= "</table>";

	print $html;
}


function readHardware( $host )
{
	global $libdir;

        $hwinfo = file_get_contents( "$libdir/content/downloaded/" . $host . ".hwinfo" );
	$netinfo = file_get_contents( "$libdir/content/downloaded/" . $host . ".netinfo" );

        preg_match( "/address.*?\[(.*?)\]/sm" , "$netinfo" , $dns );
        preg_match_all( "/ipAddress = \"(.*?)\"/", "$netinfo" , $ipaddr );
        preg_match( "/memorySize = ([0-9]+),/" , "$hwinfo" , $memory );
        preg_match( "/numCpuPackages = ([0-9]+)/" , "$hwinfo" , $cpunum );
        preg_match( "/description = \"(.*?)\"/" , "$hwinfo" , $cpu );
        preg_match( "/numCpuThreads = ([0-9]+)/" , $hwinfo , $cpunumt );
	
	$html = "";
	$html .= "<table width='500px' cellpadding='5'>";
	
	$html .= "<tr>";
	$html .= "<td width='25%'><b>DNS</b></td><td width='75%'>" . $dns[1] . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>IP</b></td><td width='75%'>" . implode( " " ,  $ipaddr[1] ) . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Memory</b></td><td width='75%'>" . memorySize( $memory[1] / 1024 / 1024 ) . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Name</b></td><td width='75%'>" . $host . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Processors</b></td><td width='75%'>" . $cpunum[1] . " (" . $cpunumt[1] . ")</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Processor type</b></td><td width='75%'>" . $cpu[1] . "</td>";
	$html .= "</tr>";
	
	$html .= "</table>";
	$html .= "";
	
	print $html;
}


function readBios( $host )
{
	global $libdir;

        $hwinfo = file_get_contents( "$libdir/content/downloaded/" . $host . ".hwinfo" );
        $netinfo = file_get_contents( "$libdir/content/downloaded/" . $host . ".netinfo" );

        preg_match_all( "/identifierValue = \"(.*?)\"/" , "$hwinfo" , $assettag );
        preg_match( "/releaseDate = \"(.*?)\"/" , "$hwinfo" , $biosdate );
        preg_match( "/biosVersion = \"(.*?)\"/" , "$hwinfo" , $biosversion );
        preg_match( "/systemInfo.*?vendor = \"(.*?)\"/sm" , $hwinfo , $vendor );
	preg_match( "/systemInfo.*?model = \"(.*?)\"/sm" , $hwinfo , $model );

	$html = "<table width='500px' cellpadding='5'>";
	
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Asset Tag</b></td><td width='75%'>" . $assettag[1][1] . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Date</b></td><td width='75%'>" . $biosdate[1] . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Version</b></td><td width='75%'>" . $biosversion[1] . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Manufactuer</b></td><td width='75%'>" . $vendor[1] . "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
	$html .= "<td width='25%'><b>Model</b></td><td width='75%'>" . $model[1] . "</td>";
	$html .= "</tr>";
	
	$html .= "</table>";
	
	print $html;
}


function cleanupstaleandlogs()
{
	global $libdir;

        shell_exec( "rm -rvf $libdir/content/temp/*" );
}


function getNodes( $filter = null )
{
	global $hnodes;

        $ret_nodes = array();

        $nodes = shell_exec( "/bin/ucat nodes key speciality" );
        $nodes = explode( "\n" , $nodes );

	foreach( $nodes as $node )
	{
		if( trim( $node ) == "" ) continue;

		$c_node = explode( " " , $node );
		if( $filter == null )
		{
			if( strstr( $c_node[1] , "ESX" ) )
			{
				$ret_nodes[] = trim( $c_node[0] );
			}
		}
		else if( $c_node[1] == "$filter" )
		{
			$ret_nodes[] = trim( $c_node[0] );
		}
	}

	sort( $ret_nodes );

        return $ret_nodes;
}


function getNodeVms( $node )
{
	global $files;

	$vms = array();

	foreach( $files as $file )
	{
		if( preg_match( "/" . $node . ".*/" , $file , $matches ) )
		{
			$vms[] = $file;
		}
	}

	sort( $vms );

	return $vms;
}


function getNodeVmsCount( $node )
{
	return count( getNodeVms( $node ) );
}


function getNodeVmById( $node , $vmid )
{
        global $files;

        $vm = array();

        foreach( $files as $file )
        {
                if( preg_match( "/" . $node . ".*?" . $vmid . ".*/" , $file , $matches ) )
                {
                        $vm[] = $file;
                }
        }

        return $vm;
}


function editform()
{
	global $ldap, $host , $vmid;
 
        $vm = getNodeVmById( $host , $vmid );

        $vmfile = explode( "." , $vm[0] );
	if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" ) )
	{
        	$info = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".info" );
	}
	if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".device" ) )
	{
        	$device = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".device" );
	}
	if( file_exists( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".power" ) )
	{
        	$power = file_get_contents( $vmfile[0] . "." . $vmfile[1] . "." . $vmfile[2] . "." . $vmfile[3] . "." . $vmfile[4] . ".power" );  
	}

        $vmid = $vmfile[4];

        preg_match( "/overallStatus = \"(.*?)\"/" , "$info" , $status );
        preg_match( "/name = \"(.*?)\",/" , "$info" , $name );          
        preg_match( "/memorySizeMB = ([0-9]+),/" , "$info" , $memory ); 
        preg_match( "/macAddress = \"(.*?)\",/" , "$device" , $mac );   
        preg_match( "/numCpu = ([0-9]+),/" , "$info" , $cpu );          
        preg_match( "/annotation = \"(.*?)\",/sm" , $info , $customer );
        preg_match( "/Powered ([a-zA-Z]+)/" , $power , $powerinfo );    
        preg_match( "/vmPathName = \"\[(.*?)\] (.*?)\",/" , $info , $vmpath );
        preg_match( "/toolsStatus = \"(.*?)\",/" , $info , $vtools );         

        $custproj = "";
        $custos = "";  
        $custver = ""; 
        $custarch = "";
        $custcon = ""; 
        $cust = "";    

        if( stristr( $customer[1] , ":" ) )
        {                                  
                $customer = explode( "\n" , $customer[1] );
                foreach( $customer as $detail )            
                {                                          
                        $det = explode( ":" , $detail );   

                        switch( strtolower(trim($det[0])) )
                        {                                  
                                case "project": $custproj = trim( $det[1] ); break;
                                case "email": $custcon = trim( $det[1] ); break;   
                                case "customer": $cust = trim( $det[1] ); break;   
                                case "os": $custos = trim( $det[1] ); break;       
                                case "version": $custver = trim( $det[1] ); break; 
                                case "arch": $custarch = trim( $det[1] ); break;   
                        }                                                          
                }                                                                  
        }                                                                          

        $ldapuser = $ldap->getldapuser( $cust );
                                                
        print "                                 
        <form>                                  
        <table cellpadding='5'>                 
        <tr>                                    
                <td valign='top' align='right' width='120'>
                        <img src='content/images/vm.png'/>         
                </td>                                      
                <td colspan='2'><h1 style='margin-top:0px;padding-top:0px;margin-bottom:0px;padding-bottom:0px;line-height:14pt'>$host</h1>
                                <h3 style='margin-top:5px;padding-top:0px;line-height:14pt'><img src='content/images/vmoff.png''/> $name[1] </h3></td>
        </tr>                                                                                                                                                   
        <tr>                                                                                                                                                    
                <td align='right'>Project</td>                                                                                                                  
                <td><input type='text' id='project' name='project' value='$custproj' style='width:300px'></td>                                                  
        </tr>                                                                                                                                                   
        <tr>                                                                                                                                                    
                <td align='right'>OS</td>
                <td><input type='text' id='os' name='os' value='$custos' style='width:300px'></td>
        </tr>
        <tr>
                <td align='right'>Version</td>
                <td><input type='text' id='version' name='version' value='$custver'></td>
        </tr>
        <tr>
                <td align='right'>Architecture</td>
                <td><input type='text' id='architecture' name='architecture' value='$custarch'></td>
        </tr>
        <tr>
                <td align='right'>Customer Idsid</td>
                <td><input type='text' id='customer' name='customer' value='$cust'> &nbsp;<a href='javascript:checkname()'><img src='content/images/checknames.gif' border='0'></a></td>
        </tr>
        <tr>
                <td align='right'>Customer Email</td>
                <td><input type='text' id='customercon' name='customercon' value='$custcon' style='width:300px' disabled='disabled'></td>
        </tr>
        <tr>
                <td></td>
                <td><input type='button' value='Modify' onclick='validate()'></td>
        </tr>
        </table>
        </form>";
}


function sync()
{
	global $libdir;
	$vmware = new Vmware( "root" );

	$downloadtempdir = $libdir . "/content/temp";
	$downloaddir = $libdir . "/content/downloaded";
	$nodes = getNodes();

	cleanupstaleandlogs();

	foreach( $nodes as $node )
	{
		print $node . "\n";
        	$vmnums = $vmware->gethostvms( $node );
	        $vmnums = explode( "\n" , $vmnums );

        	$hw = $vmware->gethosthardware( $node );
	        $fp_hw = fopen( "$downloadtempdir/$node.hwinfo" , 'w' );
	        fwrite( $fp_hw , $hw );
	        fclose( $fp_hw );

	        $net = $vmware->gethostnetwork( $node );
	        $fp_net = fopen( "$downloadtempdir/$node.netinfo" , 'w' );
	        fwrite( $fp_net , $net );
	        fclose( $fp_net );

		$ver = $vmware->getversion( $node );
                $fp_ver = fopen( "$downloadtempdir/$node.version" , 'w' );
                fwrite( $fp_ver , $ver );
                fclose( $fp_ver );
	
        	foreach( $vmnums as $vmnum )
	        {
        	        if( preg_match( '/[0-9]{1,3}/' , $vmnum ) )
	                {
        	                $vminfo = $vmware->getvmsummary( $node , $vmnum );
                	        $vmdevice = $vmware->getvmdevices( $node , $vmnum );
                        	$vmpower = $vmware->getvmstate( $node , $vmnum );

	                        $fp_info = fopen( "$downloadtempdir/$node.$vmnum.info" , 'w' );
        	                $fp_devices = fopen( "$downloadtempdir/$node.$vmnum.device" , 'w' );
                	        $fp_power = fopen( "$downloadtempdir/$node.$vmnum.power" , 'w' );

	                        fwrite( $fp_devices , $vmdevice );
        	                fwrite( $fp_info , $vminfo );
                	        fwrite( $fp_power , $vmpower );

	                        fclose( $fp_info );
        	                fclose( $fp_devices );
                	        fclose( $fp_power );
	                }
        	}
	}

	$del = shell_exec( "rm -rvf $downloaddir/*" );
	$move = shell_exec( "mv $downloadtempdir/* $downloaddir/" );	
	$chmod = shell_exec( "chmod 770 $downloaddir/*" );
	$chwon = shell_exec( "chown ec_webadm:webadm  $downloaddir/*" );
}


function getHostNetworkInfo( $host )
{
	$file = file_get_contents( "content/downloaded/" . $host . ".netinfo" );

	preg_match( "/\(vim\.host\.VirtualNic\.Config\) .*?portgroup = \"(.*?)\"/sm" , $file , $portgroup );
	preg_match( "/\(vim\.host\.VirtualNic\.Config\) .*?mac = \"(.*?)\"/sm" , $file , $mac );
	preg_match( "/\(vim\.host\.VirtualNic\.Config\) .*?ipAddress = \"(.*?)\"/sm" , $file , $ipaddress );

	$portgroup[1] = isset( $portgroup[1] ) ? $portgroup[1] : "";
	$mac[1] = isset( $mac[1] ) ? $mac[1] : "";
	$ipaddress[1] = isset( $ipaddress[1] ) ? $ipaddress[1] : "";

	return array( $portgroup[1] , $mac[1] , $ipaddress[1] );
}


function getHostHardwareInfo( $host )
{
        $file = file_get_contents( "content/downloaded/" . $host . ".hwinfo" );

	preg_match( "/\(vim\.host\.HardwareInfo\) \{.*?vendor = \"(.*?)\",/sm" , $file , $match1 );
	preg_match( "/\(vim\.host\.HardwareInfo\) \{.*?model = \"(.*?)\",/sm" , $file , $match2 );
	preg_match( "/identifierValue = \"([a-zA-Z0-9]+)\",/sm" , $file , $match3 );
	preg_match( "/cpuInfo = \(vim\.host\.CpuInfo\) \{.*?numCpuPackages = ([0-9]+),/sm" , $file , $match4 );
	preg_match( "/cpuInfo = \(vim\.host\.CpuInfo\) \{.*?numCpuCores = ([0-9]+),/sm" , $file , $match5 );
        preg_match( "/memorySize = ([0-9]+),/" , $file , $memorysize );

	$memorysize[1] = isset( $memorysize[1] ) ? $memorysize[1] : "";
	$match1[1] = isset( $match1[1] ) ? $match1[1] : "";
	$match2[1] = isset( $match2[1] ) ? $match2[1] : "";
	$match3[1] = isset( $match3[1] ) ? $match3[1] : "";
	$match4[1] = isset( $match4[1] ) ? $match4[1] : "";
	$match5[1] = isset( $match5[1] ) ? $match5[1] : "";

        return array( round( $memorysize[1] / 1024 / 1024 / 1024 ) , $match1[1] , $match2[1] , isset( $match3[1] ) ? $match3[1] : "" , $match4[1] , $match5[1] );
}
