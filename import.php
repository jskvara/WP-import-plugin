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

error_reporting(E_ALL);

$skvaraPlugin = new SkvaraPlugin();
$skvaraPlugin->bootstrap();

require_once dirname(__FILE__) ."/Importer.php";

/**
 * General controller class
 */
class SkvaraPlugin {
	const URL = "http://www.skvara.cz/investice/index.html";

	public function bootstrap() {
		register_activation_hook(__FILE__, array($this, "install"));
		register_deactivation_hook(__FILE__, array($this, "uninstall"));

		add_action("admin_menu", array($this, "addAdminMenu"));

		if(!wp_next_scheduled("importer_hook")) {
			wp_schedule_event(current_time("timestamp"), "daily", "importer_hook");
		}
		add_action("importer_hook", array($this, "importHook"));
	}

	protected function install() {}
	protected function uninstall() {}
	
	public function addAdminMenu() {
		add_options_page(__("Skvara importer", "skv-importer"), __("Skvara importer", "skv-importer"), 
			"manage_options", "skv-importer", array($this, "pluginOptions"));
	}

	protected function importHook() {
		try {
			$importer = new Importer();
			$imported = $importer->import(self::URL);

			$title = "skvaraPlugin - import";
			if (empty($imported)) {
				$message = "No new articles.";
			} else {
				$message = implode("\n", $imported);
			}
		} catch (Exception $e) {
			$title = "skvaraPlugin - import failed";
			$message = $e->getMessage();
		}

		wp_mail("jskvara@gmail.com", $title, $message);
	}

	public function pluginOptions() {
		if (!current_user_can("manage_options")) {
			// __(text, domain)
			wp_die(__("You do not have sufficient permissions to access this page."));
		}

		try {
			if (isset($_POST["submit"])) {
				$tpl["imported"] = array();
				$importer = new Importer();
				$tpl["imported"] = $importer->import(self::URL);
			}
		} catch (Exception $e) {
			$tpl["exception"] = $e;
		}

		include_once dirname(__FILE__) ."/templates/index.php";
	}
}
