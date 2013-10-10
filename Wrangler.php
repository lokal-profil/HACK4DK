<?php
require('Modules.php');
class wrangler {
    /**
     * Api which passes on the js queries to various modules and returns their responses
     * Needs to:
     * read parameters from js query
     * perform http post requests
     * get basic info from modules (names, licenses, supported types etc.)
     * return json comprising of all search results
     */
    public static $availableModules;    //available modules
    public static $thumb_width=100;     //thumb_width in px
    public static $toManyResults=500;   //how many results are to many?
    
    /*
     * Loads all modules in the /modules/ directory
     * Done this way since a proper autoloder only loads the files when
     * the classes are instantiated. Therby not giving us acces to the
     * static variables
     */
    public function loadModules(){
        if (self::$availableModules == null){
            $loadedModules = array();
            foreach (glob("modules/*.php") as $filename) {
                array_push($loadedModules, substr($filename, strlen('modules/') ,-strlen('.php')));
                require $filename;
            }
            self::$availableModules = $loadedModules;
        }
    }
    
    // a list of the available modules including a longer linked name
    public function get_availableModules(){
        $available = array();
        foreach (self::$availableModules as $moduleName){
            $a = array(
                'short_name'=>$moduleName,
                'plain_name'=>$moduleName::$plain_name,
                'linked_name'=>'<a href="' . $moduleName::$info_link .'">'. $moduleName::$long_name .'</a>',
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
        $info=Null; $warning=Null; $error=Null;
        $results = array();
        foreach (self::$availableModules as $moduleName){
            if (in_array($moduleName, $includedModules)){
                $mod = new $moduleName(self::$thumb_width);
                $queryUrl = $mod->make_query($type, $value);
                $response = self::make_httpRequest($queryUrl);
                if(empty($response)){
                    $e = 'Error in make_httpRequest for ' . $queryUrl;
                    $error = empty($error) ? $e : $error.'<br/>'.$e;
                }
                $problems = $mod->process_response($response);
                if (!$problems){
                    $results = array_merge($mod->items,$results);
                } else {
                    $p = $moduleName .' : ' .$problems;
                    $info = empty($info) ? $p : $info.'<br/>'.$p;
                }
            }
        }
        if (count($results)>self::$toManyResults){
            $w = 'The search returned A LOT of results, you probably want to limit it somhow';
            $warning = empty($warning) ? $w : $warning.'<br/>'.$w;
        }
        $payload = array(
            "header" => array(
                "hits" => count($results),
                "st" => $type,
                "q" => $value,
                "m" => $includedModules,
                "info" => $info,
                "warning" => $warning,
                "error" => $error
            ),
            "body" =>$results
        );
        self::respond($payload);
    }
    
    //turns reply into json for the js
    private function respond($payload){
      $json = json_encode($payload);
        header('Content-type: text/plain');
        echo $json;
    }
    
    //http request with temporary user agent
    private function make_httpRequest($queryUrl){
        // Create a stream
        $opts = array(
          'http'=>array(
            'method'=>"GET",
            'user_agent'=>"hack4dk/1.0 (github.com/lokal-profil/HACK4DK)"
          )
        );
        
        $context = stream_context_create($opts);
        
        // Open the file using the HTTP headers set above
        if (($file = file_get_contents($queryUrl, false, $context)) === FALSE) {
            $file=NULL;
        }
        return $file;
    }
}
?>
