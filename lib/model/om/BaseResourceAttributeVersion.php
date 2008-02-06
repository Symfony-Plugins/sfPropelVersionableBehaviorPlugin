<?php


abstract class BaseResourceAttributeVersion extends BaseObject  implements Persistent {


	
	const DATABASE_NAME = 'ipc_communityboards';

	
	protected static $peer;


	
	protected $id;


	
	protected $resource_version_id;


	
	protected $attribute_name;


	
	protected $attribute_value;

	
	protected $aResourceVersion;

	
	protected $alreadyInSave = false;

	
	protected $alreadyInValidation = false;

	
	public function getId()
	{

		return $this->id;
	}

	
	public function getResourceVersionId()
	{

		return $this->resource_version_id;
	}

	
	public function getAttributeName()
	{

		return $this->attribute_name;
	}

	
	public function getAttributeValue()
	{

		return $this->attribute_value;
	}

	
	public function setId($v)
	{

		if ($this->id !== $v) {
			$this->id = $v;
			$this->modifiedColumns[] = ResourceAttributeVersionPeer::ID;
		}

	} 
	
	public function setResourceVersionId($v)
	{

		if ($this->resource_version_id !== $v) {
			$this->resource_version_id = $v;
			$this->modifiedColumns[] = ResourceAttributeVersionPeer::RESOURCE_VERSION_ID;
		}

		if ($this->aResourceVersion !== null && $this->aResourceVersion->getId() !== $v) {
			$this->aResourceVersion = null;
		}

	} 
	
	public function setAttributeName($v)
	{

		if ($this->attribute_name !== $v) {
			$this->attribute_name = $v;
			$this->modifiedColumns[] = ResourceAttributeVersionPeer::ATTRIBUTE_NAME;
		}

	} 
	
	public function setAttributeValue($v)
	{

		if ($this->attribute_value !== $v) {
			$this->attribute_value = $v;
			$this->modifiedColumns[] = ResourceAttributeVersionPeer::ATTRIBUTE_VALUE;
		}

	} 
	
