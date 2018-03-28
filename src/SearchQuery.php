<?php

namespace Risototh\Database;

/**
*  Class implementing tokenizer and composer of search query from user input.
*
*  @author Richad Toth <riso@iklub.sk>
*/
class SearchQuery {
  const OPERATORS = ['AND NOT', 'OR NOT', 'AND', 'OR', 'NOT']; // list of operators

  const STATE_VALUE = 'val';
  const STATE_EXACT_VALUE = 'exv';
  const STATE_SUBQUERY = 'sub';

  const T_VALUE = 0;
  const T_OPERATOR = 1;
  const T_SUBQUERY = 2;
  const T_EXACT_VALUE = 3;

  /**
   * Composes the SQL statement fragment from user entry and datasource definition.
   *
   * @param string $searchQuery User entry
   * @param string $dataSource Field name (datasource) or expression with placeholder
   * @param type $placeholder (optional) Placeholder used in the expression
   * @return string
   */
  public static function make($searchQuery, $dataSource, $placeholder = null) {
    $tokenList = self::tokenize($searchQuery);
    $tokenListFiltered = self::filter($tokenList);
    return self::compose($tokenListFiltered, $dataSource, $placeholder);
  }

  /**
   * Composes the SQL statement fragment from token list and datasource definition.
   *
   * @param array $tokenList Token list
   * @param \PDO $pdo PDO connection to be used for value escaping
   * @param string $dataSource Field name (datasource) or expression with placeholder
   * @param type $placeholder (optional) Placeholder used in the expression
   * @return string
   */
  public static function compose($tokenList, \PDO $pdo, $dataSource, $placeholder = null) {
    if (empty($tokenList)) return '';

    $result = '';

    foreach ($tokenList as $token) {
      switch ($token['type']) {
        case self::T_EXACT_VALUE:
        case self::T_VALUE:
          $result .= self::composePart($token['value'], $pdo, $dataSource, $placeholder);
          break;
        case self::T_OPERATOR:
          $result .= ' ' . $token['value'] . ' ';
          break;
        case self::T_SUBQUERY:
          $result .= '(' . self::compose($token['value'], $pdo, $dataSource, $placeholder) . ')';
          break;
      }
    }

    return $result;
  }

  /**
   * Compose single value comparison part.
   *
   * @param string $value
   * @param \PDO $pdo PDO connection to be used for value escaping
   * @param string $dataSource Field name (datasource) or expression with placeholder
   * @param type $placeholder (optional) Placeholder used in the expression
   * @return string
   */
  public static function composePart($value, \PDO $pdo, $dataSource, $placeholder = null) {
    $valueQuoted = $pdo->quote('%' . $value . '%');

    if ($placeholder === null) {
      return $dataSource . ' LIKE ' . $valueQuoted;
    }
    else {
      $valueQuoted = mb_substr($valueQuoted, 1, mb_strlen($valueQuoted) - 2);
      return str_replace($placeholder, $valueQuoted, $dataSource);
    }
  }

  /**
   * Filter token list to contain only valid sequences of value-operator-value or vice versa.
   *
   * @param array $tokenList
   * @return array
   */
  public static function filter($tokenList) {
    if (empty($tokenList)) return [];

    $resultList = [];

    while ($tokenList[0]['type'] == self::T_OPERATOR && $tokenList[0]['value'] != 'NOT') {
      if ($tokenList[0]['value'] == 'AND NOT') $tokenList[0]['value'] = 'NOT'; // replace AND NOT at begining with simple NOT
      else array_shift($tokenList);
    }

    // add first
    if ($tokenList[0]['type'] == self::T_SUBQUERY) {
      $resultList[] = [
        'type' => self::T_SUBQUERY,
        'value' => self::filter($tokenList[0]['value'])
        ];
    }
    else {
      $resultList[] = $tokenList[0];
    }

    // traverse through the rest
    for ($i = 1; $i < count($tokenList); $i++) {
      if ($tokenList[$i]['value'] == 'NOT') $tokenList[$i]['value'] = 'AND NOT'; // replace all subsequent NOT with AND NOT

      $fOperatorPrev = ($tokenList[$i - 1]['type'] == self::T_OPERATOR);
      $fOperator = ($tokenList[$i]['type'] == self::T_OPERATOR);

      if (($fOperatorPrev && !$fOperator) || (!$fOperatorPrev && $fOperator)) { // is operator - value or value - operator sequence

        if ($i == count($tokenList) - 1 && $fOperator) continue;

        if ($tokenList[$i]['type'] == self::T_SUBQUERY) {
          $resultList[] = [
            'type' => self::T_SUBQUERY,
            'value' => self::filter($tokenList[$i]['value'])
            ];
        }
        else {
          $resultList[] = $tokenList[$i];
        }
      }
    }

    if (count($resultList) == 1 && $resultList[0]['type'] == self::T_OPERATOR) $resultList = [];


    return $resultList;
  }


