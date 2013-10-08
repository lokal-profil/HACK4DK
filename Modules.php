<?php
/**
 * Base module
 * For implemented modules see the /modules directory
 */
abstract class modul {
    protected static $short_name;   //short parseable name of the datasource
    public static $long_name;       //name of datasource
    public static $plain_name;      //short but descriptive
    public static $info_link;       //url to more information for datasource
    public static $data_license;    //license for the data (excluding images)
    protected static $service_url;  //The address for the api
    protected $thumb_width;         //The width of the requested thumbnail (if requested)
    public $items;                  //array of response items
    public static $supported_types; //array of types supported by make_query
    
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

?>
