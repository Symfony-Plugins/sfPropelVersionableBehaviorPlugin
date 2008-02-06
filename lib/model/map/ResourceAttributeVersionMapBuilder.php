<?php


	
class ResourceAttributeVersionMapBuilder {

	
	const CLASS_NAME = 'plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceAttributeVersionMapBuilder';	

    
    private $dbMap;

	
    public function isBuilt()
    {
        return ($this->dbMap !== null);
    }

	
    public function getDatabaseMap()
    {
        return $this->dbMap;
    }

    
    public function doBuild()
    {
		$this->dbMap = Propel::getDatabaseMap('ipc_communityboards');
		
		$tMap = $this->dbMap->addTable('resource_attribute_version');
		$tMap->setPhpName('ResourceAttributeVersion');

		$tMap->setUseIdGenerator(true);

		$tMap->addPrimaryKey('ID', 'Id', 'int', CreoleTypes::INTEGER, true, 11);

		$tMap->addForeignKey('RESOURCE_VERSION_ID', 'ResourceVersionId', 'int', CreoleTypes::INTEGER, 'resource_version', 'ID', true, 11);

		$tMap->addColumn('ATTRIBUTE_NAME', 'AttributeName', 'string', CreoleTypes::VARCHAR, true, 255);

		$tMap->addColumn('ATTRIBUTE_VALUE', 'AttributeValue', 'string', CreoleTypes::LONGVARCHAR, false);
				
    } 
} 