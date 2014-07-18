<?php  namespace Fawesome\Meetup;

use Exception;
use Fawesome\Http\Passthrough;

class Caller
{
	protected $api_key;
	protected $group;
	protected $passthrough;
	protected $root_path = 'http://api.meetup.com/2/';

	public function __construct($api_key, Group $group, Passthrough $passthrough)
	{
		$this->group = $group;
		$this->passthrough = $passthrough;
		$this->api_key = $api_key;
	}

	/**
	 * Get upcoming events
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function getUpcomingEvents()
	{
		$path = 'events?group_id=' . $this->group->getGroupId() . '&status=upcoming&page=20&order=time&desc=true';
		return $this->passthroughUrl($path);
	}

	/**
	 * Get all past and upcoming events
	 *
	 * @return mixed
	 * @throws \Exception If API key not set
	 */
	public function getEvents()
	{
		$path = 'events?group_id=' . $this->group->getGroupId() . '&page=20&status=upcoming,past&order=time&desc=true';
		return $this->passthroughUrl($path);
	}

	/**
	 * Get a specific event by ID
	 *
	 * @param $event_id
	 * @return mixed
	 * @throws \Exception If API key not set
	 */
	public function getEvent($event_id)
	{
		$path = 'event/' . $event_id . '?&page=1';
		return $this->passthroughUrl($path);
	}

	/**
	 * Given a path, assemble the full URL and then get it from Passthrough
	 *
	 * @param $path
	 * @return mixed
	 * @throws \Exception If API key not set
	 */
	protected function passthroughUrl($path)
	{
		if ($this->api_key === null) {
			throw new Exception('Cannot connect to Meetup API without API key set');
		}
		$result = $this->passthrough->getFromUrl($this->root_path . $path . '&key=' . $this->api_key . '&format=json&photo-host=public');
		return $this->stripApiKeyFromMeta($result);
	}

	/**
	 * API Key is returned in the result. Strip it out, in case you're allowing others to access this
	 *
	 * @param $result
	 */
	protected function stripApiKeyFromMeta($result)
	{
		$body = json_decode($result['body']);

		if ( ! property_exists($body, 'meta')) {
			return $result;
		}

		$body->meta->url = str_replace($this->api_key, 'OBFUSCATED_API_KEY', $body->meta->url);

		$result['body'] = json_encode($body);

		return $result;
	}
} 
