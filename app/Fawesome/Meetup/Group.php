<?php  namespace Fawesome\Meetup;

class Group
{
	protected $group_id;
	protected $group_url_key;

	public function __construct($group_id, $group_url_key)
	{
		$this->group_id = $group_id;
		$this->group_url_key = $group_url_key;
	}

	/**
	 * Get group ID
	 *
	 * @return mixed
	 */
	public function getGroupId()
	{
		return $this->group_id;
	}

	/**
	 * Get group URL key
	 *
	 * @return mixed
	 */
	public function getGroupUrlKey()
	{
		return $this->group_url_key;
	}
} 
