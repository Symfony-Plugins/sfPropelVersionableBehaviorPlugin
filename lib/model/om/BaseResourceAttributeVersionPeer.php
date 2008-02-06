<?php


abstract class BaseResourceAttributeVersionPeer {

	
	const DATABASE_NAME = 'ipc_communityboards';

	
	const TABLE_NAME = 'resource_attribute_version';

	
	const CLASS_DEFAULT = 'plugins.sfPropelVersionableBehaviorPlugin.lib.model.ResourceAttributeVersion';

	
	const NUM_COLUMNS = 4;

	
	const NUM_LAZY_LOAD_COLUMNS = 0;


	
	const ID = 'resource_attribute_version.ID';

	
	const RESOURCE_VERSION_ID = 'resource_attribute_version.RESOURCE_VERSION_ID';

	
	const ATTRIBUTE_NAME = 'resource_attribute_version.ATTRIBUTE_NAME';

	
	const ATTRIBUTE_VALUE = 'resource_attribute_version.ATTRIBUTE_VALUE';

	
	private static $phpNameMap = null;


	
	private static $fieldNames = array (
		BasePeer::TYPE_PHPNAME => array ('Id', 'ResourceVersionId', 'AttributeName', 'AttributeValue', ),
		BasePeer::TYPE_COLNAME => array (ResourceAttributeVersionPeer::ID, ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceAttributeVersionPeer::ATTRIBUTE_NAME, ResourceAttributeVersionPeer::ATTRIBUTE_VALUE, ),
		BasePeer::TYPE_FIELDNAME => array ('id', 'resource_version_id', 'attribute_name', 'attribute_value', ),
		BasePeer::TYPE_NUM => array (0, 1, 2, 3, )
	);

	
	private static $fieldKeys = array (
		BasePeer::TYPE_PHPNAME => array ('Id' => 0, 'ResourceVersionId' => 1, 'AttributeName' => 2, 'AttributeValue' => 3, ),
		BasePeer::TYPE_COLNAME => array (ResourceAttributeVersionPeer::ID => 0, ResourceAttributeVersionPeer::RESOURCE_VERSION_ID => 1, ResourceAttributeVersionPeer::ATTRIBUTE_NAME => 2, ResourceAttributeVersionPeer::ATTRIBUTE_VALUE => 3, ),
		BasePeer::TYPE_FIELDNAME => array ('id' => 0, 'resource_version_id' => 1, 'attribute_name' => 2, 'attribute_value' => 3, ),
		BasePeer::TYPE_NUM => array (0, 1, 2, 3, )
	);

	
	public static function getMapBuilder()
	{
		include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/map/ResourceAttributeVersionMapBuilder.php';
		return BasePeer::getMapBuilder('plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceAttributeVersionMapBuilder');
	}
	
	public static function getPhpNameMap()
	{
		if (self::$phpNameMap === null) {
			$map = ResourceAttributeVersionPeer::getTableMap();
			$columns = $map->getColumns();
			$nameMap = array();
			foreach ($columns as $column) {
				$nameMap[$column->getPhpName()] = $column->getColumnName();
			}
			self::$phpNameMap = $nameMap;
		}
		return self::$phpNameMap;
	}
	
	static public function translateFieldName($name, $fromType, $toType)
	{
		$toNames = self::getFieldNames($toType);
		$key = isset(self::$fieldKeys[$fromType][$name]) ? self::$fieldKeys[$fromType][$name] : null;
		if ($key === null) {
			throw new PropelException("'$name' could not be found in the field names of type '$fromType'. These are: " . print_r(self::$fieldKeys[$fromType], true));
		}
		return $toNames[$key];
	}

	

	static public function getFieldNames($type = BasePeer::TYPE_PHPNAME)
	{
		if (!array_key_exists($type, self::$fieldNames)) {
			throw new PropelException('Method getFieldNames() expects the parameter $type to be one of the class constants TYPE_PHPNAME, TYPE_COLNAME, TYPE_FIELDNAME, TYPE_NUM. ' . $type . ' was given.');
		}
		return self::$fieldNames[$type];
	}

	
	public static function alias($alias, $column)
	{
		return str_replace(ResourceAttributeVersionPeer::TABLE_NAME.'.', $alias.'.', $column);
	}

	
	public static function addSelectColumns(Criteria $criteria)
	{

		$criteria->addSelectColumn(ResourceAttributeVersionPeer::ID);

		$criteria->addSelectColumn(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID);

		$criteria->addSelectColumn(ResourceAttributeVersionPeer::ATTRIBUTE_NAME);

		$criteria->addSelectColumn(ResourceAttributeVersionPeer::ATTRIBUTE_VALUE);

	}

