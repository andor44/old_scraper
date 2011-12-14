<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
// let's open db first
try
{
    $dbh = new PDO("sqlite:scrape.db");
}
catch(PDOException $e)
{
    echo $e->getMessage();
    echo "Scrape DB failed to load, alart andor";
    die( ":(");
}

// some scriptwide vars
$SITE_ADDR = "http://neferty.in/~andor/chanscrape/";
$IMAGE_ENGINES = array( "regex-exif" 	=> array("name" => "EXIF",		"url" => "http://regex.info/exif.cgi?url="),
						"iqdb"			=> array("name" => "iqdb",		"url" => "http://iqdb.org/?url="),
						"google"		=> array("name" => "Google",	"url" => "http://google.com/searchbyimage?image_url="),
						"tineye"		=> array("name" => "Tineye",	"url" => "http://tineye.com/search?url="),
						"saucenao"		=> array("name" => "saucenao",	"url" => "http://saucenao.com/search.php?db=999&url=")
					);
					
if (isset($_GET['id']) || isset($_GET['random']))
{
	// sidebar
	$id_params = array();
	if(isset($_GET['random']))
	{
		$queryRes = $dbh->query("SELECT MAX(id) FROM scrape");
		$maxid = $queryRes->fetch();
		$id = rand(0, intval($maxid[0]));
		$_GET['id'] = strval($id);
	}
	$queryRes = $dbh->query("SELECT id, locnam FROM scrape WHERE id = " . $dbh->quote($_GET['id']));
	$result = $queryRes->fetch();
	$id_params['id'] = $result[0]; // for next/prev
	$id_params['locnam'] = $result[1]; // image search engines
	
	$queryRes = $dbh->query("SELECT MAX(id) FROM scrape");
	$result = $queryRes->fetch();
	$id_params['maxid'] = $result[0]; // for last
	
	// actual fetch for display
	$query = $dbh->query("SELECT nick, chan, timestamp, msg FROM scrape WHERE id = " . $dbh->quote($_GET['id']));
	$result = $query->fetch();
	$id_params['nick'] = $result[0];
	$id_params['chan'] = $result[1];
	$id_params['timestamp'] = $result[2];
	$id_params['msg'] = $result[3];
}

echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n\n";

echo "<head>\n";
echo "\t<meta http-equiv=\"content-type\" content=\"text/html; charset=iso-8859-1\" />\n";
echo "\t<title>Chanscraper</title>\n";
echo "\t<style type=\"text/css\" media=\"all\">@import \"style.css\";</style>\n";
echo "\t<script type=\"text/javascript\">\n";
echo "\t\tfunction clickimg() {\n";
echo "\t\t\t	var img = document.getElementById('img');\n";
echo "\t\t\t	if (img.style.maxWidth != '99999px') { img.style.width = 'auto'; img.style.maxWidth = '99999px'; img.style.height = 'auto'; img.style.maxHeight = '99999px'; }\n";
echo "\t\t\t	else { img.style.maxWidth = '100%'; img.style.maxHeight = '100%'; } \n";
echo "\t\t}\n";
echo "\t</script>\n";

echo "</head>\n\n";

echo "<body>\n";
echo "<div id=\"Header\">anderp chanscraper</div>\n\n";

echo "<div id=\"Menu\">\n";
echo "\t<a href=\"index.php\">Home</a></br>\n";
echo "\t<a href=\"index.php?random\">Random</a></br>\n";
echo "\t<a href=\"index.php?chans\">Channels</a></br>\n";
echo "\t<a href=\"index.php?nicks\">Nicks</a></br>\n";

