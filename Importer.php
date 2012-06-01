<?php

require_once dirname(__FILE__) ."/String.php";

class Importer {

	public function import($url) {
		$imported = array();

		$urls = $this->getArticlesUrl($url);
		foreach($urls as $url) {
			$content = $this->getArticleContent($url);

			if ($this->articleExists($content["title"])) {
				continue;
			}

			$this->insertPost($content["title"], $content["content"], 1, 8/*investice*/);
			$imported[] = $content["title"];
		}

		return $imported;
	}

	protected function articleExists($title) {
		global $wpdb;

		$prep = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->posts . 
			" WHERE post_status = 'publish' AND post_title = %s", $title);
		$count = $wpdb->get_var($prep);
		
		return (bool) $count;
	}

	protected function getArticlesUrl($url) {
		$urls = array();
	
		$oldSetting = libxml_use_internal_errors(true);
		libxml_clear_errors();
	
		$dom = new DOMDocument();
		$dom->loadHtmlFile($url);

		$xpath = new DOMXPath($dom);
		$articles = $xpath->query('/html/body/form/table/tr[3]/td[2]/table/tr/td[2]/table/tr/td/table');
	
		foreach($articles as $article) {
			$div = $article->childNodes->item(1)->childNodes;

			$div = $article->childNodes->item(2)->firstChild->childNodes;
			if (is_object($div->item((max($div->length-2, 0)))->firstChild)) {
				$href = $div->item((max($div->length-2, 0)))->firstChild->getAttribute("href");
				$urls[] = $href;
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);

		return $urls;
	}

	protected function getArticleContent($url) {
		$content = array(
			"title" => "",
			"content" => "",
		);
		$oldSetting = libxml_use_internal_errors(true);
		libxml_clear_errors();
	
		$dom = new DOMDocument();
		$dom->loadHtmlFile($url);

		$xpath = new DOMXPath($dom);
		$articles = $xpath->query('/html/body/form/table/tr[3]/td[2]/table/tr/td[2]/table/tr/td/table');
		
		foreach ($articles as $article) {
			$content["title"] = $article->childNodes->item(2)->firstChild->nodeValue;
			
			$c = $article->childNodes->item(4)->firstChild;
			$content["content"] = $dom->saveXML($c);
		}

		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);

		return $content;
	}

	protected function insertPost($title, $content, $author, $category) {
		$name = String::webalize($title);
		$post = array(
			'comment_status' => 'closed',
			'ping_status' => 'open',
			'post_author' => $author,
			'post_category' => array($category),
			'post_content' => $content,
			'post_date' => date("Y-m-d H:i:s"),
			'post_name' => $name, // slug
			'post_status' => 'publish',
			'post_title' => $title,
			'post_type' => 'post',
		);
		$return = wp_insert_post($post);

		if (is_wp_error($return)) {
			throw new Exception($return->get_error_message());
		}
	}

	protected function tidy($string) {
		if (!extension_loaded('tidy')) {
			return $string;
		}

		$tidy = new tidy();
		$string = $tidy->repairString($string);

		return $tidy;
	}
}