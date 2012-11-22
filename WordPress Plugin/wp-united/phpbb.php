<?php
/** 
*
* @package WP-United
* @version $Id: v0.8.5RC2 2010/02/06 John Wells (Jhong) Exp $
* @copyright (c) 2006-2010 wp-united.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License  
* @author John Wells
*
* phpBB status abstraction layer
* When in WordPress, we often want to switch between phpBB & WordPress functions
* By accessing through this class, it ensures that things are done cleanly.
* This will eventually replace much of the awkward variable swapping that wp-integration-class is
* doing.
*/

/**
 */
if ( !defined('ABSPATH') && !defined('IN_PHPBB') ) exit;

/**
 * phpBB abstraction class -- a neat way to access phpBB from WordPress
 * 
 */
class WPU_Phpbb {


	private 
		$wpTablePrefix,
		$wpUser,
		$wpCache,
		$phpbbTablePrefix,
		$phpbbUser,
		$phpbbCache,
		$phpbbDbName,
		$phpbbTemplate,
		$wpTemplate,
		$state,
		$was_out,
		$_savedID,
		$_savedIP,
		$_savedAuth,
		$tokens,
		$_loaded;
	
	public 
		$lang,
		$seo,
		$url,
		$_transitioned_user;		
	
	/**
	 * Class initialisation
	 */
	public function __construct() {
		if(defined('IN_PHPBB')) { 
			$this->lang = $GLOBALS['user']->lang;
			$this->calculate_url();
			$this->state = 'phpbb';
			$this->phpbbTemplate = $GLOBALS['template'];
			$this->phpbbTablePrefix = $GLOBALS['table_prefix'];
			$this->phpbbUser = $GLOBALS['user'];
			$this->phpbbCache = $GLOBALS['cache'];
		}
		$this->tokens = array();
		$this->was_out = false;
		$this->seo = false;
		
		$this->_transitioned_user = false;
		$this->_savedID = -1;
		$this->_savedIP = '';
		$this->_savedAuth = NULL;
		
		$this->_loaded = false;
		
	}	
	
	public function is_phpbb_loaded() {
		return $this->_loaded;
	}
	
	public function can_connect_to_phpbb() {
		global $wpUnited;
		
		$rootPath = $wpUnited->get_setting('phpbb_path');
		
		if(!$rootPath) {
			return false;
		}
		
		static $canConnect = false;
		static $triedToConnect = false;
		
		if($triedToConnect) {
			return $canConnect;
		}
		
		 $canConnect = @file_exists($rootPath);
		 $triedToConnect = true;
		 
		 
		 return $canConnect;
		
	}
	
	/**
	 * Loads the phpBB environment if it is not already
	 */
	public function load() {
		global $phpbb_hook, $phpbb_root_path, $phpEx, $IN_WORDPRESS, $db, $table_prefix, $wp_table_prefix, $wpUnited;
		global $dbms, $auth, $user, $cache, $cache_old, $user_old, $config, $template, $dbname, $SID, $_SID;
		
		if($this->is_phpbb_loaded()) {
			return;
		}
		$this->_loaded = true;
			
		$this->backup_wp_conflicts();
		

		if ( !defined('IN_PHPBB') ) {
			$phpEx = substr(strrchr(__FILE__, '.'), 1);
			define('IN_PHPBB', true);
		}
		
		$phpbb_root_path = $wpUnited->get_setting('phpbb_path');
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		
		$this->make_phpbb_env();
		
		if(!$this->can_connect_to_phpbb()) {
			$wpUnited->disable_connection('error'); 
			die();
		}
		require_once($phpbb_root_path . 'common.' . $phpEx);
		
		// various tests for success:
		if(!isset($user)) {
			$wpUnited->disable_connection('error');
		}
		
		if(!is_object($user)) {
			$wpUnited->disable_connection('error');
		}
		
		
		
		$this->calculate_url();
		
		// phpBB's deregister_globals is unsetting $template if it is also set as a WP post var
		// so we just set it global here
		$GLOBALS['template'] = &$template;

		$user->session_begin();
		$auth->acl($user->data);

		if(!is_admin()) {
			if ($config['board_disable'] && !defined('IN_LOGIN') && !$auth->acl_gets('a_', 'm_') && !$auth->acl_getf_global('m_')) {
				// board is disabled. 
				$user->add_lang('common');
				define('WPU_BOARD_DISABLED', (!empty($config['board_disable_msg'])) ? '<strong>' . $user->lang['BOARD_DISABLED'] . '</strong><br /><br />' . $config['board_disable_msg'] : $user->lang['BOARD_DISABLE']);
			} else {
				if(($wpUnited->get_setting('showHdrFtr') == 'FWD') && (defined('WPU_INTEG_DEFAULT_STYLE') && WPU_INTEG_DEFAULT_STYLE)) {
					// This option forces the default phpBB style in a forward integration
					$user->setup('mods/wp-united', $config['default_style']);
				} else {
					$user->setup('mods/wp-united');
				}
			}
		} else {	
			$user->setup('mods/wp-united');
		}
		
		if(defined('WPU_BLOG_PAGE') && !defined('WPU_HOOK_ACTIVE')) {
			$cache->purge();
			trigger_error($user->lang['wpu_hook_error'], E_USER_ERROR);
		}
		
		
		//fix phpBB SEO mod
		global $phpbb_seo;
		if (empty($phpbb_seo) ) {
			if(@file_exists($phpbb_root_path . 'phpbb_seo/phpbb_seo_class.'.$phpEx)) {
				require_once($phpbb_root_path . 'phpbb_seo/phpbb_seo_class.'.$phpEx);
				$phpbb_seo = new phpbb_seo();
				$this->seo = true;
			}
		}

		$this->lang = $GLOBALS['user']->lang;
		
		$this->backup_phpbb_state();
		$this->switch_to_wp_db();
		$this->restore_wp_conflicts();
		$this->make_wp_env();
	}
	