if (isset($_GET['id']))
{
	echo "\t<hr style=\"border: 1px solid #000; opacity:0.35; height:0px;\" />\n";
	echo "\t<a href=\"index.php?id=1\">First</a></br>\n";
	if($id_params['id'] != "1")
		echo "\t<a href=\"index.php?id=" . strval(intval($id_params['id']) - 1)  . "\">Previous</a></br>\n";
	if($id_params['id'] != $id_params['maxid']) 
		echo "\t<a href=\"index.php?id=" . strval(intval($id_params['id']) + 1)  . "\">Next</a></br>\n";
	echo "\t<a href=\"index.php?id=" . $id_params['id'] . "\">Last</a></br>\n";
	echo "\t<hr style=\"border: 1px solid #000; opacity:0.25; height:0px; width:80%;\" />\n";
	foreach($IMAGE_ENGINES as $engine)
	{
		echo "<a href=\"" . $engine['url'] . $SITE_ADDR . $id_params['locnam'] . "\">" . $engine['name'] . "</a></br>\n"; 
	}
}

echo "\t<hr style=\"border: 1px solid #000; opacity:0.35; height:0px;\" />\n";
echo "\t<a href=\"http://desuchan.net\">Desuchan</a></br>\n";
echo "\t<a href=\"irc://irc.irchighway.net/desuchan\">#desuchan</a>\n";
echo "</div>\n\n";

echo "<div id=\"Content\">\n";

if(isset($_GET['random']) || isset($_GET['id']))
{
	echo $id_params['nick'] . " on " . $id_params['chan'] . " at " . $id_params['timestamp'] . "<p class=\"msg\">&lt;" . $id_params['nick'] . "&gt; " . $id_params['msg'] . "</p></br>\n";
	echo "<img id='img' src='" . $id_params['locnam'] . "'\ class=\"resize_w\" onclick=\"clickimg()\" />";
}
elseif(isset($_GET['nick']))
{
	$queryRes = $dbh->query("SELECT * FROM scrape WHERE nick = " . $dbh->quote($_GET['nick']));
	foreach($queryRes as $row)
	{
		echo "<p style=\"font-family:monospace\">";
		echo "<a href=\"" . "index.php?id=" . $row['id'] . "\">" .  "[" . $row['chan'] . "]" . "(" . $row['nick'] . ") " . $row['msg'] . "</a>" . "</br>";
		echo "</p>\n";
	}
}
elseif(isset($_GET['chan']))
{
	$queryRes = $dbh->query("SELECT * FROM scrape WHERE chan = " . $dbh->quote("#" . $_GET['chan']));
    foreach($queryRes as $row)
    {
        echo "<p style=\"font-family:monospace\">";
        echo "<a href=\"" . "index.php?id=" . $row['id'] . "\">" .  "[" . $row['chan'] . "]" . "(" . $row['nick'] . ") " . $row['msg'] . "</a>" . "</br>";
        echo "</p>\n";
    }
}
elseif(isset($_GET['chans']))
{
	$queryRes = $dbh->query("SELECT DISTINCT chan FROM scrape");
	foreach($queryRes as $row)
    {
        echo "<a href=\"index.php?chan=" . substr($row['chan'], 1) . "\">" . $row['chan'] . "</a></br>\n";
    }
}
elseif(isset($_GET['nicks']))
{
	$queryRes = $dbh->query("SELECT DISTINCT nick FROM scrape");
	foreach($queryRes as $row)
	{
		echo "<a href=\"index.php?nick=" . $row['nick'] . "\">" . $row['nick'] . "</a></br>\n";
	}
}
else
{
	echo "\t<h2><center>Last 50 images</h2></center></br>\n";
	$res = $dbh->query("SELECT * FROM scrape ORDER BY id DESC LIMIT 50");
	foreach($res as $row)
	{
		echo "\t<p style=\"font-family:monospace\">";
		echo "[<a href=\"index.php?chan=" . substr($row['chan'], 1) . "\">" . $row['chan'] . "</a>]" . "(<a href=\"index.php?nick=" . $row['nick'] . "\">" . $row['nick'] . "</a>)"  . "<a href=\"index.php?id=" . $row['id'] . "\">" . $row['msg'] . "</a></br>";
		echo "</p>\n";
	}
}

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
// CLOSE db connection
$dbh = NULL;
echo "<!-- koneko, chanscraper and this interface by andor uhlar (c) 2011, et al. licensed under the zlib/png license, source soon(tm) -->";
?>
