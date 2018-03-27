<?php

namespace RisoToth\Database;

/**
*  Class implementing tokenizer and composer of search query from user input.
*
*  @author Richad Toth <riso@iklub.sk>
*/
class SearchQuery {
  const OPERANDS = ['AND NOT', 'OR NOT', 'AND', 'OR', 'NOT']; // list of operands

  const STATE_VALUE = 'val';
  const STATE_EXACT_VALUE = 'exv';
  const STATE_SUBQUERY = 'sub';

  const T_VALUE = 0;
  const T_OPERAND = 1;
  const T_SUBQUERY = 2;

  public static function tokenizeAndCompose($searchQuery, $dataSource, $placeholder = null) {
    $tokenList = self::tokenize($searchQuery);
    if (empty($tokenList)) return '';

    return self::compose($tokenList, $dataSource, $placeholder);
  }

  public static function compose($tokenList, $dataSource, $placeholder = null) {

  }

  public static function tokenize($searchQuery) {
    $resultTokens = [];
    $valueStack = '';
    $currentMachineState = self::STATE_VALUE;
    $subqueryLevelCounter = 0;

    $searchQueryTmp = trim((string)$searchQuery);
    while (mb_strlen($searchQueryTmp)) {
      // <editor-fold defaultstate="collapsed" desc="operand matching">
      if ($currentMachineState == self::STATE_VALUE) {
        $matches = [];
        if (preg_match(self::getOperandsMatchingRE(), $searchQueryTmp, $matches)) {
          // save previous stack if not empty
          switch ($currentMachineState) {
            case self::STATE_VALUE:
            case self::STATE_EXACT_VALUE:
              self::addToken($resultTokens, self::T_VALUE, $valueStack);
              break;
            case self::STATE_SUBQUERY:
              self::addToken($resultTokens, self::T_SUBQUERY, $valueStack);
              break;
          }
          $valueStack = ''; // empty the stack

          $resultTokens[] = [
            'type' => self::T_OPERAND,
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
//      echo "&gt; CH:$char, S:$state, L:$subqueryLevel</br>";

      // <editor-fold defaultstate="collapsed" desc="state machine">
      if ($currentMachineState == self::STATE_VALUE && $char == '"') { // exact value start
        self::addToken($resultTokens, self::T_VALUE, $valueStack);
        $valueStack = '';

        $currentMachineState = self::STATE_EXACT_VALUE;
        continue;
      }
      elseif ($currentMachineState == self::STATE_EXACT_VALUE && $char == '"') { // exact value end
        self::addToken($resultTokens, self::T_VALUE, $valueStack);
        $valueStack = '';

        $currentMachineState = self::STATE_VALUE;
        continue;
      }
      elseif (($currentMachineState == self::STATE_VALUE/* || $state == self::STATE_EXACT_VALUE*/) && $char == '(') { // subquery start
        self::addToken($resultTokens, self::T_VALUE, $valueStack);
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
      case self::STATE_EXACT_VALUE:
        self::addToken($resultTokens, self::T_VALUE, $valueStack);
        break;
      case self::STATE_SUBQUERY:
        self::addToken($resultTokens, self::T_SUBQUERY, $valueStack);
        break;
    }

    return $resultTokens;
  }

  private static function addToken(&$tokens, $type, $value) {
    if ((is_string($value) && trim($value) !== '') || (is_array($value) && !empty($value))) {
      $tokens[] = [
        'type' => $type,
        'value' => $value,
      ];
    }
  }

  private static function getOperandsMatchingRE() {
    return '/^\s(' . implode('|', array_map('preg_quote', self::OPERANDS)) . ')\s/';
  }
}