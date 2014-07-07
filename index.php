<?php

include( "inc_header.php" );

#print "<h1>ESSV</h1><br>";
quickList( "ESX-Server.Infra" );

#print "<br><h1>Production</h1><br>";
quickList( "ESX-Server,ESX-Server.Standalone" );

#print "<br><h1 style='margin-left:30px'>Lab</h1><br>";
quickList( "ESX-Server,ESX-Server.Standalone,Lab-Server,Lab-Equipment" );

#print "<br><h1>ILab</h1><br>";
quickList( "ESX-Server,ESX-Server.ilab,Lab-Server,Lab-Equipment" );

include( "inc_footer.php" );
