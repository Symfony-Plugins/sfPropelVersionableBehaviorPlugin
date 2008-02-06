<?php


abstract class BaseResourceVersion extends BaseObject  implements Persistent {


	
	const DATABASE_NAME = 'ipc_communityboards';

	
	protected static $peer;


	
	protected $id;


	
	protected $number;


	
	protected $resource_uuid;


	
	protected $resource_name;

	
	protected $aPost;

	
	protected $collResourceAttributeVersions;

	
	protected $lastResourceAttributeVersionCriteria = null;

	
	protected $alreadyInSave = false;

	
	protected $alreadyInValidation = false;

	
	public function getId()
	{

		return $this->id;
	}

	
	public function getNumber()
	{

		return $this->number;
	}

	
	public function getResourceUuid()
	{

		return $this->resource_uuid;
	}

	
	public function getResourceName()
	{

		return $this->resource_name;
	}

	
	public function setId($v)
	{

		if ($this->id !== $v) {
			$this->id = $v;
			$this->modifiedColumns[] = ResourceVersionPeer::ID;
		}

	} 
	
	public function setNumber($v)
	{

		if ($this->number !== $v) {
			$this->number = $v;
			$this->modifiedColumns[] = ResourceVersionPeer::NUMBER;
		}

	} 
	
	public function setResourceUuid($v)
	{

		if ($this->resource_uuid !== $v) {
			$this->resource_uuid = $v;
			$this->modifiedColumns[] = ResourceVersionPeer::RESOURCE_UUID;
		}

		if ($this->aPost !== null && $this->aPost->getUuid() !== $v) {
			$this->aPost = null;
		}

	} 
	
	public function setResourceName($v)
	{

		if ($this->resource_name !== $v) {
			$this->resource_name = $v;
			$this->modifiedColumns[] = ResourceVersionPeer::RESOURCE_NAME;
		}

	} 
	
	public function hydrate(ResultSet $rs, $startcol = 1)
	{
		try {

			$this->id = $rs->getInt($startcol + 0);

			$this->number = $rs->getInt($startcol + 1);

			$this->resource_uuid = $rs->getString($startcol + 2);

			$this->resource_name = $rs->getString($startcol + 3);

			$this->resetModified();

			$this->setNew(false);

						return $startcol + 4; 
		} catch (Exception $e) {
			throw new PropelException("Error populating ResourceVersion object", $e);
		}
	}

	
	public function delete($con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceVersion:delete:pre') as $callable)
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
			$con = Propel::getConnection(ResourceVersionPeer::DATABASE_NAME);
		}

		try {
			$con->begin();
			ResourceVersionPeer::doDelete($this, $con);
			$this->setDeleted(true);
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
	

    foreach (sfMixer::getCallables('BaseResourceVersion:delete:post') as $callable)
    {
      call_user_func($callable, $this, $con);
    }

  }
	
	public function save($con = null)
	{

    foreach (sfMixer::getCallables('BaseResourceVersion:save:pre') as $callable)
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
			$con = Propel::getConnection(ResourceVersionPeer::DATABASE_NAME);
		}

		try {
			$con->begin();
			$affectedRows = $this->doSave($con);
			$con->commit();
    foreach (sfMixer::getCallables('BaseResourceVersion:save:post') as $callable)
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


												
			if ($this->aPost !== null) {
				if ($this->aPost->isModified()) {
					$affectedRows += $this->aPost->save($con);
				}
				$this->setPost($this->aPost);
			}


						if ($this->isModified()) {
				if ($this->isNew()) {
					$pk = ResourceVersionPeer::doInsert($this, $con);
					$affectedRows += 1; 										 										 
					$this->setId($pk);  
					$this->setNew(false);
				} else {
					$affectedRows += ResourceVersionPeer::doUpdate($this, $con);
				}
				$this->resetModified(); 			}

			if ($this->collResourceAttributeVersions !== null) {
				foreach($this->collResourceAttributeVersions as $referrerFK) {
					if (!$referrerFK->isDeleted()) {
						$affectedRows += $referrerFK->save($con);
					}
				}
			}

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


												
			if ($this->aPost !== null) {
				if (!$this->aPost->validate($columns)) {
					$failureMap = array_merge($failureMap, $this->aPost->getValidationFailures());
				}
			}


			if (($retval = ResourceVersionPeer::doValidate($this, $columns)) !== true) {
				$failureMap = array_merge($failureMap, $retval);
			}


				if ($this->collResourceAttributeVersions !== null) {
					foreach($this->collResourceAttributeVersions as $referrerFK) {
						if (!$referrerFK->validate($columns)) {
							$failureMap = array_merge($failureMap, $referrerFK->getValidationFailures());
						}
					}
				}


			$this->alreadyInValidation = false;
		}

