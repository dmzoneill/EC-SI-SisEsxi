<?php

include( "inc_common.php" );

?>
	<html>
	<head>
	<link rel='stylesheet' href='content/css/intel.css' type='text/css' media='screen'>
	<link rel='stylesheet' href='content/css/smoothness/jquery-ui-1.8.13.custom.css' type='text/css' media='all' />
        <script type='text/javascript' src='content/js/jquery-1.7.2.min.js'></script>
	<script type='text/javascript' src='content/js/jquery-ui-1.8.18.custom.min.js'></script>
        <link rel='stylesheet' href='content/css/jquery.fancybox.css' type='text/css' media='screen' />
	<script type='text/javascript' src='content/js/jquery.fancybox.pack.js'></script>
        <script type='text/javascript' src='content/js/intel.js'></script>
	<script type='text/javascript'>

        function checkname()                       
        {                                          
                var checkuser = $( "#customer" ).val();     
                var url = 'index.php?checkname=' + checkuser;
                                                                                                                        
                $.get( url , function( data )                                                                           
                {                                                                                                       
                        if( data != "0" )                                                                               
                        {                                                                                               
                                $( "#customercon" ).val( data );                                                        
                                $( "#customer" ).css( "color" , "#000000" );                                            
                                $( "#customer" ).css( "text-decoration" , "underline" );                                
                        }                                                                                               
                        else                                                                                            
                        {                                                                                               
                                $( "#customer" ).css( "color" , "#AA0000" );                                            
                                $( "#customer" ).css( "text-decoration" , "none" );                                     
                        }                                                                                               
                });                                                                                                     
        }                                                                                                               

        function validate()
        {                  
                var checkuser = $( "#customer" ).val();
                var checkusercon = $( "#customercon" ).val();
                var url = 'index.php?checkname=' + checkuser;

                $.get( url , function( data )
                {                            
                        if( data != "0" )    
                        {                    
                                var project = $( "#project" ).val();
                                var os = $( "#os" ).val();          
                                var version = $( "#version" ).val();
                                var arch = $( "#architecture" ).val();

                                $( "#customercon" ).val( data );
                                $( "#customer" ).css( "color" , "#000000" );
                                $( "#customer" ).css( "text-decoration" , "underline" );
                                                                                        
                                if( project.match( /^[a-z0-9 \-]+$/i ) )                
                                {                                                       
                                        if( os.match( /^[a-z0-9 \-]+$/i ) )             
                                        {                                               
                                                if( version.match( /^[a-z0-9 \-]+$/i ) )
                                                {                                       
                                                        if( arch.match( /^[a-z0-9 \-]+$/i ) )
                                                        {                                    
                                                                var url = 'index.php?update=1';
                                                                $.post( 'index.php' , {
                                                                        pproject: project,
                                                                        pos: os,
                                                                        pversion: version,
                                                                        parch: arch,
                                                                        puser: checkuser,
                                                                        pusercon: checkusercon,
                                                                        phost: '<?php print $host; ?>',
                                                                        ppath: '/vmfs/volumes/<?php print $vmpath; ?>',
                                                                        pvmid: '<?php print $vmid; ?>'
                                                                } , function( data )
                                                                {
                                                                        window.parent.location.href = 'index.php?r=' + Math.random();
                                                                });
                                                        }
                                                        else
                                                        {
                                                                $( "#project" ).css( "color" , "#AA0000" );
                                                                $( "#project" ).css( "text-decoration" , "none" );
                                                        }
                                                }
                                                else
                                                {
                                                        $( "#version" ).css( "color" , "#AA0000" );
                                                        $( "#version" ).css( "text-decoration" , "none" );
                                                }
                                        }
                                        else
                                        {
                                                $( "#os" ).css( "color" , "#AA0000" );
                                                $( "#os" ).css( "text-decoration" , "none" );
                                        }
                                }
                                else
                                {
                                        $( "#project" ).css( "color" , "#AA0000" );
                                        $( "#project" ).css( "text-decoration" , "none" );
                                }
                        }
                        else
                        {
                                $( "#customer" ).css( "color" , "#AA0000" );
                                $( "#customer" ).css( "text-decoration" , "none" );
                        }
                });

        }
	
	</script>
<?php 

