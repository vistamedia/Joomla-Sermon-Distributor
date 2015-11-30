<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.2.9
	@build			30th November, 2015
	@created		22nd October, 2015
	@package		Sermon Distributor
	@subpackage		statistics.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Statistics Model
 */
class SermondistributorModelStatistics extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
        {
			$config['filter_fields'] = array(
				'a.id','id',
				'a.published','published',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.filename','filename',
				'a.sermon','sermon',
				'a.preacher','preacher',
				'a.series','series',
				'a.counter','counter'
			);
		}

		parent::__construct($config);
	}
	
	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}
		$filename = $this->getUserStateFromRequest($this->context . '.filter.filename', 'filter_filename');
		$this->setState('filter.filename', $filename);

		$sermon = $this->getUserStateFromRequest($this->context . '.filter.sermon', 'filter_sermon');
		$this->setState('filter.sermon', $sermon);

		$preacher = $this->getUserStateFromRequest($this->context . '.filter.preacher', 'filter_preacher');
		$this->setState('filter.preacher', $preacher);

		$series = $this->getUserStateFromRequest($this->context . '.filter.series', 'filter_series');
		$this->setState('filter.series', $series);

		$counter = $this->getUserStateFromRequest($this->context . '.filter.counter', 'filter_counter');
		$this->setState('filter.counter', $counter);
        
		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);
        
		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);
        
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
        
		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		// List state information.
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{ 
		// [10502] check in items
		$this->checkInNow();

		// load parent items
		$items = parent::getItems();

		// [10577] set values to display correctly.
		if (SermondistributorHelper::checkArray($items))
		{
			// [10580] get user object.
			$user = JFactory::getUser();
			foreach ($items as $nr => &$item)
			{
				$access = ($user->authorise('statistic.access', 'com_sermondistributor.statistic.' . (int) $item->id) && $user->authorise('statistic.access', 'com_sermondistributor'));
				if (!$access)
				{
					unset($items[$nr]);
					continue;
				}

			}
		} 
        
		// return items
		return $items;
	}
	
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// [7363] Get the user object.
		$user = JFactory::getUser();
		// [7365] Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// [7368] Select some fields
		$query->select('a.*');

		// [7375] From the sermondistributor_item table
		$query->from($db->quoteName('#__sermondistributor_statistic', 'a'));

		// [7516] From the sermondistributor_sermon table.
		$query->select($db->quoteName('g.name','sermon_name'));
		$query->join('LEFT', $db->quoteName('#__sermondistributor_sermon', 'g') . ' ON (' . $db->quoteName('a.sermon') . ' = ' . $db->quoteName('g.id') . ')');

		// [7516] From the sermondistributor_preacher table.
		$query->select($db->quoteName('h.name','preacher_name'));
		$query->join('LEFT', $db->quoteName('#__sermondistributor_preacher', 'h') . ' ON (' . $db->quoteName('a.preacher') . ' = ' . $db->quoteName('h.id') . ')');

		// [7516] From the sermondistributor_series table.
		$query->select($db->quoteName('i.name','series_name'));
		$query->join('LEFT', $db->quoteName('#__sermondistributor_series', 'i') . ' ON (' . $db->quoteName('a.series') . ' = ' . $db->quoteName('i.id') . ')');

		// [7389] Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published = 0 OR a.published = 1)');
		}

		// [7401] Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
		// [7404] Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where('a.access = ' . (int) $access);
		}
		// [7409] Implement View Level Access
		if (!$user->authorise('core.options', 'com_sermondistributor'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');
		}
		// [7486] Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.filename LIKE '.$search.' OR a.sermon LIKE '.$search.' OR g.name LIKE '.$search.' OR a.preacher LIKE '.$search.' OR h.name LIKE '.$search.' OR a.series LIKE '.$search.' OR i.name LIKE '.$search.')');
			}
		}

		// [7720] Filter by sermon.
		if ($sermon = $this->getState('filter.sermon'))
		{
			$query->where('a.sermon = ' . $db->quote($db->escape($sermon, true)));
		}
		// [7720] Filter by preacher.
		if ($preacher = $this->getState('filter.preacher'))
		{
			$query->where('a.preacher = ' . $db->quote($db->escape($preacher, true)));
		}
		// [7720] Filter by series.
		if ($series = $this->getState('filter.series'))
		{
			$query->where('a.series = ' . $db->quote($db->escape($series, true)));
		}

		// [7445] Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'asc');	
		if ($orderCol != '')
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	* Method to get list export data.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExportData($pks)
	{
		// [7153] setup the query
		if (SermondistributorHelper::checkArray($pks))
		{
			// [7156] Get the user object.
			$user = JFactory::getUser();
			// [7158] Create a new query object.
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			// [7161] Select some fields
			$query->select('a.*');

			// [7163] From the sermondistributor_statistic table
			$query->from($db->quoteName('#__sermondistributor_statistic', 'a'));
			$query->where('a.id IN (' . implode(',',$pks) . ')');
			// [7173] Implement View Level Access
			if (!$user->authorise('core.options', 'com_sermondistributor'))
			{
				$groups = implode(',', $user->getAuthorisedViewLevels());
				$query->where('a.access IN (' . $groups . ')');
			}

			// [7180] Order the results by ordering
			$query->order('a.ordering  ASC');

			// [7182] Load the items
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				$items = $db->loadObjectList();

				// [10577] set values to display correctly.
				if (SermondistributorHelper::checkArray($items))
				{
					// [10580] get user object.
					$user = JFactory::getUser();
					foreach ($items as $nr => &$item)
					{
						$access = ($user->authorise('statistic.access', 'com_sermondistributor.statistic.' . (int) $item->id) && $user->authorise('statistic.access', 'com_sermondistributor'));
						if (!$access)
						{
							unset($items[$nr]);
							continue;
						}

						// [10790] unset the values we don't want exported.
						unset($item->asset_id);
						unset($item->checked_out);
						unset($item->checked_out_time);
					}
				}
				// [10799] Add headers to items array.
				$headers = $this->getExImPortHeaders();
				if (SermondistributorHelper::checkObject($headers))
				{
					array_unshift($items,$headers);
				}
				return $items;
			}
		}
		return false;
	}

	/**
	* Method to get header.
	*
	* @return mixed  An array of data items on success, false on failure.
	*/
	public function getExImPortHeaders()
	{
		// [7202] Get a db connection.
		$db = JFactory::getDbo();
		// [7204] get the columns
		$columns = $db->getTableColumns("#__sermondistributor_statistic");
		if (SermondistributorHelper::checkArray($columns))
		{
			// [7208] remove the headers you don't import/export.
			unset($columns['asset_id']);
			unset($columns['checked_out']);
			unset($columns['checked_out_time']);
			$headers = new stdClass();
			foreach ($columns as $column => $type)
			{
				$headers->{$column} = $column;
			}
			return $headers;
		}
		return false;
	} 
	
	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * @return  string  A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// [10125] Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.filename');
		$id .= ':' . $this->getState('filter.sermon');
		$id .= ':' . $this->getState('filter.preacher');
		$id .= ':' . $this->getState('filter.series');
		$id .= ':' . $this->getState('filter.counter');

		return parent::getStoreId($id);
	}

	/**
	* Build an SQL query to checkin all items left checked out longer then a set time.
	*
	* @return  a bool
	*
	*/
	protected function checkInNow()
	{
		// [10518] Get set check in time
		$time = JComponentHelper::getParams('com_sermondistributor')->get('check_in');
		
		if ($time)
		{

			// [10523] Get a db connection.
			$db = JFactory::getDbo();
			// [10525] reset query
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__sermondistributor_statistic'));
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows())
			{
				// [10533] Get Yesterdays date
				$date = JFactory::getDate()->modify($time)->toSql();
				// [10535] reset query
				$query = $db->getQuery(true);

				// [10537] Fields to update.
				$fields = array(
					$db->quoteName('checked_out_time') . '=\'0000-00-00 00:00:00\'',
					$db->quoteName('checked_out') . '=0'
				);

				// [10542] Conditions for which records should be updated.
				$conditions = array(
					$db->quoteName('checked_out') . '!=0', 
					$db->quoteName('checked_out_time') . '<\''.$date.'\''
				);

				// [10547] Check table
				$query->update($db->quoteName('#__sermondistributor_statistic'))->set($fields)->where($conditions); 

				$db->setQuery($query);

				$db->execute();
			}
		}

		return false;
	}
}