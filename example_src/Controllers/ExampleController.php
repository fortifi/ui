<?php
namespace Fortifi\UiExample\Controllers;

use Cubex\Http\Response;
use Cubex\View\LayoutController;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDataType as QBDT;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinition as QBD;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinitions;
use Fortifi\Ui\ProjectSupport\FortifiUiLayout;
use Packaged\Glimpse\Tags\Div;
use Packaged\Helpers\Arrays;
use Packaged\Helpers\Objects;

class ExampleController extends LayoutController
{
  protected $_views = [];

  protected function _init()
  {
    $views = glob(dirname(__DIR__) . '/Views/*View.php');
    foreach($views as $view)
    {
      $view = basename($view);
      if($view == 'AbstractUiExampleView.php')
      {
        continue;
      }
      $key = strtolower(substr($view, 0, -8));
      $obj = Objects::create('Fortifi\\UiExample\\Views\\' . substr($view, 0, -4), []);
      $this->_views[$key] = $obj;
    }
    $layout = new FortifiUiLayout($this);
    $layout->setData('views', $this->_views);
    $this->setLayout($layout);
  }

  public function defaultAction($page = null)
  {
    if(isset($this->_views[$page]))
    {
      return $this->_views[$page];
    }
    return 'Page Unavailable';
  }

  public function getRoutes()
  {
    return [
      'querybuilder/definition' => 'qbDefinition',
      'querybuilder/policy'     => 'qbPolicyData',
      'querybuilder/browsers'   => 'qbBrowsers',
      'querybuilder/sids'       => 'qbSids',

      'dropdowns/content' => 'dropContent',

      ':page' => 'defaultAction',
    ];
  }

  public function dropContent()
  {
    return Div::create('this is a dropdown loaded by ajax');
  }

  public function qbDefinition()
  {
    $definitions = new QueryBuilderDefinitions();

    $browserDefinition = new QBD(
      'browser',
      'Browser',
      QBDT::STRING
    );
    $browserDefinition->setValues(['arg' => 'moo']);
    $browserDefinition->setValuesUrl('/querybuilder/browsers');
    $browserDefinition->setComparators(
      [
        QBD::COMPARATOR_EQUALS,
        QBD::COMPARATOR_IN,
      ]
    );
    $definitions->addDefinition($browserDefinition);

    $browserDefinition = new QBD(
      'dropdown_test',
      'DropTest',
      QBDT::STRING
    );
    $browserDefinition->setValues(['drop1', 'drop2', 'drop3']);
    $definitions->addDefinition($browserDefinition);

    $between = new QBD('between_test', 'Between Test', QBDT::DECIMAL);
    $between->setComparators(
      [
        QBD::COMPARATOR_BETWEEN,
        QBD::COMPARATOR_NOT_BETWEEN,
      ]
    );
    $definitions->addDefinition($between);

    $multipleOf = new QBD('multiple_of_test', 'Multiple Of Test', QBDT::NUMBER);
    $multipleOf->setComparators(
      [
        QBD::COMPARATOR_MULTIPLE_OF,
      ]
    );
    $definitions->addDefinition($multipleOf);

    $between = new QBD('timestamp_day', 'Timestamp (Day)', QBDT::TIMESTAMP_DAY);
    $between->setComparators(
      [
        QBD::COMPARATOR_GREATER_OR_EQUAL,
        QBD::COMPARATOR_LESS_OR_EQUAL,
        QBD::COMPARATOR_BETWEEN,
        QBD::COMPARATOR_NOT_BETWEEN,
      ]
    );
    $definitions->addDefinition($between);

    // SID
    $sidDefinition = new QBD(
      'sid',
      'Sub ID',
      QBDT::STRING
    );
    $sidDefinition->setValues($this->qbSids());
    $sidDefinition->setStrict(false);
    $sidDefinition->setComparators(
      [
        QBD::COMPARATOR_EQUALS,
        QBD::COMPARATOR_NOT_EQUALS,
        QBD::COMPARATOR_NOT_EQUALS_INSENSITIVE,
        QBD::COMPARATOR_LIKE_IN,
        QBD::COMPARATOR_NOT_LIKE_IN,
        QBD::COMPARATOR_IN,
        QBD::COMPARATOR_NOT_IN,
      ]
    );
    $definitions->addDefinition($sidDefinition);

    //
    $expiryDateDefinition = new QBD('expiryDate', 'Expiry Date', QBDT::DATE);
    $expiryDateDefinition->addComparator(QBD::COMPARATOR_BETWEEN);
    $definitions->addDefinition($expiryDateDefinition);

    $hasOrdersDefinition = new QBD('hasOrders', 'Has Orders', QBDT::BOOL);
    $definitions->addDefinition($hasOrdersDefinition);

    $def = new QBD('required', 'Required', QBDT::STRING);
    $def->setRequired(true);
    $definitions->addDefinition($def);

    $def = new QBD('unique', 'Unique', QBDT::STRING);
    $def->setUnique(true);
    $definitions->addDefinition($def);

    $def = new QBD('unique_required', 'Unique & Required', QBDT::STRING);
    $def->setRequired(true);
    $def->setUnique(true);
    $def->setValues(
      [
        'unique1' => 'Unique One',
        'unique2' => 'Unique Two',
        'unique3' => 'Unique Three',
      ]
    );
    $definitions->addDefinition($def);

    $def = new QBD('aaa', 'aaa', QBDT::STRING);
    $def->setValues(
      [
        'test1' => 'Test One',
        'test2' => 'Test Two',
        'test3' => 'Test Three',
      ]
    );
    $definitions->addDefinition($def);
    return new Response(json_encode($definitions->forOutput()));
  }

