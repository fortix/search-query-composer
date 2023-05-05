<?php

use Risototh\Database\SearchQuery;
use PHPUnit\Framework\TestCase;

/**
 *  Corresponding Class to test SearchQuery class
 *
 *  @author Richad Toth <riso@iklub.sk>
 */
final class SearchQueryTest extends TestCase {

  /**
   * Just check if the YourClass has no syntax error
   */
  public function testIsThereAnySyntaxError(): void {
    $var = new Risototh\Database\SearchQuery;
    $this->assertTrue(is_object($var));
    unset($var);
  }

  public function testTokenize1(): void {
    $var1 = SearchQuery::tokenize('a AND NOT (b OR (c AND "d AND e")) AND (x OR y)');
    $var2 = [
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 3, 'value' => 'd AND e'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];
    $this->assertEquals($var1, $var2);
    unset($var1);
    unset($var2);
  }

  public function testTokenize2(): void {
    $var1 = SearchQuery::tokenize('something or something else and also this or not ?');
    $var2 = [
      ['type' => 0, 'value' => 'something or something else and also this or not ?'],
    ];
    $this->assertEquals($var1, $var2);
    unset($var1);
    unset($var2);
  }

  public function testFilter1(): void {
    $src = [
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 0, 'value' => 'd AND e'],
          ['type' => 1, 'value' => 'AND'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'NOT'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND'],
      ]],
      ['type' => 1, 'value' => 'NOT'],
    ];
    $var1 = SearchQuery::filter($src);
    $var2 = [
      ['type' => 1, 'value' => 'NOT'],
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 0, 'value' => 'd AND e'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND NOT'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];

    $this->assertEquals($var1, $var2);
    unset($src);
    unset($var1);
    unset($var2);
  }

  public function testFilter2(): void {
    $src = [
      ['type' => 1, 'value' => 'AND'],
      ['type' => 1, 'value' => 'NOT'],
    ];
    $var1 = SearchQuery::filter($src);
    $var2 = [];

    $this->assertEquals($var1, $var2);
    unset($src);
    unset($var1);
    unset($var2);
  }

  public function testFilter3(): void {
    $src = [
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'NOT'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND'],
      ]],
    ];
    $var1 = SearchQuery::filter($src);
    $var2 = [
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND NOT'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];

    $this->assertEquals($var1, $var2);
    unset($src);
    unset($var1);
    unset($var2);
  }

  public function testFilter4(): void {
    $src = [
      ['type' => 0, 'value' => 'x'],
      ['type' => 0, 'value' => 'y'],
    ];
    $var1 = SearchQuery::filter($src);
    $var2 = [
      ['type' => 0, 'value' => 'x'],
    ];

    $this->assertEquals($var1, $var2);
    unset($src);
    unset($var1);
    unset($var2);
  }

  public function testFilter5(): void {
    $this->assertEquals(SearchQuery::filter([]), []);
  }

  public function testComposePart1(): void {
    $pdo = $this->getConnection();
    $var1 = SearchQuery::composePart('test\'"\xa0 something', $pdo, '`field`');
    $var2 = '`field` LIKE \'%test\'\'"\xa0 something%\'';
    $this->assertEquals($var1, $var2);
  }

  public function testComposePart2(): void {
    $pdo = $this->getConnection();
    $var1 = SearchQuery::composePart('test\'"\xa0 something', $pdo, '`field` LIKE "%^FLT%"', '^FLT');
    $var2 = '`field` LIKE "%%test\'\'"\xa0 something%%"';
    $this->assertEquals($var1, $var2);
  }

  public function testCompose1(): void {
    $pdo = $this->getConnection();
    $src = [
      ['type' => 1, 'value' => 'NOT'],
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 0, 'value' => 'd AND e'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND NOT'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];
    $var1 = SearchQuery::compose($src, $pdo, '`field`');
    $var2 = ' NOT `field` LIKE \'%a%\' AND NOT (`field` LIKE \'%b%\' OR (`field` LIKE \'%c%\' AND `field` LIKE \'%d AND e%\')) AND (`field` LIKE \'%x%\' OR `field` LIKE \'%y%\' AND NOT `field` LIKE \'%y%\')';
    $this->assertEquals($var1, $var2);
  }

  public function testCompose2(): void {
    $pdo = $this->getConnection();
    $src = [
      ['type' => 1, 'value' => 'NOT'],
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 3, 'value' => 'd AND e'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND NOT'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];
    $var1 = SearchQuery::compose($src, $pdo, '`field` LIKE "%^FLT%"', '^FLT');
    $var2 = ' NOT `field` LIKE "%%a%%" AND NOT (`field` LIKE "%%b%%" OR (`field` LIKE "%%c%%" AND `field` LIKE "%%d AND e%%")) AND (`field` LIKE "%%x%%" OR `field` LIKE "%%y%%" AND NOT `field` LIKE "%%y%%")';
    $this->assertEquals($var1, $var2);
  }

  public function testMake1(): void {
    $pdo = $this->getConnection();
    $var1 = SearchQuery::make('a AND NOT (b OR (c AND "d AND e")) AND (x OR y)', $pdo, '`field`');
    $var2 = '`field` LIKE \'%a%\' AND NOT (`field` LIKE \'%b%\' OR (`field` LIKE \'%c%\' AND `field` LIKE \'%d AND e%\')) AND (`field` LIKE \'%x%\' OR `field` LIKE \'%y%\')';
    $this->assertEquals($var1, $var2);
  }

  public function testMake2(): void {
    $pdo = $this->getConnection();
    $var1 = SearchQuery::make('a AND NOT (b OR (c AND "d AND e")) AND (x OR y)', $pdo, '`field` LIKE "%^FLT%"', '^FLT');
    $var2 = '`field` LIKE "%%a%%" AND NOT (`field` LIKE "%%b%%" OR (`field` LIKE "%%c%%" AND `field` LIKE "%%d AND e%%")) AND (`field` LIKE "%%x%%" OR `field` LIKE "%%y%%")';
    $this->assertEquals($var1, $var2);
  }

  public function testReconstruct1(): void {
    $src = [
      ['type' => 1, 'value' => 'NOT'],
      ['type' => 0, 'value' => 'a'],
      ['type' => 1, 'value' => 'AND NOT'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'b'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 2, 'value' => [
          ['type' => 0, 'value' => 'c'],
          ['type' => 1, 'value' => 'AND'],
          ['type' => 3, 'value' => 'd AND e'],
        ]],
      ]],
      ['type' => 1, 'value' => 'AND'],
      ['type' => 2, 'value' => [
        ['type' => 0, 'value' => 'x'],
        ['type' => 1, 'value' => 'OR'],
        ['type' => 0, 'value' => 'y'],
        ['type' => 1, 'value' => 'AND NOT'],
        ['type' => 0, 'value' => 'y'],
      ]],
    ];
    $var1 = SearchQuery::reconstruct($src);
    $var2 = 'NOT a AND NOT (b OR (c AND "d AND e")) AND (x OR y AND NOT y)';
    $this->assertEquals($var1, $var2);
  }

  public function testReconstruct2(): void {
    $src = [
      ['type' => 0, 'value' => 'a'],
    ];
    $var1 = SearchQuery::reconstruct($src);
    $var2 = 'a';
    $this->assertEquals($var1, $var2);
  }

  public function testForwardBackward(): void {
    $src = 'NOT a AND NOT (b OR (c AND "d AND e")) AND (x OR y AND NOT y)';
    $tokens = SearchQuery::tokenize($src);
    $result = SearchQuery::reconstruct($tokens);
    $this->assertEquals($src, $result);
  }

  /**
   * @return \PDO
   */
  public function getConnection(): \PDO {
    return new \PDO('sqlite::memory:');
  }
}