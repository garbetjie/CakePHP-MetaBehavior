<?php
App::uses('Model', 'Model');
class MetaBehavior extends ModelBehavior {
/**
 * The base model to use when saving and retrieving Meta Data.
 * @var Model
 */
	private $Model;
	
/**
 * Constructs a base model to be used when saving information.
 */
	public function __construct () {
		// Populate the model to use.
		$this->Model = new Model(false, 'meta_data');
		$this->Model->alias = 'MetaData';
		
		// Call the parent's constructor.
		parent::__construct();
	}
	
/**
 * Array of settings used.
 * @var array
 */
	public $settings = array();
	
/**
 * Creates settings for each model. Options currently available:
 * 
 *   'callbacks': Can be true, false or an array containing 'find' and/or 'save'.
 *                These callbacks will hook into the save and find methods, to determine
 *                whether meta data will be brought back automatically.
 *                Default - true
 * 
 *   'table'    : The name of the table to use for the meta data for this model.
 *                Default - meta_data
 * 
 *   'alias'    : The alias to use in save and find data for this model.
 *                Default - MetaData
 * 
 * @param Model $Model
 * @param array $settings An array of the settings used.
 */
	public function setup (Model $Model, $settings = array()) {
		// Set defaults.
		$this->settings[$Model->alias] = array('callbacks' => array('find', 'save'), 'table' => 'meta_data', 'alias' => 'MetaData', 'cache' => false);
		
		// Check for callbacks.
		if (isset($settings['callbacks'])) {
			if (is_string($settings['callbacks'])) {
				$this->settings[$Model->alias]['callbacks'] = array($settings['callbacks']);
			} else if ($settings['callbacks'] === false) {
				$this->settings[$Model->alias]['callbacks'] = array();
			}
		}
		
		// Check for table name.
		if (isset($settings['table']) && is_string($settings['table'])) {
			$this->settings[$Model->alias]['table'] = $settings['table'];
		}
		
		// Check for data index.
		if (isset($settings['alias']) && is_string($settings['alias'])) {
			$this->settings[$Model->alias]['alias'] = $settings['alias'];
		}
		
		// Check for initialized cache configuration.
		if (isset($settings['cache']) && Cache::isInitialized($settings['cache'])) {
			$this->settings[$Model->alias]['cache'] = $settings['cache'];
		}
	}
	
/**
 * Allows for models to call the saveMeta method directly.
 * @param Model $Model
 * @param array|string $one An array containing keys and values to save, or a string name of a value to save.
 * @param string $two The value to save when $one is a string.
 * @return boolean
 */
	public function saveMeta (Model $Model, $one, $two = null) {
		// Ensure the model has an id.
		if (!$Model->id) {
			return false;
		}
		
		// Format to an array.
		if (!is_array($one)) {
			$one = array($one => $two);
		}
		
		// Save data.
		return $this->__save($Model, $one);
	}
	
/**
 * Performs the actual saving of the data. Receives the Model for which we're saving
 * the meta data, as well as the data to be save [ in the format array('key' => 'val') ].
 * @param Model $Model
 * @param array $data The data to save.
 * @return boolean
 */
	private function __save (Model $Model, $data) {
		// Set the table to use.
		$this->Model->useTable = $this->settings[$Model->alias]['table'];
		
		// First, find IDs of meta data items to update.
		$existing = $this->Model->find('list', array(
			'conditions' => array(
				"{$this->Model->alias}.rel_model" => $Model->alias,
				"{$this->Model->alias}.rel_id" => $Model->id,
			),
			'fields' => array('name', 'id'),
			'recursive' => -1,
		));
		
		// Build save data.
		$saveData = array();
		foreach ($data as $k => $v) {
			$id = (isset($existing[$k]) ? $existing[$k] : null);
			$rel_model = $Model->alias;
			$rel_id = $Model->id;
			$name = $k;
			$value = $v;
			$saveData[] = compact('id', 'rel_model', 'rel_id', 'name', 'value');
		}
		
		// Save the data.
		$this->Model->create();
		$return = $this->Model->saveAll($saveData);
		
		// Clear the cache.
		if ($this->settings[$Model->alias]['cache']) {
			
		}
		
		// Return the initial value.
		return $return;
	}
	
/**
 * Finds the specified (or not) meta data assigned to the current model. There are
 * multiple ways the meta data can be found.
 * 
 * 1. A single field [$Model->meta('field')] can be specified. The value (or FALSE
 *    if it is not found) will be returned.
 * 
 * 2. An array of fields [$Model->meta(array('field1', 'field2'))] can be specified.
 *    An array containing the values indexed by fields will be returned. If the
 *    specified field does not exist, it will not be returned in the results.
 * 
 * 3. Multiple arguments can be passed [$Model->meta('field1', 'field2')]. This
 *    yields the same results as passing an array.
 * 
 * 4. No fields can be passed. This will cause all the assigned meta data to come
 *    back.
 * 
 * @param Model $Model
 * @return string|array|boolean
 */
	public function meta (Model $Model) {
		// Get and format the arguments.
		$args = func_get_args();
		array_shift($args);
		
		// Determine how we are fetching the data.
		$isSingle = false;
		if (count($args) == 1 && is_string($args[0])) {
			$isSingle = true;
			$fetch = array($args[0]);
		} else if (count($args) == 0) {
			$fetch = null;
		} else if (is_array($args[0])) {
			$fetch = $args[0];
		} else {
			foreach ($args as $k => $v) {
				if (!is_string($v)) {
					unset($args[$k]);
				}
			}
			$fetch = array_values($args);
		}
		
		// Ensure there is an id.
		if (!$Model->id) {
			return ($isSingle ? false : array());
		}
		
		// Find the items.
		$items = $this->__find($Model->alias, $Model->id);
		
		// Format return value.
		if ($isSingle) {
			return isset($items[$args[0]]) ? $items[$args[0]] : false;
		}
		return $items;
	}
	
/**
 * Finds the values for the specified model and id.
 * @param string $relModel The model's alias to find meta data for.
 * @param mixed $relId The id of the model to find meta data for.
 * @param array $fetch An optional array of fields to find data for.
 * @return array An array of the meta data items.
 */
	private function __find ($relModel, $relId) {
		// Ensure there is an id.
		if (!$relId) {
			return array();
		}
		
		// Check whether we'll be fetch data from the cache.
		if (!empty($this->settings[$relModel]['cache'])) {
			// Build up the cache key.
			$cacheKey = "{$relModel}:{$relId}";
			
			// Check for cached data.
			$cacheData = Cache::read($cacheKey, $this->settings[$relModel]['cache']);
			if ($cacheData !== false) {
				return $cacheData;
			}
		}
		
		// Build find conditions and find items.
		$conditions = array("{$this->Model->alias}.rel_model" => $relModel, "{$this->Model->alias}.rel_id" => $relId);
		$recursive = -1;
		$fields = array('name', 'value');
		$return = $this->Model->find('list', compact('conditions', 'recursive', 'fields'));
		
		// Cache data if necessary.
		if (!empty($this->settings[$relModel]['cache'])) {
			Cache::write($cacheKey, $return, $this->settings[$relModel]['cache']);
		}
		
		// Return the data.
		return $return;
	}
	
/**
 * afterSave callback. Used to hook into the saving of MetaData when saving models
 * normally.
 * @param Model $Model
 * @param boolean $created
 * @return boolean
 */
	public function afterSave (Model $Model, $created) {
		// Check whether we should be saving data.
		if (!in_array('save', $this->settings[$Model->alias]['callbacks'])) {
			return true;
		}
		
		// Ensure there is data.
		if (empty($Model->data[$this->settings[$Model->alias]['alias']])) {
			return true;
		}
		
		// Ensure we have an ID.
		if (!$Model->id) {
			return true;
		}
		
		// Perform the save.
		return $this->__save($Model, $Model->data[$this->settings[$Model->alias]['alias']]);
	}

/**
 * Populates the result data with meta data. This is only done for the direct model.
 * @see ModelBehavior::afterFind().
 * @return mixed The modified $results. 
 */
	public function afterFind (Model $Model, $results, $primary) {
		// Check whether we should be appending the data.
		if (!in_array('find', $this->settings[$Model->alias]['callbacks']) || !$results) {
			return $results;
		}
		
		// Cycle through results, and find each of them.
		foreach ($results as $index => $result) {
			// Can't find the results. This shouldn't ever come about really.
			if (!isset($result[$Model->alias])) {
				$results[$index][$this->settings[$Model->alias]['alias']] = array();
				continue;
			}
			
			// Populate the data.
			$data = $this->__find($Model->alias, $result[$Model->alias][$Model->primaryKey]);
			$results[$index][$this->settings[$Model->alias]['alias']] = $data;
		}
		
		// Return modified results.
		return $results;
	}
}