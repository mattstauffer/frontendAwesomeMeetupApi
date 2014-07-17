<?php

	/*
	|--------------------------------------------------------------------------
	| Application Routes
	|--------------------------------------------------------------------------
	|
	| Here is where you can register all of the routes for an application.
	| It's a breeze. Simply tell Laravel the URIs it should respond to
	| and give it the Closure to execute when that URI is requested.
	|
	*/

	define('MEETUP_API_KEY', getenv('MEETUP_API_KEY'));
	define('CACHE_LENGTH', 60);
	define('GROUP_ID', 11851552);

	if ( ! function_exists('http_parse_headers'))
	{
		function http_parse_headers($raw_headers)
		{
			$headers = array();
			$key = ''; // [+]

			foreach(explode("\n", $raw_headers) as $i => $h)
			{
				$h = explode(':', $h, 2);

				if (isset($h[1]))
				{
					if (!isset($headers[$h[0]]))
						$headers[$h[0]] = trim($h[1]);
					elseif (is_array($headers[$h[0]]))
					{
						// $tmp = array_merge($headers[$h[0]], array(trim($h[1]))); // [-]
						// $headers[$h[0]] = $tmp; // [-]
						$headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
					}
					else
					{
						// $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [-]
						// $headers[$h[0]] = $tmp; // [-]
						$headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
					}

					$key = $h[0]; // [+]
				}
				else // [+]
				{ // [+]
					if (substr($h[0], 0, 1) == "\t") // [+]
						$headers[$key] .= "\r\n\t".trim($h[0]); // [+]
					elseif (!$key) // [+]
						$headers[0] = trim($h[0]);trim($h[0]); // [+]
				} // [+]
			}

			return $headers;
		}
	}

	if ( ! function_exists('curl_passthrough')) {
		/**
		 * Pass through a curl call and get header and body
		 *
		 * @todo make class
		 * @param $path
		 * @return array [
		 *                  array 'header'
		 *                  string 'body'
		 *                  ]
		 */
		function curl_passthrough($path)
		{
			$base_url = 'http://api.meetup.com/';
			$url = $base_url . $path;

			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);

			$response = curl_exec($ch);

			// Get headers
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = http_parse_headers(substr($response, 0, $header_size));
			$body = substr($response, $header_size);

			curl_close($ch);

			return array(
				'header' => $header,
				'body' => $body
			);
		}
	}

	function pathToResponse($path)
	{
		$response = curl_passthrough($path);
		extract($response);

		$statusCode = 200;

		$response = Response::make($body, $statusCode);
		$response->header('Content-Type', $header['Content-Type']);

		return $response;
	}



	// @todo: real Laravel CORS handling
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: X-Requested-With');

	Route::group(array('prefix' => 'api'), function() {
		Route::get('/meetup/events', function () {
			$path = '2/events?group_id=' . GROUP_ID . '&status=upcoming&order=time&limited_events=False&desc=false&offset=0&photo-host=public&format=json&page=20&fields=&key=' . MEETUP_API_KEY;

			if (Cache::has($path)) {
				return Cache::get($path);
			}

			// @todo: is caching a full response object good? Probably not.
			$response = pathToResponse($path);

			Cache::put($path, $response, CACHE_LENGTH);

			return $response;
		});


		Route::get('/meetup/events/{event_id}', function ($event_id) {
			// @todo cache
			$path = '2/event/' . $event_id . '?&photo-host=public&page=20&key=' . MEETUP_API_KEY;

			if (Cache::has($path)) {
				return Cache::get($path);
			}

			// @todo: is caching a full response object good? Probably not.
			$response = pathToResponse($path);

			Cache::put($path, $response, CACHE_LENGTH);

			return $response;
		});
	});
