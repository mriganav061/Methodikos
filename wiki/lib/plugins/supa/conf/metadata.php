<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the supa plugin
 *
 * @author Christoph Linder <post@christoph-linder.de>
 */

//$meta['imagecodec']      = array( 'multichoice', 
//										'_choices' => array( 'png', 'jpg' ) );
$meta['previewscaler']   = array( 'multichoice', 
										'_choices' => array( 'fit to canvas', 'original size' ) );
$meta['previewwidth']    = array( 'string' );
$meta['previewheight']   = array( 'string' );
#$meta['previewscaler'] = array('onoff');

