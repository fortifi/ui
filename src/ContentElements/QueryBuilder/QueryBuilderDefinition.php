<?php
namespace Fortifi\Ui\ContentElements\QueryBuilder;

class QueryBuilderDefinition
{
  const COMPARATOR_EQUALS                 = 'eq';
  const COMPARATOR_NOT_EQUALS             = 'neq';
  const COMPARATOR_EQUALS_INSENSITIVE     = 'eqi';
  const COMPARATOR_NOT_EQUALS_INSENSITIVE = 'neqi';
  const COMPARATOR_IN                     = 'in';
  const COMPARATOR_NOT_IN                 = 'nin';
  const COMPARATOR_CONTAINS               = 'contains';
  const COMPARATOR_DOES_NOT_CONTAIN       = 'dncontain';
  const COMPARATOR_GREATER_THAN           = 'gt';
  const COMPARATOR_GREATER_OR_EQUAL       = 'gte';
  const COMPARATOR_LESS_THAN              = 'lt';
  const COMPARATOR_LESS_OR_EQUAL          = 'lte';
  const COMPARATOR_BETWEEN                = 'bet';
  const COMPARATOR_NOT_BETWEEN            = 'nbet';
  const COMPARATOR_MULTIPLE_OF            = 'multipleof';
  const COMPARATOR_LIKE                   = 'like';
  const COMPARATOR_NOT_LIKE               = 'nlike';
  const COMPARATOR_LIKE_IN                = 'likein';
  const COMPARATOR_NOT_LIKE_IN            = 'nlikein';
  const COMPARATOR_STARTS                 = 'starts';
  const COMPARATOR_NOT_STARTS             = 'nstarts';
  const COMPARATOR_ENDS                   = 'ends';
  const COMPARATOR_NOT_ENDS               = 'nends';
  const COMPARATOR_BEFORE                 = 'before';
  const COMPARATOR_AFTER                  = 'after';

  //elastic specific search comparators
  const COMPARATOR_MATCH                   = 'match';
  const COMPARATOR_NOT_MATCH               = 'nmatch';
  const COMPARATOR_MATCH_PHRASE            = 'matchphrase';
  const COMPARATOR_NOT_MATCH_PHRASE        = 'nmatchphrase';
  const COMPARATOR_MATCH_PHRASE_PREFIX     = 'matchphrasepre';
  const COMPARATOR_NOT_MATCH_PHRASE_PREFIX = 'nmatchphrasepre';
  const COMPARATOR_WILDCARD                = 'wild';
  const COMPARATOR_NOT_WILDCARD            = 'nwild';
  const COMPARATOR_FUZZY                   = 'fuzzy';
  const COMPARATOR_NOT_FUZZY               = 'nfuzzy';

  protected $_key = '';
  protected $_displayName = '';
  protected $_dataType = QueryBuilderDataType::STRING;
  protected $_inputType = null;
  protected $_showSingleComparator = null;
  protected $_comparators = [self::COMPARATOR_EQUALS];
  protected $_required = false;
  protected $_unique = false;
  protected $_values;
  protected $_valuesUrl;
  protected $_strictValues = true;

  public function __construct($key, $displayName, $dataType = null)
  {
    $this->_key = $key;
    $this->_displayName = $displayName;
    $this->_dataType = $dataType;
  }

  public function showSingleComparator($showSingleComparator = null)
  {
    $this->_showSingleComparator = $showSingleComparator;
    return $this;
  }

  public function setComparators(array $comparators, $showSingleComparator = null)
  {
    $this->_comparators = [];
    foreach($comparators as $comparator)
    {
      $this->addComparator($comparator);
    }
    $this->_showSingleComparator = $showSingleComparator;
    return $this;
  }

  public function addComparator($comparator)
  {
    if(array_search($comparator, $this->_comparators) === false)
    {
      $this->_comparators[] = $comparator;
    }
    return $this;
  }

  public function removeComparator($comparator)
  {
    $idx = array_search($comparator, $this->_comparators);
    if($idx !== false)
    {
      unset($this->_comparators[$idx]);
    }
    return $this;
  }

  public function setInputType($inputType)
  {
    $this->_inputType = $inputType;
    return $this;
  }

  public function getKey()
  {
    return $this->_key;
  }

  /**
   * @return mixed
   */
  public function getDisplayName()
  {
    return $this->_displayName;
  }

  public function setRequired($required)
  {
    $this->_required = $required;
    return $this;
  }

  public function setUnique($unique)
  {
    $this->_unique = $unique;
    return $this;
  }

  // Values get converted to an object to stop [0=>'Zero', 1=>'One'] in PHP becoming ['Zero', 'One'] in JSON
  public function setValues(array $values)
  {
    if(empty($values))
    {
      $this->_values = null;
    }
    else
    {
      $this->_values = (object)$values;
    }
    return $this;
  }

  public function setValuesUrl($url)
  {
    $this->_valuesUrl = $url;
    return $this;
  }

  public function setStrict($value)
  {
    $this->_strictValues = $value;
    return $this;
  }

  public function toArray()
  {
    return [
      'key'                  => $this->_key,
      'displayName'          => $this->_displayName,
      'comparators'          => array_values($this->_comparators),
      'showSingleComparator' => $this->_showSingleComparator,
      'dataType'             => $this->_dataType,
      'inputType'            => $this->_inputType,
      'required'             => $this->_required,
      'unique'               => $this->_unique,
      'values'               => $this->_values,
      'valuesUrl'            => $this->_valuesUrl,
      'strictValues'         => $this->_strictValues,
    ];
  }

  public static function timeComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_GREATER_OR_EQUAL,
      QueryBuilderDefinition::COMPARATOR_GREATER_THAN,
      QueryBuilderDefinition::COMPARATOR_LESS_THAN,
      QueryBuilderDefinition::COMPARATOR_LESS_OR_EQUAL,
      QueryBuilderDefinition::COMPARATOR_EQUALS,
    ];
  }

  public static function enumComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
      QueryBuilderDefinition::COMPARATOR_NOT_EQUALS,
    ];
  }

  public static function boolComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
    ];
  }

  public static function containsComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_CONTAINS,
      QueryBuilderDefinition::COMPARATOR_DOES_NOT_CONTAIN,
    ];
  }

  public static function idComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
      QueryBuilderDefinition::COMPARATOR_NOT_EQUALS,
      QueryBuilderDefinition::COMPARATOR_STARTS,
      QueryBuilderDefinition::COMPARATOR_NOT_STARTS,
    ];
  }

  public static function emailComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
      QueryBuilderDefinition::COMPARATOR_NOT_EQUALS,
      QueryBuilderDefinition::COMPARATOR_STARTS,
      QueryBuilderDefinition::COMPARATOR_NOT_STARTS,
      QueryBuilderDefinition::COMPARATOR_WILDCARD,
      QueryBuilderDefinition::COMPARATOR_NOT_WILDCARD,
    ];
  }

  public static function textComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
      QueryBuilderDefinition::COMPARATOR_NOT_EQUALS,
      QueryBuilderDefinition::COMPARATOR_LIKE,
      QueryBuilderDefinition::COMPARATOR_NOT_LIKE,
      QueryBuilderDefinition::COMPARATOR_STARTS,
      QueryBuilderDefinition::COMPARATOR_ENDS,
      QueryBuilderDefinition::COMPARATOR_EQUALS_INSENSITIVE,
    ];
  }
}
