<?php  namespace Fawesome\Http;

use Illuminate\Cache\Repository;

class Passthrough
{
	/**
	 * @var int Cache length in minutes
	 */
	protected $cache_length = 60;
	/**
	 * @var Repository
	 */
	private $cache;

	public function __construct(Repository $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Set cache length
	 *
	 * @param int $length Length to cache in minutes
	 */
	public function setCacheLength($length)
	{
		$this->cache_length = (int) $length;
	}

	/**
	 * Get a full Curl request from a URL
	 *
	 * @param $url
	 * @return array|mixed
	 */
	public function getFromUrl($url)
	{
		if ($this->cache->has($url)) {
			return $this->cache->get($url);
		} else {
			$result = $this->curlPath($url);
			$this->cache->put($url, $result, $this->cache_length);
			return $result;
		}
	}

	/**
	 * Pass through a curl call and get header and body
	 *
	 * @param $url
	 * @return array [
	 *                  array 'header'
	 *                  string 'body'
	 *               ]
	 */
	protected function curlPath($url)
	{
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);

		$response = curl_exec($ch);

		// Get headers
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = $this->httpParseHeaders(substr($response, 0, $header_size));
		$body = substr($response, $header_size);

		curl_close($ch);

		return array(
			'header' => $header,
			'body' => $body
		);
	}

	/**
	 * Mimic http_parse_headers
	 *
	 * @source http://php.net/manual/en/function.http-parse-headers.php#112986
	 * @param $raw_headers
	 * @return array
	 */
	protected function httpParseHeaders($raw_headers)
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