	public function hydrate(ResultSet $rs, $startcol = 1)
	{
		try {

			$this->id = $rs->getInt($startcol + 0);

			$this->resource_version_id = $rs->getInt($startcol + 1);

			$this->attribute_name = $rs->getString($startcol + 2);

			$this->attribute_value = $rs->getString($startcol + 3);

			$this->resetModified();

			$this->setNew(false);

						return $startcol + 4; 
		} catch (Exception $e) {
			throw new PropelException("Error populating ResourceAttributeVersion object", $e);
		}
	}

	
	public function delete($con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceAttributeVersion:delete:pre') as $callable)
    {
      $ret = call_user_func($callable, $this, $con);
      if ($ret)
      {
        return;
      }
    }


		if ($this->isDeleted()) {
			throw new PropelException("This object has already been deleted.");
		}

		if ($con === null) {
			$con = Propel::getConnection(ResourceAttributeVersionPeer::DATABASE_NAME);
		}

		try {
			$con->begin();
			ResourceAttributeVersionPeer::doDelete($this, $con);
			$this->setDeleted(true);
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	

    foreach (sfMixer::getCallables('BaseResourceAttributeVersion:delete:post') as $callable)
    {
      call_user_func($callable, $this, $con);
    }

  }
	
	public function save($con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceAttributeVersion:save:pre') as $callable)
    {
      $affectedRows = call_user_func($callable, $this, $con);
      if (is_int($affectedRows))
      {
        return $affectedRows;
      }
    }


		if ($this->isDeleted()) {
			throw new PropelException("You cannot save an object that has been deleted.");
		}

		if ($con === null) {
			$con = Propel::getConnection(ResourceAttributeVersionPeer::DATABASE_NAME);
		}

		try {
			$con->begin();
			$affectedRows = $this->doSave($con);
			$con->commit();
    foreach (sfMixer::getCallables('BaseResourceAttributeVersion:save:post') as $callable)
    {
      call_user_func($callable, $this, $con, $affectedRows);
    }

			return $affectedRows;
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	}

	
	protected function doSave($con)
	{
		$affectedRows = 0; 		if (!$this->alreadyInSave) {
			$this->alreadyInSave = true;


												
			if ($this->aResourceVersion !== null) {
				if ($this->aResourceVersion->isModified()) {
					$affectedRows += $this->aResourceVersion->save($con);
				}
				$this->setResourceVersion($this->aResourceVersion);
			}


						if ($this->isModified()) {
				if ($this->isNew()) {
					$pk = ResourceAttributeVersionPeer::doInsert($this, $con);
					$affectedRows += 1; 										 										 
					$this->setId($pk);  
					$this->setNew(false);
				} else {
					$affectedRows += ResourceAttributeVersionPeer::doUpdate($this, $con);
				}
				$this->resetModified(); 			}

			$this->alreadyInSave = false;
		}
		return $affectedRows;
	} 
	
	protected $validationFailures = array();

	
	public function getValidationFailures()
	{
		return $this->validationFailures;
	}

	
	public function validate($columns = null)
	{
		$res = $this->doValidate($columns);
		if ($res === true) {
			$this->validationFailures = array();
			return true;
		} else {
			$this->validationFailures = $res;
			return false;
		}
	}

	
	protected function doValidate($columns = null)
	{
		if (!$this->alreadyInValidation) {
			$this->alreadyInValidation = true;
			$retval = null;

			$failureMap = array();


												
			if ($this->aResourceVersion !== null) {
				if (!$this->aResourceVersion->validate($columns)) {
					$failureMap = array_merge($failureMap, $this->aResourceVersion->getValidationFailures());
				}
			}


			if (($retval = ResourceAttributeVersionPeer::doValidate($this, $columns)) !== true) {
				$failureMap = array_merge($failureMap, $retval);
			}



			$this->alreadyInValidation = false;
		}

		return (!empty($failureMap) ? $failureMap : true);
	}

	
	public function getByName($name, $type = BasePeer::TYPE_PHPNAME)
	{
		$pos = ResourceAttributeVersionPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
		return $this->getByPosition($pos);
	}

	
	public function getByPosition($pos)
	{
		switch($pos) {
			case 0:
				return $this->getId();
				break;
			case 1:
				return $this->getResourceVersionId();
				break;
			case 2:
				return $this->getAttributeName();
				break;
			case 3:
				return $this->getAttributeValue();
				break;
			default:
				return null;
				break;
		} 	}

	
	public function toArray($keyType = BasePeer::TYPE_PHPNAME)
	{
		$keys = ResourceAttributeVersionPeer::getFieldNames($keyType);
		$result = array(
			$keys[0] => $this->getId(),
			$keys[1] => $this->getResourceVersionId(),
			$keys[2] => $this->getAttributeName(),
			$keys[3] => $this->getAttributeValue(),
		);
		return $result;
	}

	
	public function setByName($name, $value, $type = BasePeer::TYPE_PHPNAME)
	{
		$pos = ResourceAttributeVersionPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
		return $this->setByPosition($pos, $value);
	}

	
	public function setByPosition($pos, $value)
	{
		switch($pos) {
			case 0:
				$this->setId($value);
				break;
			case 1:
				$this->setResourceVersionId($value);
				break;
			case 2:
				$this->setAttributeName($value);
				break;
			case 3:
				$this->setAttributeValue($value);
				break;
		} 	}

	
	public function fromArray($arr, $keyType = BasePeer::TYPE_PHPNAME)
	{
		$keys = ResourceAttributeVersionPeer::getFieldNames($keyType);

		if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
		if (array_key_exists($keys[1], $arr)) $this->setResourceVersionId($arr[$keys[1]]);
		if (array_key_exists($keys[2], $arr)) $this->setAttributeName($arr[$keys[2]]);
		if (array_key_exists($keys[3], $arr)) $this->setAttributeValue($arr[$keys[3]]);
	}

	
	public function buildCriteria()
	{
		$criteria = new Criteria(ResourceAttributeVersionPeer::DATABASE_NAME);

		if ($this->isColumnModified(ResourceAttributeVersionPeer::ID)) $criteria->add(ResourceAttributeVersionPeer::ID, $this->id);
		if ($this->isColumnModified(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID)) $criteria->add(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, $this->resource_version_id);
		if ($this->isColumnModified(ResourceAttributeVersionPeer::ATTRIBUTE_NAME)) $criteria->add(ResourceAttributeVersionPeer::ATTRIBUTE_NAME, $this->attribute_name);
		if ($this->isColumnModified(ResourceAttributeVersionPeer::ATTRIBUTE_VALUE)) $criteria->add(ResourceAttributeVersionPeer::ATTRIBUTE_VALUE, $this->attribute_value);

		return $criteria;
	}

	
	public function buildPkeyCriteria()
	{
		$criteria = new Criteria(ResourceAttributeVersionPeer::DATABASE_NAME);

		$criteria->add(ResourceAttributeVersionPeer::ID, $this->id);

		return $criteria;
	}

	
	public function getPrimaryKey()
	{
		return $this->getId();
	}

	
	public function setPrimaryKey($key)
	{
		$this->setId($key);
	}

	
	public function copyInto($copyObj, $deepCopy = false)
	{

		$copyObj->setResourceVersionId($this->resource_version_id);

		$copyObj->setAttributeName($this->attribute_name);

		$copyObj->setAttributeValue($this->attribute_value);


		$copyObj->setNew(true);

		$copyObj->setId(NULL); 
	}

	
	public function copy($deepCopy = false)
	{
				$clazz = get_class($this);
		$copyObj = new $clazz();
		$this->copyInto($copyObj, $deepCopy);
		return $copyObj;
	}

	
	public function getPeer()
	{
		if (self::$peer === null) {
			self::$peer = new ResourceAttributeVersionPeer();
		}
		return self::$peer;
	}

	
	public function setResourceVersion($v)
	{


		if ($v === null) {
			$this->setResourceVersionId(NULL);
		} else {
			$this->setResourceVersionId($v->getId());
		}


		$this->aResourceVersion = $v;
	}


	
	public function getResourceVersion($con = null)
	{
				include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/om/BaseResourceVersionPeer.php';

		if ($this->aResourceVersion === null && ($this->resource_version_id !== null)) {

			$this->aResourceVersion = ResourceVersionPeer::retrieveByPK($this->resource_version_id, $con);

			
		}
		return $this->aResourceVersion;
	}


  public function __call($method, $parameters)
  {
    if (!$callable = sfMixer::getCallable('BaseResourceAttributeVersion:'.$method))
    {
      throw new sfException(sprintf('Call to undefined method BaseResourceAttributeVersion::%s', $method));
    }

    array_unshift($arguments, $this);

    return call_user_func_array($callable, $arguments);
  }


} 