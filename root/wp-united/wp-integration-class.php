<?php

/** 
*
* WP-United -- Integration class (the bit that talks to WordPress!)
*
* @package WP-United
* @version $Id: wp-united.php,v0.9.5[phpBB2]/v 0.7.1[phpBB3] 2009/05/18 John Wells (Jhong) Exp $
* @copyright (c) 2006-2009 wp-united.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
* @author John Wells
*
*/

// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
//
//


if ( !defined('IN_PHPBB') ) exit;

/**
 * This class provides access to WordPress
 * It should be accessed as a singleton, and made global
 * It calculates and caches code pathways to invoke WordPress, which can then be evaluated in the global scope
 * 
 * It also handles modifying WordPress as necessary in order to integrate it.
 * @package WP-United Core Integration
 */
Class WPU_Integration {
 
	
	// The instructions we build in order to execute WordPress
	var $wpRun;
	
	/**
	 * This is a list of  vars phpBB also uses. We'll unset them when the class is instantiated. 
	 */
	var $phpbbVarsToUnset = array('table_prefix', 'userdata', 'search', 'error', 'author');
	
	/**
	 * More vars that phpBB or MODS could use, that MUST be unset before WP runs
	 */
	var $moreVarsToUnset = array('m', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search',
		'exact', 'sentence', 'debug', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 
		'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second',
		'name', 'category_name', 'feed', 'author_name', 'static', 'pagename', 'page_id', 'error',
		'comments_popup', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots');
		
	
	/** 
	 * A list of vars that we *know* WordPress will want in the global scope. 
	 * These are ONLY needed on pages where WP is called from within a function -- which is only on message_die pages. On such pages, most of these won't be needed anyway
	 */
	var $globalVarNames = array(
		// basic
		'wpdb', 
		'wp_db_version', 
		'wp_did_header', 
		'userdata', 
		'user_ID', 
		'current_user',
		'error',
		'errors',
		'post',
		'posts',
		'post_cache',
		'table_prefix', 
		'IN_WORDPRESS',
		'wp_version',
		'wp_taxonomies',
		'wp_object_cache',
		'options',
		
		// widgets
		'registered_sidebars', 
		'registered_widgets', 
		'registered_widget_controls', 
		'registered_widget_styles', 
		'register_widget_defaults', 
		'wp_registered_sidebars', 
		'wp_registered_widgets', 
		'wp_registered_widget_controls', 
		'wp_registered_widget_updates',
		'_wp_deprecated_widgets_callbacks',
		
		// comments
		'comment', 
		'comments', 
		'comment_type', 
		// k2
		'allowedtags', 
		'allowedposttags', 
		'k2sbm_k2_path', 
		'k2sbm_theme_path',
		'wp_version',
  		'wp_taxonomies', 
  		// inove
  		'inove_nosidebar',
  		// plugins
  		// awpcp
  		/*'awpcp_db_version',
  		'isclassifiedpage',
  		'hasregionsmodule',
  		'hascaticonsmodule',
  		'message',
  		'user_identity',
  		'imagesurl',
  		'haspoweredbyremovalmodule',
  		'clearform',
  		'hascaticonsmodule',*/

		// you could add your own here
	);
	
	/**
	 * these are set as references to objects in the global scope
	 */
	var $globalRefs = array( 
		'wp', 
		'wp_object_cache',
		'wp_rewrite', 
		'wp_the_query', 
		'wp_query',
		'wp_locale',
		'wp_widget_factory'
	);
	
	// We'll put phpBB's current variable state "on ice" in here.
	var $sleepingVars = array();
	
	var $wpu_settings;
	var $phpbb_root;
	var $phpEx;
	var $phpbb_usr_data;
	var $phpbb_db_name;
	
	var $wpVersion;
	
	// prevents the main WordPress script from being included more than once
	var $wpLoaded;
	

	// Compatibility mode?
	var $wpu_compat;
	
	var $debugBuffer;
	var $debugBufferFull;
	
	/*
	 * This class MUST be called as a singleton using this method
	 * @param array $varsToSave An array of variable names that should be saved in their current state
	 */
	function getInstance ($varsToSave = FALSE ) {
		static $instance;
		if (!isset($instance)) {
			$instance = new WPU_Integration($varsToSave);
        } 
        	return $instance;
    	}

	/**
	 * Class constructor.
	 * Takes a snapshot of phpBB variables at this point.
	 * When we exit WordPress, these can be restored.
	 */
	function WPU_Integration($varsToSave) {

		//these are constants that ain't gonna change - we're going to need them in our class
		$this->wpRun = '';
		$this->phpbb_usr_data = $GLOBALS['userdata']; 
		$this->phpEx = $GLOBALS['phpEx'];
		$this->phpbb_db_name = $GLOBALS['dbname'];
		$this->phpbb_root = $GLOBALS['phpbb_root_path'];
		$this->wpu_settings = $GLOBALS['wpSettings'];
		// store all vars set by phpBB, ready for retrieval after we exit WP
		if ($varsToSave === FALSE ) {
			$varsToSave = array(
				'table_prefix' => $GLOBALS['table_prefix'], 
				'userdata' => $GLOBALS['userdata']
			);
		}
		$this->sleepingVars = $varsToSave;
		$this->wpLoaded = FALSE;
		$this->debugBuffer = '';
		$this->debugBufferFull = FALSE;
		$this->wpVersion = 0;
		
		// Load plugin fixer -- must be loaded regardless of settings, as core cache may contain plugin fixes
		require($this->phpbb_root . 'wp-united/plugin-fixer.' . $this->phpEx);
		
		// Several library functions are required, and might not have been included if this is called directly from a phpBB function
		// (e.g. suring setup)
		require_once($this->phpbb_root . 'wp-united/wpu-helper-funcs.' . $this->phpEx);
	}
	
	/**
	 * Test connection to WordPress
	 */
	function can_connect_to_wp() {
		$test = str_replace('http://', '', $this->wpu_settings['wpPath']); // urls sometimes return true on php 5.. this makes sure they don't.
		if ( !file_exists( $test . 'wp-config.php') ) {
			// Now wp-config can be moved one level up, so we try that as well:
			if ( file_exists( $test . '../wp-config.php') ) {
				return TRUE;
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * Tests if the core cache is ready
	 */
	function core_cache_ready() {
		global $wpuCache, $wpuAbs;
	
		if ( !$wpuCache->core_cache_enabled() ){
			return false;
		}

		if ($wpuCache->use_core_cache($wpuAbs->wpu_ver, $this->wpVersion, $this->wpu_compat)) {
			return true;
		}
		return false;
	} 
		

	
	/**
	 * Prepares code for execution by adding it to the internal code store
	 */
	function prepare($wpCode) {
		$this->wpRun .= $wpCode . "\n";
	}
	
	
	/**
	 * Returns the code to be executed.
	 * eval() can be called directly on the returned string
	 */	
	function exec() {
		
		$code = $this->wpRun; 
		//echo $code;
		$this->wpRun = '';
		return $code;
	}
	
	/**
	 * Loads up the WordPress install, to just before the point that the template would be shown
	 */
	function enter_wp_integration() {

		global $wpuCache;
		//Tell phpBB that we're in WordPress. This controls the branching of the duplicate functions get_userdata and make_clickable
		$GLOBALS['IN_WORDPRESS'] = 1;
		
		// This is not strictly necessary, but it cleans up the vars we know are important to WP, or unsets variables we want to explicity get rid of.
		$to_unset=array_merge( $this->phpbbVarsToUnset, $this->moreVarsToUnset);
		foreach ( $to_unset as $varNames) {
			unset($GLOBALS[$varNames]);
		} 

		//Determine if WordPress will be running in the global scope -- in rare ocasions, such as in message_die, it won't be. 
		// This is fine - even preferable, but many third-party plugins are not prepared for this and we must hold their hands
		$this->wpu_compat = ( isset($GLOBALS['amIGlobal']) ) ? TRUE : FALSE;
		
		//Override site cookie path if set in options.php
		if ( (defined('WP_ROOT_COOKIE')) && (WP_ROOT_COOKIE) ) {
			define  ('SITECOOKIEPATH', '/');
			define  ('COOKIEPATH', '/');
			define  ('ADMIN_COOKIE_PATH', '/');
		}		


		if (!$this->wpu_compat) {
			$this->prepare('foreach ($wpUtdInt->globalVarNames as $globalVarName) global $$globalVarName;');
			$this->prepare('$beforeVars = array_keys(get_defined_vars());');
		}
		
		//finally, prepare the code for accessing WordPress:
			
		if ( !$this->wpLoaded ) {
		
			$this->wpLoaded = true;
			
			//Which version of WordPress are we about to load?
			global $wp_version;
			require($this->wpu_settings['wpPath'] . 'wp-includes/version.php');
			$this->wpVersion = $wp_version;
			
			// A few WordPress functions that we have changed - all in a separate file for easy updating.
			require($this->phpbb_root . 'wp-united/wp-functions.' . $this->phpEx);
			
			// Load widgets
			require($this->phpbb_root . 'wp-united/wpu-widgets.' . $this->phpEx);
			
			
			
			global $lang, $wpuAbs;
			// Added realpath to account for symlinks -- 
			//otherwise it is inconsistent with __FILE__ in WP, which causes plugin inconsistencies.
			$realAbsPath = realpath($this->wpu_settings['wpPath']);
			$realAbsPath = ($realAbsPath[strlen($realAbsPath)-1] == "/" ) ? $realAbsPath : $realAbsPath . "/";
			define('ABSPATH',$realAbsPath);

			if (!$this->core_cache_ready()) { 
				
				// Now wp-config can be moved one level up, so we try that as well:
				$wpConfigLoc = (!file_exists($this->wpu_settings['wpPath'] . 'wp-config.php')) ? $this->wpu_settings['wpPath'] . '../wp-config.php' : $this->wpu_settings['wpPath'] . 'wp-config.php';

				$cConf = file_get_contents($wpConfigLoc);
				$cSet = file_get_contents($this->wpu_settings['wpPath'] . 'wp-settings.php');
				//Handle the make clickable conflict
				if (file_exists($this->wpu_settings['wpPath'] . 'wp-includes/formatting.php')) {
					$fName='formatting.php';  //WP >= 2.1
				} elseif (file_exists($this->wpu_settings['wpPath'] . 'wp-includes/functions-formatting.php')) {
					$fName='functions-formatting.php';  //WP< 2.1
				} else {
					$wpuAbs->err_msg(GENERAL_ERROR, $lang['Function_Duplicate'], 'WordPress Integration Error' . WPU_SET,'','','');
				}
				$cFor = file_get_contents($this->wpu_settings['wpPath'] . "wp-includes/$fName");
				$cFor = '?'.'>'.trim(str_replace('function make_clickable', 'function wp_make_clickable', $cFor)).'<'.'?php ';
				$cSet = str_replace('require (ABSPATH . WPINC . ' . "'/$fName","$cFor // ",$cSet);	
				unset ($cFor);
				
				// Fix plugins
				if(!empty($this->wpu_settings['pluginFixes'])) {
					$strCompat = ($this->wpu_compat) ? "true" : "false";
					$cSet = str_replace('get_option(\'active_plugins\');', 'get_option(\'active_plugins\');global $wpuPluginFixer; $wpuPluginFixer = WPU_WP_Plugins::getInstance(WP_PLUGIN_DIR, \'' . $wpuAbs->wpu_ver . '\', \'' .  $this->wpVersion . '\', ' . $strCompat . ');', $cSet);
					$cSet = str_replace('include_once(WP_PLUGIN_DIR . \'/\' . $plugin);', ' include_once($wpuPluginFixer->fix(WP_PLUGIN_DIR  . \'/\' . $plugin, true));', $cSet);
					if (!$this->wpu_compat) {
						$cSet = str_replace('do_action(\'plugins_loaded\');', 'eval($wpuPluginFixer->get_globalString()); do_action(\'plugins_loaded\');', $cSet);
					}
				}
				
				if (!$this->wpu_compat) {
					// fix theme template functions!
					/**
					 * @todo Add to plugin fixes
					 */
					$cSet = str_replace('include(TEMPLATEPATH . \'/functions.php\');', '{ eval($wpUtdInt->fix_template_funcs()); include(TEMPLATEPATH . \'/functions.php\'); }', $cSet);
					$cSet = str_replace('include(STYLESHEETPATH . \'/functions.php\');', '{ eval($wpUtdInt->fix_template_funcs()); include(TEMPLATEPATH . \'/functions.php\'); }', $cSet);
				}
				
				//here we handle references to objects that need to be available in the global scope when we're not.
				if (!$this->wpu_compat) {
					foreach ( $this->globalRefs as $gloRef ) {
						$cSet = str_replace('$'. $gloRef . ' ', '$GLOBALS[\'' . $gloRef . '\'] ',$cSet);
						$cSet = str_replace('$'. $gloRef . '->', '$GLOBALS[\'' . $gloRef . '\']->',$cSet);
						$cSet = str_replace('=& $'. $gloRef . ';', '=& $GLOBALS[\'' . $gloRef . '\'];',$cSet);
					}
				}

				
				$cSet = '?'.'>'.trim($cSet).'<'.'?php ';
				$cConf = str_replace('require_once',$cSet . ' // ',$cConf);
				$this->prepare($content = '?'.'>'.trim($cConf).'<'.'?php ');
				unset ($cConf, $cSet);

				if ( $wpuCache->core_cache_enabled()) {
					$wpuCache->save_to_core_cache($content, $wpuAbs->wpu_ver, $this->wpVersion, $this->wpu_compat);
				}
			} else {
				$this->prepare('require_once(\'' . $wpuCache->coreCacheLoc . '\');');
			}
			if ( defined('WPU_PERFORM_ACTIONS') ) {
				$this->prepare($GLOBALS['wpu_add_actions']);
			}
			
			if ( !$this->wpu_compat ) {
				$this->prepare('$newVars = array_diff(array_keys(get_defined_vars()), $beforeVars);');
				$this->prepare('foreach($newVars as $newVar) { if ($newVar != \'beforeVars\') $GLOBALS[$newVar] =& $$newVar;}');
			}
			return TRUE;
		} else {
			$this->prepare('$wpUtdInt->switch_db(\'TO_W\');');
			return FALSE;
		}
		
	}
	/**
	 * Prepares the code path wrapper for integrating logins
	 */
	function integrate_login() {
		if ( !empty($this->wpu_settings['integrateLogin']) ) {
			$this->prepare('$wpUtdInt->do_integrate_login();');
		}
	}
	
	/**
	 * If we want to integrate login, this gets WordPress up to speed with the current user and their details. 
	 * If the user doesn't exist, we create them.
	 */
	function do_integrate_login() {
		if ( !empty($this->wpu_settings['integrateLogin']) ) {
			global $wpuAbs;

			$loggedInUser = '';
			$newWpUser = '';
			$phpbbRawUser = $wpuAbs->phpbb_username();
			$phpbbUserName = sanitize_user($phpbbRawUser, true);
			$integratedID = ( array_key_exists('user_wpuint_id', $wpuAbs->userdata()) ) ? $wpuAbs->userdata('user_wpuint_id') : 0 ; 
			$wpUserData = '';
			$newUserID = '';
			
			$user_level = $this->wpu_get_userlevel($wpuAbs->userdata());
	
			// Integrate only if logged in, and user level is mapped to integrate
			if (  (!$wpuAbs->user_logged_in()) || ($user_level === FALSE) || (!$wpuAbs->user_normal()) ) {
				//log them out of WP, and look needlessly suspicious
				$this->lDebug('Not logged in');
				$this->do_wp_logout();
				wp_set_current_user(0, 0);
			} else {
				$wpUserdata = get_userdata($integratedID);
				if ( empty($wpUserdata) ) {
					$integratedID = 0;
					$this->update_int_ID($wpuAbs->phpbb_user_id(), $integratedID);
				} else {
					$this->lDebug('Logged into phpBB, username=' .  $phpbbRawUser . '(' . $phpbbUserName . ',' . $integratedID . ')');
				}
				//SECTION TO CREATE INTEGRATED ACCOUNT
				if ( empty($integratedID) ) {
					// The user hasn't integrated an account yet. If they're logged in for some reason, assume it is suspicious and log them out
					$this->do_wp_logout();
					wp_set_current_user(0, 0);
					$loggedInUser = '';
					
					$this->lDebug('No WP Account Detected, Creating.');
					// No user reaching this point has an integrated account. So let's create one.
					$tryThisName = $phpbbUserName;
					
					//start with the plain username, if unavailable then append a number onto the login name until we find one that is available
					$i = 0; $foundFreeName = FALSE;
					while ( !$foundFreeName ) {
						if ( !username_exists($tryThisName) ) {
							$foundFreeName = TRUE;
						} else {
							// A username already exists. But it could belong to a different person 
							$whoIsIt = get_userdatabylogin($signUpName);
							// print_r($whoIsIt);
							if ( $whoIsIt->phpbb_userLogin == $phpbbRawUser ) {
								//uh-oh, we have a problem. Why has this integration come undone?
								// This will pop up for users integrated prior to v0.8.9.2 who haven't been converted w/ the update script... bah! too much validation...
								$wpuAbs->err_msg(GENERAL_ERROR, 'Error! Your Integration has become decoupled! Please contact an administrator and inform them of this error message.', 'WordPress Integration Error', __LINE__, __FILE__, '');
							}
							$i++; 
							$tryThisName = $phpbbUserName . $i;
						}
					}
					$signUpName = $tryThisName;
					
					$this->lDebug('Found a suitable WP username: '.$signUpName);
					
					//Now we have a unique signup name.. let's create the user.
					$newWpUser->user_login = $signUpName;
					$newWpUser->user_pass = $wpuAbs->phpbb_passwd();
					$newWpUser->user_email = $wpuAbs->phpbb_email();
					$newUserID = wp_insert_user(get_object_vars($newWpUser));
					$integratedID = $newUserID;
					$this->update_int_ID($wpuAbs->phpbb_user_id(), $integratedID);
					$this->lDebug('Created a user with ID = ' . $integratedID);
					$newUserData = get_userdata($newUserID);

					//Set usermeta options and check details consistency
					$wpUpdateData =	$this->check_details_consistency($newUserData, $wpuAbs->userdata());
					if ( $wpUpdateData ) {
						wp_update_user($wpUpdateData);
					}	
				}
											
				$loggedInUser = wp_get_current_user();
				
				
				// Check that they're not already logged into the wrong account (weird stuff happens)
				if ($loggedInUser->ID !== $integratedID && ($loggedInUser->ID)) {
					$this->lDebug('You are logged into the wrong account! (WP ID = ' . $loggedInUser->ID . ', integrated ID = ' . $integratedID . '). Logging out!');
					$this->do_wp_logout();
					wp_set_current_user(0, 0);
					$loggedInUser = wp_get_current_user();
					
				}
				
				
				//SECTION TO LOG USER IN
				if ( empty($loggedInUser->ID) ) {
					global $error;
					//user isn't logged in
					$wpUser = get_userdata($integratedID);
					$wpUserName = $wpUser->user_login;
					$this->lDebug('WP account detected, logging into account (ID=' . $integratedID . ',Username=' . $wpUserName . ')');
					//see if user can log into WP (need to double-hash password)  
					// This authentication is really unneccessary at this point.... but we need them to have a strong password in a WP cookie for Admin panel access
					

					if($this->wpSignIn($wpUserName, $wpuAbs->phpbb_passwd())) {					
						$loggedInUser = wp_set_current_user($wpUser->ID);
						$this->lDebug('Logged in successfully. Cookie set. Current user=' . $GLOBALS['current_user']->ID);
					} else {
						$this->lDebug('Could not authenticate. (' . $error .') Synchronising password.');
						// they couldn't log in... so let's just change their password
						$wpUpdateData =	$this->check_details_consistency($wpUser, $wpuAbs->userdata()); 
						if ( $wpUpdateData ) {
							wp_update_user($wpUpdateData);
						}
						//It must work now....
						$wpUser = get_userdata($integratedID);
						$wpUserName = $wpUser->user_login;
						
						if($this->wpSignIn($wpUserName, $wpuAbs->phpbb_passwd())) {
							$loggedInUser = wp_set_current_user($wpUser->ID);
							$this->lDebug('Logged in successfully. Cookie set. Current user=' . $GLOBALS['current_user']->ID);
						} else {
							//Unbelievable.... something is clearly wrong. Sound apologetic.
							
							$this->exit_wp_integration();
							$this->lDebug('Failed, aborting (' . $error .')', 1);
							$wpuAbs->err_msg(GENERAL_ERROR, 'WP-United has encountered an unknown integration error. We tried twice to log you in and it didn\'t work. Sorry! Please inform an administrator of this message', 'WordPress Integration Error', __LINE__, __FILE__, '');
						}
					}
				}
								
				if ( !empty($loggedInUser->ID) ) {
					$this->lDebug('Checking Profile Consistency');
					$userdata = $this->check_userlevels($loggedInUser->ID, $user_level);
					$userdata = get_userdata($userdata->ID);
					wp_set_current_user($userdata->ID);
					$wpUpdateData =	$this->check_details_consistency($userdata, $wpuAbs->userdata());					
					if ( $wpUpdateData ) {
						$this->lDebug('Synchronising Profiles');
						$loggedInID = wp_update_user($wpUpdateData);
						$loggedInUser = wp_set_current_user($loggedInID);
						$loggedInUser = get_userdata($loggedInUser->ID);
					}
				} else {
					//The login integration has failed. Log them out of WP just in case, and raise a stink.
					$this->do_wp_logout();
					$this->exit_wp_integration();
					$this->lDebug('Failed, aborting2', 1);
					$wpuAbs->err_msg(GENERAL_ERROR, 'Integration Error with your account! Please contact an administrator.', __LINE__, __FILE__, '');
				}
				
				if ( !($loggedInUser->ID == $integratedID) ) {
					//ID mismatch. something is heavily borked.
					$wpuAbs->err_msg(GENERAL_ERROR, 'Integration Mismatch Error with your account! Please contact an administrator.', 'WordPress Integration Error', __LINE__, __FILE__, '');
				}
				
			}
			if (!$this->debugBufferFull) $this->lDebug('',1);
		}
		
	} //end of the integration	
	

	/**
	 * Log users into WordPresss -- It's a private function, designed to be called from
	 * do_integrate_login(). It handles the various methods of logging into WP, maintaining backwards compatibility
	 */
	function wpSignIn($wpUsr, $pass) { 

		/* This overrides authentication in wp_check_password() [wp-functions.php]
		 * This is OK to set here, as phpBB has already dealt with integration.
		 * DO NOT define this anywhere else, ever!
		 */
		define('PASSWORD_ALREADY_HASHED', TRUE);	
		
		global $error;
		if ( function_exists('wp_signon') ) {
			$result = wp_signon(array('user_login' => $wpUsr, 'user_password' => $pass, 'remember' => false));
			if ( !is_wp_error($result) ) {
				return true;
			} 
			$error = $result->get_error_message();
		} else { 
			if ( wp_login($wpUsr, md5($pass), true) ) {
				wp_setcookie($wpUsr, md5($pass), true, '', '', false);
				do_action('wp_login', $wpUsr);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Generates the page.
	 * Grabs the raw WordPress page and hands it back in $toVarName for processing.
	 * Yes, this looks ugly, but it *must* be executed in the same scope as everything else... 
	 * @param string $varName the name of the variable in the global scope to fill with the page contents.
	*/
	function get_wp_page($toVarName) {
		$this->prepare('
			require(\'' . $this->phpbb_root . 'wp-united/wpu-template-funcs.' . $this->phpEx . '\');
			ob_start();
			global $latest;
			if ( $latest ) {
				define(\'WP_USE_THEMES\', false);
			} else {
				define(\'WP_USE_THEMES\', true);
			}
			global $wp_did_header;
			$wp_did_header = true;   
			wp();
			if ( !$latest ) {
				if ( !defined(\'WPU_REVERSE_INTEGRATION\') ) {
					include(\'' . $this->phpbb_root . 'wp-united/wp-template-loader.' . $this->phpEx . '\');
				}
			} else {
				include(\'' . $this->phpbb_root . 'wp-united/wpu-latest-posts.' . $this->phpEx . '\');	
			}
			$' . $toVarName . ' = ob_get_contents();
			ob_end_clean();'); 
	}

	/**
	 * Used by usercp_register.php - changes the WP username.
	 * @param string $oldName The old WordPress username
	 * @param string $newName The new WordPress username
	 */
	function wp_change_username($oldName, $newName) {
			// set the global vars we need
			foreach ($this->globalVarsStore as $varName => $varValue) {
				if ( !array_key_exists($varName, $this->globalRefs) ) {
					if ( !($varName == 'oldName') && !($varName == 'newName') ) {
						global $$varName;
						$$varName = $varValue;
					}
				}
			}	
		
			//load relevant user data
			$oldName = sanitize_user($oldName, true);
			$newName = sanitize_user($newName, true);
			$wpUserdata = get_userdatabylogin($oldName);
			if ( !empty($wpUserdata) ) {
				$wpID = $wpUserdata->ID;
				$query = "UPDATE $wpdb->users SET user_login='$newName' WHERE ID = '$wpID'";

				$wpdb->query( $query );
				wp_cache_delete($wpUserdata->ID, 'users');
				wp_cache_delete($wpUserdata->user_login, 'userlogins');
				wp_cache_delete($newName, 'userlogins');
				
				// not much else to do after this - so no need to check for new vars in this fn.
		}
	}
	
	/**
	 * Code wrapper for logging out of WordPress]
	 */
	function wp_logout() {
		$this->prepare('$wpUtdInt->do_wp_logout();');
	}

	/** 
	 * 
	 * Logs the current user out of WordPress
	 */
	function do_wp_logout() {
		if($this->wpVersion >= 2.5) {
			wp_logout();
			unset($_COOKIE[AUTH_COOKIE]);
			unset($_COOKIE[SECURE_AUTH_COOKIE]);
			unset($_COOKIE[LOGGED_IN_COOKIE]);
		} else {
			wp_clearcookie();
		}
		do_action('wp_logout');
		wp_set_current_user(0, 0);
		nocache_headers();
		unset($_COOKIE[USER_COOKIE]);
		unset($_COOKIE[PASS_COOKIE]);
	
	}
	/**
	 * Interated login debug -- adds to or displays the debugging information
	 * @param string $add_to_debug The string to add
	 * @param int $end_debug_now Make non-zero to close the debug block and make it ready for display
	 */
	function lDebug($add_to_debug, $end_debug_now = 0) {
		if ( defined('WPU_DEBUG') && (WPU_DEBUG == TRUE) ) {
			if ( empty($this->debugBuffer) ) {
				$this->debugBuffer = '<!-- wpu-debug --><div style="border: 1px solid #8f1fff; background-color: #cc99ff; padding: 3px; margin: 6px; color: #ffff99;"><strong>DEBUG</strong><br />WP Version = ' . $GLOBALS['wp_version'] . '<br />';
			}
			if ( !empty($add_to_debug) ) {
				$this->debugBuffer .= $add_to_debug . '<br />';
			}
			if ($end_debug_now) {
				global $lDebug;
				$lDebug = $this->debugBuffer . '</div><!-- /wpu-debug -->';
				$this->debugBufferFull = TRUE;				
			}

		}	
	
	}
	
	/**
	 * Exits this class, and cleans up, restoring phpBB variable state
	 */
	function exit_wp_integration() {

		//Switch back to the phpBB DB:
		$this->switch_db('TO_P');
		
		// We previously here mopped up all the WP vars that had been created... but it is a waste of CPU and usually unnecessary

		//reinstate all the phpBB variables that we've put "on ice", let them overwrite any variables that were claimed by WP.
		foreach ($this->sleepingVars as $varName => $varVal) {
			global $userdata, $table_prefix;
				if ( ($varName != 'wpuNoHead') && ($varName != 'wpuCache') ) {
					global $$varName;
					$$varName = $varVal;
				}
		}
		
		// WordPress removes $_COOKIE from $_REQUEST, which is the source of much wailing and gnashing of teeth
		$_REQUEST = array_merge($_COOKIE, $_REQUEST);
		
		$GLOBALS['IN_WORDPRESS'] = 0; //just in case
		$this->wpRun = '';
	}
	
	/**
	 * switch DB must be called *every time* whenever we want to switch between the WordPress and phpBB DB
	 * We can't just acces $db and $wpdb without doing this first.
	 * @param string $direction Set to 'TO_P' to switch to phpBB, or 'TO_W' to switch to WordPress.
	 */
	function switch_db ($direction = 'TO_P') {
		//global $wpdb;
		//we originally used $wpdb->select here, but it doesn't seem to work in all circumstances
		if ( ($this->wpLoaded) && (!$this->phpbb_db_name != DB_NAME) ) {
			switch ( $direction ) {
				case 'TO_P':
					mysql_select_db($this->phpbb_db_name);
					break;
				case 'TO_W':
				default;
					mysql_select_db(DB_NAME);
					break;
			}
		}
	}
	
	/**
	 *  Fixes common 'global assumption' in functions.php in templates that results in fatal error
	 */
	function fix_template_funcs() {
		$return_exec = '';
		if (!$this->wpu_compat) {
			$fnCont = file_get_contents(TEMPLATEPATH . '/functions.php');
			if ($fnCont = strstr($fnCont, 'themetoolkit(')) {
				$fnCont = explode("'", $fnCont);
				if (isset($fnCont[1])) {
					$fnCont = strip_tags(trim($fnCont[1]));
					if ( (strpos($fnCont, ' ') === FALSE) && (strpos($fnCont, ';') === FALSE) && (strpos($fnCont, "\n") === FALSE) && (strpos($fnCont, "\r") === FALSE) && (strpos($fnCont, "'") === FALSE) ) {
						// Ensure that we have not matched more than a single word, as that would be a security risk
						return 'global $' . $fnCont . ';';
					} 
				}
			}
		
		}
	}
	
	/**
	 * Updates the Integration ID stored in phpBB profile
	 */
	function update_int_ID($pID, $intID) {
		global $db, $wpuAbs, $cache;

		//Do we need to update the integration ID?
		if ( !empty($intID) ) { 
			//Switch back to the phpBB DB:
			$this->switch_db('TO_P');
			$updated = FALSE;
			if ( !empty($pID) ) { 
				$sql = 'UPDATE ' . USERS_TABLE . " 
					SET user_wpuint_id = $intID 
					WHERE user_id = '$pID'";
				if(!$result = $db->sql_query($sql)) {
					$wpuAbs->err_msg(CRITICAL_ERROR, $sql . ' WP-United could not update your integration ID in phpBB, due to a database access error. Please contact an administrator and inform them of this error.', 'Database access error', __LINE__, __FILE__, $sql);
				} else {
					$updated = TRUE;
				}
			}
			//Switch back to the WP DB:
			$this->switch_db('TO_W');
			if ( !$updated ) {
				$wpuAbs->err_msg(CRITICAL_ERROR, 'WP-United could not update your integration ID in phpBB, due to an unknown error. Please contact an administrator and inform them of this error.', 'Could not update integration data', __LINE__, __FILE__, '');
			}
		}
	}	
	
	/**
	 * Gets the logged-in user's level so we can arbitrate permissions
	 */
	function wpu_get_userlevel($phpbb_userdata) {

		global $db, $wpuAbs;
		
		$user_level = FALSE;
		
		if ( (!$wpuAbs->user_logged_in()) || (!$wpuAbs->user_normal()) ) {
			return FALSE;
		}
		
		global $auth, $user;
		$auth->acl($user->data);
		$debug = 'Checking permissions: ';
		if ( $auth->acl_get('u_wpu_subscriber') ) {
			$user_level = 'subscriber'; 
			$debug .= '[' . $user_level . ']';
		}
		if ( $auth->acl_get('u_wpu_contributor') ) {
			$user_level = 'contributor'; 
			$debug .= '[' . $user_level . ']';
		}
		if ( $auth->acl_get('u_wpu_author') ) {
			$user_level = 'author'; 
			$debug .= '[' . $user_level . ']';
		}
		if ( $auth->acl_get('m_wpu_editor') ) {
			$user_level = 'editor'; 
			$debug .= '[' . $user_level . ']';
		}
		if ( $auth->acl_get('a_wpu_administrator') ) {
			$user_level = 'administrator'; 
			$debug .= '[' . $user_level . ']';
		}			
		$this->lDebug($debug);
		$this->lDebug('User level set to: ' . $user_level);

		return $user_level;
	}
	
	
	/**
	 * Arbitrates the user permissions between phpBB and WordPress. Called internally by integrate_login
	 * @param int $ID WordPress ID
	 * @param the required WordPress role
	 */
	function check_userlevels ($ID, $usrLevel) {
		global $userdata;
		$user = new WP_User($ID);
		$user->set_role($usrLevel);
		$userdata = get_userdata($ID);
		wp_set_current_user($ID);
		return $userdata;
	}
	
	/**
	 * Arbitrates the user details - e-mail, password, website, aim, yim, between phpBB & WordPress -- called internally by integrate_login
	 * Basically, just overwrites WP values with the current phpBB values.
	 * We try to update these whenever they are changed, but that's not always the case, so for now we also do this on each access.
	 *	@param mixed $wpData WordPress user data
	 * @param mixed $pData phpBB user data
	 * @return mixed array of fields to update
	 */
	function check_details_consistency($wpData, $pData) {	

		global $wpuAbs;
	
		if ( !empty($wpData->ID) ) {
			$wpMeta = get_usermeta($wpData->ID);
		}
		// we use this so we can direct to their phpBB profile without faffing around
		if ($pData['user_id'] != $wpData->phpbb_userid) {
			update_usermeta( $wpData->ID, 'phpbb_userid', $pData['user_id']);
		}
		// We only update the user's nicename -- they're stuck with their username.
		if ( (!($pData['username'] == $wpData->user_nicename)) &&  (!empty($pData['username'])) ) {
			update_usermeta( $wpData->ID, 'phpbb_userLogin', $pData['username']);
			$update['user_nicename'] = $pData['username'];
			$doWpUpdate = true;
		}
	
		$doWpUpdate = false;
		if ( (!($pData['user_email'] == $wpData->user_email)) && (!empty($pData['user_email'])) ) {
			$update['user_email'] = $pData['user_email'];
			$doWpUpdate = true;
		} 
		
		// Store our password in a WordPress compatible format
		if(substr($pData['user_password'], 0, 3) == '$H$') {
			$pData['user_password'] = substr_replace($pData['user_password'], '$P$', 0, 3);
		}
		
		if ( (!($pData['user_password'] == $wpData->user_pass)) && (!empty($pData['user_password'])) ) {
			$update['user_pass'] = $pData['user_password']; 
			$doWpUpdate = true;
		}
		if ( (!($pData['user_website'] == $wpData->user_url)) && (!empty($pData['user_website'])) ) {
			$update['user_url'] = $pData['user_website'];
			$doWpUpdate = true;
		}
		if ( (!($pData['user_aim'] == $wpData->aim)) && (!empty($pData['user_aim'])) ) {
			$update['aim'] = $pData['user_aim'];
			$doWpUpdate = true;
		}
		if ( (!($pData['user_yim'] == $wpData->yim)) && (!empty($pData['user_yim'])) ) {
			$update['yim'] = $pData['user_yim'];
			$doWpUpdate = true;
		}
		if ( (!($pData['user_jabber'] == $wpData->jabber)) && (!empty($pData['user_jabber'])) && ($wpuAbs->ver == 'PHPBB3') ) {
			$update['jabber'] = $pData['user_jabber'];
			$doWpUpdate = true;
		}		
		if ( (!($pData['user_avatar_type'] == $wpMeta['wpu_avatar_type'])) && (!empty($pData['user_avatar_type'])) ) {
			if ( !empty($wpData->ID) ) {
				update_usermeta( $wpData->ID, 'wpu_avatar_type', $pData['user_avatar_type']);
			}
		}
		if ( (!($pData['user_avatar'] == $wpMeta['wpu_avatar'])) && (!empty($pData['user_avatar'])) ) {
			if ( !empty($wpData->ID) ) {
				update_usermeta( $wpData->ID, 'wpu_avatar', $pData['user_avatar']);
			}
		}
		if ( (!($pData['user_allowavatar'] == $wpMeta['wpu_allowavatar'])) && (!empty($pData['user_allowavatar'])) && ($wpuAbs->ver == 'PHPBB2') ) {
			if ( !empty($wpData->ID) ) {
				update_usermeta( $wpData->ID, 'wpu_allowavatar', $pData['user_allowavatar']);
			}
		}
		if ( (!($pData['user_avatar_width'] == $wpMeta['wpu_avatar_width'])) && (!empty($pData['user_avatar_width'])) && ($wpuAbs->ver == 'PHPBB3') ) {
			if ( !empty($wpData->ID) ) {
				update_usermeta( $wpData->ID, 'wpu_avatar_width', $pData['user_avatar_width']);
			}
		}	
		if ( (!($pData['user_avatar_height'] == $wpMeta['wpu_avatar_height'])) && (!empty($pData['user_avatar_height'])) && ($wpuAbs->ver == 'PHPBB3') ) {
			if ( !empty($wpData->ID) ) {
				update_usermeta( $wpData->ID, 'wpu_avatar_height', $pData['user_avatar_height']);
			}
		}								
		if ( $doWpUpdate ) {
			if ( !empty($wpData->ID) ) {
				$update['ID'] = $wpData->ID;
			}
			return $update;
		} else {
			return false;
		}	
	}	
	
	
}
?>
