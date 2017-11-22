/**
 * NamespaceManager extension
 *
 * @author     Stephan Muggli <muggli@hallowelt.com>
 * @package    Bluespice_Extensions
 * @subpackage NamespaceManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

Ext.onReady( function(){
	Ext.create( 'BS.NamespaceManager.Panel', {
		renderTo: 'bs-namespacemanager-grid'
	} );
} );