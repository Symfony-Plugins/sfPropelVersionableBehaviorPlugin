<?php



class ResourceVersionMapBuilder {

	
	const CLASS_NAME = 'plugins.sfPropelVersionableBehaviorPlugin.lib.model.map.ResourceVersionMapBuilder';

	
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
		$this->dbMap = Propel::getDatabaseMap('propel');

		$tMap = $this->dbMap->addTable('resource_version');
		$tMap->setPhpName('ResourceVersion');

		$tMap->setUseIdGenerator(true);

		$tMap->addPrimaryKey('ID', 'Id', 'int', CreoleTypes::INTEGER, true, null);

		$tMap->addPrimaryKey('NUMBER', 'Number', 'int', CreoleTypes::INTEGER, true, 11);

		$tMap->addColumn('RESOURCE_UUID', 'ResourceUuid', 'string', CreoleTypes::CHAR, true, 36);

		$tMap->addColumn('RESOURCE_NAME', 'ResourceName', 'string', CreoleTypes::VARCHAR, true, 255);

	} 
} 