	/**
	 * Gets the current forum/WP status
	 */
	public function get_state() {
		return $this->state;
	}
	
	/**
	 * Enters the phpBB environment
	 * @access private
	 */
	private function enter() { 
		$this->lang = (isset($this->phpbbUser->lang)) ? $this->phpbbUser->lang : $this->lang;
		if($this->state != 'phpbb') {
			$this->backup_wp_conflicts();
			$this->restore_phpbb_state();
			$this->make_phpbb_env();
			$this->switch_to_phpbb_db();
		}
	}
	
	/**
	 * Returns to WordPress
	 */
	private function leave() { 
		if(isset($GLOBALS['user'])) {
			$this->lang = (sizeof($GLOBALS['user']->lang)) ? $GLOBALS['user']->lang : $this->lang;
		}
		if($this->state == 'phpbb') {
			$this->backup_phpbb_state();
			if(defined('DB_NAME')) {
				$this->switch_to_wp_db();
			}
			$this->restore_wp_conflicts();
			$this->make_wp_env();
		}
	}
	
	/**
	 * Passes content through the phpBB word censor
	 */
	public function censor($content) { 

		if(!$this->is_phpbb_loaded()) {
			return $content;
		}
		$fStateChanged = $this->foreground();
		$content = censor_text($content);
		$this->restore_state($fStateChanged);
		return $content;
	}
	
	/**
	 * Returns if the current user is logged in
	 */
	public function user_logged_in() {
		$fStateChanged = $this->foreground();
		$result = ( empty($GLOBALS['user']->data['is_registered']) ) ? false : true;
		$this->restore_state($fStateChanged);
		return $result;
	}
	
	/**
	 * Returns the currently logged-in user's username
	 */
	public function get_username() {
		$fStateChanged = $this->foreground();
		$result = $GLOBALS['user']->data['username'];
		$this->restore_state($fStateChanged);
		return $result;
	}
	
	/**
	 * Returns a userdata item (or full data array) for a user
	 * Caches the result for the session
	 */
	public function get_userdata($key = '', $userID = false, $refreshCache = false) {
		
		static $userDataCache = array();
		
		$userCacheKey = ($userID === false) ? '[BLANK]' : $userID;
	
		if(!$refreshCache) {
			if(isset($userDataCache[$userCacheKey])) {
				if(empty($key)) {
					return $userDataCache[$userCacheKey];
				} else {
					if(isset($userDataCache[$userCacheKey][$key])) {
						return $userDataCache[$userCacheKey][$key];
					}
				}
			}
		}
		
		
		$fStateChanged = $this->foreground();
		

		if($userID !== false) {
			$result = $this->fetch_userdata_for($userID);
		} else {
			$result = $GLOBALS['user']->data;
		}
		$userDataCache[$userCacheKey] = $result;
		
		$this->restore_state($fStateChanged);
		
		if ( !empty($key) ) {
			if(isset($userDataCache[$userCacheKey][$key])) {
				return $userDataCache[$userCacheKey][$key];
			} else {
				return false;
			}
		} else {
			return $userDataCache[$userCacheKey];
		}

	}
	
	/**
	 * 	fetch data for a specific user
	 */
	private function fetch_userdata_for($id) {
		global $db;
		
		
	    $sql = 'SELECT * FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $id;
    
		$result = $db->sql_query($sql);
		$user_row = @$db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$user_row) {
			return false;
		}	
		
