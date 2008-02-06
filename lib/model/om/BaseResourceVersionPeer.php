<?php


abstract class BaseResourceVersionPeer {

	
	const DATABASE_NAME = 'propel';

	
	const TABLE_NAME = 'resource_version';

	
	const CLASS_DEFAULT = 'plugins.sfPropelVersionableBehaviorPlugin.lib.model.ResourceVersion';

	
	const NUM_COLUMNS = 4;

	
	const NUM_LAZY_LOAD_COLUMNS = 0;


	
	const ID = 'resource_version.ID';

	
	const NUMBER = 'resource_version.NUMBER';

	
	const RESOURCE_UUID = 'resource_version.RESOURCE_UUID';

	
	const RESOURCE_NAME = 'resource_version.RESOURCE_NAME';

	
	private static $phpNameMap = null;


	
	private static $fieldNames = array (
		BasePeer::TYPE_PHPNAME => array ('Id', 'Number', 'ResourceUuid', 'ResourceName', ),
		BasePeer::TYPE_COLNAME => array (ResourceVersionPeer::ID, ResourceVersionPeer::NUMBER, ResourceVersionPeer::RESOURCE_UUID, ResourceVersionPeer::RESOURCE_NAME, ),
		BasePeer::TYPE_FIELDNAME => array ('id', 'number', 'resource_uuid', 'resource_name', ),
		BasePeer::TYPE_NUM => array (0, 1, 2, 3, )
	);

	
	private static $fieldKeys = array (
		BasePeer::TYPE_PHPNAME => array ('Id' => 0, 'Number' => 1, 'ResourceUuid' => 2, 'ResourceName' => 3, ),
		BasePeer::TYPE_COLNAME => array (ResourceVersionPeer::ID => 0, ResourceVersionPeer::NUMBER => 1, ResourceVersionPeer::RESOURCE_UUID => 2, ResourceVersionPeer::RESOURCE_NAME => 3, ),
		BasePeer::TYPE_FIELDNAME => array ('id' => 0, 'number' => 1, 'resource_uuid' => 2, 'resource_name' => 3, ),
		BasePeer::TYPE_NUM => array (0, 1, 2, 3, )
	);

	
	public static function getMapBuilder()
	{
		include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/map/ResourceVersionMapBuilder.php';
		return BasePeer::getMapBuilder('plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceVersionMapBuilder');
	}
	
