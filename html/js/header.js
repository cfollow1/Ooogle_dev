  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-49011602-1', 'iplantcollaborative.org');
  ga('send', 'pageview');







function generateHeadingBar()
{
	var heading;
	heading = "<table  class='bluebox'>"+
		"<tr>"+
		"	<td><a href='input.php' style='color:white;'>Browse Genomes</a></td>"+
		"	<td><a href='csv.php' style='color:white'>CSV Interface</a></td>"+
		"	<td><a href='recent.php' style='color:white'>Recent Searches</a></td>"+
		"</tr>"+
	"</table>"+
	"<tr>"+
	"</table>";
	
	
	var div=
	"<div> \
		<div class='bluebox mini_bluebox'><a href='index.php' style='color:white;'>Browse Genomes</a></div>\
		<div class='bluebox mini_bluebox'><a href='bulk.php' style='color:white;'>Bulk Interface</a></div>\
		<div class='bluebox mini_bluebox'><a href='recent.php' style='color:white;'>Recent Searches</a></div>\
	</div><br><br><br>\
	";

	
	
	document.write(div);
}
generateHeadingBar();
