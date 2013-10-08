<?php
include('Wrangler.php');
$wrangler = new wrangler();
$wrangler::loadModules();
/**
 * Listener. If action=query
 *  make_queries($type, $value, $includedModules)
 */
if(isset($_GET['action'])){
    switch ($_GET['action']) {
        case 'query' :
            $wrangler::make_queries($_GET['st'],$_GET['q'],$_GET['m']);
            break;
        case 'allModules' :
            $wrangler::get_availableModules();
            break;
        case 'hasType' :
            $wrangler::supports_type($_GET['st']);
            break;
        default:
            echo 'invalid action: ' . $_GET['action'];
            break;
    }
} elseif(isset($_POST['action'])){
    switch ($_POST['action']) {
        case 'query' :
            $wrangler::make_queries($_POST['st'],$_POST['q'],$_POST['m']);
            break;
        case 'allModules' :
            $wrangler::get_availableModules();
            break;
        case 'hasType' :
            $wrangler::supports_type($_POST['st']);
            break;
        default:
            echo 'invalid action: ' . $_POST['action'];
            break;
    }
}
?>
