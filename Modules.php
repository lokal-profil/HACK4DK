<?php

/* -------------------------------------------------------------------------------- */
abstract class modul {
    public static $long_name;      //name of datasource
    public static $info_link;      //url to more information for datasource
    public static $data_license;   //license for the data (excluding images)
    protected static $short_name;  //The address for the api
    protected static $service_url; //The address for the api
    protected $thumb_width; //The address for the api
    public $items;          //array of replsponded items
    public static $supported_types;//array of types supported by make_query
    
    /** Construct the class, giving thumb_width in px */
    abstract public function __construct($thumb_width);

    /** Construct and return the queryurl for the given type and value
     *  Returns NULL if type isn't supported
     */
    abstract public function make_query($type, $value);
    /**
     * Valid type parameters:
     * artist
     * title
     * place (words)
     ** material        - add in phase 2
     * #Place-coordinates - removed since not all api's support this. Potentially selecting this would shade out incompatible api's
     * #Modules to include/exclude - not sent down to module level
     */

    /** 
     * Process the returned response and fill internal $items array
     * Returns NULL on success, otherwise an error message
     * parameters formated per https://github.com/lokal-profil/HACK4DK/blob/master/web/mockup/js/stageobject.js
     */
    abstract public function process_response($response);
}

/**
 * -------------------------------------------------------------------------------- 
 * Description of dataset
 */
class odok extends modul {
    protected static $short_name = 'odok'; 
    public static $long_name = 'ODOK - Public art in Sweden';
    public static $info_link = 'http://offentligkonst.se';
    protected static $service_url = 'http://wlpa.wikimedia.se/odok-bot/api.php';
    public static $data_license = NULL;
    public static $supported_types = array('artist', 'title', 'place');
    
    public function __construct($thumb_width) {//these should not be instance properties
        $this->thumb_width = $thumb_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl .= self::$service_url . '?action=get&format=json&limit=100';
        switch ($type) {
            case 'artist':
                $queryUrl .= '&artist=' . urlencode($value);
                break;
            case 'title':
                $queryUrl .= '&title=' . urlencode($value);
                break;
            case 'place':
                $queryUrl .= '&address=' . urlencode($value); //Should return a warnign that it doesn't search on district/city/municipality/county
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
        
        $totalResults = count($json['body']);
        $success = $json['head'];
        if ($totalResults < 1) {
            $this->items = Array();
            $success = $json['head']['status'];
            if ($success == 0) {
                return $json['head']['error_number'] . ": " . $json['head']['error_message'];
            }
            break;
        } else {
            $arr = Array();
            foreach ($json['body'] as $key => $value){
                $a = $value['hit'];
                if ($a['image']){
                    $media = array(
                        "mediatype" => 'image',
                        "thumb" => 'https://commons.wikimedia.org/w/thumb.php?f=' . $a['image'] . '&width=' . $this->thumb_width,
                        "medialink" => self::getImageFromCommons($a['image']),
                        "medialic" => NULL,
                        "byline" => 'See <a href="https://commons.wikimedia.org/wiki/File:' . $a['image'] .'">the image page on Wikimedia Commons</a>.'
                    );
                } else {
                    $media = array( "mediatype" => 'none');
                }
                $item = array(
                    "id" => $a['id'],
                    "title" => $a['title'],
                    "artist" => $a['artist'],//wikidata
                    "year" => $a['year'],
                    "material" => $a['material'],
                    "place" => $a['address'] . ', ' . $a['district'],
                    "geodata" => array(
                        "lat" => $a['lat'],
                        "lon" => $a['lon'],
                    ),
                    "media" => $media,
                    "text" => array(
                        "fulltext" => !$a['descr']=='' ? $a['descr'] : NULL,
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
        
    /* 
     * Given the filename on Commons this returns the url of the full image
     * From: https://fisheye.toolserver.org/browse/erfgoed/api/includes/CommonFunctions.php
     */
    private static function getImageFromCommons($filename) {
        if ($filename) {
            $filename = ucfirst($filename);
            $filename = str_replace(' ', '_', $filename);
            $md5hash=md5($filename);
            $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/' . $md5hash[0] . '/' . $md5hash[0] . $md5hash[1] . '/' . $filename;
            return $url;
        }
    }
}

/**
 * -------------------------------------------------------------------------------- 
 * Description of dataset
 */
class voreskunst extends modul {
    protected static $short_name = 'voreskunst'; 
    public static $long_name = 'Vores Kunst - Kulturstyrelsen';
    public static $info_link = 'http://vores.kunst.dk/';
    protected static $service_url = 'http://kunstpaastedet.dk/wsd/search/';
    public static $data_license = NULL;
    public static $supported_types = array('artist', 'title', 'place');
    
    public function __construct($thumb_width) {
        $this->thumb_width = $thumb_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl .= self::$service_url;
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
            break;
        } else {
            $arr = Array();
            foreach ($json[$first_key] as $a){
                $item = array(
                    "id" => $a['object_id'],
                    "title" => $a['title'],
                    "artist" => array_key_exists('artist',$a) ? $a['artist'] : NULL,
                    "year" => array_key_exists('date',$a) ? $a['date'] : NULL,
                    "material" => NULL,
                    "place" => $a['location address'] . ', ' . $a['location name'],
                    "geodata" => array(
                        "lat" => $a['latitude'],
                        "lon" => $a['longitude'],
                    ),
                    "media" => array(
                        "mediatype" => 'none' //As $a['primary_image'] points to a dispatcher
                    ),
                    "text" => array(
                        "fulltext" => array_key_exists('primary_image',$a) ? 'You can <a href="' . $a['primary_image'] . '">download an image of this artwork</a>.' : NULL,
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
