<?php

class update {
	public static function version() {
		// first time
		if (!$last = Config::meta('last_update_check')) {
			$last = static ::setup();
		}
		// was update in the last 30 days
		if (strtotime($last) < time() - (60 * 60 * 24 * 30)) {
			static ::renew();
		}
	}

	public static function setup() {
		$version = static ::touch();
		$today = date('Y-m-d H:i:s');
		$table = Base::table('meta');
		Query::table($table)->insert(['key' => 'last_update_check', 'value' => $today]);
		Query::table($table)->insert(['key' => 'update_version', 'value' => $version]);
		// reload database metadata
		foreach (Query::table($table)->get() as $item) {
			$meta[$item->key] = $item->value;
		}
		Config::set('meta', $meta);
	}

	public static function renew() {
		$version = static ::touch();
		$today = date('Y-m-d H:i:s');
		$table = Base::table('meta');
		Query::table($table)->where('key', '=', 'last_update_check')->update(['value' => $today]);
		Query::table($table)->where('key', '=', 'update_version')->update(['value' => $version]);
		// reload database metadata
		foreach (Query::table($table)->get() as $item) {
			$meta[$item->key] = $item->value;
		}
		Config::set('meta', $meta);
	}

	public static function touch() {
		$url = 'http://anchorcms.com/version';
		$result = false;
		$updateable = @fsockopen('http://anchorcms.com/version', 80);
		if ($updateable) {
			if (in_array(ini_get('allow_url_fopen'), ['true', '1', 'On'])) {
				$context = stream_context_create(['http' => ['timeout' => 2]]);
				$result = file_get_contents($url, false, $context);
			} elseif (function_exists('curl_init')) {
				$session = curl_init();
				curl_setopt_array($session, [CURLOPT_URL => $url, CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true]);
				$result = curl_exec($session);
				curl_close($session);
			}
		}

		return $result;
	}
}
