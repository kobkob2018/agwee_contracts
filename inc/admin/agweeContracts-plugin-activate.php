<?php
/**
 * @package  AgweeContractsPlugin
 */
class AgweeContractsActivate
{
	public static function activate() {
		flush_rewrite_rules();
	}
}