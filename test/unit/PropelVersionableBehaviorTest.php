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
 */

// configuration
// -- an existing application name
$app = 'frontend';

// -- the model class the tests should use
// -- NOTE : this class must have a getTitle and a setTitle methods in order for the tests to run
define('VERSIONABLE_CLASS', 'Post');

// -- path to the symfony project where the plugin resides
$sf_path = '/home/trivoallan/workspace/ipc-cb-trunk';
 
// bootstrap
include($sf_path . '/test/bootstrap/functional.php');

// create a new test browser
$browser = new sfTestBrowser();
$browser->initialize();

// initialize database manager
$databaseManager = new sfDatabaseManager();
$databaseManager->initialize();

$con = Propel::getConnection();

// cleanup database
call_user_func(array(_create_resource()->getPeer(), 'doDeleteAll'));
ResourceVersionPeer::doDeleteAll();

// save()
$t = new lime_test(15, new lime_output_color());
$t->diag('save()');

$r = _create_resource();
$r->setTitle('V1');
$r->save();

$t->isnt($r->getUuid(), null, 'save() generates a universal unique id for new resources');

$uuid = $r->getUuid();
$r->setTitle('V2');
$r->save();

$t->is($r->getUuid(), $uuid, 'save() does not generate a new universal unique id for existing resources');

$c = new Criteria();
$c->add(ResourceVersionPeer::RESOURCE_UUID, $r->getUuid());
$c->add(ResourceVersionPeer::NUMBER, $r->getVersion());
$version = ResourceVersionPeer::doSelectOne($c);
$t->isnt($version, null, 'save() creates a new version of resource in database');
foreach ($version->getResourceAttributeVersions() as $attrib_version)
{
  $getter = sprintf('get%s', $attrib_version->getAttributeName());
  $t->is($attrib_version->getAttributeValue(), $r->$getter(), 'save() creates a new version of resource in database with appropriate parameters');
//  $t->diag(sprintf('%s : %s == %s', $attrib_version->getAttributeName(),
//                                    $attrib_version->getAttributeValue(),
//                                    $r->$getter()));
}

// getLastVersion()
$t = new lime_test(2, new lime_output_color());
$t->is($r->getLastVersion()->getResourceInstance()->getTitle(), 'V2', 'getLastVersion() returns last version of resource');

$r->setTitle('do not version me');
$r->save();
$t->is($r->getVersion(), 2, 'save() complies with conditional versioning feature');

// toVersion()
$t = new lime_test(3, new lime_output_color());
$t->diag('toVersion()');
$r->toVersion(1);
$t->is($r->getTitle(), 'V1', 'toVersion() sets resource attributes to appropriate values');
$r->save();
$t->is($r->getVersion(), 3, 'save() correctly increments version number after toVersion() call');
try
{
  $r->toVersion(0);
  $t->fail('toVersion() throws an exception when requested version does not exist');
}
catch (Exception $e)
{
  $t->pass('toVersion() throws an exception when requested version does not exist');
}

// getAllVersions()
$t = new lime_test(2, new lime_output_color());

$r->setTitle('V4');
$r->save();
$all_versions = $r->getAllVersions();
$target_versions = array('V1', 'V2', 'V1', 'V4');
$t->diag('getAllVersions()');
$t->is(count($all_versions), 4, 'getAllVersions() returns right count of versions');
$versions_titles = array();
foreach($all_versions as $v)
{
  $versions_titles[] = $v->getResourceInstance()->getTitle();
}
$t->is($versions_titles, $target_versions, 'getAllVersions() returns the right versions');

// delete()
$t = new lime_test(1, new lime_output_color());
$t->diag('delete()');
$r->delete();
$t->is($r->getAllVersions(), null, 'delete() also deletes resource version history');

// setVersionConditionMethod()
$t = new lime_test(2, new lime_output_color());
$t->diag('setVersionConditionMethod()');
sfPropelVersionableBehavior::getVersionConditionMethod();
sfPropelVersionableBehavior::setVersionConditionMethod('someMethod');
$new_method = sfPropelVersionableBehavior::getVersionConditionMethod();
$t->is($new_method, 'someMethod', 'setVersionConditionMethod() changes behavior\'s version condition method');

$original_method = sfPropelVersionableBehavior::setVersionConditionMethod('someOtherMethod');
$t->is($original_method, 'someMethod', 'setVersionConditionMethod() returns previous method name');

// #1563 sfPropelVersionableBehaviorPlugin does not create a version if YourClass::versionConditionMet() is not found
$t = new lime_test(1, new lime_output_color());
$t->diag('#1563 : sfPropelVersionableBehaviorPlugin does not create a version if YourClass::versionConditionMet() is not found');
$r = _create_resource();
$r->setTitle('v1');
$r->save();
sfPropelVersionableBehavior::setVersionConditionMethod('nonExistentMethod');
$r->setTitle('do not version me');
$r->save();
$t->is($r->getLastVersion()->getNumber(), 2, 'save() creates a version even if YourClass::versionConditionMet() is not found');

// #1564 crashes while creating a new version if no prior version exists
$t = new lime_test(1, new lime_output_color());
$t->diag('#1564 : sfPropelVersionableBehaviorPlugin crashes while creating a new version if no prior version exists');
$r = _create_resource();
$r->save();
$r->setTitle('#1564');
$r->save();
$t->pass('save() does not crash when creating a new version if no prior version exists and object is not new');

// Helper functions

/**
 * Resource creation "abstraction".
 * 
 * @return  BaseObject
 */
function _create_resource()
{
  $classname = VERSIONABLE_CLASS;
  
  if (!class_exists($classname))
  {
    throw new Exception(sprintf('Unknown class "%s"', $classname));
  }
  
  $node = new $classname();

  return new $node;
}
