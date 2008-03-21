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
You need a model built with a running database to run these tests.
The tests expect a model similar to this one:

    propel:
      article:
        id:
        version: integer
        title: varchar(255)
        category_id:
      category:
        id:
        name: varchar(255)
      comment:
        id:
        content: varchar(255)
        article_id:

Beware that the tables for these models will be emptied by the tests.
You can override the model class and columns the tests should use in your app.yml

    all:
      sfPropelVersionableBehaviorPlugin:
        test_class:               Article
        test_version_column:      version
        test_title_column:        title
        test_n_1_class:           Category
        test_n_1_name_column:     name
        test_1_n_class:           Comment
        test_1_n_content_column:  content

For the tests to run, your class must hav the following method:

    public function versionConditionMet()
    {
      return $this->getTitle() != 'do not version me';
    }
*/
$test_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_class', 'Article');
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
sfPropelBehavior::add($test_class, array('versionable' => array('columns' => array(
  'version'  => $test_class_version_column
))));

$t = new lime_test(59, new lime_output_color());

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

// Incremental storage
$t->diag('Incremental storage (do not store unchanged values)');
$c = new Criteria();
$c->add(ResourceVersionPeer::RESOURCE_ID, $r->getPrimaryKey());
$c->add(ResourceVersionPeer::NUMBER, 2);
$c->addJoin(ResourceVersionPeer::ID, ResourceAttributeVersionHashPeer::RESOURCE_VERSION_ID);
$c->add(ResourceAttributeVersionHashPeer::IS_MODIFIED, true);
$c->addJoin(ResourceAttributeVersionHashPeer::RESOURCE_ATTRIBUTE_VERSION_ID, ResourceAttributeVersionPeer::ID);
// Only 2 columns should be saved during the last save(): the title, and the version of course!
$t->is(ResourceAttributeVersionPeer::doCount($c), 2, 'Only modified columns are saved');
$r->setByName($test_class_title_column, 'V2', BasePeer::TYPE_FIELDNAME);
$r->save();
$t->is($r->getVersion(), 2, 'Only modified objects are saved');
$c = new Criteria();
$c->add(ResourceVersionPeer::RESOURCE_ID, $r->getPrimaryKey());
$c->add(ResourceVersionPeer::NUMBER, 3);
$c->addJoin(ResourceVersionPeer::ID, ResourceAttributeVersionHashPeer::RESOURCE_VERSION_ID);
$c->addJoin(ResourceAttributeVersionHashPeer::RESOURCE_ATTRIBUTE_VERSION_ID, ResourceAttributeVersionPeer::ID);
// No columns should be saved during the last save(): nothing has changed
$t->is(ResourceAttributeVersionPeer::doCount($c), 0, 'Only modified objects are saved');

// getLastResourceVersion() and getCurrentResourceVersion()
$t->diag('getLastResourceVersion() and getCurrentResourceVersion()');