		return $user_row;
		 
	}
	
	/**
	 * Returns the user's IP address
	 */
	public function get_userip() {
		$fStateChanged = $this->foreground();
		$result = $GLOBALS['user']->ip;
		$this->restore_state($fStateChanged);
		return $result;			
	}
	
	/**
	 * Returns a statistic
	 */
	public function stats($stat) {
		 return $GLOBALS['config'][$stat];
	}
	
	
	/**
	 * Returns rank info for currently logged in, or specified, user.
	 */
	public function get_user_rank_info($userID = '') {
		global $db;
		$fStateChanged = $this->foreground();
		
		if (!$userID ) {
			if( $this->user_logged_in() ) {
				$usrData = $this->get_userdata();
			} 
		} else {
			$sql = 'SELECT user_rank, user_posts 
						FROM ' . USERS_TABLE .
						' WHERE user_wpuint_id = ' . $userID;
				if(!($result = $db->sql_query($sql))) {
					wp_die($this->lang['WP_DBErr_Retrieve']);
				}
				$usrData = $db->sql_fetchrow($result);
		}
		if( $usrData ) {
				global $phpbb_root_path, $phpEx;
				if (!function_exists('get_user_rank')) {
					require_once($phpbb_root_path . 'includes/functions_display.php');
				}
				$rank = array();
				$rank['text'] = $rank['image_tag'] = $rank['image']  = '';
				get_user_rank($usrData['user_rank'], $usrData['user_posts'], $rank['text'], $rank['image_tag'], $rank['image']);
				$this->restore_state($fStateChanged);
				return $rank;
		}
		$this->restore_state($fStateChanged);
	}
	
	
	/**
	 * Lifts latest phpBB topics from the DB. (this is the phpBB2 version) 
	 * $forum_list limits to a specific forum (comma delimited list). $limit sets the number of posts fetched. 
	 */
	public function get_recent_topics($forum_list = '', $limit = 50) {
		global $db, $auth, $wpUnited;
		
		$fStateChanged = $this->foreground();

		$forum_list = (empty($forum_list)) ? array() :  explode(',', $forum_list); //forums to explicitly check
		$forums_check = array_unique(array_keys($auth->acl_getf('f_read', true))); //forums authorised to read posts in
		if (sizeof($forum_list)) {
			$forums_check = array_intersect($forums_check, $forum_list);
		}
		if (!sizeof($forums_check)) {
			return FALSE;
		}
		$sql = 'SELECT t.topic_id, t.topic_time, t.topic_title, u.username, u.user_id,
				t.topic_replies, t.forum_id, t.topic_poster, t.topic_status, f.forum_name
			FROM ' . TOPICS_TABLE . ' AS t, ' . USERS_TABLE . ' AS u, ' . FORUMS_TABLE . ' AS f 
			WHERE ' . $db->sql_in_set('f.forum_id', $forums_check)  . ' 
				AND t.topic_poster = u.user_id 
					AND t.forum_id = f.forum_id 
						AND t.topic_status <> 2 
			ORDER BY t.topic_time DESC';
			
		if(!($result = $db->sql_query_limit($sql, $limit, 0))) {
			wp_die($this->lang['WP_DBErr_Retrieve']);
		}		

		$posts = array();
		$i = 0;
		while ($row = $db->sql_fetchrow($result)) {
			$posts[$i] = array(
				'topic_id' 		=> $row['topic_id'],
				'topic_replies' => $row['topic_replies'],
				'topic_title' 	=> $wpUnited->censor_content($row['topic_title']),
				'user_id' 		=> $row['user_id'],
				'username' 		=> $row['username'],
				'forum_id' 		=> $row['forum_id'],
				'forum_name' 	=> $row['forum_name']
			);
			$i++;
		}
		$db->sql_freeresult($result);
		$this->restore_state($fStateChanged);
		return $posts;
	}	
	
	/**
	 * Transitions to/from the currently logged-in user
	 */
	 public function transition_user($toID = false, $toIP = false) {
		 global $auth, $user, $db;
		 
		 $fStateChanged = $this->foreground();
		 
		 if( ($toID === false) && ($this->_transitioned_user == true) ) {
			  // Transition back to the currently logged-in user
			$user->data = $this->_savedData;
			$user->ip = $this->_savedIP;
			$auth = $this->_savedAuth;
			$this->_transitioned_user = false;
		} else if(($toID !== false) && ($toID !== $user->data['user_id'])) {
			// Transition to a new user
			if($this->_transitioned == false) {
				// backup current user
				$this->_savedData= $user->data;
				$this->_savedIP = $user->ip;
				$this->_savedAuth = $auth;
			}
			$sql = 'SELECT *
				FROM ' . USERS_TABLE . "
				WHERE user_id = {$toID}";

			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			$user->data = array_merge($user->data, $row);
			$user->ip = $toIP;
			$auth->acl($user->data);
			$this->_transitioned_user = true;
		}
		
		$this->restore_state($fStateChanged);
		 
	}
	
	public function get_cookie_domain() {
		global $config;
		
		if(!$this->is_phpbb_loaded()) {
			return false;
		}
		
		$fStateChanged = $this->foreground();
		$cookieDomain = $config['cookie_domain'];
		$this->restore_state($fStateChanged);

		return $cookieDomain;
	}
	
	public function get_cookie_path() {
		global $config;
		
		if(!$this->is_phpbb_loaded()) {
			return false;
		}
		
		$fStateChanged = $this->foreground();
		$cookieDomain = $config['cookie_path'];
		$this->restore_state($fStateChanged);

		return $cookieDomain;
	}
	
	/**
	 * Sets a phpBB permission for a specific user if they don't have permission to do that already
	 * Lifted from https://www.phpbb.com/kb/article/permission-system-overview-for-mod-authors-part-two/
	 * 
	 * @param grant|remove $mode defines whether roles are granted to removed
	 * @param strong $role_name role name to update
	 * @param mixed $options auth_options to grant (a auth_option has to be specified)
	 * @param ACL_YES|ACL_NO|ACL_NEVER $auth_setting defines the mode acl_options are getting set with
	 *
	 */
	public function update_user_permissions($mode = 'grant', $user_id, $options = array(), $auth_setting = ACL_YES) {
		global $db, $auth, $cache;
		
		if(!$user_id) {
			return false;
		}
		
		$fStateChanged = $this->foreground();

		//Get All Current Options For User
		$user_options = array();
		$sql = "SELECT auth_option_id
			FROM " . ACL_USERS_TABLE . "
			WHERE user_id = " . (int) $user_id . "
			GROUP BY auth_option_id";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result)) {
			$user_options[] = $row;
		}
		$db->sql_freeresult($result);

		//Get Option ID Values For Options Granting Or Removing
		$acl_options_ids = array();
		$sql = "SELECT auth_option_id
			FROM " . ACL_OPTIONS_TABLE . "
			WHERE " . $db->sql_in_set('auth_option', $options) . "
			GROUP BY auth_option_id";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result)) {
			$acl_options_ids[] = $row;
		}
		$db->sql_freeresult($result);


		//If Granting Permissions
		if ($mode == 'grant') {
			//Make Sure We Have Option IDs
			if (empty($acl_options_ids)) {
				$this->restore_state($fStateChanged);
				return false;
			}
			
			//Build SQL Array For Query
			$sql_ary = array();
			for ($i = 0, $count = sizeof($acl_options_ids);$i < $count; $i++) {

				//If Option Already Granted To User Then Skip It
				if (in_array($acl_options_ids[$i]['auth_option_id'], $user_options)) {
					continue;
				}
				$sql_ary[] = array(
					'user_id'        => (int) $user_id,
					'auth_option_id'    => (int) $acl_options_ids[$i]['auth_option_id'],
					'auth_setting'        => $auth_setting,
				);
			}

			$db->sql_multi_insert(ACL_USERS_TABLE, $sql_ary);
			$cache->destroy('acl_options');
			$auth->acl_clear_prefetch();
		}

		//If Removing Permissions
		if ($mode == 'remove') {
			//Make Sure We Have Option IDs
			if (empty($acl_options_ids)) {
				$this->restore_state($fStateChanged);
				return false;
			}
			
			//Process Each Option To Remove
			for ($i = 0, $count = sizeof($acl_options_ids);$i < $count; $i++) {
				$sql = "DELETE
					FROM " . ACL_USERS_TABLE . "
					WHERE auth_option_id = " . $acl_options_ids[$i]['auth_option_id'];

				$db->sql_query($sql);
			}

			$cache->destroy('acl_options');
			$auth->acl_clear_prefetch();
		}
		
		$this->restore_state($fStateChanged);
		return;
	}
	
	/**
	* Update group-specific ACL options. Function can grant or remove options. If option already granted it will NOT be updated.
	* Lifted from https://www.phpbb.com/kb/article/permission-system-overview-for-mod-authors-part-two/
	*
	* @param grant|remove $mode defines whether roles are granted to removed
	* @param string $group_name group name to update
	* @param mixed $options auth_options to grant (a auth_option has to be specified)
	* @param ACL_YES|ACL_NO|ACL_NEVER $auth_setting defines the mode acl_options are getting set with
	*
	*/
	public function update_group_permissions($mode = 'grant', $group_name, $options = array(), $auth_setting = ACL_YES) {
		global $db, $auth, $cache;
		
		$fStateChanged = $this->foreground();
		
		//First We Get Role ID
		$sql = "SELECT g.group_id
			FROM " . GROUPS_TABLE . " g
			WHERE group_name = '$group_name'";
		$result = $db->sql_query($sql);
		$group_id = (int) $db->sql_fetchfield('group_id');
		$db->sql_freeresult($result);

		//Now Lets Get All Current Options For Role
		$group_options = array();
		$sql = "SELECT auth_option_id
			FROM " . ACL_GROUPS_TABLE . "
			WHERE group_id = " . (int) $group_id . "
			GROUP BY auth_option_id";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result)) {
			$group_options[] = $row;
		}
		$db->sql_freeresult($result);

		//Get Option ID Values For Options Granting Or Removing
		$sql = "SELECT auth_option_id
			FROM " . ACL_OPTIONS_TABLE . "
			WHERE " . $db->sql_in_set('auth_option', $options) . "
			GROUP BY auth_option_id";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result)) {
			$acl_options_ids[] = $row;
		}
		$db->sql_freeresult($result);


		//If Granting Permissions
		if ($mode == 'grant') {
			//Make Sure We Have Option IDs
			if (empty($acl_options_ids)) {
				return false;
			}
			
			//Build SQL Array For Query
			$sql_ary = array();
			for ($i = 0, $count = sizeof($acl_options_ids);$i < $count; $i++) {
				
				//If Option Already Granted To Role Then Skip It
				if (in_array($acl_options_ids[$i]['auth_option_id'], $group_options)) {
					continue;
				}
				$sql_ary[] = array(
					'group_id'        => (int) $group_id,
					'auth_option_id'    => (int) $acl_options_ids[$i]['auth_option_id'],
					'auth_setting'        => $auth_setting,
				);
			}

			$db->sql_multi_insert(ACL_GROUPS_TABLE, $sql_ary);
			$cache->destroy('acl_options');
			$auth->acl_clear_prefetch();
		}

		//If Removing Permissions
		if ($mode == 'remove') {
			//Make Sure We Have Option IDs
			if (empty($acl_options_ids)) {
				return false;
			}
			
			//Process Each Option To Remove
			for ($i = 0, $count = sizeof($acl_options_ids);$i < $count; $i++) {
				$sql = "DELETE
					FROM " . ACL_GROUPS_TABLE . "
					WHERE auth_option_id = " . $acl_options_ids[$i]['auth_option_id'];

				$db->sql_query($sql);
			}

			$cache->destroy('acl_options');
			$auth->acl_clear_prefetch();
		}
		
		$this->restore_state($fStateChanged);

		return;
	}
	
	/**
	 * Remove all WP-United permissions from phpBB groups
	 */
	
	public function clear_group_permissions() {
		global $db;
		
		$perms = array_keys(wpu_permissions_list());
		
		$fStateChanged = $this->foreground();
		
		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE;
		$result = $db->sql_query($sql);

		$groups = array();
		while ($row = $db->sql_fetchrow($result)) {
			$groups[] = $row['group_name'];
		}
		$db->sql_freeresult($result);
		
		foreach($groups as $group) {
			$this->update_group_permissions('remove', $group, $perms);
		}
		
		$this->restore_state($fStateChanged);
	}

		
	
	/**
	 * Logs out the current user
	 */
	 public function logout() {
		 global $user;
		 
		 $fStateChanged = $this->foreground();
		 
		 if($user->data['user_id'] != ANONYMOUS) {
			$user->session_kill();
		}
		
		$this->restore_state($fStateChanged);
	}
	
	/**
	 * Returns a list of smilies
	 */
	public function get_smilies() {
		global $db;
		
		if(!$this->is_phpbb_loaded()) {
			return '';
		}
		
		$fStateChanged = $this->foreground();
	
		$result = $db->sql_query('SELECT code, emotion, smiley_url FROM '.SMILIES_TABLE.' GROUP BY emotion ORDER BY smiley_order ', 3600);

		$i = 0;
		$smlOutput =  '<span id="wpusmls">';
		while ($row = $db->sql_fetchrow($result)) {
			if (empty($row['code'])) {
				continue;
			}
			if ($i == 7) {
				$smlOutput .=  '<span id="wpu-smiley-more" style="display:none">';
			}
		
			$smlOutput .= '<a href="#"><img src="'.$this->url.'images/smilies/' . $row['smiley_url'] . '" alt="' . $row['code'] . '" title="' . $row['emotion'] . '" /></a> ';
			$i++;
		}
		$db->sql_freeresult($result);
	
		$this->restore_state($fStateChanged);
	
	
		if($i >= 7) {
			$smlOutput .= '</span>';
			if($i>7) {
				$smlOutput .= '<a id="wpu-smiley-toggle" href="#" onclick="return wpuSmlMore();">' . __('More smilies') . '&nbsp;&raquo;</a></span>';
			}
		}
		$smlOutput .= '</span>';
	
		return $smlOutput;
		 
	}
	
	public function get_avatar($phpbbId, $width=0, $height=0, $alt) {
		global $db, $config, $phpbb_root_path, $phpEx;
		
		$fStateChanged = $this->foreground();
		
		require_once($phpbb_root_path . 'includes/functions_display.' . $phpEx);
		
		$sql = 'SELECT user_avatar, user_avatar_type, user_avatar_width, user_avatar_height 
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $phpbbId;
		$result = $db->sql_query($sql);
		$avatarDetails = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		$width = (empty($width)) ? $avatarDetails['user_avatar_width'] : $width;
		$height = (empty($height)) ? $avatarDetails['user_avatar_height'] : $height;
		
		$phpbbAvatar = get_user_avatar($avatarDetails['user_avatar'], $avatarDetails['user_avatar_type'], $width, $height, $alt);
		
		$this->restore_state($fStateChanged);
		
		
		// convert path to URL for returned avatar HTML
		$phpbbAvatar = str_replace('src="' . $phpbb_root_path, 'src="' . $this->url, $phpbbAvatar);

		return $phpbbAvatar;
	}
	
	
	// send the WP avatar to phpBB if the phpBB one is unset
	public function put_avatar($html, $id, $width=90, $height=90) {
		global $db;	
			
		$userItems = $this->convert_avatar_to_phpbb($html, $id, $width, $height);
		return $this->update_userdata($id, $userItems);
		
	}
	
	/**
	 * Update_userdata -- Updates user information for a given user
	 * @param integer $id phpBB user ID
	 * @param array $userItems an associative array of key names and values to update
	 * @return sql update result, false on failure (?)
	 */
	public function update_userdata($id, $userItems) {
		global $db;	
		
		if(!is_array($userItems) || !sizeof($userItems) || empty($id)) {
			return false;
		}

		$fStateChanged = $this->foreground();
		
		$sql = 'UPDATE ' . USERS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $userItems) . '
			WHERE user_id = ' . $id;
		$status = $db->sql_query($sql);		
		
		$this->restore_state($fStateChanged);
		
		return $status;
		
	
	}
	
	/**
	 * Convert an avatar into relevant $user array items for phpBB
	 * @return array array of user items or empty array on failure
	 */
	public function convert_avatar_to_phpbb($html, $id, $width=90, $height=90) {
		global $config;	
		
		
		$width = (int)$width;
		$height = (int)$height;
		
		if(($width < 50) || ($height < 50)) { 
			return array();
		}
		
		if(!preg_match('/src\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $matches)) {
			return array();
		} 
		$avatarUrl = $matches[1];
		
		if(!$avatarUrl) {
			return array();
		}
		
		// we leave a marker for ourselves to show this avatar was put by wpu
		$avatarUrl = $avatarUrl . '&amp;wpuput=1';

		$fStateChanged = $this->foreground();
		
		$userItems = array();
		
		if($config['allow_avatar'] && $config['allow_avatar_remote']) {
		
			// calling avatar_remote uses too many resources, so we put in the images directly to the DB
			
			$width = ($width > $config['avatar_max_width']) ? $config['avatar_max_width'] : $width;
			$height = ($height > $config['avatar_max_height']) ? $config['avatar_max_height'] : $height;
			
			$userItems = array(
				'user_avatar_type' 		=> AVATAR_REMOTE,
				'user_avatar'			=> $avatarUrl,
				'user_avatar_width'		=> $width,
				'user_avatar_height'	=> $height,
			);
					
		}
		
		$this->restore_state($fStateChanged);	
		
		return $userItems;
	}
	
	
	/**
	 * transmits new settings from the WP settings panel to phpBB
	 */
	public function synchronise_settings($dataToStore) {
		global $wpUnited, $cache, $user, $auth, $config, $db, $phpbb_root_path, $phpEx;
		
		
		if(empty($dataToStore)) {
			echo "No settings to process";
			return false;
		}
		
		$fStateChanged = $this->foreground();
				
		$adminLog = array();
		$adminLog[] = __('Receiving settings from WP-United...');
		
		if  ( !array_key_exists('user_wpuint_id', $user->data) ) {
			$sql = 'ALTER TABLE ' . USERS_TABLE . ' 
				  ADD user_wpuint_id VARCHAR(10) NULL DEFAULT NULL';

			if (!$result = $db->sql_query($sql)) {
				trigger_error('ERROR: Cannot add the integration column to the users table', E_USER_ERROR); exit();
			}
			$adminLog[] = __('Modified USERS Table (Integration ID)');
		}
		
		if  ( !array_key_exists('user_wpublog_id', $user->data) ) {
			$sql = 'ALTER TABLE ' . USERS_TABLE . ' 
				ADD user_wpublog_id VARCHAR(10) NULL DEFAULT NULL';
			if (!$result = $db->sql_query($sql)) {
				trigger_error('ERROR: Cannot add blog ID column to users table', E_USER_ERROR); exit();
			}
			$adminLog[] = __('Modified USERS Table (Blog ID)');
		}
		
		$sql = 'SELECT * FROM ' . POSTS_TABLE;
		$result = $db->sql_query_limit($sql, 1);

		$row = (array)$db->sql_fetchrow($result);

		if (!array_key_exists('post_wpu_xpost', $row) ) {
			$sql = 'ALTER TABLE ' . POSTS_TABLE . ' 
				ADD post_wpu_xpost VARCHAR(10) NULL DEFAULT NULL';

			if (!$result = $db->sql_query($sql)) {
				trigger_error('ERROR: Cannot add cross-posting column to posts table', E_USER_ERROR); exit();
			}
			$adminLog[] = __('Modified POSTS Table (Cross-Posting Link)');
		}
		
		$db->sql_freeresult($result);

		
		$adminLog[] = __('Adding WP-United Permissions');
		
		// Setup $auth_admin class so we can add permission options
		include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
		$auth_admin = new auth_admin();

		// Add permissions
		$auth_admin->acl_add_option(array(
			'local'      => array('f_wpu_xpost'),
			'global'   => array('u_wpu_subscriber','u_wpu_contributor','u_wpu_author','m_wpu_editor','a_wpu_administrator')
		));

		$adminLog[] = __('Storing the new WP-United settings');
		
		// this stores the passed-in settings object, which is a bit brittle
		// TODO: ask $wpUnited->settings to store/reload itself, without making it public
		$this->clear_settings();
		$sql = array();
		$sql[] = array(
			'config_name'	=>	'wpu_location',
			'config_value'	=>	$wpUnited->get_plugin_path()
		);
		$dataIn = base64_encode(gzcompress(serialize($dataToStore)));
		$currPtr=1;
		$chunkStart = 0;
		while($chunkStart < strlen($dataIn)) {
			$sql[] = array(
				'config_name' 	=> 	"wpu_settings_{$currPtr}",
				'config_value' 	=>	substr($dataIn, $chunkStart, 255)
			);
			$chunkStart = $chunkStart + 255;
			$currPtr++;
		}
		
		$db->sql_multi_insert(CONFIG_TABLE, $sql);
		$cache->destroy('config');
		

		if($wpUnited->get_setting('integrateLogin') && $wpUnited->get_setting('avatarsync')) {
			if(!$config['allow_avatar'] || !$config['allow_avatar_remote']) {
				$adminLog[] = __('Updating avatar settings');

				$sql = 'UPDATE ' . CONFIG_TABLE . ' 
					SET config_value = 1
					WHERE ' . $db->sql_in_set('config_name', array('allow_avatar', 'allow_avatar_remote'));
				$db->sql_query($sql);

				$cache->destroy('config');
			}
		}

		
		// clear out the WP-United cache on settings change
		$adminLog[] = __('Purging the WP-United cache');
		require_once($wpUnited->get_plugin_path() . 'cache.php');
		$wpuCache = WPU_Cache::getInstance();
		$wpuCache->purge();
		
		$adminLog[] = __('Completed successfully');
		
		// Save the admin log in a nice dropdown in phpBB admin log. 
		// Requires a bit of template hacking using JS since we don't want to have to do a mod edit for new script
		
		// generate unique ID for details ID
		$randSeed = rand(0, 99999);
		$bodyContent = '<div style="display: none; border: 1px solid #cccccc; background-color: #ccccff; padding: 3px;" id="wpulogdets' . $randSeed . '">';
		$bodyContent .= implode("<br />\n\n", $adminLog) . '</div>';
		$ln = "*}<span class='wpulog'><script type=\"text/javascript\">// <![CDATA[
		var d = document;
		function wputgl{$randSeed}() {
			var l = d.getElementById('wpulogdets{$randSeed}');
			var p = d.getElementById('wpulogexp{$randSeed}'); var n = p.firstChild.nodeValue;
			l.style.display = (n == '-') ? 'none' : 'block';
			p.firstChild.nodeValue = (n == '-') ? '+' : '-';
			return false;
		}
		if(typeof wpual == 'undefined') {
			var wpual = window.onload;
			window.onload = function() {
				if (typeof wpual == 'function') wpual();
				try {
					var hs = d.getElementsByClassName('wpulog');
					for(h in hs) {var p = hs[h].parentNode; p.firstChild.nodeValue = ''; p.lastChild.nodeValue = '';}
				} catch(e) {}	  
			};
		}
		// ]]>
		</script>";
		
		$ln .= '<strong><a href="#" onclick="return wputgl' . $randSeed . '();" title="click to see details">' . __('Changed WP-United Settings (click for details)') . '<span id="wpulogexp' . $randSeed . '">+</span></a></strong>' . $bodyContent . '</span>{*';

		add_log('admin', $ln);
		
		$cache->purge();
		 

		

		$this->restore_state($fStateChanged);
		return true;

	}
	
	public function load_style_keys() {
		global $config;
		$fStateChanged = $this->foreground();
		
		$key = 1;
		$fullKey = '';
		while(isset( $config["wpu_style_keys_{$key}"])) {
			$fullKey .= $config["wpu_style_keys_{$key}"];
			$key++;
		}
		$this->restore_state($fStateChanged);

		if(!empty($fullKey)) {
			return unserialize(base64_decode($fullKey));
		} else {
			return array();
		}
		
	}
	
	public function erase_style_keys()	{
		global $db, $config, $cache;

		$fStateChanged = $this->foreground();
		
		if(isset($config['wpu_style_keys_1'])) {
			$sql = 'DELETE FROM ' . CONFIG_TABLE . ' 
				WHERE config_name LIKE \'wpu_style_keys_%\'';
			$db->sql_query($sql);
		}	
		$cache->destroy('config');
		
		$this->restore_state($fStateChanged);
		
		
	}
	
	/**
	 * Saves updated style keys to the database.
	 * phpBB $config keys can only store 255 bytes of data, so we usually need to store the data
	 * split over several config keys
	  * We want changes to take place as a single transaction to avoid collisions, so we 
	  * access DB directly rather than using set_config
	 * @return int the number of config keys used
	 */ 
	public function commit_style_keys($styleKeys) {
		global $cache, $db;
		
		$this->erase_style_keys();
		$fStateChanged = $this->foreground();
		
		$fullLocs = (base64_encode(serialize($styleKeys))); 
		$currPtr=1;
		$chunkStart = 0;
		$sql = array();
		while($chunkStart < strlen($fullLocs)) {
			$sql[] = array(
				'config_name' 	=> 	"wpu_style_keys_{$currPtr}",
				'config_value' 	=>	substr($fullLocs, $chunkStart, 255)
			);
			$chunkStart = $chunkStart + 255;
			$currPtr++;
		}
		
		$db->sql_multi_insert(CONFIG_TABLE, $sql);
		$cache->destroy('config');
		
		$this->restore_state($fStateChanged);
	
		return $currPtr;
	}	
	
	public function clear_settings() {
		global $db;
		
		$fStateChanged = $this->foreground();
		
		$sql = 'DELETE FROM ' . CONFIG_TABLE . "
			WHERE config_name LIKE 'wpu_settings_%' 
			OR config_name LIKE 'wpu_location'";
		$db->sql_query($sql);
		
		$this->restore_state($fStateChanged);
	
	}
	
	// returns the default group for new phpBB users
	public function get_newuser_group() {
		global $config;
		
		$fStateChanged = $this->background();
		
		// if 0, they aren't added to the group -- else they are in group until they have this number of posts
		$newMemberGroup = ($config['new_member_post_limit'] != 0);
		
		$this->restore_state($fStateChanged);
		
		return ($newMemberGroup) ? array('REGISTERED', 'NEWLY_REGISTERED') : array('REGISTERED');
	
	}
	
	
	
	// Update blog link column
	/**
	 * @TODO: this doesn't need to happen every time
	 */
	public function update_blog_link($author) {
		global $db;
		
		if(!$this->is_phpbb_loaded()) {
			return '';
		}
		
		$fStateChanged = $this->foreground();
		
		if ( !empty($author) ) {
			$sql = 'UPDATE ' . USERS_TABLE . ' SET user_wpublog_id = ' . $author . " WHERE user_wpuint_id = '{$author}'";
			if (!$result = $db->sql_query($sql)) {
				return false;
			}
			$db->sql_freeresult($result);
		}
		$this->restore_state($fStateChanged);
		return true;
	}
	
	public function add_smilies($postContent, $maxSmilies) {
		static $match;
		static $replace;
		global $db;
	

		// See if the static arrays have already been filled on an earlier invocation
		if (!is_array($match)) {
		
			$fStateChanged = $this->foreground();
			
			$result = $db->sql_query('SELECT code, emotion, smiley_url FROM '.SMILIES_TABLE.' ORDER BY smiley_order', 3600);

			while ($row = $db->sql_fetchrow($result)) {
				if (empty($row['code'])) {
					continue; 
				} 
				$match[] = '(?<=^|[\n .])' . preg_quote($row['code'], '#') . '(?![^<>]*>)';
				$replace[] = '<!-- s' . $row['code'] . ' --><img src="' . $this->url . '/images/smilies/' . $row['smiley_url'] . '" alt="' . $row['code'] . '" title="' . $row['emotion'] . '" /><!-- s' . $row['code'] . ' -->';
			}
			$db->sql_freeresult($result);
			
			$this->restore_state($fStateChanged);
			
		}
		if (sizeof($match)) {
			if ($maxSmilies) {
				$num_matches = preg_match_all('#' . implode('|', $match) . '#', $postContent, $matches);
				unset($matches);
			}
			
			// Make sure the delimiter # is added in front and at the end of every element within $match
			$postContent = trim(preg_replace(explode(chr(0), '#' . implode('#' . chr(0) . '#', $match) . '#'), $replace, $postContent));
		}
		
		return $postContent;
	}	
	
	
	
	/**
	 * Calculates the URL to the forum
	 * @access private
	 */
	private function calculate_url() {
			global $config;
			$server = $config['server_protocol'] . add_trailing_slash($config['server_name']);
			$scriptPath = add_trailing_slash($config['script_path']);
			$scriptPath= ( $scriptPath[0] == "/" ) ? substr($scriptPath, 1) : $scriptPath;
			$this->url = $server . $scriptPath;
	}
	
	
	/**
	 * @access private
	 */
	private function make_phpbb_env() {
		global $IN_WORDPRESS;
		
		// WordPress removes $_COOKIE from $_REQUEST, which is the source of much wailing and gnashing of teeth
		$IN_WORDPRESS = 1; 
		$this->state = 'phpbb';
		$_REQUEST = array_merge($_COOKIE, $_REQUEST);
	}

	/**
	 * @access private
	 */	
	private function make_wp_env() {
		$this->state = 'wp';
		$_REQUEST = array_merge($_GET, $_POST);
		restore_error_handler();
	}

	/**
	 * @access private
	 */	
	private function backup_wp_conflicts() {
		global $table_prefix, $user, $cache, $template;
		
		$this->wpTemplate = $template;
		$this->wpTablePrefix = $table_prefix;
		$this->wpUser = (isset($user)) ? $user: '';
		$this->wpCache = (isset($cache)) ? $cache : '';
	}

	/**
	 * @access private
	 */	
	private function backup_phpbb_state() {
		global $table_prefix, $user, $cache, $dbname, $template;

		$this->phpbbTemplate = $template;
		$this->phpbbTablePrefix = $table_prefix;
		$this->phpbbUser = (isset($user)) ? $user: '';
		$this->phpbbCache = (isset($cache)) ? $cache : '';
		$this->phpbbDbName = $dbname;
	}

	/**
	 * @access private
	 */	
	private function restore_wp_conflicts() {
		global $table_prefix, $user, $cache, $template;
		
		$template = $this->wpTemplate;
		$user = $this->wpUser;
		$cache = $this->wpCache;
		$table_prefix = $this->wpTablePrefix;
	}

	/**
	 * @access private
	 */	
	private function restore_phpbb_state() {
		global $table_prefix, $user, $cache, $template;
		
		$template = $this->phpbbTemplate;
		$table_prefix = $this->phpbbTablePrefix;
		$user = $this->phpbbUser;
		$cache = $this->phpbbCache;
		
		// restore phpBB error handler
		set_error_handler(defined('PHPBB_MSG_HANDLER') ? PHPBB_MSG_HANDLER : 'msg_handler');


	}

	/**
	 * @access private
	 */	
	private function switch_to_wp_db() {
		global $wpdb;
		if (($this->phpbbDbName != DB_NAME) && (!empty($wpdb->dbh))) {
			@mysql_select_db(DB_NAME, $wpdb->dbh);
		}      
	}
	
	/**
	 * @access private
	 */	
	private function switch_to_phpbb_db() {
		global $db, $dbms;
		if (($this->phpbbDbName != DB_NAME) && (!empty($db->db_connect_id))) {
			if($dbms=='mysqli') {
				@mysqli_select_db($this->phpbbDbName, $db->db_connect_id);
			} else if($dbms=='mysql') {
				@mysql_select_db($this->phpbbDbName, $db->db_connect_id);
			}
		}
	}
	
	/**
	 * Originally by Poyntesm
	 * http://www.phpbb.com/community/viewtopic.php?f=71&t=545415&p=3026305
	 * @access private
	 */
	private function get_role_by_name($name) {
		$fStateChanged = $this->foreground();
	   global $db;
	   $data = null;

	   $sql = "SELECT *
		  FROM " . ACL_ROLES_TABLE . "
		  WHERE role_name = '$name'";
	   $result = $db->sql_query($sql);
	   $data = $db->sql_fetchrow($result);
	   $db->sql_freeresult($result);
		$this->restore_state($fStateChanged);
	   return $data;
	}
	
	/**
	* Set role-specific ACL options without deleting enter existing options. If option already set it will NOT be updated.
	* 
	* @param int $role_id role id to update (a role_id has to be specified)
	* @param mixed $auth_options auth_options to grant (a auth_option has to be specified)
	* @param ACL_YES|ACL_NO|ACL_NEVER $auth_setting defines the mode acl_options are getting set with
	* @access private
	*
	*/
	private function acl_update_role($role_id, $auth_options, $auth_setting = ACL_YES) {
	   global $db, $cache, $auth;
		$fStateChanged = $this->foreground();
		$acl_options_ids = $this->get_acl_option_ids($auth_options);

		$role_options = array();
		$sql = "SELECT auth_option_id
			FROM " . ACL_ROLES_DATA_TABLE . "
			WHERE role_id = " . (int) $role_id . "
			GROUP BY auth_option_id";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))	{
			$role_options[] = $row;
		}
		$db->sql_freeresult($result);

		$sql_ary = array();
		foreach($acl_options_ids as $option)	{
			if (!in_array($option, $role_options)) {
				$sql_ary[] = array(
					'role_id'      		=> (int) $role_id,
					'auth_option_id'	=> (int) $option['auth_option_id'],
					'auth_setting'      => $auth_setting
				);	
			}
		}

	   $db->sql_multi_insert(ACL_ROLES_DATA_TABLE, $sql_ary);

	   $cache->destroy('acl_options');
	   $auth->acl_clear_prefetch();
	   $this->restore_state($fStateChanged);
	}

	/**
	* Get ACL option ids
	*
	* @param mixed $auth_options auth_options to grant (a auth_option has to be specified)
	* @access private
	*/
	private function get_acl_option_ids($auth_options) {
		$fStateChanged = $this->foreground();
	   global $db;

	   $data = array();
	   $sql = "SELECT auth_option_id
		  FROM " . ACL_OPTIONS_TABLE . "
		  WHERE " . $db->sql_in_set('auth_option', $auth_options) . "
		  GROUP BY auth_option_id";
	   $result = $db->sql_query($sql);
	   while ($row = $db->sql_fetchrow($result))  {
		  $data[] = $row;
	   }
	   $db->sql_freeresult($result);
		$this->restore_state($fStateChanged);
	   return $data;
	}
	
	/**
	 * Brings phpBB (db, conflicting vars, etc) to the foreground
	 * calling functions can track the returned $state parameter and use it to restore the same state when
	 * they exit.
	 * @return bool whether phpBB was in the background and we actually had to do anything.
	 */
	public function foreground() {
		if($this->state != 'phpbb') {
			$this->enter();
			return true;
		}
		return false;
	}
	
	/**
	 * Sends phpBB into the background, restoring WP database and vars
	 * @param bool $state whether to perform the action. Optional -- shortcut for if in the calling function
	 */
	public function background($state = true) {
		if($state) {
			$this->leave();
		}
	}
	
	/**
	 * Alias for background(). However $state must be provided
	 * @ param bool $state whether to perform the action (shortcut for if in the calling function.)
	 */
	public function restore_state($state) {
		$this->background($state);
	}
	
}

?>