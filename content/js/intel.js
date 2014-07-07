
        function attachHandlers()
        {                        
                mks.OnConnectionStateChange = function( connected )
                {                                                  
                }                                                  

                mks.OnDeviceConnectionStateChange = function( cntd , x , y )
                {                                                           
                }                                                           

                mks.OnMessage = function( type , message )
                {                                         
                        alert( 'Message: ' + message );   
                }                                         

                mks.OnWindowStateChange = function( windowState )
                {                                                
                        if( windowState == 3 )                   
                        {                                        
                                if( confirm( "You are about to enter fullscreen mode. Press Ctl+Alt to return to windowed mode." ) ) 
                                {                                                                                                    
                                        mks.setFullScreen( true );                                                                   
                                }                                                                                                    
                        }                                                                                                            
                }                                                                                                                    

                mks.OnGrabStateChange = function( grabState )
                {                                            
                        if( grabState == 1 )                 
                        {                                    
                                window.status = "Press Ctl+Alt to release cursor.";
                        }                                                          
                        else                                                       
                        {                                                          
                                window.status = "";                                
                        }                                                          
                }                                                                  

                mks.OnSizeChange = function()
                {                            
                        mks.height = mks.VMScreenHeight + 1;
                        mks.width = mks.VMScreenWidth + 2;  
                }                                           
        }                                                   

        function connect() 
        {                                   
                try                         
                {                           
                        if( mks.Connect( vmhostname , port , vmpath , username , password ) ) 
                        {                                                                     
                                setTimeout( "$('#mks').css('height',mks.VMScreenHeight+1);$('#mks').css('width',mks.VMScreenWidth+2);" , 100 );
                        }                                                                                                                      
                        else                                                                                                                   
                        {                                                                                                                      
                                alert( 'Failed to open the VM (it may be powered off)' );                                                      
                        }                                                                                                                      
                }                                                                                                                              
                catch( e )                                                                                                                     
                {                                                                                                                              
                        alert('Exception: ' + e.message);                                                                                      
                }                                                                                                                              
        }                                                                                                                                      

        function reconnect()
        {                   
                mks.Connect( vmhostname , port , vmpath, username , password );
        }                                                                      

        window.onbeforeunload = function()
        {                                 
                var deviceNode = $('#DeviceNode').val();

                if( checkCDROM( deviceNode ) )
                {                             
                        event.returnValue = 'You have mounted a client iso file to a CDROM. The iso will be un-mounted when you leave current page.';
                }                                                                                                                                    
        }                                                                                                                                            

        function shut( deviceNode ) 
        {                           
                mks.Disconnect();   
                detachCDROM( deviceNode );
        }                                 

        function attachCDROM( deviceNode , localDevicePath ) 
        {                                                    
                try                                          
                {                                            
                        if( deviceNode == null || deviceNode == "" )
                        {                                           
                                alert( "Error: CD ROM is empty!" ); 
                                return;                             
                        }                                           

                        if( localDevicePath == null || localDevicePath == "" )
                        {                                                     
                                alert( "ISO Image is empty." );               
                                return;                                       
                        }                                                     

                        if( checkCDROM( deviceNode ) )
                        {                             
                                detachCDROM( deviceNode );
                        }                                 

                        result = mks.AttachRemoteCDROM( vmhostname, port, vmpath, username, password, deviceNode, rawDevice, exclusiveDevice, imageISO, localDevicePath );

                        if( !result )
                        {            
                                alert("CD ROM cannot be attached. Make sure the iso path is right and no other iso image is attached already.");
                                return;                                                                                                         
                        }                                                                                                                       

                        setTimeout( "checkAttachResult('" + deviceNode + "')", 5000 );
                }                                                                     
                catch( e )                                                            
                {                                                                     
                        alert( e.name + ": " + e.message );                           
                }                                                                     
        }                                                                             

        function checkAttachResult(deviceNode)
        {                                     
                if( checkCDROM( deviceNode ) )
                {                             

                }
                else
                {   
                        alert("CD ROM cannot be attached. Make sure the iso path is right and no other iso image is attached already.");
                }                                                                                                                       
        }                                                                                                                               

        function detachCDROM( deviceNode ) 
        {                                  
                try                        
                {                          
                        var devicePath = checkCDROM( deviceNode );

                        if( devicePath )
                        {               
                                mks.DetachRemoteDevice( devicePath, vmpath );

                                if( !checkCDROM( deviceNode ) )
                                {                              
                                        return;                
                                }                              
                        }                                      
                }                                              
                catch( e )                                     
                {                                              
                        alert( e.name + ": " + e.message );    
                }                                              
        }                                                      

        function checkCDROM( deviceNode )
        {                                
                var devicePath = mks.GetLocalDevicePath( deviceNode, vmpath );
                return devicePath;                                            
        }                                                                     

        function powerresetvm()
        {               
                var url = 'index_console.php?host=' + vmhostnameenc + '&vmpath=' + vmpathenc + "&vmid=" + vmid + '&reset=1&r=' + Math.random();  

                $.get( url , function( data ) 
                {                             
                        $( '#vmres' ).html( data );
                        reconnect();               
                });                                
        }                                          

        function poweroffvm()
        {                    
                var url = 'index_console.php?host=' + vmhostnameenc + '&vmpath=' + vmpathenc + "&vmid=" + vmid + '&poweroff=1&r=' + Math.random();

                $.get( url , function( data )
                {                            
                        $( '#vmres' ).html( data );
                        reconnect();               
                });                                
        }                                          

        function poweronvm()
        {                   
                var url = 'index_console.php?host=' + vmhostnameenc + '&vmpath=' + vmpathenc + "&vmid=" + vmid + '&poweron=1&r=' + Math.random();
                                                                                                                        
                $.get( url , function( data )                                                                           
                {                                                                                                       
                        $( '#vmres' ).html( data );                                                                     
                        reconnect();                                                                                    
                });                                                                                                     
        }                                                                                                               

        function activex()
        {                        
                loading++;       

                if( loading == 5 )
                {                 
                        var ihtml = "";

                        if( $.browser.msie )
                        {
                                ihtml += "<object id='mks' classid='CLSID:338095E4-1806-4ba3-AB51-38A3179200E9' codebase='activex/vmware-mks.cab' style='width:100%;height:100%;margin:0px;padding:0px'></object>";
                                ihtml += "<div id='vmcontrols'>";
                                ihtml += "<img src='content/images/vmopton.png' onclick='poweronvm()' style='cursor:pointer' alt='Turn on' title='Turn on'/> ";
                                ihtml += "<img src='content/images/vmoptreset.png' onclick='powerresetvm()' style='cursor:pointer' alt='Reset' title='Reset'/> ";
                                ihtml += "<img src='content/images/vmoptoff.png' onclick='poweroffvm()' style='cursor:pointer' alt='Turn off' title='Turn off'/> ";
                                ihtml += "</div>";
                                ihtml += "<div id='vmres'/>";
                        }
                        else
                        {
                                ihtml += "<br><br><br><br><br><br><br><br><br><br><br>Unsupported browser. Only IE Supports ActiveX";
                        }
                        $( "#inner" ).html( ihtml );
                }
                else if( loading == 6 )
                {
                        attachHandlers();
                }
                else if( loading == 7 )
                {
                        connect();
                }
		else if( loading > 7 )
		{
			$.fancybox.update();
		}

                if( loading < 15 )
                {
                        setTimeout( activex , 500 );
                }
        }

	function scrolltoit( it )
	{
		var container=$( "#" + it );
		$( 'html,body' ).animate(
		{
			scrollTop: container.offset().top
		});
	}

