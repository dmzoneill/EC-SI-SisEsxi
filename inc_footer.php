			</td>		
		</tr>	
	</table>
<?php

if( !$vmconsole )
{

?>
	<script type="text/javascript">

		$(document).ready(function() 
		{
			$( ".various" ).fancybox(
			{
				transitionIn	: 'elastic',
				transitionOut 	: 'elastic',
				speedIn		: 600, 
				speedOut	: 200, 
				fitToView	: false,
				width		: 952,
				height		: 720,
				maxWidth	: 1024,
				maxHeight 	: 768,
				autoSize	: true,
				autoCenter      : true,
				closeClick	: false,
				openEffect	: 'none',
				closeEffect	: 'none'
			});

                        $( ".edit" ).fancybox(
                        {
                                transitionIn    : 'elastic',
                                transitionOut   : 'elastic',
                                speedIn         : 600,
                                speedOut        : 200,
                                maxWidth        : 500,
                                maxHeight       : 400,
                                fitToView       : false,
                                width           : '40%',
                                height          : '50%',
                                autoSize        : true,
                                closeClick      : false,
                                openEffect      : 'none',
                                closeEffect     : 'none'
                        });

		});

		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-1014155-4']);
		_gaq.push(['_trackPageview']);

		(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
<?php

}

?>

</body>
</html>
