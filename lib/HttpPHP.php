<?php
/**
 * Helper class for cURL operations.
 *
 * @package ZodaPop
 */
class HttpPHP {
	/**
	 * Fetch the specified http request via cURL.
	 */
	public static function send( $url, $method, $params = null ) {
		if (!extension_loaded( 'curl' )) {
			$this->error = 'cURL extension not loaded.';
			return false;
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_VERBOSE, 0 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		switch ($method) {
			case 'POST' :
				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
				//curl_setopt( $ch, CURLOPT_HTTPHEADER, array ("Content-Type: application/x-www-form-urlencoded\n" ));
				break;
			case 'GET' :
			default :
				break;
		}
		
		$response = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ($http_code >= 400) {
			trigger_error( 'HTTP Error Code: ' . $http_code );
		}
		
		if (!$response) {
			$this->errno = curl_errno( $ch );
			$this->error = curl_error( $ch );
			curl_close( $ch );
		}
		
		curl_close( $ch );
		
		return $response;
	}
}
?>