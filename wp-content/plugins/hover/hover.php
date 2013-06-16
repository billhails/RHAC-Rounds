<?php
/*
Plugin Name: Hover
Plugin URI: http://bc-bd.org/blog/?page_id=48
Description: Replaces keywords with links and optional onmouseover() popups.  Something not working? Send me some <a href="mailto:bd@bc-bd.org">FEEDBACK</a>. <strong>Upgrading?</strong> Make sure to read the file named UPGRADE in the archive.
Author: Stefan V&ouml;lkel
Author URI: http://bc-bd.org
Version: 0.7.2

Released under the GPLv2.

inspired by Text Link Ads 1.4 by Wil rushmer, http://www.rushmer.com
Uses domTT: http://www.mojavelinux.com/projects/domtooltip/
Uses behaviour.js: http://bennolan.com/behaviour/
Uses xajax: http://www.xajaxproject.org/

Other URLs:
http://freshmeat.net/projects/hover/
http://svn.wp-plugins.org/hover/

*/

/* since 2.5 we need to define those two global, else
 * sv_hover_install is not able to access the needed values
 * see https://bc-bd.org/trac/hover/ticket/13 */
global $table_prefix, $sv_hover_options;

/* this is where we store our data */
define('HOVER_TABLE', $table_prefix."hover");
define('HOVER_IMAGES', HOVER_TABLE."_images");
define('HOVER_BASE', get_bloginfo('wpurl')."/wp-content/plugins/hover");

define('HOVER_BEHAVIOUR_URL', HOVER_BASE."/behaviour");
define('HOVER_DOMTT_URL', HOVER_BASE."/domTT");

if (substr(get_option('upload_path'),0 , 1) == "/") {
	define('HOVER_JS_FILE', get_option("upload_path")."/"."hover.js");
} else {
	define('HOVER_JS_FILE', ABSPATH."/".get_option("upload_path")."/hover.js");
}

if (get_option('upload_url_path')) {
	define('HOVER_JS_URL', get_option('upload_url_path'));
} else {
	define('HOVER_JS_URL', get_bloginfo('wpurl')."/wp-content/uploads");
}

define('HOVER_HAS_NONE', 0);
define('HOVER_HAS_HOVERS', 1);
define('HOVER_HAS_IMAGES', 2);

define('HOVER_VERSION', 4);

define('HOVER_SUBMIT', '<div class="submit"><a href="#wphead">Top</a><input type="submit" name="info_update" value="Update options"/></div>');

/* actual content replacing happens here */
function sv_hover_get($content) {
	global $sv_hover_options, $sv_hover_data;

	/* remove [no_hover] and do nothing else if it was found */
	if (preg_match("/\[no_hover\]/", $content)) {
		$content = preg_replace("/\[no_hover\]/", "", $content);
		return $content;
        }

	/* loop over all links and replace them */
	foreach ($sv_hover_data as $hover) {
		$content = preg_replace($hover->search, $hover->placeholder,
		$content, $sv_hover_options{'maxreplace_'.$hover->type});
        }

	foreach ($sv_hover_data as $hover) {
		$content = preg_replace($hover->placeholder_search, $hover->replace,
		$content, $sv_hover_options{'maxreplace_'.$hover->type});
        }

	return $content;
}

function sv_hover_create_images() {
	global $wpdb, $sv_hover_images;

	$results = $wpdb->get_results(
		"SELECT id, src, text".
		" FROM ".HOVER_IMAGES." ORDER BY id"
	);

	if (count($results) <= 0)
		return HOVER_HAS_NONE;

	foreach ($results as $img) {
		$sv_hover_images[$img->id] = sprintf("'%s' : '%s'",
			$img->src, $img->text);
	}

	return HOVER_HAS_IMAGES;
}

