<?php
/**
 * Description of dataset
 * http://www.kulturstyrelsen.dk/kulturarv/databaser/til-udviklere/hack4dk/
 */
class voreskunst extends modul {
    protected static $short_name = 'voreskunst'; 
    public static $long_name = 'Vores Kunst - Kulturstyrelsen';
    public static $plain_name = 'Vores Kunst';     //short but descriptive
    public static $info_link = 'http://vores.kunst.dk/';
    public static $data_license = 'CC-0'; //http://www.kulturstyrelsen.dk/kulturarv/databaser/rettigheder-til-data/
    public static $supported_types = array('artist', 'title', 'place');
    protected static $service_url = 'http://kunstpaastedet.dk/wsd/search/';
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl = self::$service_url;
        switch ($type) {
            case 'artist':
                $queryUrl .= 'artist/' . urlencode($value);
                break;
            case 'title':
                $queryUrl .= 'keyword/' . urlencode($value); //Should return a warning clarifying it's a free text search. Reduce relevance
                break;
            case 'place':
                //test if zipcode
                if (strlen($value)==4 and is_numeric($value)){
                    $queryUrl .= 'zipcode/' . urlencode($value);
                } else {
                    $queryUrl .= 'keyword/' . urlencode($value); //Should return a warning clarifying it's a free text search. Reduce relevance
                }
                break;
            default:
                $queryUrl = NULL;
                break;  
        }
        return $queryUrl;
    }
    
    /** Process the returned response and fill internal parameters */
    public function process_response($response) {
        $json = json_decode($response, true);
        
        reset($json);
        $first_key = key($json);
        
        if (!is_array($json[$first_key])) {
            $this->items = Array();
            return $json[$first_key];
        } else {
            $arr = Array();
            foreach ($json[$first_key] as $a){
                //Make place separately
                $place = '';
                if (!empty($a['location address'])){
                    $place .= $a['location address'];
                    if (!empty($a['location name'])){
                        $place .= ', ' . $a['location name'];
                    }
                } elseif (!empty($a['location name'])){
                    $place .= $a['location name'];
                }
                //Make main object
                $item = array(
                    "id" => $a['object_id'],
                    "title" => empty($a['title']) ? NULL : $a['title'],
                    "artist" => empty($a['artist']) ? NULL : $a['artist'],
                    "year" => empty($a['date']) ? NULL : $a['date'],
                    "material" => NULL,
                    "place" => empty($place) ? NULL : $place,
                    "geodata" => array(
                        "lat" => $a['latitude'],
                        "lon" => $a['longitude'],
                    ),
                    "media" => array(
                        "mediatype" => 'none' //As $a['primary_image'] points to a dispatcher
                        //"medialic" => 'CC BY-NC-ND 2.5 DK'
                    ),
                    "text" => array(
                        "fulltext" => empty($a['primary_image']) ? NULL : 'You can <a href="' . $a['primary_image'] . '">download an image of this artwork</a>.',
                        "textlic" => NULL,
                        "byline" => NULL
                    ),
                    "meta" => array(
                        "module" => self::$short_name,
                        "datalic" => self::$data_license,
                        "byline"  => '<a href="' . self::$info_link . '">' . self::$long_name . '</a> /' . self::$data_license
                    )
                );
                array_push($arr, $item);
            }
            $this->items = $arr;
        }
        return; //Null
    }
}
?>
