<?php
/**
 * SUPA Action Plugin: handle upload of a screenshot file
 *
 * @author Christoph Linder <post@christoph-linder.de>
 */
 
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

define( 'SUPA_UPLOAD_MIMETYPE', 'application/x-supa-screenshot' );
 
class action_plugin_supa_action extends DokuWiki_Action_Plugin {
  /**
   * Register the eventhandlers
   */
  function register(&$controller) {
    //$controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'get_applet', array ());
    $controller->register_hook('MEDIAMANAGER_STARTED', 'BEFORE', $this, 'decode_upload', array ());
    //$controller->register_hook('MEDIAMANAGER_STARTED', 'BEFORE', $this, 'inject_mimetype', array());
    $controller->register_hook('MEDIAMANAGER_CONTENT_OUTPUT', 'AFTER', $this, 'add_mediamanager_upload_region', array());
  }

  function inject_mimetype( &$event ) {
    //$mime = getMimeTypes(); // initializes mime below
    
    //print_r( $mime );
  }
  
  function add_mediamanager_upload_region( &$event ) {
    global $NS;
    $ext = 'png';
    $default_filename = "screenshot-".date("Y-m-d_H-i-s"). "." .$ext;
    echo "<!-- SUPA begin -->\n";
    echo "<script type='text/javascript'>\n";
    #echo "alert( 'loading' );";
    echo "addInitEvent(function(){\n";
    echo "  supa_handler.init(\n";
    echo "    '".addslashes(getSecurityToken())."',\n";
    echo "    '".addslashes($this->getConf("previewscaler"))."',\n";
    echo "    '".addslashes($this->getConf("previewwidth"))."',\n";
    echo "    '".addslashes($this->getConf("previewheight"))."',\n";
    echo "    '".addslashes(hsc($NS))."',\n";
    echo "    '".addslashes($default_filename)."'\n";
    echo "  );\n";
    echo "});\n";
    echo "</script>\n";
    echo "<!-- SUPA end -->\n";
    return true;
  }

  function decode_upload( &$event ) {
    //FIXME: handle flash uploads?
    $f = &$_FILES['Filedata'];
    $id = $_REQUEST['id'];

    //print_r( $f );
    if( !$f || ! preg_match( '/\.supascreenshot$/i', $f['name'] ) ) {
      return true;
    }

    // check if the max upload size has been exceeded
    if( empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
      echo $this->getLang("max_datasize_exceeded");
      die();
    }

    //print_r( $_REQUEST );
    /*
    echo "name: ".$f['name']."\n";
    echo "type: ".$f['type']."\n";
    echo "tmp_name: ".$f['tmp_name'],"\n";
    echo "error: ".$f['error']."\n";
    echo "size: ".$f['size']."\n";
    */

    #$supa = &plugin_load( 'helper', 'supa_helper' );
    $supa = &plugin_load( 'helper', 'supa_helper' );
    #$supa = $this->loadHelper( 'supa_helper', true );
    if( !$supa ) {
      echo "Error while initializing Supa plugin";
      die();
    }

    //FIXME: do not pass the $_FILES array but separate values
    $ret = $supa->decodeScreenshotFile( $f, $id ); 
    if( $ret ) {
      $_REQUEST['id'] = $id;
      $_POST['id'] = $id;
      $_GET['id'] = $id;
    } else {
      echo "Error decoding the uploaded screenshot file";
      die();
    }
    return $ret;
  }

}

