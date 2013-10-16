<?php
/**
 * Description of dataset
 * https://se.wikimedia.org/wiki/Projekt:ODOK
 */
class odok extends modul {
    protected static $short_name = 'odok'; 
    public static $long_name = 'ÖDOK - Public art in Sweden';
    public static $plain_name = 'ÖDOK';     //short but descriptive
    public static $info_link = 'http://offentligkonst.se';
    public static $data_license = 'ODbL';
    public static $supported_types = array('artist', 'title', 'place');
    protected static $service_url = 'http://wlpa.wikimedia.se/odok-bot/api.php';
    
    /** Construct and return the query */
    public function make_query($type, $value) {
        $queryUrl = self::$service_url . '?action=get&format=json&limit=100';
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
        } else {
            $arr = Array();
            foreach ($json['body'] as $key => $value){
                $a = $value['hit'];
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
                    if (!empty($a['district'])){
                        $place .= ', ' . $a['district'];
                    }
                } elseif (!empty($a['district'])){
                    $place .= $a['district'];
                }
                //Make main object
                $item = array(
                    "id" => $a['id'],
                    "title" => empty($a['title']) ? NULL : $a['title'],
                    "artist" => empty($a['artist']) ? NULL : $a['artist'], //wikidata?
                    "year" => empty($a['year']) ? NULL : $a['year'],
                    "material" => empty($a['material']) ? NULL : $a['material'],
                    "place" => empty($place) ? NULL : $place,
                    "geodata" => array(
                        "lat" => $a['lat'],
                        "lon" => $a['lon'],
                    ),
                    "media" => $media,
                    "text" => array(
                        "fulltext" => empty($a['descr']) ? NULL : $a['descr'],
                        "textlic" => 'CC BY-SA 3.0', //as info is from Wikipedia
                        "byline" => 'Description from <a href="https://sv.wikipedia.org/">Wikipedia</a> / <a href="https://creativecommons.org/licenses/by-sa/3.0/deed.en">CC BY-SA 3.0</a>.'
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
            $url = 'https://upload.wikimedia.org/wikipedia/commons/' . $md5hash[0] . '/' . $md5hash[0] . $md5hash[1] . '/' . $filename;
            return $url;
        }
    }
}
?>
