/**
 * NamespaceManager extension
 *
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    BlueSpiceNamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

Ext.onReady( function(){
	Ext.create( 'BS.BlueSpiceNamespaceManager.Panel', {
		renderTo: 'bs-namespacemanager-grid'
	} );
} );