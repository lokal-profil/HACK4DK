<?php
include('Wrangler.php');
/**
 * Listener. If action=query
 *  make_queries($type, $value, $includedModules)
 */
if(isset($_GET['action'])){
    switch ($_GET['action']) {
        case 'query' :
            wrangler::make_queries($_GET['st'],$_GET['q'],$_GET['m']);
            break;
        case 'allModules' :
            wrangler::get_availableModules();
            break;
        case 'hasType' :
            wrangler::supports_type($_GET['st']);
            break;
        default:
            echo 'invalid action: ' . $_GET['action'];
            break;
    }
}
?>
