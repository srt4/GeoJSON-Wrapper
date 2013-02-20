<?php
header("Access-Control-Allow-Origin: *");
mysql_connect('localhost', 'root', 'toor');
mysql_select_db('geo');


if (isset($_REQUEST['json'])) {
    echo getJSON($_REQUEST['zip']);
} else if (isset($_REQUEST['centroid'])) {
    echo getCentroid($_REQUEST['zip']);
} else if (isset($_REQUEST['envelope'])) {
    echo getEnvelope($_REQUEST['zip']);
}

function getEnvelope($zip)
{
    $query = "SELECT astext(envelope(SHAPE)) AS env FROM `zips` WHERE zcta5ce10 LIKE '$zip'";
    $result = mysql_query($query);

    // get row, there should only be one
    while ($row = mysql_fetch_object($result)) {
        $box = $row->env;
    }

    $box = str_replace("POLYGON", "", $box);
    $box = str_replace(")", "", $box);
    $box = str_replace("(", "", $box);
    $box = explode(",", $box);

    $jsonArray = array();

    foreach ($box as $coord) {
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
    $query = "SELECT AsText(shape) FROM zips WHERE zcta5ce10 = '$zip'";
    $result = mysql_query($query);

    if (!$result) {
        die(mysql_error());
    }

    while ($row = mysql_fetch_array($result)) {
        if (isset($_REQUEST['debug'])) {
            echo $row[0];
        }

        $row[0] = str_replace("POLYGON((", "", $row[0]);
        $row[0] = str_replace("MULTI", "", $row[0]);
        $row[0] = str_replace("))", "", $row[0]);
        $data = $row[0];
        $data = explode("),(", $data); // for multi-polys

        $jsonobj;
        $jsonobj->type = "Polygon";
        $jsonobj->coordinates = array();

        foreach ($data as $piece) {
            $piece = str_replace("(", "", $piece);
            $piece = str_replace(")", "", $piece);
            $polyArray = array();
            $piece = explode(",", $piece);

            foreach ($piece as $geoPair) {
                $geoPair = explode(" ", $geoPair);
                $polyArray[] = array(
                    0 => (double)$geoPair[0],
                    1 => (double)$geoPair[1]
                );
            }
            $jsonobj->coordinates[] = $polyArray;
        }
    }

    return json_encode($jsonobj);
}


function getCentroid($zip)
{
    $query = "SELECT AsText(Centroid(shape)) AS point FROM zips WHERE zcta5ce10 = '$zip'";
    $result = mysql_query($query);

    $point;
    while ($row = mysql_fetch_object($result)) {
        $point = $row->point;
    }
    
    $point = str_replace('POINT(', '', $point);
    $point = str_replace(')', '', $point);
    $point = explode(' ', $point);

    $point[0] = (double)$point[0];
    $point[1] = (double)$point[1];

    return json_encode(array_reverse($point));
}
