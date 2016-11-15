<?php namespace scriptlet\unittest;

use lang\IllegalArgumentException;
use xp\scriptlet\Source;
use xp\scriptlet\ServeDocumentRootStatically;
use xp\scriptlet\WebConfiguration;
use xp\scriptlet\SingleScriptlet;
use xp\scriptlet\WebLayout;
use xp\scriptlet\BasedOnWebroot;
use scriptlet\HttpScriptlet;
use lang\ClassLoader;
use lang\System;
use lang\Object;

class SourceTest extends \unittest\TestCase {
  private static $scriptlet, $layout, $dir, $file;

  #[@beforeClass]
  public static function defineScriptlet() {
    self::$scriptlet= ClassLoader::defineClass(self::class.'_Scriptlet', HttpScriptlet::class, []);
  }

  #[@beforeClass]
  public static function site() {
    self::$layout= ClassLoader::defineClass(self::class.'_Layout', Object::class, [WebLayout::class], '{
      public function mappedApplications($profile= null) { /* Intentionally empty */ }
      public function staticResources($profile= null) { /* Intentionally empty */ }
    }');
  }

  #[@beforeClass]
  public static function makeConfigDirectory() {
    self::$dir= realpath(System::tempDir()).DIRECTORY_SEPARATOR.md5(uniqid()).'.xp'.DIRECTORY_SEPARATOR;
    if (is_dir(self::$dir) && !rmdir(self::$dir)) {
      throw new PrerequisitesNotMetError('Fixture directory exists, but cannot remove', null, self::$dir);
    }
    self::$file= self::$dir.'web.ini';

    mkdir(self::$dir);
    file_put_contents(self::$file, '[app]');
  }
 
  #[@afterClass]
  public function removeConfigDirectory() {
    if (is_dir(self::$dir)) {
      unlink(self::$file);
      rmdir(self::$dir);
    }
  }

  #[@test]
  public function from_dash() {
    $this->assertInstanceOf(ServeDocumentRootStatically::class, (new Source('-'))->site());
  }

  #[@test]
  public function from_directory() {
    $this->assertInstanceOf(BasedOnWebroot::class, (new Source(self::$dir))->site());
  }

  #[@test]
  public function from_file() {
    $this->assertInstanceOf(WebConfiguration::class, (new Source(self::$file))->site());
  }

  #[@test]
  public function from_fully_qualified_scriptlet_name() {
    $this->assertInstanceOf(SingleScriptlet::class, (new Source(self::$scriptlet->getName()))->site());
  }

  #[@test]
  public function from_fully_qualified_layout_name() {
    $this->assertInstanceOf(self::$layout, (new Source(self::$layout->getName()))->site());
  }

  #[@test]
  public function from_fully_qualified_scriptlet_name_bc_with_colon_prefix() {
    $this->assertInstanceOf(SingleScriptlet::class, (new Source(':'.self::$scriptlet->getName()))->site());
  }

  #[@test]
  public function from_fully_qualified_layout_name_bc_with_colon_prefix() {
    $this->assertInstanceOf(self::$layout, (new Source(':'.self::$layout->getName()))->site());
  }

  #[@test, @expect(IllegalArgumentException::class), @values(['lang.Object', ':lang.Object'])]
  public function cannot_create_when_passed_class_which_is_neither_scriptlet_nor_layout($name) {
    (new Source($name))->site();
  }

  #[@test, @expect(IllegalArgumentException::class), @values(['does.not.exist', ':does.not.exist'])]
  public function cannot_create_when_passed_class_does_not_exist($name) {
    (new Source($name))->site();
  }

  #[@test, @expect(IllegalArgumentException::class), @values(['', ':'])]
  public function cannot_create_when_passed_class_is_empty($name) {
    (new Source($name))->site();
  }
}