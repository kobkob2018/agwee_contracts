<?php
/**
 * @package  AgweeContractsPlugin
 */
class AgweeContractsPluginDeactivate
{
	public static function deactivate() {
		flush_rewrite_rules();
	}
}