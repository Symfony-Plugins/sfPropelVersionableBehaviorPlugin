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
   * Only accepts already saved resources, because it needs primary and foreign keys to be defined
   *
   * @param      BaseObject    $resource
   * @param      Array         $withObjects      Optional list of object classes to create and attach to the current resource
   */
  public function populateFromObject(BaseObject $resource, $withObjects = array(), $withVersion = true, $modifiedColumns = array())
  {
    if($resource->isNew())
    {
      throw new Exception('Cannot populate a ResourceVersion object from a new resource');
    }
    $this->setResourceId($resource->getPrimaryKey());
    $this->setResourceName(get_class($resource));
    if($withVersion)
    {
      $this->setNumber($resource->getVersion());
    }
    
    $previousResourceVersion = $withVersion ? $resource->getResourceVersion($resource->getVersion() - 1) : null;
    $previousResourceAttributeVersions = array();
    foreach ($resource->getPeer()->getFieldNames() as $attribute_name)
    {
      // For each attribute, we either create a new AttributeVersion, or reference an older one if not modified
      if(!$previousResourceVersion || in_array($attribute_name, $modifiedColumns))
      {
        // First version or modified attribute
        $getter = sprintf('get%s', $attribute_name);
        $attributeVersion = new ResourceAttributeVersion();
        $attributeVersion->setAttributeName($attribute_name);
        $attributeVersion->setAttributeValue($resource->$getter());
        $attributeVersion->save();
        
        $attributeVersionId = $attributeVersion->getId();
        $isModified = true;
      }
      else
      {
        // Attribute not modified
        // So we use the attribute from a previous version
        if(!$previousResourceAttributeVersions)
        {
          foreach($previousResourceVersion->getResourceAttributeVersions() as $resourceAttributeVersion)
          {
            $previousResourceAttributeVersions[$resourceAttributeVersion->getAttributeName()] = $resourceAttributeVersion->getId();
          }
        }
        
        $attributeVersionId = $previousResourceAttributeVersions[$attribute_name];
        $isModified = false;
      }
      
      $attributeVersionHash = new ResourceAttributeVersionHash();
      $attributeVersionHash->setResourceAttributeVersionId($attributeVersionId);
      $attributeVersionHash->setIsModified($isModified);
      $this->addResourceAttributeVersionHash($attributeVersionHash);
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
    $resource = sfPropelVersionableBehavior::populateResourceFromVersion(new $resource_name(), $this);
    $resource->setNew(false);
    
    return $resource;
  }
  
  public function getResourceAttributeVersions()
  {
    $c = new Criteria();
    $c->add(ResourceAttributeVersionHashPeer::RESOURCE_VERSION_ID, $this->getId());
    $c->addJoin(ResourceAttributeVersionHashPeer::RESOURCE_ATTRIBUTE_VERSION_ID, ResourceAttributeVersionPeer::ID);
    
    return ResourceAttributeVersionPeer::doSelect($c);
  }

  public function getModifiedResourceAttributeVersions()
  {
    $c = new Criteria();
    $c->add(ResourceAttributeVersionHashPeer::RESOURCE_VERSION_ID, $this->getId());
    $c->add(ResourceAttributeVersionHashPeer::IS_MODIFIED, true);
    $c->addJoin(ResourceAttributeVersionHashPeer::RESOURCE_ATTRIBUTE_VERSION_ID, ResourceAttributeVersionPeer::ID);
    
    return ResourceAttributeVersionPeer::doSelect($c);
  }

}
