<?php
header("Access-Control-Allow-Origin: *");
mysql_connect('localhost','root','toor');


mysql_select_db('geo');

require('gisconverter.php');
function wkt_to_geojson ($text) {
    $decoder = new gisconverter\WKT();
    return $decoder->geomFromText($text)->toGeoJSON();
}

if (isset($_REQUEST['json']))
{
    echo getJSON($_REQUEST['zip']);
}

else if (isset($_REQUEST['centroid']))
{
    echo getCentroid($_REQUEST['zip']);
}

if (isset($_REQUEST['envelope']))
{
	echo getEnvelope($_REQUEST['zip']);
}

function getEnvelope($zip)
{
	$query = "SELECT astext(envelope(SHAPE)) as env FROM `zips` WHERE zcta5ce10 LIKE '$zip'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_object($result))
	{
		$box = $row->env;
	}
	$box = str_replace("POLYGON", "", $box);
	$box = str_replace(")", "", $box);
	$box = str_replace("(", "", $box);
	$box = explode(",", $box);
	$jsonArray = array();
	foreach($box as $coord)
	{
		$coord = explode(" ", $coord);
		$jsonArray[] = array(
			(double)$coord[1],
			(double)$coord[0]
		);
	}
	return json_encode($jsonArray);
}

function getJSON($zip)
{ 
    $query = "select AsText(shape) from zips where      zcta5ce10 = '$zip'";
    $result = mysql_query($query);
    if (!$result) { die(mysql_error()); }
    while ($row = mysql_fetch_array($result))
    {
//	    return wkt_to_geojson($row[0]);
	    if(isset($_REQUEST['debug']))
		{
			echo $row[0];
		}
	    $row[0]= str_replace("POLYGON((","",$row[0]);
	    $row[0] = str_replace("MULTI", "", $row[0]);
	    $row[0]= str_replace("))","",$row[0]);
	    $data = $row[0];
	    $data = explode("),(", $data); // for multi-polys
	    
	    $jsonobj;
	    $jsonobj->type="Polygon";
	    $jsonobj->coordinates = array();
	    
	    foreach($data as $piece)
	    {
		$piece = str_replace("(", "", $piece);
		$piece = str_replace(")", "", $piece);
	    	$polyArray = array();
		$piece = explode(",", $piece);   
		
		foreach ($piece as $geoPair)
	    	{
	    	    $geoPair = explode(" ", $geoPair);
	            $polyArray[] = array(
	                    0 => (double)$geoPair[0],
	                    1 => (double)$geoPair[1]
	            );	
		}
		$jsonobj->coordinates[] = $polyArray;
	    }
    }
    return (json_encode($jsonobj));
}


function getCentroid($zip)
{
    $query = "select AsText(Centroid(shape)) as point from zips where 	zcta5ce10 = '$zip'";
    $result = mysql_query($query);
    $point;
    while ($row = mysql_fetch_object($result))
    {
        $point = $row->point;
    }
    $point = str_replace('POINT(', '', $point);
    $point = str_replace(')', '', $point);
    $point = explode(' ', $point);
    $point[0] = (double)$point[0];
    $point[1] = (double)$point[1];
    return json_encode(array_reverse($point));
}

function mapZipCode($zip, $color)
{				
				$zipJson = getJSON($zip);
				
			?>
<script>
(function()
{
			var poly = <?=$zipJson?>;
			var PolygonArray<?=$zip?> = new Array();
			for (var i = 0; i < poly.coordinates[0].length; i++)
			{
			    PolygonArray<?=$zip?>.push(
			        new CM.LatLng(
			            poly.coordinates[0][i][1],
			            poly.coordinates[0][i][0]
		            )
	            );
           }
           var polygon<?=$zip?> = new CM.Polygon(PolygonArray<?=$zip?>, "<?=$color?>", 4, 0.7);
           map.addOverlay(polygon<?=$zip?>);          
})();
</script>
<?php    
}
