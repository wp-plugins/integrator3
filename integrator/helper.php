<?php
/**
 * Integrator
 * 
 * @package    Integrator 3.0 - Wordpress Package
 * @copyright  2009 - 2012 Go Higher Information Services.  All rights reserved.
 * @license    ${p.PROJECT_LICENSE}
 * @version    3.0.6 ( $Id: helper.php 74 2012-10-01 15:55:25Z steven_gohigher $ )
 * @author     Go Higher Information Services
 * @since      3.0.0
 * 
 * @desc       This is a helper file for the Integrator
 *  
 */

/*-- Security Protocols --*/
//defined('_JEXEC') or exit('No direct script access allowed');
/*-- Security Protocols --*/

/*-- File Inclusions --*/
//jimport( 'joomla.application.component.helper' );
//require_once( "class.curl.php" );
/*-- File Inclusions --*/

/**
 * IntHelper class object
 * @version		3.0.6
 * 
 * @since		3.0.0
 * @author		Steven
 */
class IntHelper
{
	/**
	 * Finds a user and returns a single array
	 * @access		public
	 * @version		3.0.6
	 * @param		string		- $find: either an email or username
	 * 
	 * @return		array of data
	 * @since		3.0.0
	 */
	public function find_user( $find )
	{
		$data	= array();
		
		$qry = new WP_User_Query( array( 'fields' => 'all', 'search' => $find, 'limit' => 1 ) );
		$results	= $qry->get_results();
		
		if ( empty( $results ) ) return false;
		$data	= (array) $results[0];
		
		// Password is encrypted, so no sense in sending it back
		unset( $data['user_pass'] );
		
		$metas	= get_user_metavalues( array( $data['ID'] ) );
		
		foreach ( $metas[$data['ID']] as $meta ) {
			$data[$meta->meta_key] = $meta->meta_value;
		}
		
		return $data;
	}
	
	
	/**
	 * Creates a quick form redirection to send back to the Integrator securely
	 * @access		public
	 * @version		3.0.6
	 * @param		string		- $url: the form action to send to
	 * @param		array		- $fields: hidden fields to send
	 * 
	 * @since		3.0.0
	 */
	public function form_redirect( $url = null, $fields = array() )
	{
		$field = null;
		foreach ( $fields as $name => $value ) {
			$field .= "<input type='hidden' name='{$name}' value='{$value}' />";
		}
		
		$output = <<< OUTPUT
<form action="{$url}" method="post" name="frmlogin" id="frmlogin">
		{$field}
</form>
<script language="javascript"><!--
setTimeout ( "autoForward()", 0 );
function autoForward() {
	document.forms['frmlogin'].submit()
}
//--></script>
OUTPUT;
			exit ( $output );
	}
	
	
	/**
	 * Checks a username to see if it is actually an email address
	 * @access		public
	 * @version		3.0.6
	 * @param		string		- $username: the suspect username
	 * 
	 * @return		boolean true if email
	 * @since		3.0.0
	 */
	public function is_email( $username )
	{
		$pattern = "/\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,5}\b/i";
		$match = preg_match( $pattern, $username );
		
		return ( $match > 0 );
	}
	
	
	/**
	 * Builds a recursive tree from an array of menu items
	 * @access		public
	 * @version		3.0.6
	 * @param		integer		- $id: the parent to start from
	 * @param		string		- $indent: any indent value passed on
	 * @param		array		- $list: can be empty if parent or an array of parent items
	 * @param		array		- $children: an array of menu objects
	 * @param		integer		- $maxlevel: the furthest we will go deep
	 * @param		integer		- $level: the level we are starting from
	 * @param		integer		- $type: allows for different types of menus to be built
	 * 
	 * @return 		array containing recursive menu tree
	 * @since		3.0.0
	 */
	public function tree_recurse( $id, $indent, $list, &$children, $maxlevel=9999, $level=0, $type=1 )
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $v)
			{
				$id = $v->ID;
	
				$list[$id] = $v;
				$list[$id]->children = count( @$children[$id] );
				$list[$id]->pid		= $v->object == 'intlink' ? $v->object_id : -1;
				$list = IntHelper::tree_recurse( $id, $indent, $list, $children, $maxlevel, $level+1, $type );
			}
		}
		
		return $list;
	}
	
	
	/**
	 * Searches for a user and returns all matches
	 * @access		public
	 * @version		3.0.6
	 * @param		string		- $search
	 * 
	 * @return		array or false on failure
	 * @since		3.0.1 (0.1)
	 */
	public function user_search( $search )
	{
		$data	= array();
		
		$qry = new WP_User_Query( array( 'fields' => 'all_with_meta', 'search' => $search ) );
		$results	= $qry->get_results();
		
		if ( empty( $results ) ) return false;
		
		foreach ( $results as $result ) {
			
			$temp	= get_userdata( $result->ID );
			//$data[]	= $temp->data;
			//$data[]	= $temp;
			// Change in WP 3.2
			$data[]	= array( 'first_name' => $temp->first_name, 'last_name' => $temp->last_name, 'user_login' => $temp->user_login, 'user_email' => $temp->user_email, 'user_pass' => $temp->user_pass );
		}
		$data[]	= $search;
		return $data;
	}
}