  /**
   * Tokenizes the input string into array of tokens.
   *
   * @param string $searchQuery
   * @return array
   */
  public static function tokenize($searchQuery) {
    $resultTokens = [];
    $valueStack = '';
    $currentMachineState = self::STATE_VALUE;
    $subqueryLevelCounter = 0;

    $searchQueryTmp = ' ' . trim((string)$searchQuery); // prepend with space to match even the first operator
    while (mb_strlen($searchQueryTmp)) {
      // <editor-fold defaultstate="collapsed" desc="operator matching">
      if ($currentMachineState == self::STATE_VALUE) {
        $matches = [];
        if (preg_match(self::getOperatorsMatchingRE(), $searchQueryTmp, $matches)) {
          // save previous stack if not empty
          switch ($currentMachineState) {
            case self::STATE_VALUE:
              self::addToken($resultTokens, self::T_VALUE, trim($valueStack));
              break;
            case self::STATE_EXACT_VALUE:
              self::addToken($resultTokens, self::T_EXACT_VALUE, $valueStack);
              break;
            case self::STATE_SUBQUERY:
              self::addToken($resultTokens, self::T_SUBQUERY, $valueStack);
              break;
          }
          $valueStack = ''; // empty the stack

          $resultTokens[] = [
            'type' => self::T_OPERATOR,
            'value' => $matches[1],
          ];
          $searchQueryTmp = mb_substr($searchQueryTmp, strlen($matches[0]));
          $currentMachineState = self::STATE_VALUE;
          continue;
        }
      }
      // </editor-fold>

      // get char and shorten input stack
      $char = mb_substr($searchQueryTmp, 0, 1);
      $searchQueryTmp = mb_substr($searchQueryTmp, 1);

      // <editor-fold defaultstate="collapsed" desc="state machine">
      if ($currentMachineState == self::STATE_VALUE && $char == '"') { // exact value start
        self::addToken($resultTokens, self::T_VALUE, trim($valueStack));
        $valueStack = '';

        $currentMachineState = self::STATE_EXACT_VALUE;
        continue;
      }
      elseif ($currentMachineState == self::STATE_EXACT_VALUE && $char == '"') { // exact value end
        self::addToken($resultTokens, self::T_EXACT_VALUE, $valueStack);
        $valueStack = '';

        $currentMachineState = self::STATE_VALUE;
        continue;
      }
      elseif (($currentMachineState == self::STATE_VALUE) && $char == '(') { // subquery start
        self::addToken($resultTokens, self::T_VALUE, trim($valueStack));
        $valueStack = '';

        $currentMachineState = self::STATE_SUBQUERY;
        continue;
      }
      elseif ($currentMachineState == self::STATE_SUBQUERY && $char == ')') { // subquery end
        if ($subqueryLevelCounter == 0) {
          if (trim($valueStack) !== '') {
            self::addToken($resultTokens, self::T_SUBQUERY, self::tokenize($valueStack)); // recursive tokenization of subquery
          }
          $valueStack = '';

          $currentMachineState = self::STATE_VALUE;
          continue;
        }
        $subqueryLevelCounter--;
      }

      if ($currentMachineState == self::STATE_SUBQUERY && $char == '(') { // count subquery levels to correctly extract whole subquery
        $subqueryLevelCounter++;
      }
      // </editor-fold>

      $valueStack .= $char; // simply add char to the value stack
    }

    // save stack reminder if not empty
    switch ($currentMachineState) {
      case self::STATE_VALUE:
        self::addToken($resultTokens, self::T_VALUE, trim($valueStack));
        break;
      case self::STATE_EXACT_VALUE:
        self::addToken($resultTokens, self::T_EXACT_VALUE, $valueStack);
        break;
      case self::STATE_SUBQUERY:
        self::addToken($resultTokens, self::T_SUBQUERY, self::tokenize($valueStack));
        break;
    }

    return $resultTokens;
  }

  /**
   * Reconstruct the user input from the token list
   *
   * @param array $tokenList
   * @return string
   */
  public static function reconstruct($tokenList) {
    if (empty($tokenList)) return '';

    $result = '';

    foreach ($tokenList as $token) {
      switch ($token['type']) {
        case self::T_EXACT_VALUE:
          $result .= '"' . $token['value'] . '"';
          break;
        case self::T_VALUE:
          $result .= $token['value'];
          break;
        case self::T_OPERATOR:
          $result .= ' ' . $token['value'] . ' ';
          break;
        case self::T_SUBQUERY:
          $result .= '(' . self::reconstruct($token['value']) . ')';
          break;
      }
    }

    return trim($result);
  }

  /**
   * Adds token to token list.
   *
   * @param array $tokens
   * @param int $type
   * @param string|array $value
   */
  private static function addToken(&$tokens, $type, $value) {
    if ((is_string($value) && trim($value) !== '') || (is_array($value) && !empty($value))) {
      $tokens[] = [
        'type' => $type,
        'value' => $value,
      ];
    }
  }

  /**
   * Returns operators matching regular expression.
   *
   * @return string
   */
  private static function getOperatorsMatchingRE() {
    return '/^\s(' . implode('|', array_map('preg_quote', self::OPERATORS)) . ')\s/';
  }
}