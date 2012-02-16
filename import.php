<?php
/*
Plugin Name: Import skvara.cz
Plugin URI: http://www.skvara.cz
Description: This pulgin import articles from site skvara.cz
Author: Jakub Škvára
Version: 1.0
Author URI: http://www.jakubskvara.cz/
License: GPLv2 or later
*/

register_activation_hook(__FILE__, "install");
function install() {
}

register_deactivation_hook(__FILE__, "uninstall");
function uninstall() {
}

$skvImporter = new SkvImporter();
add_action("admin_menu", array($skvImporter, "addAdminMenu"));

require_once __DIR__ ."/String.php";

/**
 * Importer class
 */
class SkvImporter {
	
	public function addAdminMenu() {
		add_options_page(__("Skvara importer", "skv-importer"), __("Skvara importer", "skv-importer"), 
			"manage_options", "skv-importer", array($this, "pluginOptions"));
	}

	public function pluginOptions() {
		// if (!current_user_can("manage_options"))  {
		// 	wp_die( __("You do not have sufficient permissions to access this page.") );
		// }

		$url = "http://www.skvara.cz/skvara-financni-poradenstvi/investice/imesicnik022012~.html";
		$this->getArticle($url);

		include_once __DIR__ ."/templates/index.php";
	}

	protected function getArticle($url) {
		$oldSetting = libxml_use_internal_errors(true);
		libxml_clear_errors();

		$dom = new DOMDocument();
		$dom->loadHtmlFile($url);

		$xpath = new DOMXPath($dom);

		$titleQuery = "/html/body/table/tr[3]/td[2]/table/tr/td[2]/table/tr/td/table//span";
		$titleNodes = $xpath->query($titleQuery);
		$title = $titleNodes->item(0)->nodeValue;
		echo "Title: ". $title;

		$contentQuery = "/html/body/table/tr[3]/td[2]/table/tr/td[2]/table/tr/td/table/tr[3]/td";
		$contentNodes = $xpath->query($contentQuery);
		$contentNode = $contentNodes->item(0);//->nodeValue;
		$children = $contentNode->childNodes; 
		foreach ($children as $child) { 
			$content .= $child->ownerDocument->saveXML( $child ); 
		}
		$content = $this->tidy($content);
		// echo "Content: ". $content;
		echo "Content: ". $content;
		
		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);

		$this->insertPost($title, $content, "", "");
	}

	protected function insertPost($title, $content, $author, $category/*, $date*/) {
		$name = String::webalize($title);
		$post = array(
			'comment_status' => 'closed',
			'ping_status' => 'open',
			'post_author' => $author,
			'post_category' => array($category),
			'post_content' => $content,
//			'post_date' => [ Y-m-d H:i:s ],
			'post_name' => $name, // slug
			'post_status' => 'publish',
			'post_title' => $title,
			'post_type' => 'post',
		);
		var_export($post);
		// $return = wp_insert_post($post, $wp_error);

		// if (is_wp_error($return)) {
		// 	throw new Exception($return->get_error_message());
		// }
	}

	protected function tidy($string) {
		if (!extension_loaded('tidy')) {
			return $string;
		}

		$tidy = new tidy();
		$string = $tidy->repairString($string);

		return $tidy;
	}

	//"http://www.skvara.cz/skvara-financni-poradenstvi/investice/index.html"
	protected function getContent($url) {
		$oldSetting = libxml_use_internal_errors(true);
		libxml_clear_errors();
	
		$dom = new DOMDocument();
		$dom->loadHtmlFile($url);

		$xpath = new DOMXPath($dom);
		$articles = $xpath->query('/html/body/table/tr[3]/td[2]/table/tr/td[2]/table/tr/td/table');
	
		$actual = "";
		$i = 0;
		foreach($articles as $article) {
			// title
			$title = $article->childNodes->item(1)->nodeValue;
			if( mb_strlen($title) > 40 ) {
				$title = mb_substr($title, 0, 40, "utf-8")."...";
			}
		
			// href
			$div = $article->childNodes->item(2)->firstChild->childNodes;
			$href = $div->item((max($div->length-2, 0)))->firstChild->getAttribute("href");
			$actual .= "<li><a href=\"http://www.skvara.cz".$href."\">- ".$title."</a></li>\n";
		}
		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);
	}
}


// __(text, domain)

// if(!wp_next_scheduled("my_task_hook")) {
// 	wp_schedule_event( time(), "hourly", "my_task_hook" );
// }
// add_action("my_task_hook", "my_task_function");

// function my_task_function() {
// 	wp_mail( "your@email.com", "Automatic email", "Automatic scheduled email from WordPress.");
// }