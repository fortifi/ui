<?php
namespace Fortifi\UiExample\Controllers;

use Cubex\Http\Response;
use Cubex\View\LayoutController;
use Fortifi\Ui\ProjectSupport\FortifiUiLayout;
use Fortifi\UiExample\Views\ColoursView;
use Fortifi\UiExample\Views\ObjectListsView;
use Fortifi\UiExample\Views\PageNavigationView;
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
      default:
        return 'Coming Soon';
    }
  }

  public function qbDefinition()
  {
    $response = [
      'browser'       => [
        'display'     => 'Browser Name',
        'comparators' => ['eq' => 'Equals', 'in' => 'IN'],
        'values'      => [
          'chrome'  => 'Chrome',
          'firefox' => 'Firefox',
          'safari'  => 'Safari'
        ],
        'ajaxUrl'     => ''
      ],
      'company'       => [
        'display'     => 'Company',
        'comparators' => ['eq' => 'Equals', 'in' => 'IN'],
      ],
      'hasOrders'     => [
        'display'  => 'Has Orders',
        'dataType' => 'bool'
      ],
      'affiliateType' => [
        'display'     => 'Affilaite Type',
        'comparators' => ['eq' => 'Equals', 'in' => 'IN'],
      ]
    ];
    return new Response(json_encode($response));
  }

  public function qbPolicyData()
  {
    $policy = [
      ['key' => 'browser', 'comparator' => 'eq', 'value' => 'chrome'],
      ['key' => 'company', 'comparator' => 'in', 'value' => ['x', 'y']],
      ['key' => 'affiliateType', 'comparator' => 'eq', 'value' => 'a'],
    ];
    return new Response(json_encode($policy));
  }

  public function getRoutes()
  {
    return [
      'querybuilder/definition' => 'qbDefinition',
      'querybuilder/policy'     => 'qbPolicyData',
      ':page'                   => 'defaultAction',
    ];
  }
}
