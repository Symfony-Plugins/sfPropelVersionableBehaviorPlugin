<?php
/*
 * This file is part of the sfPropelVersionableBehaviorPlugin package.
 * 
 * (c) 2006-2007 Tristan Rivoallan <tristan@rivoallan.net>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This behavior adds versioning capabilities to any Propel object.
 * 
 * To enable this behavior add this after Propel stub class declaration :
 * 
 * <code>
 *   $columns_map = array('version'  => MyClassPeer::VERSION);
 * 
 *   sfPropelBehavior::add('MyClass', array('versionable' => array('columns' => $columns_map)));
 * </code>
 * 
 * Column map values signification :
 * 
 *  - version : Model column holding resource's current version number
 * 
 * @author Tristan Rivoallan <tristan@rivoallan.net>
 * @author Francois Zaninotto <francois.zaninotto@symfony-project.com>
 */
class sfPropelVersionableBehavior
{

  /**
   * Holds name of the method used for conditional versioning.
   * 
   * @var string
   */
  protected static $condition_method;

# ---- PUBLIC API

  /**
   * Sets resource properties to their value for requested version.
   * 
   * @param      BaseObject    $resource
   * @param      integer       $version_num
   * @throws     Exception     When requested version does not exist
   */
  public function toVersion(BaseObject $resource, $version_num)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->add(ResourceVersionPeer::NUMBER, $version_num);
    $version = ResourceVersionPeer::doSelectOne($c);
    
    if (is_null($version))
    {
      $msg = sprintf('Resource "%s" has no version "%d"', $resource->getPrimaryKey(), $version_num);
      throw new Exception($msg);
    }
    