function sv_hover_create_hovers() {
	global $wpdb, $sv_hover_options, $sv_hover_behaviour, $sv_hover_data;

	/* retrieve data from database */
	$results = $wpdb->get_results(
		"SELECT id, search, type, link, description".
		" FROM ".HOVER_TABLE." ORDER BY LENGTH(search) DESC"
		);

	/* no need to add script, css or filter if there is no data */
	if (count($results) <= 0)
		return HOVER_HAS_NONE;

	$blank = '';
	if ($sv_hover_options{'blank'})
		$blank = 'target="_blank"';

	foreach ($results as $link){
		# in case we have a conditional replace we move the colon from
		# the search term, to the condition.
		#
		# we need to do this to support case insensitive replaces, but
		# keep the original case when replacing
		if ($link->search[0] == ':') {
			$cond = ':';
			$search = substr($link->search, 1);
		} else {
			$cond = '';
			$search = $link->search;
		}

                $boundary = "";
                if (preg_match("/(\d|\w)$/", $search)) $boundary = "\b(?![-\.]\b)";

		# start with replacing with the found term.
		$replace = $search;

                $search = preg_quote($search, '#');

		# the search pattern to look for
		# - first we have a negative look behind pattern making sure we
		#   are on a word boundary
		# - the next negative look behind pattern makes sure we are not
		#   preceeded by a colon, this is to support conditional
		#   replacement
		# - pattern for the condional replace, see above
		# - next, the term to search for, e.g. the hover
		# - next, a word boundary to not replace midword
		# - next, a negative look ahead pattern to make sure that no
		#   dash or colon is following, e.g. hoover-0.7.0.tar.gz
		# - next, make sure that we are not inside a html tag, e.g.
		#   <img src=="http://bc-bd.org/hover/" />
		$search = "#(?<!&|\w)(?<!:)".
			$cond.'('.$search.')'.
			$boundary . "(?![^<>]*?>)#ims";

		$id = 'hover'.$link->id;

		/* If javascript is disabled, we set the title attribute */
		if ($sv_hover_options{'usejs'} == "0")
			$title = " title=\"$link->description\" ";
		else
			$title = "";

		$websnapr = "";

		$desc = $link->description;

		// and build together our hover
		switch ($link->type) {
			case "abbr":
				$replace = '<abbr '.$title.'>'.
					'<span class="'.$id.'">'.
					$replace.
					'</span></abbr>';
				break;
			case "acronym":
				$replace = '<acronym '.$title.'>'.
					'<span class="'.$id.'">'.
					$replace.
					'</span></acronym>';

				if ($sv_hover_options{websnapr_acronym})
					$desc .= $websnapr;

				break;
			case "link":
				$replace = '<a class="hover-link"'.
					' href="'.$link->link.'" '.
					$blank.' '.
					$title.'><span class="'.$id.'">'.
					$replace.
					'</span></a>';

				if ($sv_hover_options{'websnapr_link'})
					$desc .= $websnapr;

				break;
		}

		$sv_hover_data[$id]->search = $search;
		$sv_hover_data[$id]->placeholder = "#####$id#####";
		$sv_hover_data[$id]->placeholder_search = "/#####$id#####/";
		$sv_hover_data[$id]->replace = $replace;
		$sv_hover_data[$id]->type = $link->type;

		// reference java script on mouse events
		if ($sv_hover_options{'usejs'} != "0")
			$sv_hover_behaviour[$id] = $desc;
	}

	return HOVER_HAS_HOVERS;
}

/* create needed date here */
function sv_hover_create_data() {
	return (sv_hover_create_images() | sv_hover_create_hovers());
}

/* initialize our plugin here */
function sv_hover_header () {
	global $sv_hover_options, $sv_hover_behaviour, $wpdb;

	/* create data, skip js inclusion if no data exists */
	$result = sv_hover_create_data();
	if (!$result)
		return false;
	elseif ($result & HOVER_HAS_HOVERS) {
		/* register content filter */
		add_filter('the_content', 'sv_hover_get', 18);
		add_filter('widget_text', 'sv_hover_get', 18);
	}

	/* include java script of wanted */
	if ($sv_hover_options{'usejs'} == 1) {
		add_action('wp_footer', 'sv_hover_footer');

		echo '<script type="text/javascript" src="'.
			HOVER_BEHAVIOUR_URL.
			'/behaviour.js"></script>'."\n";

		echo '<script type="text/javascript" src="'.
			HOVER_DOMTT_URL.
			'/domLib.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.
			HOVER_DOMTT_URL.
			'/domTT.js"></script>'."\n";

		/* no need to include fading if it is not enabled */
		if ("neither" != $sv_hover_options{'fade'}) {
			echo '<script type="text/javascript" src="'.
				HOVER_DOMTT_URL.
				'/fadomatic.js"></script>'."\n";
		}

		/* we need to define or style regardless if we should include
		 * domTT or not since above we already checked for js support */
		echo '<script type="text/javascript">';
		echo 'var domTT_styleClass = \'hover\';';
		echo '</script>'."\n";

		printf('<script type="text/javascript" src="%s"></script>'.
			"\n", HOVER_BASE."/hover.js");
	}

	/* include css if wanted */
	if ($sv_hover_options{'usecss'}) {
		echo '<link type="text/css" rel="stylesheet" href="'.
			HOVER_BASE.'/hover.css" />'."\n";
	}
}

