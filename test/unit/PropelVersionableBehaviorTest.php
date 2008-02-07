<?php
/*
 * This file is part of the sfPropelVersionableBehavior package.
 * 
 * (c) 2007 Tristan Rivoallan <tristan@rivoallan.net>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit tests for the sfPropelVersionableBehavior plugin.
 *
 * Despite running unit tests, we use the functional tests bootstrap to take advantage of propel
 * classes autoloading...
 * 
 * In order to run the tests in your context, you have to copy this file in a symfony test directory
 * and configure it appropriately (see the "configuration" section at the beginning of the file)
 *  
 * @author   Tristan Rivoallan <tristan@rivoallan.net>
 * @author   Francois Zaninotto <francois.zaninotto@symfony-project.com>
 */

// configuration
// Autofind the first available app environment
$sf_root_dir = realpath(dirname(__FILE__).'/../../../../');
$apps_dir = glob($sf_root_dir.'/apps/*', GLOB_ONLYDIR);
$app = substr($apps_dir[0], 
              strrpos($apps_dir[0], DIRECTORY_SEPARATOR) + 1, 
              strlen($apps_dir[0]));
if (!$app)
{
  throw new Exception('No app has been detected in this project');
}

// -- path to the symfony project where the plugin resides
$sf_path = dirname(__FILE__).'/../../../..';
 
// bootstrap
include($sf_path . '/test/bootstrap/functional.php');

/*
You can override the model class and columns the tests should use in your app.yml
all:
  sfPropelVersionableBehaviorPlugin:
    test_class:          Article
    test_version_column: version
    test_title_column:   title
For the tests to run, your class must hav the following method:
    public function versionConditionMet()
    {
      return $this->getTitle() != 'do not version me';
    }
*/
$test_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_class', 'Post');
$test_class_version_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_version_column', 'version');
$test_class_title_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_title_column', 'title');


// create a new test browser
$browser = new sfTestBrowser();
$browser->initialize();

// initialize database manager
$databaseManager = new sfDatabaseManager();
$databaseManager->initialize();

$con = Propel::getConnection();

// cleanup database
call_user_func(array(_create_resource()->getPeer(), 'doDeleteAll'));
ResourceAttributeVersionPeer::doDeleteAll();
ResourceVersionPeer::doDeleteAll();

// register behavior on test object
sfPropelBehavior::add($test_class, array('versionable' => array(
  'version'  => $test_class_version_column
)));

$t = new lime_test(30, new lime_output_color());

// save()
$t->diag('save()');

$r = _create_resource();
$r->setByName($test_class_title_column, 'V1', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 1, 'save() initializes the version number to 1 for new objects');

$c = new Criteria();
$c->add(ResourceVersionPeer::RESOURCE_ID, $r->getPrimaryKey());
$c->add(ResourceVersionPeer::RESOURCE_NAME, get_class($r));
$c->add(ResourceVersionPeer::NUMBER, $r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME));
$version = ResourceVersionPeer::doSelectOne($c);
$t->isnt($version, null, 'save() creates a new version of resource in database');
foreach ($version->getResourceAttributeVersions() as $attrib_version)
{
  $getter = sprintf('get%s', $attrib_version->getAttributeName());
  $t->is($attrib_version->getAttributeValue(), $r->$getter(), 'save() creates a new version of resource in database with appropriate parameters');
}
$r->setByName($test_class_title_column, 'V2', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 2, 'save() increments the version number');

// getLastResourceVersion()
$t->diag('getLastResourceVersion()');

$t->is($r->getLastResourceVersion()->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V2', 'getLastVersion() returns last version of resource');

// conditional versioning
$t->diag('conditional versioning');
$r->setByName($test_class_title_column, 'do not version me', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 2, 'save() hooks can be deactivated by a versionConditionMet() method');

sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', false);
$r->setByName($test_class_title_column, 'do not version me either, but for another reason', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 2, 'save() hooks can be deactivated by changing app_sfPropelVersionableBehaviorPlugin_auto_versioning to off');
sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', true);

// addVersion()
$t->diag('addVersion()');
sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', false);
$r->setByName($test_class_title_column, 'this time, please version me, but manually', BasePeer::TYPE_FIELDNAME);
$r->addVersion();
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 3, 'addVersion() creates a new version even when app_sfPropelVersionableBehaviorPlugin_auto_versioning is set to off');
sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', true);
try
{
  $r->addVersion();
  $t->fail('calling addVersion() when save hooks are activated throws an exception');
} catch (Exception $e) {
  $t->pass('calling addVersion() when save hooks are activated throws an exception');
}

