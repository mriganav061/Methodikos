<?php
//vim :set ts=2 sw=2 expandtab enc=utf-8
/**
 * Supa helper plugin
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Christoph Linder <post@christoph-linder.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 */
class helper_plugin_supa_helper extends DokuWiki_Plugin {

  function getMethods() {
    $result = array();
    $result[] = array(
      'name'  => 'decodeScreenshotFile',
      'desc'  => 'Decodes an uploaded screenshot file so that it is compatible to the "normal" file upload mechanism. Note: parameter &file is a ref to an entry in the $FILES superglobal!',
      'params' => array( '&file' => 'array',
               '&id' => 'string' ),
      'return' => "boolean: success or failure"
    );
    return $result;
  }

  function html_supa_applet( $ns, $auth ) {
    // this is just a stub to not make dokuwiki crash in case the
    // user forgot to remove the legacy source patch
  }

  //FIXME: do not pass the $_FILES array but separate values
  //FIXME: move this to action.php?
  function decodeScreenshotFile( &$upload_file, &$id ) {
    $filename = $upload_file['tmp_name'];

    // read entire source file
    $fh = fopen( $filename, 'r' );
    $data = fread( $fh, filesize( $filename ) );
    fclose( $fh );

    // write decoded data to destination file
    $decoded = base64_decode( $data );
    if( !$decoded ) {
      msg( "Err: ".$lang['err_decoding'], -1 );
      return false;
    }

    $fh = fopen( $filename, "w" );
    fwrite( $fh, $decoded );
    fclose( $fh );

    // change info of the uploaded file to the decoded values
    clearstatcache();
    $upload_file['type'] = 'image/png';
    $upload_file['size'] = filesize( $filename );
    //echo "sizeeeee: ".filesize( $filename );
    $upload_file['name'] = preg_replace( '/\.supascreenshot$/i', '', $upload_file['name'] );
    $id = preg_replace( '/\.supascreenshot$/i', '', $id );
    //print_r( $upload_file );
    return true;
  }

}