function sv_hover_footer_js() {
	global $sv_hover_options, $sv_hover_behaviour, $sv_hover_images;

	/* no need to include fading code if it is not enabled */
	if ("neither" != $sv_hover_options{fade})
		$fade .=
			"  ,'fade', '".$sv_hover_options{fade}."', ".
			"  'fadeMax', ".$sv_hover_options{fademax};
	else
		$fade = "";

	$line = "\n\n";

	if (count($sv_hover_images) > 0) {
		$line .= sprintf("var hover_image_map = { \n%s\n };\n\n",
			implode(",\n", $sv_hover_images));

		$line .= sprintf("hover_images(\"%s\");\n\n",
				"'trail', true ".$fade);
	}

	if ($sv_hover_options{replace}) {
		$line .= sprintf("hover_replaceTitlesByTags('%s', \"%s\");\n\n",
			$sv_hover_options{replace},
			"'trail', true ".$fade);
	}

        /* do not add javascript on empty replace array */
        if (sizeof($sv_hover_behaviour) == 0)
                return $line;

	$line .= 'var hover = {'."\n";

	foreach (array_keys($sv_hover_behaviour) as $b)
		$sv_hover_behaviour[$b] = 
			"'span.$b' : function(element) {\n".
			" element.onmouseover = function(event) {\n".
			"  domTT_activate(this, event, ".
			"  'content', '$sv_hover_behaviour[$b]', ".
			"  'trail', true ".
			$fade.
			"  );\n".
			" }"."\n".
			"}"."\n";

	$line .= implode(",", $sv_hover_behaviour);

	$line .= '};'."\n\n";
    	$line .= 'Behaviour.register(hover);'."\n";

	return $line;
}

function sv_hoover_footer_js_writefile() {
	$f = fopen(HOVER_JS_FILE, 'w');

	if (!$f) {
		echo "Could not open file: '".HOVER_JS_FILE."'";
		return false;
	}

	fwrite($f, sv_hover_footer_js());
	fclose($f);

	return true;
}

/* create data array for behaviour.js */
function sv_hover_footer($footer) {
	global $sv_hover_options;

	if ($sv_hover_options{'usefile'} && is_file(HOVER_JS_FILE)) {
		echo '<script type="text/javascript" src="'.
			HOVER_JS_URL.'/hover.js'.
			'"></script>'."\n";
	} else {
		/* we need to enclose our javascript code in CDATA tags
		 * since XHTML 1.0 Strict defines the contents of
		 * <script> as PCDATA which would us deny the
		 * possibility to use special html characters */
		echo '<script type="text/javascript">';
		echo '/* <![CDATA[ */';
		echo sv_hover_footer_js();
		echo '/* ]]> */';
		echo '</script>';
	}
}

/* called on plugin activation */
function sv_hover_install () {
	global $wpdb;

	@include('inc/activate.php');
}

if (is_admin()) {
	@include('inc/admin.php');

	# we pull in xajax here since it won't work from inc/admin.php
	require_once('xajax/xajax_core/xajax.inc.php');
} else {
	/* register head hook */
	add_action('wp_head', 'sv_hover_header');
}

/* register activation and options hooks */
register_activation_hook(WP_PLUGIN_DIR.'/hover/hover.php', 'sv_hover_install');

$sv_hover_options = get_option('SV_HOVER');
?>