// toVersion()
$t->diag('toVersion()');

$r->toVersion(1);
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 1, 'toVersion() sets resource version to appropriate values');
$t->is($r->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V1', 'toVersion() sets resource attributes to appropriate values');
$r->save();
$t->is($r->getByName($test_class_version_column, BasePeer::TYPE_FIELDNAME), 4, 'save() correctly increments version number after toVersion() call');
try
{
  $r->toVersion(0);
  $t->fail('toVersion() throws an exception when requested version does not exist');
} catch (Exception $e) {
  $t->pass('toVersion() throws an exception when requested version does not exist');
}

// getAllResourceVersions()
$t->diag('getAllResourceVersions()');

$r->setByName($test_class_title_column, 'V5', BasePeer::TYPE_FIELDNAME);
$r->save();
$all_versions = $r->getAllResourceVersions();
$target_versions = array('V1', 'V2', 'this time, please version me, but manually', 'V1', 'V5');
$t->is(count($all_versions), 5, 'getAllResourceVersions() returns right count of versions');
$versions_titles = array();
foreach($all_versions as $v)
{
  $versions_titles[] = $v->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME);
}
$t->is($versions_titles, $target_versions, 'getAllResourceVersions() returns the right versions');

// getAllVersions()
$t->diag('getAllVersions()');
$all_object_versions = $r->getAllVersions();
$t->is(count($all_object_versions), 5, 'getAllVersions() returns right count of objects');
$versions_titles = array();
$versions_versions = array();
foreach($all_object_versions as $obj)
{
  $versions_titles[] = $obj->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME);
  $versions_versions[] = $obj->getVersion();
}
$t->is($versions_titles, $target_versions, 'getAllVersions() returns the right versions');
$t->is($versions_versions, array(1, 2, 3, 4, 5), 'getAllVersions() returns the array of ordered versions');

// delete()
$t->diag('delete()');
$versions = $r->getAllResourceVersions();
$r->delete();
$t->is($r->getAllResourceVersions(), null, 'delete() also deletes resource version history');
foreach($versions as $version)
{
  // These verison objects now have no counterpart in database, but they are a convenient way to get to the ResourceAttributeVersion objects
  $t->is($version->getResourceAttributeVersions(), null, 'delete() also deletes resource attribute version history');
}

// setVersionConditionMethod()
$t->diag('setVersionConditionMethod()');

sfPropelVersionableBehavior::getVersionConditionMethod();
sfPropelVersionableBehavior::setVersionConditionMethod('someMethod');
$new_method = sfPropelVersionableBehavior::getVersionConditionMethod();
$t->is($new_method, 'someMethod', 'setVersionConditionMethod() changes behavior\'s version condition method');

$original_method = sfPropelVersionableBehavior::setVersionConditionMethod('someOtherMethod');
$t->is($original_method, 'someMethod', 'setVersionConditionMethod() returns previous method name');

// #1563 sfPropelVersionableBehaviorPlugin does not create a version if YourClass::versionConditionMet() is not found
$t->diag('#1563 : sfPropelVersionableBehaviorPlugin does not create a version if YourClass::versionConditionMet() is not found');

$r = _create_resource();
$r->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$r->save();
sfPropelVersionableBehavior::setVersionConditionMethod('nonExistentMethod');
$r->setByName($test_class_title_column, 'do not version me', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getLastResourceVersion()->getNumber(), 2, 'save() creates a version even if YourClass::versionConditionMet() is not found');

// #1564 crashes while creating a new version if no prior version exists
$t->diag('#1564 : sfPropelVersionableBehaviorPlugin crashes while creating a new version if no prior version exists');

$r = _create_resource();
try
{
  $r->save();
  $r->setByName($test_class_title_column, '#1564', BasePeer::TYPE_FIELDNAME);
  $r->save();
  $t->pass('save() does not crash when creating a new version if no prior version exists and object is not new');
} catch (Exception $e) {
  $t->fail('save() does not crash when creating a new version if no prior version exists and object is not new');
}

// Helper functions

/**
 * Resource creation "abstraction".
 * 
 * @return  BaseObject
 */
function _create_resource()
{
  $classname = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_class', 'Post');
  
  if (!class_exists($classname))
  {
    throw new Exception(sprintf('Unknown class "%s"', $classname));
  }
  
  $node = new $classname();

  return new $node;
}
