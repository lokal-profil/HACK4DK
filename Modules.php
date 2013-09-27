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
     ** material        - add in phase 2
     ** place (words)   - add in phase 2
     * #Place-coordinates - removed since not all api's support this. Potentially selecting this would shade out incompatible api's
     * #Modules to include/exclude - not sent down to module elvel
     */

    /** Process the returned reply and fill internal parameters
     *  Returns null on success, otherwise an error message
     */
    abstract public function process_reply($reply);
    /**
     * Returns an array of items where each item has following properties:
     * ID (+Module)
     * Title
     * Artist
     * Year (of purchase, construction or something)
     * Material
     * Place_wordy
     * Place_coord
     * Image_link
     * Image_license
     * License -of data
     * Free_text
     */
}


/* -------------------------------------------------------------------------------- */
class odok extends modul {
    public function __construct($image_width) {
        $this->short_name = 'odok'; 
        $this->long_name = 'ODOK - Public art in Sweden';
        $this->info_link = 'http://offentligkonst.se';
        $this->service_url = 'http://wlpa.wikimedia.se/odok-bot/api.php';
        $this->data_license = 'unknown';
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
                $image_license = NULL;
                if ($a['image']){
                    $image_license = 'See <a href="https://commons.wikimedia.org/wiki/File:' . $a['image'] .'">the image page on Wikimedia Commons</a>.';
                }
                $item = array("id" => $a['id'],
                              "title" => $a['title'],
                              "artist" => $a['artist'],//wikidata
                              "year" => $a['year'],
                              "material" => $a['material'],
                              "lat" => $a['lat'],
                              "lon" => $a['lon'],
                              "place" => $a['address'] . ", " . $a['district'] . ", " . $a['district'],
                              "image" => 'https://commons.wikimedia.org/w/thumb.php?f=' . $a['image'] . '&width=' . $this->image_width,
                              "image_license" => $image_license,
                              "free text" => $a['descr'],
                              "data_license" => $this->data_license,
                              "module" => $this->short_name
                            );
                array_push($arr, $item);
            }
            $this->items = $arr;
        }
        return; //Null
    
    }
    /**
     * Parse reply. Deal with problems. Return answer as list of items
     * ID (+Module)
     * Title
     * Artist
     * Year (of purchase, construction or something)
     * Material
     * Place_wordy
     * Place_coord (lat, lon)
     * Image_link
     * Image_license
     * License -of data
     * Free_text
     */
}

/* -------------------------------------------------------------------------------- */
class vores_kunst extends modul {
    public function __construct($image_width) {
        $this->short_name = 'vores_kunst'; 
        $this->long_name = 'Vores Kunst - Kulturstyrelsen';
        $this->info_link = 'http://vores.kunst.dk/';
        $this->service_url = 'http://kunstpaastedet.dk/wsd/search/';
        $this->data_license = 'unknown';
        $this->image_width = $image_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        /**
         * a. http://kunstpaastedet.dk/wsd/search/keyword/<SOME SEARCH STRING>
         *      EXAMPLE:  http://kunstpaastedet.dk/wsd/search/keyword/jan
         * b. http://kunstpaastedet.dk/wsd/search/artist/<SOME SEARCH STRING>
         *      EXAMPLE:  http://kunstpaastedet.dk/wsd/search/artist/Erik A. Frandsen
         * c. http://kunstpaastedet.dk/wsd/search/zipcode/<SOME SEARCH STRING>
         *      EXAMPLE:  http://kunstpaastedet.dk/wsd/search/zipcode/8900
         */
     }
    
    /** Process the returned reply and fill internal parameters */
    public function process_reply($reply) {}

}
