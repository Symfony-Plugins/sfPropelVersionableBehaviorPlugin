<?php
/*
 * This file is part of the sfPropelVersionableBehavior package.
 * 
 * (c) 2009 François Zaninotto <tristan@rivoallan.net>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unit tests for the sfPropelVersionableBehavior plugin comparison methods.
 *
 * Despite running unit tests, we use the functional tests bootstrap to take advantage of propel
 * classes autoloading...
 * 
 * In order to run the tests in your context, you have to copy this file in a symfony test directory
 * and configure it appropriately (see the "configuration" section at the beginning of the file)
 *  
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

*/
$test_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_class', 'Article');
$test_class_version_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_version_column', 'version');
$test_class_title_column = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_title_column', 'title');
$test_n_1_class = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_n_1_class', 'Category');
$test_n_1_class_fk = $test_n_1_class.'Id';

// create a new test browser
$browser = new sfTestBrowser();
$browser->initialize();

// initialize database manager
$databaseManager = new sfDatabaseManager();
$databaseManager->initialize();

$con = Propel::getConnection();

// cleanup database
call_user_func(array(_create_resource()->getPeer(), 'doDeleteAll'));
call_user_func(array($test_n_1_class . 'Peer', 'doDeleteAll'));
ResourceAttributeVersionPeer::doDeleteAll();
ResourceVersionPeer::doDeleteAll();

// register behavior on test object
sfPropelBehavior::add($test_class, array('versionable' => array('columns' => array(
  'version'  => $test_class_version_column
))));

$t = new lime_test(10, new lime_output_color());

// compare()
$t->diag('compare()');

$r = _create_resource();
$r->setByName($test_class_title_column, 'V1', BasePeer::TYPE_FIELDNAME);
$r->save();

try
{
  $r->compare(1, 1);
  $t->pass('Versionned models have a compare() method');
}
catch (sfException $e)
{
  $t->fail('Versionned models have a compare() method');
}

$r->setByName($test_class_title_column, 'V2', BasePeer::TYPE_FIELDNAME);
$r->save();

$diff = array(
  'Title'   => array(1 => 'V1', 2 => 'V2')
);
$calculatedDiff = $r->compare(1, 2);

$t->is_deeply($diff, $calculatedDiff, 'compare() returns an array of modified columns');
$t->ok(!array_key_exists('Version', $calculatedDiff), 'compare() does not return differences on the version column');
$t->ok(!array_key_exists($test_n_1_class_fk, $calculatedDiff), 'compare() does not return columns that have no change');

$diff = array(
  'Title'   => array(2 => 'V2', 1 => 'V1')
);

$t->is_deeply($diff, $r->compare(2, 1), 'compare() also works with version numbers in reverse order');

$r->setByName($test_class_title_column, 'V3', BasePeer::TYPE_FIELDNAME);
$r->save();

$diff = array(
  'Title'   => array(1 => 'V1', 3 => 'V3')
);

$t->is_deeply($diff, $r->compare(1, 3), 'compare() results use the version number as key');

$category = new Category();
$category->setName('foo');
$category->save();

$r->setByName($test_n_1_class_fk, $category->getId());
$r->setByName($test_class_title_column, 'V4', BasePeer::TYPE_FIELDNAME);
$r->save();

$diff = array(
  'CategoryId' => array(3 => NULL, 4 => (string) $category->getId()),
  'Title'      => array(3 => 'V3', 4 => 'V4')
);

$t->is_deeply($diff, $r->compare(3, 4), 'compare() also compares foreign keys');

$diff = array(
  '3' => array('CategoryId' => NULL, 'Title' => 'V3'),
  '4' => array('CategoryId' => (string) $category->getId(), 'Title' => 'V4')
);

$t->is_deeply($diff, $r->compare(3, 4, sfPropelVersionableBehavior::DIFF_VERSIONS), 'compare() uses versions as first level key in the diff result when passed 1 as third parameter');

$attr3 = $r->getResourceVersion(3)->getAttributesArray();
$attr4 = $r->getResourceVersion(4)->getAttributesArray();
$diff = array(
  'CategoryId' => array(
    3 => array(
      'value' => NULL,
      'id'    => $attr3['CategoryId']['id']
    ),
    4 => array(
      'value' => '1',
      'id'    => $attr4['CategoryId']['id']
    )
  ),
  'Title' => array(
    3 => array(
      'value' => 'V3',
      'id'    => $attr3['Title']['id']
    ),
    4 => array(
      'value' => 'V4',
      'id'    => $attr4['Title']['id']
    )
  )
);

$t->is_deeply($diff, $r->compare(3, 4, sfPropelVersionableBehavior::DIFF_ATTRIBUTES), 'compare() returns details on the resource version objects when passed 2 as third parameter');

// Trick: In order to save a new version, a column must 'look' modified
$old = $r->getByName($test_class_title_column, BasePeer::TYPE_FIELDNAME);
$r->setByName($test_class_title_column, 'foobar', BasePeer::TYPE_FIELDNAME);
$r->setByName($test_class_title_column, $old, BasePeer::TYPE_FIELDNAME);
$r->save();

$t->is_deeply(array(), $r->compare(4, 5), 'compare() returns an empty array when comparing two identical revisions');


// Helper functions

/**
 * Resource creation "abstraction".
 * 
 * @return  BaseObject
 */
function _create_resource()
{
  $classname = sfConfig::get('app_sfPropelVersionableBehaviorPlugin_test_class', 'Article');
  
  if (!class_exists($classname))
  {
    throw new Exception(sprintf('Unknown class "%s"', $classname));
  }
  
  $node = new $classname();

  return new $node;
}