	const COUNT = 'COUNT(resource_attribute_version.ID)';
	const COUNT_DISTINCT = 'COUNT(DISTINCT resource_attribute_version.ID)';

	
	public static function doCount(Criteria $criteria, $distinct = false, $con = null)
	{
				$criteria = clone $criteria;

				$criteria->clearSelectColumns()->clearOrderByColumns();
		if ($distinct || in_array(Criteria::DISTINCT, $criteria->getSelectModifiers())) {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT_DISTINCT);
		} else {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT);
		}

				foreach($criteria->getGroupByColumns() as $column)
		{
			$criteria->addSelectColumn($column);
		}

		$rs = ResourceAttributeVersionPeer::doSelectRS($criteria, $con);
		if ($rs->next()) {
			return $rs->getInt(1);
		} else {
						return 0;
		}
	}
	
	public static function doSelectOne(Criteria $criteria, $con = null)
	{
		$critcopy = clone $criteria;
		$critcopy->setLimit(1);
		$objects = ResourceAttributeVersionPeer::doSelect($critcopy, $con);
		if ($objects) {
			return $objects[0];
		}
		return null;
	}
	
	public static function doSelect(Criteria $criteria, $con = null)
	{
		return ResourceAttributeVersionPeer::populateObjects(ResourceAttributeVersionPeer::doSelectRS($criteria, $con));
	}
	
	public static function doSelectRS(Criteria $criteria, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceAttributeVersionPeer:addDoSelectRS:addDoSelectRS') as $callable)
    {
      call_user_func($callable, 'BaseResourceAttributeVersionPeer', $criteria, $con);
    }


		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		if (!$criteria->getSelectColumns()) {
			$criteria = clone $criteria;
			ResourceAttributeVersionPeer::addSelectColumns($criteria);
		}

				$criteria->setDbName(self::DATABASE_NAME);

						return BasePeer::doSelect($criteria, $con);
	}
	
	public static function populateObjects(ResultSet $rs)
	{
		$results = array();
	
				$cls = ResourceAttributeVersionPeer::getOMClass();
		$cls = Propel::import($cls);
				while($rs->next()) {
		
			$obj = new $cls();
			$obj->hydrate($rs);
			$results[] = $obj;
			
		}
		return $results;
	}

	
	public static function doCountJoinResourceVersion(Criteria $criteria, $distinct = false, $con = null)
	{
				$criteria = clone $criteria;
		
				$criteria->clearSelectColumns()->clearOrderByColumns();
		if ($distinct || in_array(Criteria::DISTINCT, $criteria->getSelectModifiers())) {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT_DISTINCT);
		} else {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT);
		}
		
				foreach($criteria->getGroupByColumns() as $column)
		{
			$criteria->addSelectColumn($column);
		}

		$criteria->addJoin(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceVersionPeer::ID);

		$rs = ResourceAttributeVersionPeer::doSelectRS($criteria, $con);
		if ($rs->next()) {
			return $rs->getInt(1);
		} else {
						return 0;
		}
	}


	
	public static function doSelectJoinResourceVersion(Criteria $c, $con = null)
	{
		$c = clone $c;

				if ($c->getDbName() == Propel::getDefaultDB()) {
			$c->setDbName(self::DATABASE_NAME);
		}

		ResourceAttributeVersionPeer::addSelectColumns($c);
		$startcol = (ResourceAttributeVersionPeer::NUM_COLUMNS - ResourceAttributeVersionPeer::NUM_LAZY_LOAD_COLUMNS) + 1;
		ResourceVersionPeer::addSelectColumns($c);

		$c->addJoin(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceVersionPeer::ID);
		$rs = BasePeer::doSelect($c, $con);
		$results = array();

		while($rs->next()) {

			$omClass = ResourceAttributeVersionPeer::getOMClass();

			$cls = Propel::import($omClass);
			$obj1 = new $cls();
			$obj1->hydrate($rs);

			$omClass = ResourceVersionPeer::getOMClass();

			$cls = Propel::import($omClass);
			$obj2 = new $cls();
			$obj2->hydrate($rs, $startcol);

			$newObject = true;
			foreach($results as $temp_obj1) {
				$temp_obj2 = $temp_obj1->getResourceVersion(); 				if ($temp_obj2->getPrimaryKey() === $obj2->getPrimaryKey()) {
					$newObject = false;
										$temp_obj2->addResourceAttributeVersion($obj1); 					break;
				}
			}
			if ($newObject) {
				$obj2->initResourceAttributeVersions();
				$obj2->addResourceAttributeVersion($obj1); 			}
			$results[] = $obj1;
		}
		return $results;
	}


	
	public static function doCountJoinAll(Criteria $criteria, $distinct = false, $con = null)
	{
		$criteria = clone $criteria;

				$criteria->clearSelectColumns()->clearOrderByColumns();
		if ($distinct || in_array(Criteria::DISTINCT, $criteria->getSelectModifiers())) {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT_DISTINCT);
		} else {
			$criteria->addSelectColumn(ResourceAttributeVersionPeer::COUNT);
		}
		
				foreach($criteria->getGroupByColumns() as $column)
		{
			$criteria->addSelectColumn($column);
		}

		$criteria->addJoin(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceVersionPeer::ID);

		$rs = ResourceAttributeVersionPeer::doSelectRS($criteria, $con);
		if ($rs->next()) {
			return $rs->getInt(1);
		} else {
						return 0;
		}
	}


	
	public static function doSelectJoinAll(Criteria $c, $con = null)
	{
		$c = clone $c;

				if ($c->getDbName() == Propel::getDefaultDB()) {
			$c->setDbName(self::DATABASE_NAME);
		}

		ResourceAttributeVersionPeer::addSelectColumns($c);
		$startcol2 = (ResourceAttributeVersionPeer::NUM_COLUMNS - ResourceAttributeVersionPeer::NUM_LAZY_LOAD_COLUMNS) + 1;

		ResourceVersionPeer::addSelectColumns($c);
		$startcol3 = $startcol2 + ResourceVersionPeer::NUM_COLUMNS;

		$c->addJoin(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceVersionPeer::ID);

		$rs = BasePeer::doSelect($c, $con);
		$results = array();
		
		while($rs->next()) {

			$omClass = ResourceAttributeVersionPeer::getOMClass();

			
			$cls = Propel::import($omClass);
			$obj1 = new $cls();
			$obj1->hydrate($rs);

				
					
			$omClass = ResourceVersionPeer::getOMClass();

	
			$cls = Propel::import($omClass);
			$obj2 = new $cls();
			$obj2->hydrate($rs, $startcol2);
			
			$newObject = true;
			for ($j=0, $resCount=count($results); $j < $resCount; $j++) {
				$temp_obj1 = $results[$j];
				$temp_obj2 = $temp_obj1->getResourceVersion(); 				if ($temp_obj2->getPrimaryKey() === $obj2->getPrimaryKey()) {
					$newObject = false;
					$temp_obj2->addResourceAttributeVersion($obj1); 					break;
				}
			}
			
			if ($newObject) {
				$obj2->initResourceAttributeVersions();
				$obj2->addResourceAttributeVersion($obj1);
			}

			$results[] = $obj1;
		}
		return $results;
	}

	
	public static function getTableMap()
	{
		return Propel::getDatabaseMap(self::DATABASE_NAME)->getTable(self::TABLE_NAME);
	}

	
	public static function getOMClass()
	{
		return ResourceAttributeVersionPeer::CLASS_DEFAULT;
	}

	
	public static function doInsert($values, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceAttributeVersionPeer:doInsert:pre') as $callable)
    {
      $ret = call_user_func($callable, 'BaseResourceAttributeVersionPeer', $values, $con);
      if (false !== $ret)
      {
        return $ret;
      }
    }


		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		if ($values instanceof Criteria) {
			$criteria = clone $values; 		} else {
			$criteria = $values->buildCriteria(); 		}

		$criteria->remove(ResourceAttributeVersionPeer::ID); 

				$criteria->setDbName(self::DATABASE_NAME);

		try {
									$con->begin();
			$pk = BasePeer::doInsert($criteria, $con);
			$con->commit();
		} catch(PropelException $e) {
			$con->rollback();
			throw $e;
		}

		
    foreach (sfMixer::getCallables('BaseResourceAttributeVersionPeer:doInsert:post') as $callable)
    {
      call_user_func($callable, 'BaseResourceAttributeVersionPeer', $values, $con, $pk);
    }

    return $pk;
	}

	
	public static function doUpdate($values, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceAttributeVersionPeer:doUpdate:pre') as $callable)
    {
      $ret = call_user_func($callable, 'BaseResourceAttributeVersionPeer', $values, $con);
      if (false !== $ret)
      {
        return $ret;
      }
    }


		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		$selectCriteria = new Criteria(self::DATABASE_NAME);

		if ($values instanceof Criteria) {
			$criteria = clone $values; 
			$comparison = $criteria->getComparison(ResourceAttributeVersionPeer::ID);
			$selectCriteria->add(ResourceAttributeVersionPeer::ID, $criteria->remove(ResourceAttributeVersionPeer::ID), $comparison);

		} else { 			$criteria = $values->buildCriteria(); 			$selectCriteria = $values->buildPkeyCriteria(); 		}

				$criteria->setDbName(self::DATABASE_NAME);

		$ret = BasePeer::doUpdate($selectCriteria, $criteria, $con);
	

    foreach (sfMixer::getCallables('BaseResourceAttributeVersionPeer:doUpdate:post') as $callable)
    {
      call_user_func($callable, 'BaseResourceAttributeVersionPeer', $values, $con, $ret);
    }

    return $ret;
  }

	
	public static function doDeleteAll($con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}
		$affectedRows = 0; 		try {
									$con->begin();
			$affectedRows += BasePeer::doDeleteAll(ResourceAttributeVersionPeer::TABLE_NAME, $con);
			$con->commit();
			return $affectedRows;
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	}

	
	 public static function doDelete($values, $con = null)
	 {
		if ($con === null) {
			$con = Propel::getConnection(ResourceAttributeVersionPeer::DATABASE_NAME);
		}

		if ($values instanceof Criteria) {
			$criteria = clone $values; 		} elseif ($values instanceof ResourceAttributeVersion) {

			$criteria = $values->buildPkeyCriteria();
		} else {
						$criteria = new Criteria(self::DATABASE_NAME);
			$criteria->add(ResourceAttributeVersionPeer::ID, (array) $values, Criteria::IN);
		}

				$criteria->setDbName(self::DATABASE_NAME);

		$affectedRows = 0; 
		try {
									$con->begin();
			
			$affectedRows += BasePeer::doDelete($criteria, $con);
			$con->commit();
			return $affectedRows;
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	}

	
	public static function doValidate(ResourceAttributeVersion $obj, $cols = null)
	{
		$columns = array();

		if ($cols) {
			$dbMap = Propel::getDatabaseMap(ResourceAttributeVersionPeer::DATABASE_NAME);
			$tableMap = $dbMap->getTable(ResourceAttributeVersionPeer::TABLE_NAME);

			if (! is_array($cols)) {
				$cols = array($cols);
			}

			foreach($cols as $colName) {
				if ($tableMap->containsColumn($colName)) {
					$get = 'get' . $tableMap->getColumn($colName)->getPhpName();
					$columns[$colName] = $obj->$get();
				}
			}
		} else {

		}

		$res =  BasePeer::doValidate(ResourceAttributeVersionPeer::DATABASE_NAME, ResourceAttributeVersionPeer::TABLE_NAME, $columns);
    if ($res !== true) {
        $request = sfContext::getInstance()->getRequest();
        foreach ($res as $failed) {
            $col = ResourceAttributeVersionPeer::translateFieldname($failed->getColumn(), BasePeer::TYPE_COLNAME, BasePeer::TYPE_PHPNAME);
            $request->setError($col, $failed->getMessage());
        }
    }

    return $res;
	}

	
	public static function retrieveByPK($pk, $con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		$criteria = new Criteria(ResourceAttributeVersionPeer::DATABASE_NAME);

		$criteria->add(ResourceAttributeVersionPeer::ID, $pk);


		$v = ResourceAttributeVersionPeer::doSelect($criteria, $con);

		return !empty($v) > 0 ? $v[0] : null;
	}

	
	public static function retrieveByPKs($pks, $con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		$objs = null;
		if (empty($pks)) {
			$objs = array();
		} else {
			$criteria = new Criteria();
			$criteria->add(ResourceAttributeVersionPeer::ID, $pks, Criteria::IN);
			$objs = ResourceAttributeVersionPeer::doSelect($criteria, $con);
		}
		return $objs;
	}

} 
if (Propel::isInit()) {
			try {
		BaseResourceAttributeVersionPeer::getMapBuilder();
	} catch (Exception $e) {
		Propel::log('Could not initialize Peer: ' . $e->getMessage(), Propel::LOG_ERR);
	}
} else {
			require_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/map/ResourceAttributeVersionMapBuilder.php';
	Propel::registerMapBuilder('plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceAttributeVersionMapBuilder');
}
