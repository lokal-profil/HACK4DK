<?php
/**
 * Base module
 * 
 * All implemented modules must be in the /modules directory and follow
 * the naming /modules/$class.php
 */
abstract class modul {
    protected static $short_name;   //short parseable name of the datasource
    public static $long_name;       //name of datasource
    public static $plain_name;      //short but descriptive
    public static $info_link;       //url to more information for datasource
    public static $data_license;    //license for the data (excluding images)
    public static $supported_types; //array of types supported by make_query
    protected static $service_url;  //The address for the api
    protected $thumb_width;         //The width of the requested thumbnail (if requested)
    protected $requireCoords;       //Whether only results with coordinates should be requested/returned
    public $items;                  //array of response items
    
    /** Construct the class, giving any instance properties */
    public function __construct($thumb_width, $requireCoords) {
        $this->thumb_width = $thumb_width;
        $this->requireCoords = $requireCoords;
    }

    /** 
     * Construct and return the queryurl for the given type and value
     * Returns NULL if type isn't supported
     * Valid type parameters: artist, title, place
     * Possible future types: material, coordinates...
     */
    abstract public function make_query($type, $value);

    /** 
     * Process the returned response and fill internal $items array
     * Returns NULL on success, otherwise an error message
     * parameters formated per https://github.com/lokal-profil/HACK4DK/blob/master/web/mockup/js/stageobject.js
     */
    abstract public function process_response($response);
}

?>
