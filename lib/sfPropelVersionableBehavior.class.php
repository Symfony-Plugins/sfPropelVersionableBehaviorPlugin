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
 *   $columns_map = array('uuid'     => MyClassPeer::UUID,
 *                        'version'  => MyClassPeer::VERSION);
 * 
 *   sfPropelBehavior::add('MyClass', array('versionable' => array('columns' => $columns_map)));
 * </code>
 * 
 * Column map values signification :
 * 
 *  - uuid : Model column holding resource's universally unique identifier (behavior takes care of generating these)
 *  - version : Model column holding resource's current version number
 * 
 * @author Tristan Rivoallan <tristan@rivoallan.net>
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
    $c->add(ResourceVersionPeer::RESOURCE_UUID, $resource->getUuid());
    $c->add(ResourceVersionPeer::NUMBER, $version_num);
    $version = ResourceVersionPeer::doSelectOne($c);
    
    if (is_null($version))
    {
      $msg = sprintf('Resource "%s" has no version "%d"', $resource->getUuid(), $version_num);
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
  public function getLastVersion(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_UUID, $resource->getUuid());
    $c->addDescendingOrderByColumn(ResourceVersionPeer::NUMBER);
    return ResourceVersionPeer::doSelectOne($c);
  }
 
  /**
   * Returns all versions of resource.
   * 
   * @param      BaseObject    $resource
   * @return     array
   */
  public function getAllVersions(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_UUID, $resource->getUuid());
    return ResourceVersionPeer::doSelect($c);
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
   * Returns resource UUID. Proxy method to real getter.
   * 
   * @param      BaseObject    $resource
   * @return     string
   */
  public function getUuid(BaseObject $resource)
  {
    $getter = self::forgeMethodName($resource, 'get', 'uuid');
    return $resource->$getter();    
  }

  /**
   * Sets resource UUID. Proxy method to real setter.
   * 
   * @param      BaseObject    $resource
   * @param      string        $uuid
   */
  public function setUuid(BaseObject $resource, $uuid)
  {
    $setter = self::forgeMethodName($resource, 'set', 'uuid');
    return $resource->$setter($uuid);    
  }

# ---- HOOKS

  /**
   * This hook is called before object is saved. It takes care of generating a new UUID if necessary.
   * 
   * @param      BaseObject    $resource
   */
  public function preSave(BaseObject $resource)
  {

    if (self::versionConditionMet($resource))
    {
      if ($resource->isNew())
      {
        $resource->setUuid(sfPropelVersionableBehaviorToolkit::generateUuid());
      }

      if ($version = $resource->getLastVersion())
      {
        $resource->setVersion($version->getNumber() + 1);
      }
      else
      {
        $resource->setVersion(1); 
      }
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
      $version = new ResourceVersion();
      $version->populateFromObject($resource);
      $version->setNumber($resource->getVersion());
      $version->setResourceUuid($resource->getUuid());
      $version->save();
    }
  }

  /**
   * This hook is called just after a resource is deleted and takes care of deleting its version history.
   */
  public function postDelete(BaseObject $resource)
  {
    $c = new Criteria();
    $c->add(ResourceVersionPeer::RESOURCE_UUID, $resource->getUuid());
    ResourceVersionPeer::doDelete($c);
  }

# ---- HELPER METHODS

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
        $msg = sprint('Impossible to set attribute "%s" on resource "%s"', 
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
   * @param     string        $column     uuid|version
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
    if (!$method = self::$condition_method)
    {
      $conf_directive = sprintf('propel_behavior_versionable_%s_conditional', get_class($resource));
      $method = sfConfig::get($conf_directive);
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