		return (!empty($failureMap) ? $failureMap : true);
	}

	
	public function getByName($name, $type = BasePeer::TYPE_PHPNAME)
	{
		$pos = ResourceVersionPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
		return $this->getByPosition($pos);
	}

	
	public function getByPosition($pos)
	{
		switch($pos) {
			case 0:
				return $this->getId();
				break;
			case 1:
				return $this->getNumber();
				break;
			case 2:
				return $this->getResourceUuid();
				break;
			case 3:
				return $this->getResourceName();
				break;
			default:
				return null;
				break;
		} 	}

	
	public function toArray($keyType = BasePeer::TYPE_PHPNAME)
	{
		$keys = ResourceVersionPeer::getFieldNames($keyType);
		$result = array(
			$keys[0] => $this->getId(),
			$keys[1] => $this->getNumber(),
			$keys[2] => $this->getResourceUuid(),
			$keys[3] => $this->getResourceName(),
		);
		return $result;
	}

	
	public function setByName($name, $value, $type = BasePeer::TYPE_PHPNAME)
	{
		$pos = ResourceVersionPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
		return $this->setByPosition($pos, $value);
	}

	
	public function setByPosition($pos, $value)
	{
		switch($pos) {
			case 0:
				$this->setId($value);
				break;
			case 1:
				$this->setNumber($value);
				break;
			case 2:
				$this->setResourceUuid($value);
				break;
			case 3:
				$this->setResourceName($value);
				break;
		} 	}

	
	public function fromArray($arr, $keyType = BasePeer::TYPE_PHPNAME)
	{
		$keys = ResourceVersionPeer::getFieldNames($keyType);

		if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
		if (array_key_exists($keys[1], $arr)) $this->setNumber($arr[$keys[1]]);
		if (array_key_exists($keys[2], $arr)) $this->setResourceUuid($arr[$keys[2]]);
		if (array_key_exists($keys[3], $arr)) $this->setResourceName($arr[$keys[3]]);
	}

	
	public function buildCriteria()
	{
		$criteria = new Criteria(ResourceVersionPeer::DATABASE_NAME);

		if ($this->isColumnModified(ResourceVersionPeer::ID)) $criteria->add(ResourceVersionPeer::ID, $this->id);
		if ($this->isColumnModified(ResourceVersionPeer::NUMBER)) $criteria->add(ResourceVersionPeer::NUMBER, $this->number);
		if ($this->isColumnModified(ResourceVersionPeer::RESOURCE_UUID)) $criteria->add(ResourceVersionPeer::RESOURCE_UUID, $this->resource_uuid);
		if ($this->isColumnModified(ResourceVersionPeer::RESOURCE_NAME)) $criteria->add(ResourceVersionPeer::RESOURCE_NAME, $this->resource_name);

		return $criteria;
	}

	
	public function buildPkeyCriteria()
	{
		$criteria = new Criteria(ResourceVersionPeer::DATABASE_NAME);

		$criteria->add(ResourceVersionPeer::ID, $this->id);
		$criteria->add(ResourceVersionPeer::NUMBER, $this->number);

		return $criteria;
	}

	
	public function getPrimaryKey()
	{
		$pks = array();

		$pks[0] = $this->getId();

		$pks[1] = $this->getNumber();

		return $pks;
	}

	
	public function setPrimaryKey($keys)
	{

		$this->setId($keys[0]);

		$this->setNumber($keys[1]);

	}

	
	public function copyInto($copyObj, $deepCopy = false)
	{

		$copyObj->setResourceUuid($this->resource_uuid);

		$copyObj->setResourceName($this->resource_name);


		if ($deepCopy) {
									$copyObj->setNew(false);

			foreach($this->getResourceAttributeVersions() as $relObj) {
				$copyObj->addResourceAttributeVersion($relObj->copy($deepCopy));
			}

		} 

		$copyObj->setNew(true);

		$copyObj->setId(NULL); 
		$copyObj->setNumber(NULL); 
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
			self::$peer = new ResourceVersionPeer();
		}
		return self::$peer;
	}

	
	public function setPost($v)
	{


		if ($v === null) {
			$this->setResourceUuid(NULL);
		} else {
			$this->setResourceUuid($v->getUuid());
		}


		$this->aPost = $v;
	}


	
	public function getPost($con = null)
	{
				include_once 'lib/model/om/BasePostPeer.php';

		if ($this->aPost === null && (($this->resource_uuid !== "" && $this->resource_uuid !== null))) {

			$this->aPost = PostPeer::retrieveByPK($this->resource_uuid, $con);

			
		}
		return $this->aPost;
	}

	
	public function initResourceAttributeVersions()
	{
		if ($this->collResourceAttributeVersions === null) {
			$this->collResourceAttributeVersions = array();
		}
	}

	
	public function getResourceAttributeVersions($criteria = null, $con = null)
	{
				include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/om/BaseResourceAttributeVersionPeer.php';
		if ($criteria === null) {
			$criteria = new Criteria();
		}
		elseif ($criteria instanceof Criteria)
		{
			$criteria = clone $criteria;
		}

		if ($this->collResourceAttributeVersions === null) {
			if ($this->isNew()) {
			   $this->collResourceAttributeVersions = array();
			} else {

				$criteria->add(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, $this->getId());

				ResourceAttributeVersionPeer::addSelectColumns($criteria);
				$this->collResourceAttributeVersions = ResourceAttributeVersionPeer::doSelect($criteria, $con);
			}
		} else {
						if (!$this->isNew()) {
												

				$criteria->add(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, $this->getId());

				ResourceAttributeVersionPeer::addSelectColumns($criteria);
				if (!isset($this->lastResourceAttributeVersionCriteria) || !$this->lastResourceAttributeVersionCriteria->equals($criteria)) {
					$this->collResourceAttributeVersions = ResourceAttributeVersionPeer::doSelect($criteria, $con);
				}
			}
		}
		$this->lastResourceAttributeVersionCriteria = $criteria;
		return $this->collResourceAttributeVersions;
	}

	
	public function countResourceAttributeVersions($criteria = null, $distinct = false, $con = null)
	{
				include_once 'plugins/sfPropelVersionableBehaviorPlugin/lib/model/om/BaseResourceAttributeVersionPeer.php';
		if ($criteria === null) {
			$criteria = new Criteria();
		}
		elseif ($criteria instanceof Criteria)
		{
			$criteria = clone $criteria;
		}

		$criteria->add(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, $this->getId());

		return ResourceAttributeVersionPeer::doCount($criteria, $distinct, $con);
	}

	
	public function addResourceAttributeVersion(ResourceAttributeVersion $l)
	{
		$this->collResourceAttributeVersions[] = $l;
		$l->setResourceVersion($this);
	}


  public function __call($method, $parameters)
  {
    if (!$callable = sfMixer::getCallable('BaseResourceVersion:'.$method))
    {
      throw new sfException(sprintf('Call to undefined method BaseResourceVersion::%s', $method));
    }

    array_unshift($arguments, $this);

    return call_user_func_array($callable, $arguments);
  }


} 