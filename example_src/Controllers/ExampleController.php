<?php
namespace Fortifi\UiExample\Controllers;

use Cubex\Http\Response;
use Cubex\View\LayoutController;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDataType as QBDT;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinition as QBD;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinitions;
use Fortifi\Ui\ProjectSupport\FortifiUiLayout;
use Fortifi\UiExample\Views\ColoursView;
use Fortifi\UiExample\Views\FlipperView;
use Fortifi\UiExample\Views\IconsView;
use Fortifi\UiExample\Views\ObjectListsView;
use Fortifi\UiExample\Views\PageNavigationView;
use Fortifi\UiExample\Views\PanelsView;
use Fortifi\UiExample\Views\QueryBuilderView;
use Fortifi\UiExample\Views\TextView;

class ExampleController extends LayoutController
{
  protected function _init()
  {
    $this->setLayout(new FortifiUiLayout($this));
  }

  public function defaultAction($page = null)
  {
    switch($page)
    {
      case 'panels':
        return new PanelsView();
      case 'colours':
        return new ColoursView();
      case 'navigation':
        return new PageNavigationView();
      case 'text':
        return new TextView();
      case 'objectlist':
        return new ObjectListsView();
      case 'querybuilder':
        return new QueryBuilderView();
      case 'flipper':
        return new FlipperView();
      case 'icons':
        return new IconsView();
      default:
        return 'Coming Soon';
    }
  }

  public function qbDefinition()
  {
    $definitions = new QueryBuilderDefinitions();

    $browserDefinition = new QBD(
      'browser',
      'Browser',
      QBDT::STRING
    );
    $browserDefinition->setValues(['' => '']);
    $browserDefinition->setValuesUrl('/querybuilder/browsers');
    $browserDefinition->setComparators(
      [
        QBD::COMPARATOR_EQUALS,
        QBD::COMPARATOR_IN
      ]
    );
    $definitions->addDefinition($browserDefinition);

    $between = new QBD('between_test', 'Between Test', QBDT::DECIMAL);
    $between->setComparators(
      [
        QBD::COMPARATOR_BETWEEN,
        QBD::COMPARATOR_NOT_BETWEEN
      ]
    );
    $definitions->addDefinition($between);

    $between = new QBD('between_date', 'Between Test (Date)', QBDT::DATE);
    $between->setComparators(
      [
        QBD::COMPARATOR_BETWEEN,
        QBD::COMPARATOR_NOT_BETWEEN
      ]
    );
    $definitions->addDefinition($between);

    $sidDefinition = new QBD(
      'sid',
      'Sub ID',
      QBDT::STRING
    );
    $sidDefinition->setComparators(
      [
        QBD::COMPARATOR_EQUALS,
        QBD::COMPARATOR_IN,
        QBD::COMPARATOR_LIKE,
        QBD::COMPARATOR_ENDS,
        QBD::COMPARATOR_STARTS
      ]
    );
    $definitions->addDefinition($sidDefinition);

    $expiryDateDefinition = new QBD('expiryDate', 'Expiry Date', QBDT::DATE);
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
        'unique3' => 'Unique Three'
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
        'comparator' => 'in',
        'value'      => ['chrome', 'firefox']
      ],
      [
        'key'        => 'browser',
        'comparator' => 'eq',
        'value'      => '"><script>alert(\'break\')</script>'
      ],
      ['key' => 'expiryDate', 'comparator' => 'eq', 'value' => date('Y-m-d')],
      'sid' => ['12'],
    ];
    return $policy;
  }

  public function qbBrowsers()
  {
    $query = $this->_getRequest()->query->get('search');
    $values = [
      ['value' => 'chrome', 'text' => 'Chrome'],
      ['value' => 'firefox', 'text' => 'Firefox'],
      ['value' => 'safari', 'text' => 'Safari'],
      ['value' => '"><script>alert(\'break 1\')</script>', 'text' => 'Break 1'],
      ['value' => 'Break 2', 'text' => '"><script>alert(\'break 2\')</script>'],
      [
        'value' => '"><script>alert(\'break 3\')</script>',
        'text'  => '"><script>alert(\'break 3\')</script>'
      ],
    ];
    return array_filter(
      $values,
      function ($var) use ($query)
      {
        return stripos($var['value'], $query) !== false
        && stripos($var['text'], $query) !== false;
      }
    );
  }

  public function getRoutes()
  {
    return [
      'querybuilder/definition' => 'qbDefinition',
      'querybuilder/policy'     => 'qbPolicyData',
      'querybuilder/browsers'   => 'qbBrowsers',
      ':page'                   => 'defaultAction',
    ];
  }
}