    $resource = self::populateResourceFromVersion($resource, $version);
  }
 
  public function isLastVersion(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->add(ResourceVersionPeer::NUMBER, $resource->getVersion(), Criteria::GREATER_THAN);
    
    return ResourceVersionPeer::doCount($c) > 0 ? false : true;
  }
  
  /**
   * Returns last version of resource.
   * 
   * @param      BaseObject    $resource
   * @return     ResourceVersion
   */
  public function getLastResourceVersion(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->addDescendingOrderByColumn(ResourceVersionPeer::NUMBER);
    
    return ResourceVersionPeer::doSelectOne($c);
  }

  /**
   * Returns current version of resource.
   * 
   * @param      BaseObject    $resource
   * @return     ResourceVersion
   */
  public function getCurrentResourceVersion(BaseObject $resource)
  {
    return self::getResourceVersion($resource, $resource->getVersion());
  }
   
  /**
   * Returns given version of resource.
   * 
   * @param      BaseObject    $resource
   * @param      integer       $version
   * @return     ResourceVersion
   */
  public function getResourceVersion(BaseObject $resource, $version = 1)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->add(ResourceVersionPeer::NUMBER, $version);
    
    return ResourceVersionPeer::doSelectOne($c);
  }

  /**
   * Returns all ResourceVersion instances related to the object, ordered by version asc.
   * 
   * @param      BaseObject   $resource
   * @return     array        List of ResourceVersion objects
   */
  public function getAllResourceVersions(BaseObject $resource, $order = 'asc')
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->add(ResourceVersionPeer::NUMBER, null, Criteria::ISNOTNULL);
    if ($order == 'asc')
    {
      $c->addAscendingOrderByColumn(ResourceVersionPeer::NUMBER);
    }
    else
    {
      $c->addDescendingOrderByColumn(ResourceVersionPeer::NUMBER);
    }
    
    return ResourceVersionPeer::doSelect($c);
  }

  /**
   * Returns all versions of a resource, ordered by version asc.
   * 
   * @param      BaseObject   $resource
   * @return     array        List of BaseObject objects
   */
  public function getAllVersions(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->add(ResourceVersionPeer::NUMBER, null, Criteria::ISNOTNULL);
    $c->addAscendingOrderByColumn(ResourceVersionPeer::NUMBER);
    $c->addJoin(ResourceVersionPeer::ID, ResourceAttributeVersionHashPeer::RESOURCE_VERSION_ID);
    $attributeHashes = ResourceAttributeVersionHashPeer::doSelectJoinResourceAttributeVersion($c);
    
    $objects = array();
    $object = null;
    $class= get_class($resource);
    $current_id = null;
    foreach($attributeHashes as $attributeHash)
    {
      if($attributeHash->getResourceVersionId() != $current_id)
      {
        if($object)
        {
          $objects[]= $object;
        }
        $current_id = $attributeHash->getResourceVersionId();
        $object = new $class;
      }
      $attribute = $attributeHash->getResourceAttributeVersion();
      $attrib_name = $attribute->getAttributeName();
      $setter = sprintf('set%s', $attrib_name);
      
      if (!method_exists($resource, $setter))
      {
        $msg = sprintf('Impossible to set attribute "%s" on resource "%s"', 
                      $attrib_name, get_class($resource));
        throw new Exception($msg);
      }
      $object->$setter($attribute->getAttributeValue());
      
    }
    $objects[] = $object;
    
    return $objects;
  }
  
  
  const 
    DIFF_COLUMNS    = 0,
    DIFF_VERSIONS   = 1,
    DIFF_ATTRIBUTES = 2;
    
  /**
   * Returns an array of differences between two versions of a versioned object
   * Example:
   *   // checking the differences between versions 5 and 23 of the $article object
   *   $differences = $article->compare(5, 23);
   *   => array(
   *        'title'       => array(5 => 'Original title', 23 => 'Modified title'),
   *        'category_id' => array(5 => 3345,             23 => 634),
   *      );
   * 
   * @param      BaseObject $resource
   * @param      Integer    $versionFrom
   * @param      Integer    $versionTo
   * @param      Integer    $diffType (self::DIFF_COLUMNS (default), self::DIFF_VERSIONS, or self::DIFF_ATTRIBUTES)
   *
   * @return     Array      Associative array of the columns that changed
   */
  public static function compare(BaseObject $resource, $versionFrom, $versionTo, $diffType = self::DIFF_COLUMNS)
  {
    $resourceVersionFrom = $resource->getResourceVersion($versionFrom);
    $resourceVersionTo   = $resource->getResourceVersion($versionTo);
    
    return self::compareResources($versionFrom, $resourceVersionFrom, $versionTo, $resourceVersionTo, $diffType);
  }
  
  /**
   * Returns an array of differences between two resourceVersion objects
   * Uses recursion for related objects
   * 
   * @param      Integer         $versionFrom
   * @param      ResourceVersion $resourceVersionFrom
   * @param      Integer         $versionTo
   * @param      ResourceVersion $resourceVersionTo
   * @param      Integer    $diffType (self::DIFF_COLUMNS (default), self::DIFF_VERSIONS, or self::DIFF_ATTRIBUTES)
   *
   * @return     Array      Associative array of the columns that changed
   */
  public static function compareResources($versionFrom, $resourceVersionFrom, $versionTo, $resourceVersionTo, $diffType = self::DIFF_COLUMNS)
  {
    // preliminary checks
    $classFrom = $resourceVersionFrom instanceof ResourceVersion ? $resourceVersionFrom->getResourceName() : null;
    $classTo   = $resourceVersionTo   instanceof ResourceVersion ? $resourceVersionTo->getResourceName() : null;
    if(($classFrom != $classTo && !is_null($classFrom) && !is_null($classTo)) || (is_null($classFrom) && is_null($classTo)))
    {
      throw new sfException('compareResource() can only compare resources of the same type');
    }
    else
    {
      $class = is_null($classTo) ? $classFrom : $classTo;
    }

    // initialization
    $attributeChanges = array();
    
    // columns comparison
    $attributesFrom = is_null($classFrom) ? array() : $resourceVersionFrom->getAttributesArray();
    $attributesTo   = is_null($classTo)   ? array() : $resourceVersionTo->getAttributesArray();
    // do not include the version column
    try
    {
      $tmp = new $class;
      $versionColumn =  $tmp->getPeer()->translateFieldName(self::getColumnConstant($class, 'version'), BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
    }
    catch(PropelException $e)
    {
      $versionColumn = '';
    }
    $keys = array_unique(array_merge(array_keys($attributesFrom), array_keys($attributesTo)));
    // order keys as they come in the object
    $orderedKeys = array();
    foreach($tmp->getPeer()->getFieldNames() as $key)
    {
      if(in_array($key, $keys))
      {
        $orderedKeys []= $key;
      }
    }
    // check differences for each key
    foreach ($orderedKeys as $key)
    {
      if($key == $versionColumn) continue;
      $attributeFromId = isset($attributesFrom[$key]) ? $attributesFrom[$key]['id'] : null;
      $attributeToId   = isset($attributesTo[$key]) ? $attributesTo[$key]['id'] : null;
      $valueFrom       = is_null($attributeFromId) ? null : $attributesFrom[$key]['value'];
      $valueTo         = is_null($attributeToId) ? null : $attributesTo[$key]['value'];
      if($attributeFromId != $attributeToId && $valueFrom != $valueTo)
      {
        switch ($diffType)
        {
          case self::DIFF_ATTRIBUTES:
            $attributeChanges[$key] = array(
              $versionFrom => is_null($attributeFromId) ? null : $attributesFrom[$key],
              $versionTo   => is_null($attributeToId) ? null : $attributesTo[$key]
            );
            break;
          case self::DIFF_VERSIONS:
            $attributeChanges[$versionFrom][$key] = $valueFrom;
            $attributeChanges[$versionTo][$key]   = $valueTo;
            break;
          default:
            $attributeChanges[$key] = array(
              $versionFrom => $valueFrom,
              $versionTo   => $valueTo
            );
            break;
        }
      }
    }
    
    // related objects comparison
    $relatedResourceVersionsFrom = is_null($classFrom) ? array() : $resourceVersionFrom->getRelatedResourceVersions();
    $relatedResourceVersionsTo   = is_null($classTo)   ? array() : $resourceVersionTo->getRelatedResourceVersions();
    $keys = array_unique(array_merge(
      array_keys($relatedResourceVersionsFrom),
      array_keys($relatedResourceVersionsTo)
    ));
    foreach ($keys as $key)
    {
      $idsFrom = array_key_exists($key, $relatedResourceVersionsFrom) ? array_keys($relatedResourceVersionsFrom[$key]) : array();
      $idsTo   = array_key_exists($key, $relatedResourceVersionsTo) ? array_keys($relatedResourceVersionsTo[$key]) : array();
      $ids = array_unique(array_merge($idsFrom, $idsTo));
      foreach($ids as $id)
      {
        $from = array_key_exists($key, $relatedResourceVersionsFrom) ? (array_key_exists($id, $relatedResourceVersionsFrom[$key]) ? $relatedResourceVersionsFrom[$key][$id] : null) : null;
        $to   = array_key_exists($key, $relatedResourceVersionsTo) ? (array_key_exists($id, $relatedResourceVersionsTo[$key]) ? $relatedResourceVersionsTo[$key][$id] : null) : null;
        switch ($diffType)
        {
          case self::DIFF_ATTRIBUTES:
          $attributeChanges[] = array(
            'class' => $key,
            'diff' => self::compareResources($versionFrom, $from, $versionTo, $to, self::DIFF_ATTRIBUTES));
            break;
          case self::DIFF_VERSIONS:
            $diff = self::compareResources($versionFrom, $from, $versionTo, $to, self::DIFF_VERSIONS);
            $attributeChanges[$versionFrom][] = array_merge(
              array('class' => $key),
              $diff[$versionFrom]
            );
            $attributeChanges[$versionTo][] = array_merge(
              array('class' => $key),
              $diff[$versionTo]
            );
            break;
          default:
            $attributeChanges[] = array(
              'class' => $key,
              'diff' => self::compareResources($versionFrom, $from, $versionTo, $to, self::DIFF_COLUMNS));
            break;
        }
      }
    }
    
    return $attributeChanges;
  }
  
  /**
   * Increments the object's version number (without saving it) and creates a new ResourceVersion record.
   * To be used when versionConditionMet() is false
   * 
   * @param      BaseObject   $resource
   * @param      string   Optional name of the revision author
   * @param      string   Optional comment about the revision
   * @param      array    Optional list of related object classes to version together with the main object
   */
  public function addVersion(BaseObject $resource, $createdBy = '', $comment = '', $withObjects = null)
  {
    if (self::versionConditionMet($resource))
    {
      throw new Exception("Impossible to use addVersion() when auto_versioning is on and versionConditionMet() is true");
    }
    self::incrementVersion($resource);
    if(!$createdBy && isset($resource->versionCreatedBy) && $resource->versionCreatedBy != '')
    {
      $createdBy = $resource->versionCreatedBy;
      $resource->versionCreatedBy = '';
    }
    if(!$comment && isset($resource->versionComment) && $resource->versionComment != '')
    {
      $comment = $resource->versionComment;
      $resource->versionComment = '';
    }
    $resource->resourceVersion = self::createResourceVersion($resource, $createdBy, $comment);
    // resourceVersion will be saved in the postSave() method, when the resource has primary and foreign key determined
    
    if(is_array($withObjects) && $withObjects != self::getBehaviorParameter(get_class($resource), 'with', array()))
    {
      $resource->versionWithObjects = $withObjects;
    }
  }

# ---- GETTERS & SETTERS

  /**
   * Returns resource version number. Proxy method to real getter.
   * 
   * @param      BaseObject    $resource
   * @return     integer
   */
  public function getVersion(BaseObject $resource)
  {
    $getter = self::forgeMethodName($resource, 'get', 'version');
    return $resource->$getter();
  }

  /**
   * Sets resource version number. Proxy method to real setter.
   * 
   * @param      BaseObject    $resource
   * @param      integer       $version_number
   */
  public function setVersion(BaseObject $resource, $version_number)
  {
    $setter = self::forgeMethodName($resource, 'set', 'version');
    return $resource->$setter($version_number);
  }
  
  /**
   * Sets resource version comment. Temporarily stored in the resource itself.
   * 
   * @param      BaseObject    $resource
   * @param      string        $comment
   */
  public function setVersionComment(BaseObject $resource, $comment)
  {
    $resource->versionComment = $comment;
  }

  /**
   * Gets resource version comment.
   * 
   * @param      BaseObject    $resource
   *
   * @return     string
   */
  public function getVersionComment(BaseObject $resource)
  {
    if(isset($resource->versionComment) && $resource->versionComment)
    {
      return $resource->versionComment;
    }
    else
    {
      return $resource->getCurrentResourceVersion()->getComment();
    }
  }

  /**
   * Sets resource version author. Temporarily stored in the resource itself
   * 
   * @param      BaseObject    $resource
   * @param      string        $createdBy
   */

  public function setVersionCreatedBy(BaseObject $resource, $createdBy)
  {
    $resource->versionCreatedBy = $createdBy;
  }

  /**
   * Gets resource version author.
   * 
   * @param      BaseObject    $resource
   *
   * @return     string
   */
  public function getVersionCreatedBy(BaseObject $resource)
  {
    if(isset($resource->versionCreatedBy) && $resource->versionCreatedBy)
    {
      return $resource->versionCreatedBy;
    }
    else
    {
      return $resource->getCurrentResourceVersion()->getCreatedBy();
    }
  }

  /**
   * Proxy method for getting resource version creation date.
   * But you'd better define an 'updated_at' column directly in the resource
   * 
   * @param      BaseObject    $resource
   *
   * @return     string
   */
  public function getVersionCreatedAt(BaseObject $resource, $format = 'Y-m-d H:i:s')
  {
    return $resource->getCurrentResourceVersion()->getCreatedAt($format);
  }
  
  public function deleteHistory(BaseObject $resource, $createdBy = '', $comment = '', $keepOne = true)
  {
    $this->postDelete($resource);
    if($keepOne)
    {
      $resource->setVersion(0);
      if (!self::versionConditionMet($resource))
      {
        $resource->addVersion();
      }
      $resource->save();
    }
  }
  
# ---- HOOKS

  /**
   * This hook is called before object is saved.
   * 
   * @param      BaseObject    $resource
   */
  public function preSave(BaseObject $resource)
  {
    $resource->wasModified = $resource->isModified();
    if ($resource->wasModified && self::versionConditionMet($resource))
    {
      self::incrementVersion($resource);
    }
  }
  
  /**
   * This hook is called juste after object is saved. It takes care of creating a new version of resource.
   * 
   * @param      BaseObject    $resource
   */
  public function postSave(BaseObject $resource)
  {
    if ($resource->wasModified && self::versionConditionMet($resource))
    {
      $createdBy = '';
      $comment = '';
      if(isset($resource->versionCreatedBy) && $resource->versionCreatedBy != '')
      {
        $createdBy = $resource->versionCreatedBy;
        $resource->versionCreatedBy = '';
      }
      if(isset($resource->versionComment) && $resource->versionComment != '')
      {
        $comment = $resource->versionComment;
        $resource->versionComment = '';
      }
      $resource->resourceVersion = self::createResourceVersion($resource, $createdBy, $comment);
    }
    if(isset($resource->resourceVersion) && $resource->resourceVersion instanceOf ResourceVersion)
    {
      $withObjects = isset($resource->versionWithObjects) ? $resource->versionWithObjects : self::getBehaviorParameter(get_class($resource), 'with', array());
      $resource->resourceVersion->setResourceId($resource->getPrimaryKey());
      $resource->resourceVersion->populateFromObject($resource, $withObjects, true);
      $resource->resourceVersion->save();
      $resource->resourceVersion = null;
    }
    unset($resource->wasModified);
  }
  
  /**
   * This hook is called just after a resource is deleted and takes care of deleting its version history.
   */
  public function postDelete(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    ResourceVersionPeer::doDelete($c);
  }

# ---- HELPER METHODS

  /**
   * Increments the version number of the current object or initializes it
   *
   * @param      BaseObject    $resource
   */
  public static function incrementVersion(BaseObject $resource)
  {
    if ($version = $resource->getLastResourceVersion())
    {
      $resource->setVersion($version->getNumber() + 1);
    }
    else
    {
      $resource->setVersion(1);
    }
  }

  /**
   * Creates a new ResourceVersion record based on the object
   *
   * @param      BaseObject    $resource
   * @param      string   Optional name of the revision author
   * @param      string   Optional comment about the revision
   */
  public static function createResourceVersion(BaseObject $resource, $createdBy = '', $comment = '')
  {
    $resourceVersion = new ResourceVersion();
    $resourceVersion->setNumber($resource->getVersion());
    if($titleGetter = self::forgeMethodName($resource, 'get', 'title'))
    {
      $resourceVersion->setTitle($resource->$titleGetter());
    }
    $resourceVersion->setCreatedBy($createdBy);
    $resourceVersion->setComment($comment);
    
    return $resourceVersion;
  }
  
  /**
   * Returns a resource populated with attribute values of given version.
   * 
   * @param      BaseObject         $resource
   * @param      ResourceVersion    $version
   * @return     BaseObject
   */
  public static function populateResourceFromVersion(BaseObject $resource, BaseObject $version)
  {
    // hydrate the main resource
    foreach ($version->getResourceAttributeVersions() as $attrib_version)
    {
      $attrib_name = $attrib_version->getAttributeName();
      $setter = sprintf('set%s', $attrib_name);
      
      if (!method_exists($resource, $setter))
      {
        throw new Exception(sprintf('Impossible to set attribute "%s" on resource "%s"', $attrib_name, get_class($resource)));
      }
      $resource->$setter($attrib_version->getAttributeValue());
    }
    
    if ($relatedResourceVersions = $version->getResourceVersionsRelatedByResourceVersionId())
    {
      // Need to hydrate related objects, too, and to attach them to the main resource
      $reinits = array();
      foreach ($relatedResourceVersions as $relatedResourceVersion)
      {
        $relatedResourceClass = $relatedResourceVersion->getResourceName();
        $relatedResource = new $relatedResourceClass();
        self::populateResourceFromVersion($relatedResource, $relatedResourceVersion);
        if (method_exists($resource, 'add'.$relatedResourceClass))
        {
          // one-to-many relationship
          if (!in_array($relatedResourceClass, $reinits))
          {
            // This is the only way to trick Propel objects
            // Into not calling the database for one-to-many relationships getters
            $getter = 'get'.$relatedResourceClass.'s';
            $resource->$getter();
            $initer = 'init'.$relatedResourceClass.'s';
            $resource->$initer(true);
            $reinits[] = $relatedResourceClass;
          }
          $setter = 'add'.$relatedResourceClass;
        }
        else
        {
          // one-to-one or many_to_one relationship
          $setter = 'set'.$relatedResourceClass;
        }
        // Associate the new related object to the main resource
        $resource->$setter($relatedResource);
      }
    }
    
    return $resource;
  }
  
  /**
   * Returns getter / setter name for requested column.
   * 
   * @param     BaseObject    $resource
   * @param     string        $prefix     Usually 'get' or 'set'
   * @param     string        $column     version
   */
  private static function forgeMethodName($resource, $prefix, $column)
  {
    $columnName = self::getColumnConstant(get_class($resource), $column);
    if (in_array($columnName, $resource->getPeer()->getFieldNames(BasePeer::TYPE_FIELDNAME)))
    {
      return sprintf('%s%s', $prefix, $resource->getPeer()->translateFieldName($columnName, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME));
    }
    else
    {
      return false;
    }
  }

  /**
   * Returns constant value for requested column.
   * 
   * @param     string      $resource_class
   * @param     string      $column
   *
   * @return    string
   */
  public static function getColumnConstant($resource_class, $column)
  {
    $columns = self::getBehaviorParameter($resource_class, 'columns');

    return isset($columns[$column]) ? $columns[$column] : $column;
  }

  /**
   * Returns behavior parameter for a given class
   * 
   * @param     string      $resource_class
   * @param     string      $parameter
   * @param     Mixed       $default           Default value if the parameter was not set during behavior initialization
   *
   * @return    string
   */
  public static function getBehaviorParameter($resource_class, $parameter, $default = null)
  {
    return sfConfig::get(sprintf('propel_behavior_versionable_%s_%s', $resource_class, $parameter), $default);
  }

  /**
   * Used to decide wether or not a new version of resource should be created.
   * 
   * @param   BaseObject   $resource
   * @return  bool
   */
  public static function versionConditionMet(BaseObject $resource)
  {
    if(!sfConfig::get('app_sfPropelVersionableBehaviorPlugin_auto_versioning', true))
    {
      return false;
    }
    
    if (!$method = self::$condition_method)
    {
      $conf_directive = sprintf('propel_behavior_versionable_%s_conditional', get_class($resource));
      $method = sfConfig::get($conf_directive, 'versionConditionMet');
    }

    $has_condition_method = method_exists($resource, $method);
    
    return !$has_condition_method || ($has_condition_method && $resource->$method()); 
  }

  /**
   * Sets object method used to decide if a new version should be created
   * 
   * @param   string
   */
  public static function setVersionConditionMethod($method_name)
  {
    $previous_method = self::$condition_method;
    self::$condition_method = $method_name;
    
    return $previous_method;
  }
  
  /**
   * 
   */
  public static function getVersionConditionMethod()
  {
    return self::$condition_method;
  }
}
