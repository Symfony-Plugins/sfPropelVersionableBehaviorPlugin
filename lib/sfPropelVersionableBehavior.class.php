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
   * Returns all ResourceVersion instances related to the object, ordered by version asc.
   * 
   * @param      BaseObject   $resource
   * @return     array        List of ResourceVersion objects
   */
  public function getAllResourceVersions(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->addAscendingOrderByColumn(ResourceVersionPeer::NUMBER);
    
    return ResourceVersionPeer::doSelect($c);
  }

  /**
   * Returns all ResourceVersion instances related to the object, ordered by version asc.
   * 
   * @param      BaseObject   $resource
   * @return     array        List of BaseObject objects
   */
  public function getAllVersions(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_ID, $resource->getPrimaryKey());
    $c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($resource));
    $c->addAscendingOrderByColumn(ResourceVersionPeer::NUMBER);
    $c->addJoin(ResourceAttributeVersionPeer::RESOURCE_VERSION_ID, ResourceVersionPeer::ID);
    $attributes = ResourceAttributeVersionPeer::doSelect($c);
    
    $objects = array();
    $object = null;
    $class= get_class($resource);
    $current_id = null;
    foreach($attributes as $attribute)
    {
      if($attribute->getResourceVersionId() != $current_id)
      {
        if($object)
        {
          $objects[]= $object;
        }
        $current_id = $attribute->getResourceVersionId();
        $object = new $class;
      }
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
  
  /**
   * Increments the object's version number (without saving it) and creates a new ResourceVersion record.
   * To be used when versionConditionMet() is false
   * 
   * @param      BaseObject   $resource
   */
  public function addVersion(BaseObject $resource)
  {
    if (self::versionConditionMet($resource))
    {
      throw new Exception("Impossible to use addVersion() when auto_versioning is on and versionConditionMet() is true");
    }
    self::incrementVersion($resource);
    self::createResourceVersion($resource);
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

# ---- HOOKS

  /**
   * This hook is called before object is saved.
   * 
   * @param      BaseObject    $resource
   */
  public function preSave(BaseObject $resource)
  {
    if (self::versionConditionMet($resource))
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
    if (self::versionConditionMet($resource))
    {
      self::createResourceVersion($resource);
    }
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
  public function incrementVersion(BaseObject $resource)
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
   */
  public function createResourceVersion(BaseObject $resource)
  {
    $version = new ResourceVersion();
    $version->populateFromObject($resource);
    $version->setNumber($resource->getVersion());
    $version->save();
  }
  
  /**
   * Returns a resource populated with attribute values of given version.
   * 
   * @param      BaseObject    $resource
   * @param      BaseObject    $version
   * @return     BaseObject
   */
  public static function populateResourceFromVersion(BaseObject $resource, BaseObject $version)
  {
    foreach ($version->getResourceAttributeVersions() as $attrib_version)
    {
      $attrib_name = $attrib_version->getAttributeName();
      $setter = sprintf('set%s', $attrib_name);
      
      if (!method_exists($resource, $setter))
      {
        $msg = sprintf('Impossible to set attribute "%s" on resource "%s"', 
                      $attrib_name, get_class($resource));
        throw new Exception($msg);
      }
      $resource->$setter($attrib_version->getAttributeValue());
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
    $method_name = sprintf('%s%s', $prefix, 
                                   $node->getPeer()->translateFieldName(self::getColumnConstant(get_class($resource), $column), 
                                                                        BasePeer::TYPE_COLNAME, 
                                                                        BasePeer::TYPE_PHPNAME));
    return $method_name;
  }

  /**
   * Returns constant value for requested column.
   * 
   * @param     string      $resource_class
   * @param     string      $column
   * @return    string
   */
  private static function getColumnConstant($resource_class, $column)
  {
    $conf_directive = sprintf('propel_behavior_versionable_%s_columns', $resource_class);
    $columns = sfConfig::get($conf_directive);

    return $columns[$column];    
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