$t->is($r->getLastResourceVersion()->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V2', 'getLastResourceVersion() returns last version of resource');
$t->is($r->getCurrentResourceVersion()->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V2', 'getCurrentResourceVersion() returns current version of resource');
$r->toVersion(1);
$t->is($r->getLastResourceVersion()->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V2', 'getLastResourceVersion() returns last version of resource');
$t->is($r->getCurrentResourceVersion()->getResourceInstance()->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME), 'V1', 'getCurrentResourceVersion() returns current version of resource');
$r->toVersion(2);

// setVersionComment() and setVersionUpdatedBy
$t->diag('setVersionComment() and setVersionCreatedBy');

$r2 = _create_resource();
$r2->setByName($test_class_title_column, 'v0', BasePeer::TYPE_FIELDNAME);
$r2->setVersionCreatedBy('foo');
$r2->setVersionComment('bar');
$r2->save();
$r3 = call_user_func(array(_create_resource()->getPeer(), 'retrieveByPk'), $r2->getPrimaryKey());
$t->is($r2->getCurrentResourceVersion()->getCreatedBy(), 'foo', 'setVersionCreatedBy() defines the author name to be saved in the ResourceVersion object');
$t->is($r2->getVersionCreatedBy(), 'foo', 'getVersionCreatedBy() returns the author of the revision');
$t->is($r3->getVersionCreatedBy(), 'foo', 'getVersionCreatedBy() is a proxy method for getCurrentResourceVersion()->getCreatedBy()');
$t->is($r2->getCurrentResourceVersion()->getComment(), 'bar', 'setVersionComment() defines the comment to be saved in the ResourceVersion object');
$t->is($r2->getVersionComment(), 'bar', 'getVersionComment() is a proxy method for getCurrentResourceVersion()->getComment()');
$t->is($r3->getVersionComment(), 'bar', 'getVersionComment() is a proxy method for getCurrentResourceVersion()->getComment()');
$r2->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$r2->save();
$resourceVersion = $r2->getCurrentResourceVersion();
$t->is($resourceVersion->getCreatedBy(), '', 'setVersionCreatedBy() only affects the next version saved');
$t->is($resourceVersion->getComment(), '', 'setVersionComment() only affects the next version saved');

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

$r2 = _create_resource();
try
{
  $r2->setByName($test_class_title_column, 'v0', BasePeer::TYPE_FIELDNAME);
  $r2->addVersion();
  $r2->save();
  $t->pass('calling addVersion() on an unsaved object does not throw an exception');
} catch (Exception $e) {
  $t->fail('calling addVersion() on an unsaved object does not throw an exception');
}
$t->is($r2->getLastResourceVersion()->getNumber(), 1, 'addVersion() creates a version object even on unsaved objects');

$r2->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$r2->setVersionCreatedBy('author2');
$r2->setVersionComment('baz');
$r2->addVersion();
$r2->save();
$resourceVersion = $r2->getCurrentResourceVersion();
$t->is($resourceVersion->getCreatedBy(), 'author2', 'addVersion() allows for use of setVersionCreatedBy()');
$t->is($resourceVersion->getComment(), 'baz', 'addVersion() allows for use of setVersionComment()');

$r2->setByName($test_class_title_column, 'v2', BasePeer::TYPE_FIELDNAME);
$r2->addVersion('author3', 'bazz');
$r2->save();
$resourceVersion = $r2->getCurrentResourceVersion();
$t->is($resourceVersion->getCreatedBy(), 'author3', 'addVersion() accepts a version author name as first parameter');
$t->is($resourceVersion->getComment(), 'bazz', 'addVersion() accepts a version comment as second parameter');

$r2 = _create_resource();
$r2->setByName($test_class_title_column, 'v0', BasePeer::TYPE_FIELDNAME);
$r2->addVersion();
$r2->save();
$id = $r2->getId();
$r2->toVersion(1);
$t->is($r2->getId(), $id, 'addVersion() also saves primary keys of new objects');

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
  // These version objects now have no counterpart in database, but they are a convenient way to get to the ResourceAttributeVersion objects
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

// Version details
$t->diag('Version details');

sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', false);
$r = _create_resource();
$r->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$r->addVersion('author1');
$r->save();
$r->setByName($test_class_title_column, 'v2', BasePeer::TYPE_FIELDNAME);
$r->addVersion(null, 'because you\'re worth it');
$r->save();
$r->setByName($test_class_title_column, 'v3', BasePeer::TYPE_FIELDNAME);
$r->addVersion('author2', 'minor corrections');
$r->save();
$r->setByName($test_class_title_column, 'v4', BasePeer::TYPE_FIELDNAME);
$r->addVersion();
$r->save();
$versionAuthors = array();
$versionComments = array();
$resourceVersions = $r->getAllResourceVersions();
foreach ($resourceVersions as $resourceVersion)
{
  $versionAuthors  []= $resourceVersion->getCreatedBy();
  $versionComments []= $resourceVersion->getComment();
}
$t->is($versionAuthors, array('author1', '', 'author2', ''), 'addVersion() accepts a $createdBy parameter');
$t->is($versionComments, array('', 'because you\'re worth it', 'minor corrections', ''), 'addVersion() accepts a $comment parameter');
sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', true);

// Related objects
$t->diag('Related objects');

sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', false);

$test_n_1_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_n_1_class', 'Category');
$test_n_1_name_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_n_1_name_column', 'name');
$test_n_1_setter = 'set'.$test_n_1_class;
$test_n_1_getter = 'get'.$test_n_1_class;
call_user_func(array($test_n_1_class.'Peer', 'doDeleteAll'));

$r = _create_resource();
$r->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$category = new $test_n_1_class();
$category->setByName($test_n_1_name_column, 'Category1', BasePeer::TYPE_FIELDNAME);
$r->$test_n_1_setter($category);
$r->addVersion('author1', 'comment1', array('Category'));
$r->save();
$r = call_user_func(array(_create_resource()->getPeer(), 'retrieveByPk'), $r->getPrimaryKey());
$r->setByName($test_class_title_column, 'v2', BasePeer::TYPE_FIELDNAME);
$category->setByName($test_n_1_name_column, 'Category2', BasePeer::TYPE_FIELDNAME);
$category->save();
$r->addVersion('author2', 'comment2', array($test_n_1_class));
$r->save();
$r->toVersion(1);
$t->is($r->$test_n_1_getter()->getByName($test_n_1_name_column, BasePeer::TYPE_FIELDNAME), 'Category1', 'addVersion() allows to save objects related by a n-1 relationship');

$test_1_n_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_1_n_class', 'Comment');
$test_1_n_content_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_1_n_content_column', 'content');
$test_1_n_adder = 'add'.$test_1_n_class;
$test_1_n_getter = 'get'.$test_1_n_class.'s';
call_user_func(array($test_1_n_class.'Peer', 'doDeleteAll'));

$r = _create_resource();
$r->setByName($test_class_title_column, 'v1', BasePeer::TYPE_FIELDNAME);
$comment1 = new $test_1_n_class();
$comment1->setByName($test_1_n_content_column, 'Comment1', BasePeer::TYPE_FIELDNAME);
$r->$test_1_n_adder($comment1);
$comment2 = new $test_1_n_class();
$comment2->setByName($test_1_n_content_column, 'Comment2', BasePeer::TYPE_FIELDNAME);
$r->$test_1_n_adder($comment2);
$r->addVersion('foo', 'bar', array($test_1_n_class.'s'));
$r->save();
$r = call_user_func(array(_create_resource()->getPeer(), 'retrieveByPk'), $r->getPrimaryKey());
$r->setByName($test_class_title_column, 'v2', BasePeer::TYPE_FIELDNAME);
$comment1->setByName($test_1_n_content_column, 'Comment1 modified', BasePeer::TYPE_FIELDNAME);
$comment1->save();
$comment2->setByName($test_1_n_content_column, 'Comment2 modified', BasePeer::TYPE_FIELDNAME);
$comment2->save();
$r->addVersion('foo', 'another bar', array($test_1_n_class.'s'));
$r->save();
$r = call_user_func(array(_create_resource()->getPeer(), 'retrieveByPk'), $r->getPrimaryKey());
$r->toVersion(1);
$comments = $r->$test_1_n_getter();
$t->is($comments[0]->getByName($test_1_n_content_column, BasePeer::TYPE_FIELDNAME), 'Comment1', 'addVersion() allows to save objects related by a 1-n relationship');
$t->is($comments[1]->getByName($test_1_n_content_column, BasePeer::TYPE_FIELDNAME), 'Comment2', 'addVersion() allows to save objects related by a 1-n relationship');
$r->toVersion(2);
$comments = $r->$test_1_n_getter();
$t->is($comments[0]->getByName($test_1_n_content_column, BasePeer::TYPE_FIELDNAME), 'Comment1 modified', 'addVersion() allows to save objects related by a 1-n relationship');
$t->is($comments[1]->getByName($test_1_n_content_column, BasePeer::TYPE_FIELDNAME), 'Comment2 modified', 'addVersion() allows to save objects related by a 1-n relationship');
sfConfig::set('app_sfPropelVersionableBehaviorPlugin_auto_versioning', true);

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
