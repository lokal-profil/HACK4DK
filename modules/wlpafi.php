<?php
/**
 * Description of dataset
 * https://toolserver.org/~erfgoed/wlpa/api.php
 */
class wlpafi extends modul {
    protected static $short_name = 'wlpafi'; 
    public static $long_name = 'WLPA - Finland';
    public static $plain_name = 'WLPA-fi';     //short but descriptive
    public static $info_link = 'http://www.wikilovespublicart.com/';
    public static $data_license = '?';
    public static $supported_types = array('title', 'place');
    protected static $service_url = 'https://toolserver.org/~erfgoed/wlpa/api.php';
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl = self::$service_url . '?action=search&format=json&limit=100&srcountry=fi&srlang=fi';
        switch ($type) {
            case 'title':
                $queryUrl .= '&srname=' . urlencode($value);
                break;
            case 'place':
                $queryUrl .= '&sraddress=' . urlencode($value); //Should return a warnign that it doesn't search on district/city/municipality/county
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
        
        $totalResults = count($json['monuments']);
        $success = $json['head'];
        if ($totalResults < 1) {
            $this->items = Array();
        } else {
            $arr = Array();
            foreach ($json['monuments'] as $a){
                //Make media separately
                if (empty($a['image'])){
                    $media = array( "mediatype" => 'none');
                } else {
                    $media = array(
                        "mediatype" => 'image',
                        "thumb" => 'https://commons.wikimedia.org/w/thumb.php?f=' . $a['image'] . '&width=' . $this->thumb_width,
                        "medialink" => self::getImageFromCommons($a['image']),
                        "medialic" => NULL,
                        "byline" => 'See <a href="https://commons.wikimedia.org/wiki/File:' . $a['image'] .'">the image page on Wikimedia Commons</a>.'
                    );
                }
                //Make place separately
                $place = '';
                if (!empty($a['address'])){
                    $place .= $a['address'];
                    if (!empty($a['municipality'])){
                        $place .= ', ' . $a['municipality'];
                    }
                } elseif (!empty($a['municipality'])){
                    $place .= $a['municipality'];
                }
                //make geodata separately
                $geodata = NULL;
                if (!empty($a['lat'])){
                    $geodata = array(
                        "lat" => $a['lat'],
                        "lon" => $a['lon'],
                    );
                }
                //Make main object
                $item = array(
                    "id" => $a['id'],
                    "title" => empty($a['name']) ? NULL : $a['name'],
                    "artist" => empty($a['creator']) ? NULL : $a['creator'], //wikidata?
                    "year" => NULL,
                    "material" => NULL,
                    "place" => empty($place) ? NULL : $place,
                    "geodata" => $geodata,
                    "media" => $media,
                    "text" => '',
                    "meta" => array(
                        "module" => self::$short_name,
                        "datalic" => self::$data_license,
                        "byline"  => '<a href="' . self::$info_link . '">' . self::$long_name . '</a> /' . self::$data_license
                    )
                );
                if (!empty($item['geodata']) or !$this->requireCoords)
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
            $url = 'https://upload.wikimedia.org/wikipedia/commons/' . $md5hash[0] . '/' . $md5hash[0] . $md5hash[1] . '/' . $filename;
            return $url;
        }
    }
}
?>
