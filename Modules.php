<?php

/* -------------------------------------------------------------------------------- */
abstract class modul {
    public $long_name;      //name of datasource
    public $info_link;      //url to more information for datasource
    public $data_license;   //license for the data (excluding images)
    protected $short_name;  //The address for the api
    protected $service_url; //The address for the api
    protected $image_width; //The address for the api
    public $items;       //array of replied items
    
    /** Construct the class, giving imagewidth in px */
    abstract public function __construct($image_width);

    /** Construct and return the queryurl for the given type and value
     *  Returns false if type isn't supported
     */
    abstract public function make_query($type, $value);
    /**
     * Valid type parameters:
     * artist
     * title
     * place (words)
     ** material        - add in phase 2
     * #Place-coordinates - removed since not all api's support this. Potentially selecting this would shade out incompatible api's
     * #Modules to include/exclude - not sent down to module elvel
     */

    /** Process the returned reply and fill internal parameters
     *  Returns null on success, otherwise an error message
     */
    abstract public function process_reply($reply);
    /**
     * parameters formated per https://github.com/lokal-profil/HACK4DK/blob/master/web/mockup/js/stageobject.js
     */
}


/* -------------------------------------------------------------------------------- */
class odok extends modul {
    public function __construct($image_width) {
        $this->short_name = 'odok'; 
        $this->long_name = 'ODOK - Public art in Sweden';
        $this->info_link = 'http://offentligkonst.se';
        $this->service_url = 'http://wlpa.wikimedia.se/odok-bot/api.php';
        $this->data_license = NULL;
        $this->image_width = $image_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl .= $this->service_url . '?action=get&format=json&limit=100';
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
                $queryUrl = False;
                break;  
        }
        return $queryUrl;
    }
    
    /** Process the returned reply and fill internal parameters */
    public function process_reply($reply) {
        $json = json_decode($reply, true);
        
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
                        "thumb" => 'https://commons.wikimedia.org/w/thumb.php?f=' . $a['image'] . '&width=' . $this->image_width,
                        "medialink" => self::getImageFromCommons($a['image']),
                        "medialic" => NULL,
                        "byline" => 'See <a href="https://commons.wikimedia.org/wiki/File:' . $a['image'] .'">the image page on Wikimedia Commons</a>.'
                    );
                } else {
                    $media = array( "mediatype" => 'none');
                }
                $item = array("id" => $a['id'],
                              "title" => $a['title'],
                              "artist" => $a['artist'],//wikidata
                              "year" => $a['year'],
                              "material" => $a['material'],
                              "place" => $a['address'] . ", " . $a['district'] . ", " . $a['district'],
                              "geodata" => array(
                                  "lat" => $a['lat'],
                                  "lon" => $a['lon'],
                              ),
                              "media" => $media,
                              "text" => array(
                                  "fulltext" => $a['descr'],
                                  "textlic" => NULL,
                                  "byline" => NULL
                              ),
                              "meta" => array(
                                  "module" => $this->short_name,
                                  "datalic" => $this->data_license,
                                  "byline"  => '<a href="' . $this->info_link . '">' . $this->long_name . '</a> /' . $this->data_license
                              )
                            );
                array_push($arr, $item);
            }
            $this->items = $arr;
        }
        return; //Null
        
        /* 
         * Given the filename on Commons this returns the url of the full image
         * From: https://fisheye.toolserver.org/browse/erfgoed/api/includes/CommonFunctions.php
         */
        function getImageFromCommons($filename) {
            if ($filename) {
                $filename = ucfirst($filename);
                $filename = str_replace(' ', '_', $filename);
                $md5hash=md5($filename);
                $url = "https://upload.wikimedia.org/wikipedia/commons/thumb/" . $md5hash[0] . "/" . $md5hash[0] . $md5hash[1] . "/" . $filename;
                return $url;
            }
        }
    }
}

/* -------------------------------------------------------------------------------- */
class vores_kunst extends modul {
    public function __construct($image_width) {
        $this->short_name = 'vores_kunst'; 
        $this->long_name = 'Vores Kunst - Kulturstyrelsen';
        $this->info_link = 'http://vores.kunst.dk/';
        $this->service_url = 'http://kunstpaastedet.dk/wsd/search/';
        $this->data_license = NULL;
        $this->image_width = $image_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl .= $this->service_url;
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
                $queryUrl = False;
                break;  
        }
    }
    
    /** Process the returned reply and fill internal parameters */
    public function process_reply($reply) {
        $json = json_decode($reply, true);
        
        reset($json);
        $first_key = key($json);
        
        if (!is_array($json[$first_key])) {
            $this->items = Array();
            return $json[$first_key];
            break;
        } else {
            $arr = Array();
            foreach ($json[$first_key] as $value){
                $a = $value['hit'];
                $image_license = NULL;
                if ($a['image']){
                    $image_license = 'See <a href="https://commons.wikimedia.org/wiki/File:' . $a['image'] .'">the image page on Wikimedia Commons</a>.';
                }
                $item = array(); //...
                array_push($arr, $item);
            }
            $this->items = $arr;
        }
        return; //Null
    }

}
