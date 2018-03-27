<?php

/**
*  Corresponding Class to test YourClass class
*
*  For each class in your library, there should be a corresponding Unit-Test for it
*  Unit-Tests should be as much as possible independent from other test going on.
*
*  @author Richad Toth <riso@iklub.sk>
*/
class SearchQueryTest extends PHPUnit_Framework_TestCase{

  /**
  * Just check if the YourClass has no syntax error
  *
  * This is just a simple check to make sure your library has no syntax error. This helps you troubleshoot
  * any typo before you even use this library in a real project.
  *
  */
  public function testIsThereAnySyntaxError(){
    $var = new RisoToth\Database\SearchQuery;
    $this->assertTrue(is_object($var));
    unset($var);
  }

  /**
  * Just check if the YourClass has no syntax error
  *
  * This is just a simple check to make sure your library has no syntax error. This helps you troubleshoot
  * any typo before you even use this library in a real project.
  *
  */
  public function testMethod1(){
    $var = RisoToth\Database\SearchQuery::tokenizeAndCompose('a AND NOT (b OR (c AND "d AND e")) AND (x OR y)', 'field');
    $this->assertTrue($var == 'Hello World');
    unset($var);
  }

}