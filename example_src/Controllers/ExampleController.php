<?php
namespace Fortifi\UiExample\Controllers;

use Cubex\Http\Response;
use Cubex\View\LayoutController;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinition as QBD;
use Fortifi\Ui\ContentElements\QueryBuilder\QueryBuilderDefinitions;
use Fortifi\Ui\ProjectSupport\FortifiUiLayout;
use Fortifi\UiExample\Views\AsideView;
use Fortifi\UiExample\Views\ButtonsView;
use Fortifi\UiExample\Views\ColoursView;
use Fortifi\UiExample\Views\HeadersView;
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
      'string'
    );
    $browserDefinition->setValues(
      [
        'chrome'  => 'Chrome',
        'firefox' => 'Firefox',
        'safari'  => 'Safari'
      ]
    );
    $browserDefinition->setComparators(
      [
        QBD::COMPARATOR_EQUALS,
        QBD::COMPARATOR_IN
      ]
    );
    $definitions->addDefinition($browserDefinition);
    $sidDefinition = new QBD(
      'sid',
      'Sub ID',
      'string'
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

    $hasOrdersDefinition = new QBD('hasOrders', 'Has Orders', 'bool');
    $definitions->addDefinition($hasOrdersDefinition);

    $actionDefinition = new QBD('action', 'Action', 'string');
    $actionDefinition->setComparators([QBD::COMPARATOR_EQUALS]);
    $actionDefinition->setRequired(true);
    $actionDefinition->setValues(
      ['click' => 'Click', 'lead' => 'Lead', 'acquisition' => 'Acquisition']
    );
    $definitions->addDefinition($actionDefinition);
    return new Response(json_encode($definitions->forOutput()));
  }

  public function qbPolicyData()
  {
    $policy = [
      ['key' => 'browser', 'comparator' => 'eq', 'value' => 'chrome'],
      ['key' => 'company', 'comparator' => 'in', 'value' => ['x', 'y']],
      ['key' => 'affiliateType', 'comparator' => 'eq', 'value' => 'a'],
      ['key' => 'action', 'comparator' => 'eq', 'value' => 'lead'],
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
