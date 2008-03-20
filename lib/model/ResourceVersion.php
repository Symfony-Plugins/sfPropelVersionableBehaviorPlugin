<?php
/*
 * This file is part of the sfPropelActAsNestedSetBehavior package.
 * 
 * (c) 2006-2007 Tristan Rivoallan <tristan@rivoallan.net>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
/**
 * Subclass for representing a row from the 'resource_version' table.
 *
 *  * @package plugins.sfPropelVersionableBehaviorPlugin.lib.model
 */ 
class ResourceVersion extends BaseResourceVersion
{

  /**
   * Populates version properties and creates necessary entries in the resource_attribute_version table.
   * 
   * @param      BaseObject    $resource
   * @param      Array         $withObjects      Optional list of object classes to create and attach to the current resource
   */
  public function populateFromObject(BaseObject $resource, $withObjects = array(), $withVersion = true)
  {
    $this->setResourceId($resource->getPrimaryKey());
    $this->setResourceName(get_class($resource));
    if($withVersion)
    {
      $this->setNumber($resource->getVersion());
    }
    
    foreach ($resource->getPeer()->getFieldNames() as $attribute_name)
    {
      $getter = sprintf('get%s', $attribute_name);
      $attribute_version = new ResourceAttributeVersion();
      $attribute_version->setAttributeName($attribute_name);
      $attribute_version->setAttributeValue($resource->$getter());
      $this->addResourceAttributeVersion($attribute_version);
    }
    foreach($withObjects as $resourceName)
    {
      $getter = sprintf('get%s', $resourceName);
      $relatedResources = $resource->$getter();
      if(!is_array($relatedResources))
      {
        $relatedResources = array($relatedResources);
      }
      foreach ($relatedResources as $relatedResource)
      {
        $resourceVersion = new ResourceVersion();
        $resourceVersion->populateFromObject($relatedResource, array(), false);
        $this->addResourceVersionRelatedByResourceVersionId($resourceVersion);
      }
    }
  }

  /**
   * Returns resource instance corresponding to version.
   * 
   * @return   BaseObject
   */
  public function getResourceInstance()
  {
    $resource_name = $this->getResourceName();
    return sfPropelVersionableBehavior::populateResourceFromVersion(new $resource_name(), $this);
  }

}
