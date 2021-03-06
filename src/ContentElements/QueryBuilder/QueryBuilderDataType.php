<?php
namespace Fortifi\Ui\ContentElements\QueryBuilder;

class QueryBuilderDataType
{
  const BOOL = 'bool';
  const STRING = 'string';
  const NUMBER = 'number';
  const DATE = 'date';
  const TIMESTAMP_DAY = 'timestamp_day';
  const DECIMAL = 'decimal';

  final private function __construct() { }
}