if( $vmconsole && $ituser )
{

?>
        <script type="text/javascript">                                                                        

                var vmhostnameenc = "<?php print $_GET[ 'host' ]; ?>";
                var vmpathenc = "<?php print $_GET[ 'vmpath' ]; ?>";  

                var port = 902;
                var vmhostname = "<?php print $host; ?>.ir.intel.com";
                var vmpath = "/vmfs/volumes/<?php print $vmpath; ?>"; 
                var username = 'root';                                
                var password = '';                            
                var rawDevice = false; // I tried true and false      
                var exclusiveDevice = true;                           
                var imageISO = true;                                  
                var vmid = "<?php print $vmid; ?>";                   

                var loading = 1;

                $( document ).ready( function()
                {               
			password = prompt('Please enter root password',' ');
                        setTimeout( activex , 500 );
                });                                 
	</script>
        <style>  

                * {
                        margin:0;
                        padding:0
                }                
                                 
                html,body        
                {                
                        height:100%;
                        width:100%; 
                }                   
                                    
                body                
                {                   
                        text-align:center;
                        min-height:468px;/* for good browsers*/
                        min-width:552px;/* for good browsers*/ 
                }                                              

                #outer
                {     
                        height:100%;
                        width:100%; 
                        display:table;
                        vertical-align:middle;
                }                             

                #container
                {
                        text-align: center;
                        position:relative;
                        vertical-align:middle;
                        display:table-cell;
                        height: 150px;
                }

                #inner
                {
                        width: 100%;
                        height: 150px;
                        text-align: center;
                        margin-left:auto;
                        margin-right:auto;
                        background-image:url('content/images/intel-small.jpg');
                        background-repeat:no-repeat;
                        background-attachment:fixed;
                        background-position:center;
                }

        </style>
        <!--[if lt IE 8]>
        <style type='text/css'>

                #container
                {
                        top:50%
                }

                #inner
                {
                        top:-50%;
                        position:relative;
                        text-align:center;
                }

        </style>
        <![endif]-->

        <!--[if IE 7]>
        <style type='text/css'>

                #outer
                {
                        position:relative;
                        overflow:hidden;
                }
        </style>
        <![endif]-->
	</head>
	<body>

<?php

}

if( !$vmconsole )
{
	$vmpoweron = shell_exec( "cat /srv/www/sieesxi.ir.intel.com/content/downloaded/* | grep \"Powered on\" | wc -l" );
	$vmpoweroff = shell_exec( "cat /srv/www/sieesxi.ir.intel.com/content/downloaded/* | grep \"Powered off\" | wc -l" );

        $head = "<table width='1500'>
	<tr>
		<td width='1500' colspan='2'>
			<table>
				<tr>
				<td width='242' height='150'>
        	        	    <div id='intelloadingCon' style='width:162px;height:150px;margin-left:70px;margin-right:0px'>
	                        	<a href='index.php'>
        	                	    <img id='intelloading' src='content/images/intel-small.jpg' width='100%' height='100%' border='0'/>
	             			</a>
        		            </div>
				</td>
				<td width='1188'>
					<font style='padding-left:40px;padding-right:40px;font-size:18pt'>Shannon ESXi Inventory</font><br/>
					<span style='margin-left:70px;font-size:12pt'>$numvms virtual machines, $vmpoweron on, $vmpoweroff off</span>
				<td>
				</tr>
			</table>
		</td>
	</tr>
        <tr>
               	<td width='200' style='text-align:right;vertical-align:top;padding-right:20px'>
                       	<h2 class='hr' style='border-bottom:1px dashed #99f;margin-left:10px'>Home</h2>
	                <a href='index.php'>Homepage</a><br/><br/>
                        <h2 class='hr' style='border-bottom:1px dashed #99f;margin-left:10px'>ESSV</h2>";

	$nodes = getNodes( "ESX-Server.Infra" );

	foreach( $nodes as $node )
	{
		$node64 = base64_encode( $node );

		$head.= "[" . getNodeVmsCount( $node ) . "] <a href=\"javascript:scrolltoit( '$node64' )\">$node</a><br/>";
	}

	$head .= "<br/>
                  <h2 class='hr' style='border-bottom:1px dashed #99f;margin-left:10px'>Production</h2>";

	$nodes = getNodes( "ESX-Server,ESX-Server.Standalone" );

	foreach( $nodes as $node )
	{
		$node64 = base64_encode( $node );

		$head.= "[" . getNodeVmsCount( $node ) . "] <a href=\"javascript:scrolltoit( '$node64' )\">$node</a><br/>";
	}


	$head .= "<br/>
                  <h2 class='hr' style='border-bottom:1px dashed #99f;margin-left:10px'>Lab</h2>";

	$nodes = getNodes( "ESX-Server,ESX-Server.Standalone,Lab-Server,Lab-Equipment" );

	foreach( $nodes as $node )
	{
		$node64 = base64_encode( $node );

		$head.= "[" . getNodeVmsCount( $node ) . "] <a href=\"javascript:scrolltoit( '$node64' )\">$node</a><br/>";
	}

	$head .= "<br/>
                  <h2 class='hr' style='border-bottom:1px dashed #99f;margin-left:10px'>ILab</h2>";

	$nodes = getNodes( "ESX-Server,ESX-Server.ilab,Lab-Server,Lab-Equipment" );

	foreach( $nodes as $node )
	{
		$node64 = base64_encode( $node );

		$head.= "[" . getNodeVmsCount( $node ) . "] <a href=\"javascript:scrolltoit( '$node64' )\">$node</a><br/>";
	}

	$head .= "</td><td width='1200' style='padding:20px;vertical-align:top;border:1px dashed #99f;'>";

	print $head;
}

