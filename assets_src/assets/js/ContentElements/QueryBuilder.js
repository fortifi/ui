/* global window, document, jQuery */

(function ($, window, document, undefined) {
  'use strict';

  window.QueryBuilderConstants = window.QueryBuilderConstants || {};

  var QB_DATA_NS = 'querybuilder';
  var QB_DATA_NS_RULE = QB_DATA_NS + '.rule';

  QueryBuilderConstants.INPUT_TEXT = 'text';
  QueryBuilderConstants.INPUT_NUMBER = 'number';
  QueryBuilderConstants.INPUT_DECIMAL = 'decimal';
  QueryBuilderConstants.INPUT_SELECT = 'select';
  QueryBuilderConstants.INPUT_DATE = 'date';
  QueryBuilderConstants.INPUT_BOOL = 'bool';
  QueryBuilderConstants.INPUT_AGE = 'age';
  QueryBuilderConstants.INPUT_BETWEEN = 'between';
  QueryBuilderConstants.DATATYPE_STRING = 'string';
  QueryBuilderConstants.DATATYPE_NUMBER = 'number';
  QueryBuilderConstants.DATATYPE_DECIMAL = 'decimal';
  QueryBuilderConstants.DATATYPE_DATE = 'date';
  QueryBuilderConstants.DATATYPE_TIMESTAMP_DAY = 'timestamp_day';
  QueryBuilderConstants.DATATYPE_BOOL = 'bool';
  QueryBuilderConstants.COMPARATOR_EQUALS = 'eq';
  QueryBuilderConstants.COMPARATOR_NOT_EQUALS = 'neq';
  QueryBuilderConstants.COMPARATOR_EQUALS_INSENSITIVE = 'eqi';
  QueryBuilderConstants.COMPARATOR_NOT_EQUALS_INSENSITIVE = 'neqi';
  QueryBuilderConstants.COMPARATOR_IN = 'in';
  QueryBuilderConstants.COMPARATOR_NOT_IN = 'nin';
  QueryBuilderConstants.COMPARATOR_CONTAINS = 'contains';
  QueryBuilderConstants.COMPARATOR_DOES_NOT_CONTAIN = 'dncontain';
  QueryBuilderConstants.COMPARATOR_GREATER = 'gt';
  QueryBuilderConstants.COMPARATOR_GREATER_EQUAL = 'gte';
  QueryBuilderConstants.COMPARATOR_LESS = 'lt';
  QueryBuilderConstants.COMPARATOR_LESS_EQUAL = 'lte';
  QueryBuilderConstants.COMPARATOR_BETWEEN = 'bet';
  QueryBuilderConstants.COMPARATOR_NOT_BETWEEN = 'nbet';
  QueryBuilderConstants.COMPARATOR_MULTIPLE_OF = 'multipleof';
  QueryBuilderConstants.COMPARATOR_LIKE = 'like';
  QueryBuilderConstants.COMPARATOR_NOT_LIKE = 'nlike';
  QueryBuilderConstants.COMPARATOR_LIKE_IN = 'likein';
  QueryBuilderConstants.COMPARATOR_NOT_LIKE_IN = 'nlikein';
  QueryBuilderConstants.COMPARATOR_STARTS = 'starts';
  QueryBuilderConstants.COMPARATOR_NOT_STARTS = 'nstarts';
  QueryBuilderConstants.COMPARATOR_ENDS = 'ends';
  QueryBuilderConstants.COMPARATOR_NOT_ENDS = 'nends';
  QueryBuilderConstants.COMPARATOR_BEFORE = 'before';
  QueryBuilderConstants.COMPARATOR_AFTER = 'after';

  $.fn.QueryBuilder = function (command) {
    var args = Array.prototype.slice.call(arguments);
    if(!command)
    {
      command = 'init';
    }
    else if(typeof command === 'object')
    {
      command = 'init';
      args.unshift('init');
    }
    if(typeof QueryBuilder.prototype[command] !== 'function')
    {
      throw 'QueryBuilder command \'' + command + '\' not found';
    }
    args.shift();
    var retVal = $(this);
    $(this).each(
      function () {
        var $this = $(this), instance = $this.data(QB_DATA_NS);
        if(!instance)
        {
          $this.data(QB_DATA_NS, new QueryBuilder(this));
          instance = $this.data(QB_DATA_NS);
        }
        var result = instance[command].apply(instance, args);
        if(result)
        {
          retVal = result;
        }
      }
    );
    return retVal;
  };

  /**
   * @constructor
   */
  function QueryBuilderDefinition(data) {
    this.key = '';
    this.displayName = '- SELECT -';
    this.dataType = QueryBuilderConstants.DATATYPE_STRING;
    this.comparators = [QueryBuilderConstants.COMPARATOR_EQUALS];
    this.showSingleComparator = null;
    this.inputType = null;
    this.required = false;
    this.unique = false;
    this.values = null;
    this.valuesUrl = '';
    this.strictValues = true;
    this.count = 0;

    var self = this;
    if(data)
    {
      $.each(
        data,
        function (k, v) {
          if(self.hasOwnProperty(k))
          {
            self[k] = v;
          }
        }
      );
    }
    if(this.values && this.values instanceof Array)
    {
      // convert array to object
      var newValues = {};
      $(this.values).each(
        function (idx) {
          if(self.dataType === QueryBuilderConstants.DATATYPE_NUMBER
            || self.dataType === QueryBuilderConstants.DATATYPE_BOOL)
          {
            newValues[idx] = self.values[idx];
          }
          else
          {
            newValues[self.values[idx]] = self.values[idx];
          }
        }
      );
      this.values = newValues;
    }
  }

  (function () {
    QueryBuilderDefinition.prototype.getDataType = function () {
      return this.dataType ? this.dataType : QueryBuilderConstants.DATATYPE_STRING;
    };
    QueryBuilderDefinition.prototype.hasValues = function () {
      return !!this.values;
    };
    QueryBuilderDefinition.prototype.hasAjaxValues = function () {
      return !!this.valuesUrl;
    };
    QueryBuilderDefinition.prototype.isStrict = function () {
      return this.strictValues && (this.values || this.valuesUrl);
    };
  })();

  /**
   * @param {QueryBuilder} queryBuilder
   * @param {String?} key
   * @param {String?} comparator
   * @param {String?} value
   * @constructor
   */
  function QueryBuilderRule(queryBuilder, key, comparator, value) {
    var definition;
    if(key)
    {
      definition = queryBuilder.getDefinition(key);
      if(definition && !comparator)
      {
        comparator = definition.comparators[0]
      }
    }
    this._valCache = {};

    this._queryBuilder = queryBuilder;
    this._key = key || '';
    this._comparator = comparator || 'eq';

    // normalise value to remove invalid values if array
    if(value instanceof Object)
    {
      value = $.grep(value, function (n) { return (!!n); });
      if(value.length)
      {
        if(definition && definition.values && (!definition.valuesUrl))
        {
          value = $.grep(
            value, function (n) { return definition.values.hasOwnProperty(n); }
          );
        }
      }
    }

    this._value = this.sanitizeValue(
      value === null || value === undefined ? null : value
    );
    this._element = null;
  }

  (function () {
    /**
     * Get an input for a specified rule/definition
     *
     * @returns {jQuery}
     * @private
     */
    function getInput() {
      var inputTypeFn = this._queryBuilder.getInputTypeForRule(this);

      if(!inputTypeFn)
      {
        throw 'Input type not found for ' + this.getComparator() + ' ' + this.getDefinition().dataType;
      }
      var rule = this;
      return new inputTypeFn(
        this.getDefinition(),
        this.getComparator(),
        this.getValue(),
        function (v) { rule._setValue(v); }
      );
    } // getInput

    QueryBuilderRule.prototype.getValue = function () {
      return this._value;
    };

    QueryBuilderRule.prototype.sanitizeValue = function (value) {
      // process using the valueObject
      var valueObject = getInput.call(this);
      if(valueObject && valueObject.sanitize)
      {
        value = valueObject.sanitize(value);
      }
      return value;
    };

    QueryBuilderRule.prototype.setValue = function (value) {
      this._setValue(this.sanitizeValue(value));
    };

    QueryBuilderRule.prototype._setValue = function (value) {
      if(value === undefined)
      {
        value = null;
      }
      if(this._value !== value)
      {
        this._value = this._valCache[this._comparator] = value;
        this._queryBuilder._triggerChangeEvent();
      }
    };

    QueryBuilderRule.prototype.getComparator = function () {
      return this._comparator;
    };

    QueryBuilderRule.prototype.setComparator = function (value) {
      this._comparator = value;
      if(this._element && $('.qb-comparator', this._element).length)
      {
        $('.qb-comparator', this._element).val(value);
      }
      if(this._valCache[this._comparator] !== null && this._valCache[this._comparator] !== undefined)
      {
        this.setValue(this._valCache[this._comparator]);
      }
      else
      {
        this.setValue(this.getValue());
      }
      this.render();
      this._queryBuilder._triggerChangeEvent();
    };

    QueryBuilderRule.prototype.getKey = function () {
      return this._key;
    };

    QueryBuilderRule.prototype.setKey = function (value) {
      this._queryBuilder.changeCount(this._key, value);
      this._key = value;
      if(this._element)
      {
        $('.qb-key', this._element).val(value);
      }

      // if comparator doesnt exist
      var comparators = this.getDefinition().comparators;
      if(comparators.indexOf(this.getComparator()) === -1)
      {
        this.setComparator(comparators[0]);
      }
      else
      {
        this.setValue(null);
      }
      this.render();
      this._queryBuilder._triggerChangeEvent();
      this._queryBuilder.refreshNew();
    };

    QueryBuilderRule.prototype.render = function () {
      var self = this,
        $row = $('<div class="qb-rule"/>'),
        $propertySel = $('<select class="qb-key"/>'),
        ruleKey = this.getKey();

      $row.data(QB_DATA_NS_RULE, this);

      $row.append($propertySel);
      /**
       * @type {QueryBuilderDefinition|null}
       */
      var definition = this.getDefinition();
      if(!definition)
      {
        // no definition for ruleKey
        $propertySel.append(
          '<option selected="selected" value="' + ruleKey + '">' + ruleKey + '</option>'
        );
      }
      $.each(
        this._queryBuilder.definitions(), function (idx, def) {
          var $option = $(
            '<option value="' + def.key + '">' + this.displayName + '</option>'
          );
          if(ruleKey === def.key)
          {
            $option.prop('selected', true);
          }
          if(def.unique)
          {
            $option.addClass('unique-' + def.key);
            if(self._queryBuilder.getCount(def.key))
            {
              $option.prop('disabled', true);
            }
          }
          $propertySel.append($option);
        }
      );

      if(definition)
      {
        if(definition.required && definition.count <= 1)
        {
          $propertySel.prop('disabled', true);
        }
        if(!definition.dataType)
        {
          definition.dataType = 'string';
        }
        if(!definition['comparators'])
        {
          definition['comparators'] = ['eq'];
        }

        var showComparators = (definition.comparators.length > 1);
        // if showSingle is null, hide comparators if equals
        if(definition.comparators.length === 1)
        {
          if(definition.showSingleComparator === null)
          {
            showComparators = (definition.comparators[0] !== QueryBuilderConstants.COMPARATOR_EQUALS);
          }
          else
          {
            showComparators = definition.showSingleComparator;
          }
        }

        if(showComparators)
        {
          var $comparatorSel = $('<select class="qb-comparator"/>');
          $.each(
            definition ? definition['comparators'] : [QueryBuilderConstants.COMPARATOR_EQUALS],
            function (idx, ident) {
              var selected = (self.getComparator() === ident) ? ' selected="selected"' : '';
              $comparatorSel.append(
                '<option' + selected + ' value="' + ident + '">'
                + self._queryBuilder.getComparatorName(ident)
                + '</option>'
              );
            }
          );
          $row.append($comparatorSel);
        }
        var valueObject = getInput.call(this),
          $value = valueObject.render();
        if((this.getValue() === null)
          && definition.hasValues()
          && valueObject instanceof QueryBuilderSelectInput)
        {
          this.setValue(Object.keys(definition.values)[0]);
        }
        $row.append($value);
        if(valueObject['postRender'])
        {
          valueObject.postRender();
        }
      }

      var $removeButton = $(
        '<button class="qb-button qb-remove-rule"><span class="fa fa-trash"></span></button>'
      );
      $row.append($removeButton);
      if(definition && definition.required && definition.count <= 1)
      {
        $removeButton.prop('disabled', true);
      }

      if(!definition && this.getKey())
      {
        $propertySel.prop('disabled', true);
        $comparatorSel.prop('disabled', true);
        $value.prop('disabled', true);
      }

      if(this._element)
      {
        this._element.replaceWith($row);
        this._element = $row;
        $(this._queryBuilder._ele).trigger('render.querybuilder', this);
      }
      else
      {
        this._element = $row;
      }
      return $row;
    };

    QueryBuilderRule.prototype.getDefinition = function () {
      return this._queryBuilder.getDefinition(this.getKey());
    };

    QueryBuilderRule.prototype.getData = function () {
      return {
        'key': this.getKey(),
        'comparator': this.getComparator(),
        'value': this.getValue()
      };
    };

    QueryBuilderRule.prototype.removeElement = function () {
      if(this._element)
      {
        this._element.remove();
      }
    };

    QueryBuilderRule.prototype.getElement = function () {
      return this._element;
    };
  })();

  var _inputTypeProcessors = [];

  /**
   * @param ele
   * @constructor
   *
   * @private {Object.<String, String>} _options
   * @private {QueryBuilderDefinition[]} _definitions
   * @private {QueryBuilderRule[]} _rules
   */
  function QueryBuilder(ele) {
    this._ele = ele;
    this._options = {};
    this._definitions = [];
    this._hasRunInit = false;

    this._comparatorNames = {};
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_EQUALS, 'Equals');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_EQUALS_INSENSITIVE, 'Equals (insensitive)');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_EQUALS_INSENSITIVE, 'Does Not Equal (insensitive)');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_EQUALS, 'Does Not Equal');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_IN, 'In');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_IN, 'Not In');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_CONTAINS, 'Contains');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_DOES_NOT_CONTAIN, 'Does Not Contain');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_GREATER, 'Greater Than');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_GREATER_EQUAL, 'Greater Than or Equal to');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_LESS, 'Less Than');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_LESS_EQUAL, 'Less Than or Equal to');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_BETWEEN, 'Between');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_BETWEEN, 'Not Between');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_MULTIPLE_OF, 'Multiple Of');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_LIKE_IN, 'Like In');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_LIKE_IN, 'Not Like In');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_LIKE, 'Like');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_LIKE, 'Not Like');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_STARTS, 'Starts With');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_STARTS, 'Does Not Start With');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_ENDS, 'Ends With');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_NOT_ENDS, 'Does Not End With');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_BEFORE, 'Was Before');
    this.setComparatorName(QueryBuilderConstants.COMPARATOR_AFTER, 'Was After');

    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        var dataType = definition ? definition.getDataType() : QueryBuilderConstants.DATATYPE_STRING;
        if(dataType === QueryBuilderConstants.DATATYPE_NUMBER)
        {
          return QueryBuilderNumberInput;
        }
        else if(dataType === QueryBuilderConstants.DATATYPE_DECIMAL)
        {
          return QueryBuilderDecimalInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        var dataType = definition ? definition.getDataType() : QueryBuilderConstants.DATATYPE_STRING;
        if(dataType === QueryBuilderConstants.DATATYPE_BOOL)
        {
          return QueryBuilderBooleanInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        var dataType = definition ? definition.getDataType() : QueryBuilderConstants.DATATYPE_STRING;
        if(dataType === QueryBuilderConstants.DATATYPE_DATE)
        {
          return QueryBuilderDateInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        var dataType = definition ? definition.getDataType() : QueryBuilderConstants.DATATYPE_TIMESTAMP_DAY;
        if(dataType === QueryBuilderConstants.DATATYPE_TIMESTAMP_DAY)
        {
          return QueryBuilderTimestampInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        if(comparator === QueryBuilderConstants.COMPARATOR_BETWEEN
          || comparator === QueryBuilderConstants.COMPARATOR_NOT_BETWEEN)
        {
          return QueryBuilderBetweenInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        if(comparator === QueryBuilderConstants.COMPARATOR_BEFORE
          || comparator === QueryBuilderConstants.COMPARATOR_AFTER)
        {
          return QueryBuilderAgeInput;
        }
      }
    );
    QueryBuilder.addInputTypeProcessor(
      function (comparator, definition) {
        if(definition && definition.hasValues() && definition.isStrict())
        {
          return QueryBuilderSelectInput;
        }
      }
    );

    this._rules = [new QueryBuilderRule(this, '', '', null)];
    this._initialisedRules = false;
    this._initialisedDefinitions = false;
  }

  (function () {
    QueryBuilder.prototype.setComparatorName = function (comparator, text) {
      this._comparatorNames[comparator] = text;
    };

    QueryBuilder.prototype.getComparatorName = function (comparator) {
      if(this._comparatorNames[comparator])
      {
        return this._comparatorNames[comparator];
      }
      return comparator;
    };

    QueryBuilder.addInputTypeProcessor = function (fn) {
      _inputTypeProcessors.push(fn);
    };

    QueryBuilder.getInputType = function (comparator, definition) {
      var inputType = QueryBuilderTextInput;
      $(_inputTypeProcessors).each(
        function () {
          inputType = this(comparator, definition) || inputType;
        }
      );
      return inputType;
    };

    QueryBuilder.prototype.getInputTypeForRule = function (rule) {
      var definition = rule.getDefinition();
      if(definition && definition.inputType)
      {
        return definition.inputType;
      }
      return QueryBuilder.getInputType(rule.getComparator(), definition);
    };

    /**
     * Initialise with options - also the default command
     *
     * @param {Object} options
     */
    QueryBuilder.prototype.init = function (options) {
      var $ele = $(this._ele);
      if(!this._hasRunInit)
      {
        var $newRow = $('<select class="qb-add-rule"></select>');
        $newRow.append(
          '<option selected="selected" value="">- SELECT -</option>'
        );

        $ele
          .addClass('qb-container')
          .html($('<div class="qb-rules"/>'))
          .append($newRow)
          .on(
            'change', '.qb-add-rule', function () {
              var $container = $(this).closest('.qb-container'),
                qb = $container.data(QB_DATA_NS);
              qb.addRule($(this).val());
            }
          )
          .on(
            'change', '.qb-rule .qb-key', function () {
              var $rule = $(this).closest('.qb-rule'),
                qbr = $rule.data(QB_DATA_NS_RULE);
              qbr.setKey($(this).val());
            }
          )
          .on(
            'change', '.qb-rule .qb-comparator', function () {
              var $rule = $(this).closest('.qb-rule'),
                qbr = $rule.data(QB_DATA_NS_RULE);
              qbr.setComparator($(this).val());
            }
          )
          .on(
            'click', '.qb-rule .qb-remove-rule', function (e) {
              var $container = $(this).closest('.qb-container'),
                qb = $container.data(QB_DATA_NS),
                $rule = $(this).closest('.qb-rule'),
                qbr = $rule.data(QB_DATA_NS_RULE);
              qb.removeRule(qbr);
              e.stopPropagation();
            }
          );

        $ele.trigger('init.querybuilder', [QueryBuilder, this]);
      }

      this._hasRunInit = true;

      options = $.extend({}, $ele.data(), options);
      this.options(options);
      if(this._options && 'definitions' in this._options && this._options.definitions)
      {
        this.definitions(this._options.definitions);
      }
      if(this._options && 'rules' in this._options && this._options.rules)
      {
        this.rules(this._options.rules);
      }
    }; // init

    /**
     * Set/Get options
     *
     * @param data
     * @returns {*}
     */
    QueryBuilder.prototype.options = function (data) {
      if(data === undefined)
      {
        return this._options;
      }
      this._options = data;
      this.redraw();
    }; // options

    /**
     * Set/Get definitions
     *
     * @param data
     * @returns {*}
     */
    QueryBuilder.prototype.definitions = function (data) {
      var self = this;
      if(data === undefined)
      {
        return this._definitions;
      }

      this._initialisedDefinitions = false;

      if(typeof data === 'string')
      {
        $.getJSON(
          data, {}, function (defs) {
            self._definitions = [];
            $.each(
              defs, function () {
                self._definitions.push(new QueryBuilderDefinition(this));
              }
            );
            self._initialisedDefinitions = true;
            self.redraw();
          }
        );
      }
      else
      {
        this._definitions = [];
        $.each(
          data, function () {
            self._definitions.push(new QueryBuilderDefinition(this));
          }
        );
        self._initialisedDefinitions = true;
        this.redraw();
      }
    }; // definitions

    QueryBuilder.prototype.getDefinition = function (key) {
      var def = null;
      $.each(
        this._definitions, function () {
          if(this.key === key)
          {
            def = this;
            return false;
          }
        }
      );
      return def;
    };

    /**
     * Triggers the event notifying of rule data
     * @private
     */
    QueryBuilder.prototype._triggerChangeEvent = function () {
      $(this._ele).trigger(
        'change.querybuilder', [this.rules()]
      );
    };

    QueryBuilder.prototype.refreshNew = function () {
      var self = this,
        $newRow = $('.qb-add-rule', this._ele).empty();

      $newRow.append(
        '<option selected="selected" value="">- SELECT -</option>'
      );
      $.each(
        this.definitions(), function (idx, def) {
          var $option = $(
            '<option value="' + def.key + '">' + this.displayName + '</option>'
          );
          if(def.unique)
          {
            $option.addClass('unique-' + def.key);
            if(self.getCount(def.key))
            {
              $option.prop('disabled', true);
            }
            else
            {
              $option.prop('disabled', false);
            }
          }
          $newRow.append($option);
        }
      );

    };

    /**
     * Set/Get rules
     * Set triggers redraw
     *
     * @param data
     * @returns {Array}
     */
    QueryBuilder.prototype.rules = function (data) {
      var self = this;
      if(data === undefined)
      {
        // no data - return object of all rules
        var currentData = [];
        $(this._rules).each(
          function () {
            var data = this.getData();
            if(data.key && data.value !== null)
            {
              currentData.push(data);
            }
          }
        );
        return currentData;
      }

      this._initialisedRules = false;

      // if data is object, read it into rules
      if(typeof data === 'object')
      {
        processRules.call(this, data);
        return null;
      }
      else if(typeof data === 'function')
      {
        processRules.call(this, data());
        return null;
      }
      else if(typeof data === 'string')
      {
        $.getJSON(
          data, {}, function (rules) {
            processRules.call(self, rules);
          }
        );
        return null;
      }
      throw 'Unknown data format for rules';
    }; // rules

    /**
     * @param {String} key
     * @param {String} comparator
     * @param {String} value
     * @returns {QueryBuilderRule}
     */
    QueryBuilder.prototype.addRule = function (key, comparator, value) {
      this.incrementCounter(key);
      var rule = new QueryBuilderRule(this, key, comparator, value);
      if(rule.getDefinition())
      {
        this._rules.push(rule);
        if(this._initialisedDefinitions)
        {
          if(!rule.getValue())
          {
            var it = this.getInputTypeForRule(rule);
            rule.setValue(it.defaultValue(rule.getDefinition()))
          }

          var $ele = rule.render();
          $('.qb-rules', this._ele).append($ele);
          $(this._ele).trigger('render.querybuilder', rule);
        }
      }
      this.refreshNew();
    };

    /**
     * Redraw the container
     */
    QueryBuilder.prototype.redraw = function () {
      if(!(this._initialisedDefinitions))
      {
        return;
      }

      var self = this;
      $('.qb-rules', this._ele).empty();

      $.each(
        this._rules, function () {
          var $ele = this.render();
          $('.qb-rules', self._ele).append($ele);
          $(self._ele).trigger('render.querybuilder', this);
        }
      );

      if(self._definitions)
      {
        // add any required fields which are not already added
        $.each(
          self._definitions, function () {
            if(this.required)
            {
              var def = this, found = false;
              $.each(
                self._rules, function () {
                  if(this.getKey() === def.key)
                  {
                    found = true;
                  }
                }
              );
              if(!found)
              {
                self.addRule(def.key, def.comparators[0], null);
              }
            }
          }
        );

        if(this._definitions.length)
        {
          var count = 0;
          $.each(
            this._definitions, function () {
              if(!this.unique)
              {
                count++;
              }
            }
          );
        }
      }
      if(this._initialisedRules)
      {
        self._triggerChangeEvent();
      }
      this.refreshNew();
    }; // redraw

    QueryBuilder.prototype.removeRule = function (rule) {
      var self = this, key = rule.getKey();
      this.decrementCounter(key);
      rule.removeElement();
      $.each(
        this._rules, function (idx) {
          if(this === rule)
          {
            self._rules.splice(idx, 1);
          }
        }
      );
      this._triggerChangeEvent();
      this.refreshNew();
    }; // removeRule

    QueryBuilder.prototype.getCount = function (key) {
      var def = this.getDefinition(key);
      if(def)
      {
        return def.count;
      }
      return 0;
    };

    QueryBuilder.prototype.changeCount = function (oldKey, newKey) {
      this.decrementCounter(oldKey);
      this.incrementCounter(newKey);
    };

    QueryBuilder.prototype.incrementCounter = function (key) {
      var def = this.getDefinition(key);
      if(def)
      {
        def.count++;
        if(def.unique)
        {
          $('.qb-key .unique-' + key, this._ele).prop('disabled', true);
        }
        if(def.required && def.count > 1)
        {
          $.each(
            this._rules, function () {
              if(this.getKey() === key)
              {
                $('.qb-remove-rule', this.getElement()).prop('disabled', false);
                $('.qb-key', this.getElement()).prop('disabled', false);
              }
            }
          );
        }
      }
    };

    QueryBuilder.prototype.decrementCounter = function (key) {
      var def = this.getDefinition(key);
      if(def)
      {
        def.count--;
        if(def.unique && def.count === 0)
        {
          // allow this on all again
          $('.qb-key .unique-' + key, this._ele).prop('disabled', false);
        }
        if(def.required && def.count === 1)
        {
          // remove the delete button
          $.each(
            this._rules, function () {
              if(this.getKey() === key)
              {
                $('.qb-remove-rule', this.getElement()).prop('disabled', true);
              }
            }
          );
        }
      }
    };

    function processRules(data) {
      if(this._definitions.length > 0)
      {
        _processRules.call(this, data);
      }
      else
      {
        var self = this,
          intervalId = setInterval(
            function () {
              if(self._definitions.length > 0)
              {
                _processRules.call(self, data);
                clearInterval(intervalId);
              }
            }, 100
          );
      }
    }

    function _processRules(data) {
      var self = this;

      while(this._rules.length)
      {
        self.removeRule(this._rules[0]);
      }

      if(data)
      {
        $.each(
          data, function (key) {
            var definition = self.getDefinition(key),
              defaultComparator = definition ? definition.comparators[0] : 'eq';
            if(typeof this === 'object')
            {
              if(this instanceof String)
              {
                self.addRule(key, defaultComparator, this);
              }
              else if('key' in this && 'comparator' in this && 'value' in this)
              {
                self.addRule(this.key, this.comparator, this.value);
              }
              else if(this.length === 1)
              {
                self.addRule(key, defaultComparator, this[0]);
              }
              else
              {
                self.addRule(key, 'in', this);
              }
            }
            else
            {
              self.addRule(key, defaultComparator, this);
            }
          }
        );
        self._initialisedRules = true;
        this.redraw();
      }
    }
  })();

  function drawSelect(options, value) {
    var $select = $('<select/>');
    if(value && Object.keys(options).indexOf(value) === -1)
    {
      var $option = $('<option/>').text(value).attr('value', value);
      $option.prop('selected', true);
      $option.prop('disabled', true);
      $select.append($option);
    }
    $.each(
      options, function (idx) {
        var $option = $('<option/>').text(this).attr('value', idx);
        if(idx === value)
        {
          $option.prop('selected', true);
        }
        $select.append($option);
      }
    );
    return $select;
  } // drawSelect

  var QueryBuilderTextInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return '';
    };

    Constructor.prototype.render = function () {
      var self = this;
      return $('<input type="text"/>').attr('value', this._value).on(
        'change', function () {
          self._changeCb($(this).val());
        }
      );
    };

    return Constructor;
  })();

  var QueryBuilderBooleanInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return '1';
    };

    Constructor.prototype.render = function () {
      var self = this;
      return drawSelect(
        {'1': 'True', '0': 'False'}, this._value
      ).on(
        'change', function () {
          self._changeCb($(this).val());
        }
      );
    };

    return Constructor;
  })();

  var QueryBuilderSelectInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return Object.keys(definition.values)[0];
    };

    Constructor.prototype.sanitize = function (value) {
      if(typeof (value) === 'object' && value && value.length)
      {
        return value.length > 0 ? value[0] : null;
      }
      return value;
    };

    Constructor.prototype.render = function () {
      var self = this;
      return drawSelect(
        this._definition.values, this._value
      ).on(
        'change', function () {
          self._changeCb($(this).val());
        }
      );
    };

    return Constructor;
  })();

  var QueryBuilderDecimalInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return 0;
    };

    Constructor.prototype.render = function () {
      var self = this;
      return $('<input type="number" step="0.01" />')
        .attr('value', this._value)
        .on(
          'change', function () {
            self._changeCb($(this).val());
          }
        );
    };

    return Constructor;
  })();

  var QueryBuilderNumberInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return 0;
    };

    Constructor.prototype.render = function () {
      var self = this;
      return $('<input type="number" />')
        .attr(
          'value', this._value
        )
        .on(
          'change', function () {
            self._changeCb($(this).val());
          }
        );
    };

    return Constructor;
  })();

  var QueryBuilderDateInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null || parseInt(value) === 0 ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return getToday();
    };

    function getToday() {
      var d = new Date();
      var today = d.getUTCFullYear();
      today += '-' + ('0' + (d.getUTCMonth() + 1)).slice(-2);
      today += '-' + ('0' + d.getUTCDate()).slice(-2);
      return today;
    }

    Constructor.prototype.render = function () {
      var self = this;
      return $('<input type="date" />')
        .val(this._value)
        .on(
          'change', function () {
            self._changeCb($(this).val());
          }
        );
    };

    return Constructor;
  })();

  var QueryBuilderTimestampInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      value = (!value) || parseInt(value) === 0 ? Constructor.defaultValue(definition) : parseInt(value);
      this._value = _getTime(new Date(value * 1000), this._comparator);
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return _getTime(new Date());
    };

    Constructor.prototype.render = function () {
      var d = new Date();
      d.setTime(this._value * 1000);
      var self = this;
      return $('<input type="date" />')
        .val(_getFormatted(d))
        .on('change', function () {self._changeCb(_getTime(new Date($(this).val()), self._comparator));});
    };

    function _getFormatted(d) {
      var today = d.getUTCFullYear();
      today += '-' + ('0' + (d.getUTCMonth() + 1)).slice(-2);
      today += '-' + ('0' + d.getUTCDate()).slice(-2);
      return today;
    }

    function _getTime(d, comparator) {
      var t = Math.floor(d.getTime() / 1000);
      t = t - (t % 86400);
      if(comparator === QueryBuilderConstants.COMPARATOR_LESS_EQUAL)
      {
        t += 86399;
      }
      return t;
    }

    return Constructor;
  })();

  var QueryBuilderAgeInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      this._value = value === null ? Constructor.defaultValue(definition) : value;
      this._changeCb(this._value);
    }

    Constructor.defaultValue = function (definition) {
      return -10080;
    };

    Constructor.prototype.render = function () {
      var self = this,
        value = this._value,
        unit = 1,
        $count = $('<input class="age-count" type="number" />'),
        $unit = $('<select class="age-unit"/>'),
        $negate = $('<select class="age-unit"></select>');

      var units = {
        'Minutes': 1,
        'Hours':   60,
        'Days':    1440,
        'Weeks':   10080
      };
      var sortedUnits = [];
      for(var u in units)
      {
        if(units.hasOwnProperty(u))
        {
          sortedUnits.push([u, units[u]]);
        }
      }
      sortedUnits.sort(function (a, b) {return a[1] - b[1];});

      // find unit and mutate value
      $.each(
        sortedUnits.slice().reverse(), function (idx, v) {
          if(value % v[1] === 0)
          {
            unit = v[1];
            value = value / v[1];
            return false;
          }
        }
      );

      $.each(
        sortedUnits, function (idx, v) {
          var $option = $('<option>').val(v[1]).text(v[0]);
          if(unit === v[1])
          {
            $option.prop('selected', true);
          }
          $unit.append($option);
        }
      );

      $count.val(Math.abs(value));
      var $ago = $('<option value="-1">Ago</option>'),
        $fromNow = $('<option value="1">From now</option>');
      if(this._value <= 0)
      {
        $ago.prop('selected', true);
      }
      else
      {
        $fromNow.prop('selected', true);
      }
      $negate.append($ago, $fromNow);

      // create event to set the value
      var $return = $().add($count).add($unit).add($negate);
      $return.on(
        'change', function () {
          self._changeCb($count.val() * $unit.val() * $negate.val());
        }
      );
      return $return;
    };

    return Constructor;
  })();

  var QueryBuilderBetweenInput = (function () {
    function Constructor(definition, comparator, value, changeCb) {
      this._comparator = comparator;
      this._definition = definition;
      this._changeCb = changeCb;
      value = value === null || value === undefined || String(value).indexOf(',') === -1
        ? Constructor.defaultValue(definition) : value;

      var values = value.split(',');
      this._minVal = values[0];
      this._maxVal = values[1];
      this._value = this._minVal + ',' + this._maxVal;
      this._changeCb(this._value);
    }

    Constructor.prototype._update = function () {
      this._changeCb(this._minVal + ',' + this._maxVal);
    };

    Constructor.defaultValue = function (definition) {
      if(definition.dataType === QueryBuilderConstants.DATATYPE_DATE)
      {
        var d = QueryBuilderDateInput.defaultValue(definition);
        return d + ',' + d;
      }
      else if(definition.dataType === QueryBuilderConstants.DATATYPE_TIMESTAMP_DAY)
      {
        var t = QueryBuilderTimestampInput.defaultValue(definition);
        return t + ',' + (t + 86399);
      }
      else
      {
        return '0,0';
      }
    };

    Constructor.prototype._minUp = function (value) {
      this._minVal = value;
      this._update();
    };

    Constructor.prototype._maxUp = function (value) {
      this._maxVal = value;
      this._update();
    };

    Constructor.prototype.render = function () {
      var _minUp = this._minUp.bind(this);
      var _maxUp = this._maxUp.bind(this);
      var values = this._value.split(',');
      var $min, $max;

      switch(this._definition.dataType)
      {
        case QueryBuilderConstants.DATATYPE_DECIMAL:
          $min = (new QueryBuilderDecimalInput(this._definition, this._comparator, values[0], _minUp)).render();
          $max = (new QueryBuilderDecimalInput(this._definition, this._comparator, values[1], _maxUp)).render();
          break;
        case QueryBuilderConstants.DATATYPE_NUMBER:
          $min = (new QueryBuilderNumberInput(this._definition, this._comparator, values[0], _minUp)).render();
          $max = (new QueryBuilderNumberInput(this._definition, this._comparator, values[1], _maxUp)).render();
          break;
        case QueryBuilderConstants.DATATYPE_TIMESTAMP_DAY:
          var cl = QueryBuilderConstants.COMPARATOR_LESS_EQUAL;
          $min = (new QueryBuilderTimestampInput(this._definition, this._comparator, values[0], _minUp)).render();
          $max = (new QueryBuilderTimestampInput(this._definition, cl, values[1], _maxUp)).render();
          break;
        case QueryBuilderConstants.DATATYPE_DATE:
          $min = (new QueryBuilderDateInput(this._definition, this._comparator, values[0], _minUp)).render();
          $max = (new QueryBuilderDateInput(this._definition, this._comparator, values[1], _maxUp)).render();
          break;
        default:
          $min = (new QueryBuilderTextInput(this._definition, this._comparator, values[0], _minUp)).render();
          $max = (new QueryBuilderTextInput(this._definition, this._comparator, values[1], _maxUp)).render();
          break;
      }

      $min.addClass('qb-between-min');
      $max.addClass('qb-between-max');

      // create event to set the value
      return $().add($min).add($max);
    };

    return Constructor;
  })();

}(jQuery, window, document));