  public function qbPolicyData()
  {
    $policy = [
      [
        'key'        => 'browser',
        'comparator' => QBD::COMPARATOR_IN,
        'value'      => ['chrome', 'firefox', 'kdsfgkjsdgohwego'],
      ],
      [
        'key'        => 'browser',
        'comparator' => QBD::COMPARATOR_EQUALS,
        'value'      => '"><script>alert(\'break\')</script>',
      ],
      [
        'key'        => 'expiryDate',
        'comparator' => QBD::COMPARATOR_BETWEEN,
        'value'      => date('Y-m-d', time() - 86401) . ',' . date('Y-m-d'),
      ],
      'sid' => ['12'],
      ['key' => 'aaa', 'comparator' => QBD::COMPARATOR_EQUALS, 'value' => 'test3'],
    ];
    return $policy;
  }

  public function qbBrowsers()
  {
    $query = $this->_getRequest()->query->get('search');
    $values = [
      ['value' => '', 'text' => 'No Browser'],
      ['value' => 'chrome', 'text' => 'Chrome'],
      ['value' => 'firefox', 'text' => 'Firefox'],
      ['value' => 'safari', 'text' => 'Safari'],
      ['value' => '"><script>alert(\'break 1\')</script>', 'text' => 'Break 1'],
      ['value' => 'Break 2', 'text' => '"><script>alert(\'break 2\')</script>'],
      [
        'value' => '"><script>alert(\'break 3\')</script>',
        'text'  => '"><script>alert(\'break 3\')</script>',
      ],
    ];
    return array_filter(
      $values,
      function ($var) use ($query) {
        return stripos($var['value'], $query) !== false
          || stripos($var['text'], $query) !== false;
      }
    );
  }

  public function qbSids()
  {
    $values = [
      ['value' => 'malware15IT', 'text' => 'malware15IT'],
      ['value' => 'malware16IS', 'text' => 'malware16IS'],
      ['value' => 'malware17IG', 'text' => 'malware17IG'],
      ['value' => '2015adware', 'text' => '2015adware'],
      ['value' => '2016adware', 'text' => '2016adware'],
      ['value' => 'spyware15', 'text' => 'spyware15'],
      ['value' => 'spyware16', 'text' => 'spyware16'],
    ];

    $query = $this->_getRequest()->query->get('search');
    if($query)
    {
      return array_filter(
        $values,
        function ($var) use ($query) {
          return stripos($var['value'], $query) !== false
            && stripos($var['text'], $query) !== false;
        }
      );
    }
    else
    {
      return Arrays::ipull($values, 'text');
    }
  }
}
