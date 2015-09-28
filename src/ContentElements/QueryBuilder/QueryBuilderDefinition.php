<?php
namespace Fortifi\Ui\ContentElements\QueryBuilder;

class QueryBuilderDefinition
{
  const COMPARATOR_EQUALS = 'eq';
  const COMPARATOR_EQUALS_INSENSITIVE = 'eqi';
  const COMPARATOR_NOT_EQUALS = 'neq';
  const COMPARATOR_IN = 'in';
  const COMPARATOR_NOT_IN = 'nin';
  const COMPARATOR_GREATER_THAN = 'gt';
  const COMPARATOR_GREATER_OR_EQUAL = 'gte';
  const COMPARATOR_LESS_THAN = 'lt';
  const COMPARATOR_LESS_OR_EQUAL = 'lte';
  const COMPARATOR_BETWEEN = 'bet';
  const COMPARATOR_AGE = 'age';
  const COMPARATOR_LIKE = 'like';
  const COMPARATOR_STARTS = 'starts';
  const COMPARATOR_ENDS = 'ends';

  protected $_key = '';
  protected $_displayName = '';
  protected $_dataType = QueryBuilderDataType::STRING;
  protected $_inputType = null;
  protected $_comparators = [self::COMPARATOR_EQUALS => self::COMPARATOR_EQUALS];
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

  public function setComparators(array $comparators)
  {
    foreach($comparators as $comparator)
    {
      $this->addComparator($comparator);
    }
    return $this;
  }

  public function addComparator($comparator)
  {
    $this->_comparators[$comparator] = $comparator;
    return $this;
  }

  public function removeComparator($comparator)
  {
    unset($this->_comparators[$comparator]);
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

  public function setValues(array $values)
  {
    $this->_values = $values;
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
      'key'          => $this->_key,
      'displayName'  => $this->_displayName,
      'comparators'  => array_values($this->_comparators),
      'dataType'     => $this->_dataType,
      'inputType'    => $this->_inputType,
      'required'     => $this->_required,
      'unique'       => $this->_unique,
      'values'       => $this->_values,
      'valuesUrl'    => $this->_valuesUrl,
      'strictValues' => $this->_strictValues,
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

  public static function textComparators()
  {
    return [
      QueryBuilderDefinition::COMPARATOR_EQUALS,
      QueryBuilderDefinition::COMPARATOR_NOT_EQUALS,
      QueryBuilderDefinition::COMPARATOR_LIKE,
      QueryBuilderDefinition::COMPARATOR_STARTS,
      QueryBuilderDefinition::COMPARATOR_ENDS,
      QueryBuilderDefinition::COMPARATOR_EQUALS_INSENSITIVE,
    ];
  }
}
