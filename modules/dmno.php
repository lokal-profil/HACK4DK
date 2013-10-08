<?php
/**
 * Description of dataset
 * ...
 */
class dmno extends modul {
    protected static $short_name = 'dmno'; 
    public static $long_name = 'Digitalt museum';
    public static $plain_name = 'Digitalt museum';     //short but descriptive
    public static $info_link = 'http://digitaltmuseum.no';
    protected static $service_url = 'http://kulturnett2.delving.org/organizations/kulturnett/api/search';
    public static $data_license = '';
    public static $supported_types = array('artist', 'title');
    
    public function __construct($thumb_width) {//these should not be instance properties
        $this->thumb_width = $thumb_width;
    }
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl = self::$service_url . '?format=json&query=';
        switch ($type) {
            case 'artist':
                $queryUrl .= 'delving_creator:' . urlencode($value);
                break;
            case 'title':
                $queryUrl .= 'delving_title:' . urlencode($value);
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
        $totalResults = count($json['result']['items']);
        $success = $json['result'];
        if ($totalResults < 1) {
            $this->items = Array();
            $success = $json['head']['status'];
            if ($success == 0) {
                return "Unknown error";
            }
        } else {
            $arr = Array();
            foreach ($json['result']['items'] as $value){
                error_log(print_r($value, true));
                $a = $value['item']['fields']['delving_thumbnail'][0];
                //Make media separately
                if (empty($a)){
                    $media = array( "mediatype" => 'none');
                } else {
                    $media = array(
                        "mediatype" => 'image',
                        "thumb" => $a,
                        "medialink" => $a,
                        "medialic" => $value['item']['fields']['europeana_rights'][0],
                        "byline" => 'See <a href="' . $value['item']['fields']['delving_landingPage'][0] .'">the image page on ' . $value['item']['fields']['delving_provider'][0] . '</a>.'
                    );
                }


                $a = $value['item']['fields'];
        
                //Make place separately
                $place = '';
                if (!empty($a['abm_namedPlace'][0])){
                    $place .= $a['abm_namedPlace'][0];
                } 

                if (!empty($a['abm_municipality'][0])){
                    if(!empty($place)) {
                        $place .= ', ';
                    }
                $place .= $a['abm_municipality'][0];
                }

                if(!empty($a['abm_county'])) {
                    if(!empty($place)) {
                        $place .= ', ';
                    }
                    $place .= $a['abm_county'][0];
                }

                // Handle geopos
                $lat = NULL;
                $long = NULL;
                if($a['delving_hasGeoHash'][0] == 'true' && !empty($a['delving_geohash'][0])) {
                    list($lat, $long) = explode(',', $a['delving_geohash'][0]);
                }

                //Make main object
                $item = array(
                    "id" => $a['dc_identifier'],
                    "title" => empty($a['delving_title'][0]) ? NULL : $a['delving_title'][0],
                    "artist" => empty($a['delving_creator'][0]) ? NULL : $a['delving_creator'][0], //wikidata?
                    "year" => empty($a['dcterms_created'][0]) ? NULL : $a['dcterms_created'],
                    "material" => empty($a['dc_medium'][0]) ? NULL : $a['dc_medium'][0],
                    "place" => empty($place) ? NULL : $place,
                    "geodata" => array(
                        "lat" => $lat,
                        "lon" => $long,
                    ),
                    "media" => $media,
                    "text" => array(
                        "fulltext" => empty($a['delving_description'][0]) ? NULL : $a['delving_description'][0],
                        "textlic" => NULL, //as info is from Wikipedia
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
