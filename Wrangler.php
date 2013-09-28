<?php
include('Modules.php');
class wrangler {
    /**
     * Api which passes on the js queries to various modules and returns their responses
     * Needs to:
     * read parameters from js query
     * perform http post requests
     * get basic info from modules (names, licenses, supported types etc.
     * return json comprising of all search results
     */
    public static $availableModules = array( //the only place where you need to add new modules
        'odok',
        'voreskunst',
    );
    public static $thumb_width=100; //thumb_width
    
    // a list of the available modules including a longer linked name
    public function get_availableModules(){
        $available = array();
        foreach (self::$availableModules as $moduleName){
            $a = array(
                'short_name'=>$moduleName,
                'long_name'=>'<a href="' . $moduleName::$info_link .'">'. $moduleName::$long_name .'</a>',
            );
            array_push($available, $a);
        }
        self::respond($available);
    }
    
    //Returns an array of modules supporting a certain type
    public function supports_type($type){
        $supported = array();
        foreach (self::$availableModules as $moduleName){
            if (in_array($type,$moduleName::$supported_types)){
                array_push($supported, $moduleName);
            }
        }
        self::respond($supported);
    }
    
    //given type, value and modules to be included this collects all responses
    public function make_queries($type, $value, $includedModules){
        $includedModules = is_null($includedModules) ? self::$availableModules : $includedModules;
        $results = array();
        foreach (self::$availableModules as $moduleName){
            if (in_array($moduleName, $includedModules)){
                $mod = new $moduleName(self::$thumb_width);
                $queryUrl = $mod->make_query($type, $value);
                $response = self::make_httpRequest($queryUrl);
                $problems = $mod->process_response($response);
                if (!$problems){
                    $results = array_merge($mod->items,$results);
                } else {
                    echo $problems;
                }
            }
        }
        self::respond($results);
    }
    
    //turns reply into json for the js
    private function respond($payload){
        $json = json_encode($payload);
        echo $json;
    }
    
    //quick httpRequest - extend
    private function make_httpRequest($queryUrl){
        if (($file = file_get_contents($queryUrl)) === FALSE) {
            echo('error in make_httpRequest for ' . $queryUrl);
            $file=NULL;
        }
        return $file;
    }

}
?>