	public static function getPhpNameMap()
	{
		if (self::$phpNameMap === null) {
			$map = ResourceVersionPeer::getTableMap();
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
		return str_replace(ResourceVersionPeer::TABLE_NAME.'.', $alias.'.', $column);
	}

	
	public static function addSelectColumns(Criteria $criteria)
	{

		$criteria->addSelectColumn(ResourceVersionPeer::ID);

		$criteria->addSelectColumn(ResourceVersionPeer::NUMBER);

		$criteria->addSelectColumn(ResourceVersionPeer::RESOURCE_UUID);

		$criteria->addSelectColumn(ResourceVersionPeer::RESOURCE_NAME);

	}

	const COUNT = 'COUNT(resource_version.ID)';
	const COUNT_DISTINCT = 'COUNT(DISTINCT resource_version.ID)';

	
	public static function doCount(Criteria $criteria, $distinct = false, $con = null)
	{
				$criteria = clone $criteria;

				$criteria->clearSelectColumns()->clearOrderByColumns();
		if ($distinct || in_array(Criteria::DISTINCT, $criteria->getSelectModifiers())) {
			$criteria->addSelectColumn(ResourceVersionPeer::COUNT_DISTINCT);
		} else {
			$criteria->addSelectColumn(ResourceVersionPeer::COUNT);
		}

				foreach($criteria->getGroupByColumns() as $column)
		{
			$criteria->addSelectColumn($column);
		}

		$rs = ResourceVersionPeer::doSelectRS($criteria, $con);
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
		$objects = ResourceVersionPeer::doSelect($critcopy, $con);
		if ($objects) {
			return $objects[0];
		}
		return null;
	}
	
	public static function doSelect(Criteria $criteria, $con = null)
	{
		return ResourceVersionPeer::populateObjects(ResourceVersionPeer::doSelectRS($criteria, $con));
	}
	
	public static function doSelectRS(Criteria $criteria, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceVersionPeer:addDoSelectRS:addDoSelectRS') as $callable)
    {
      call_user_func($callable, 'BaseResourceVersionPeer', $criteria, $con);
    }


		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

		if (!$criteria->getSelectColumns()) {
			$criteria = clone $criteria;
			ResourceVersionPeer::addSelectColumns($criteria);
		}

				$criteria->setDbName(self::DATABASE_NAME);

						return BasePeer::doSelect($criteria, $con);
	}
	
	public static function populateObjects(ResultSet $rs)
	{
		$results = array();
	
				$cls = ResourceVersionPeer::getOMClass();
		$cls = Propel::import($cls);
				while($rs->next()) {
		
			$obj = new $cls();
			$obj->hydrate($rs);
			$results[] = $obj;
			
		}
		return $results;
	}
	
	public static function getTableMap()
	{
		return Propel::getDatabaseMap(self::DATABASE_NAME)->getTable(self::TABLE_NAME);
	}

	
	public static function getOMClass()
	{
		return ResourceVersionPeer::CLASS_DEFAULT;
	}

	
	public static function doInsert($values, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceVersionPeer:doInsert:pre') as $callable)
    {
      $ret = call_user_func($callable, 'BaseResourceVersionPeer', $values, $con);
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

		$criteria->remove(ResourceVersionPeer::ID); 

				$criteria->setDbName(self::DATABASE_NAME);

		try {
									$con->begin();
			$pk = BasePeer::doInsert($criteria, $con);
			$con->commit();
		} catch(PropelException $e) {
			$con->rollback();
			throw $e;
		}

		
    foreach (sfMixer::getCallables('BaseResourceVersionPeer:doInsert:post') as $callable)
    {
      call_user_func($callable, 'BaseResourceVersionPeer', $values, $con, $pk);
    }

    return $pk;
	}

	
	public static function doUpdate($values, $con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceVersionPeer:doUpdate:pre') as $callable)
    {
      $ret = call_user_func($callable, 'BaseResourceVersionPeer', $values, $con);
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
			$comparison = $criteria->getComparison(ResourceVersionPeer::ID);
			$selectCriteria->add(ResourceVersionPeer::ID, $criteria->remove(ResourceVersionPeer::ID), $comparison);

			$comparison = $criteria->getComparison(ResourceVersionPeer::NUMBER);
			$selectCriteria->add(ResourceVersionPeer::NUMBER, $criteria->remove(ResourceVersionPeer::NUMBER), $comparison);

		} else { 			$criteria = $values->buildCriteria(); 			$selectCriteria = $values->buildPkeyCriteria(); 		}

				$criteria->setDbName(self::DATABASE_NAME);

		$ret = BasePeer::doUpdate($selectCriteria, $criteria, $con);
	

    foreach (sfMixer::getCallables('BaseResourceVersionPeer:doUpdate:post') as $callable)
    {
      call_user_func($callable, 'BaseResourceVersionPeer', $values, $con, $ret);
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
			$affectedRows += ResourceVersionPeer::doOnDeleteCascade(new Criteria(), $con);
			$affectedRows += BasePeer::doDeleteAll(ResourceVersionPeer::TABLE_NAME, $con);
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
			$con = Propel::getConnection(ResourceVersionPeer::DATABASE_NAME);
		}

		if ($values instanceof Criteria) {
			$criteria = clone $values; 		} elseif ($values instanceof ResourceVersion) {

			$criteria = $values->buildPkeyCriteria();
		} else {
						$criteria = new Criteria(self::DATABASE_NAME);
												if(count($values) == count($values, COUNT_RECURSIVE))
			{
								$values = array($values);
			}
			$vals = array();
			foreach($values as $value)
			{

				$vals[0][] = $value[0];
				$vals[1][] = $value[1];
			}

			$criteria->add(ResourceVersionPeer::ID, $vals[0], Criteria::IN);
			$criteria->add(ResourceVersionPeer::NUMBER, $vals[1], Criteria::IN);
		}

				$criteria->setDbName(self::DATABASE_NAME);

		$affectedRows = 0; 
		try {
									$con->begin();
			$affectedRows += ResourceVersionPeer::doOnDeleteCascade($criteria, $con);
			$affectedRows += BasePeer::doDelete($criteria, $con);
			$con->commit();
			return $affectedRows;
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	}

	
	protected static function doOnDeleteCascade(Criteria $criteria, Connection $con)
	{
				$affectedRows = 0;

				$objects = ResourceVersionPeer::doSelect($criteria, $con);
		foreach($objects as $obj) {


			include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/ResourceAttributeVersion.php';

						$c = new Criteria();
			
			$c->add(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, $obj->getId());
			$affectedRows += ResourceAttributeVersionPeer::doDelete($c, $con);
		}
		return $affectedRows;
	}

	
	public static function doValidate(ResourceVersion $obj, $cols = null)
	{
		$columns = array();

		if ($cols) {
			$dbMap = Propel::getDatabaseMap(ResourceVersionPeer::DATABASE_NAME);
			$tableMap = $dbMap->getTable(ResourceVersionPeer::TABLE_NAME);

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

		$res =  BasePeer::doValidate(ResourceVersionPeer::DATABASE_NAME, ResourceVersionPeer::TABLE_NAME, $columns);
    if ($res !== true) {
        $request = sfContext::getInstance()->getRequest();
        foreach ($res as $failed) {
            $col = ResourceVersionPeer::translateFieldname($failed->getColumn(), BasePeer::TYPE_COLNAME, BasePeer::TYPE_PHPNAME);
            $request->setError($col, $failed->getMessage());
        }
    }

    return $res;
	}

	
	public static function retrieveByPK( $id, $number, $con = null) {
		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}
		$criteria = new Criteria();
		$criteria->add(ResourceVersionPeer::ID, $id);
		$criteria->add(ResourceVersionPeer::NUMBER, $number);
		$v = ResourceVersionPeer::doSelect($criteria, $con);

		return !empty($v) ? $v[0] : null;
	}
} 
if (Propel::isInit()) {
			try {
		BaseResourceVersionPeer::getMapBuilder();
	} catch (Exception $e) {
		Propel::log('Could not initialize Peer: ' . $e->getMessage(), Propel::LOG_ERR);
	}
} else {
			require_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/map/ResourceVersionMapBuilder.php';
	Propel::registerMapBuilder('plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceVersionMapBuilder');
}
