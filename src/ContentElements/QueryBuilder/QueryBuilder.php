<?php
namespace Fortifi\Ui\ContentElements\QueryBuilder;

use Fortifi\Ui\UiElement;
use Packaged\Dispatch\AssetManager;
use Packaged\Glimpse\Core\SafeHtml;
use Packaged\Glimpse\Tags\Div;

class QueryBuilder extends UiElement
{
  protected $_optionsUrl;
  protected $_rulesUrl;
  protected $_classes = ['query-builder'];

  public static function create($optionsUrl = null, $rulesUrl = null)
  {
    $qb = new static;
    $qb->_optionsUrl = $optionsUrl;
    $qb->_rulesUrl = $rulesUrl;
    return $qb;
  }

  public function addClass(...$class)
  {
    $this->_classes = array_unique(array_merge($this->_classes, $class));
    return $this;
  }

  public function removeClass(...$class)
  {
    $this->_classes = array_diff($this->_classes, $class);
    return $this;
  }

  public function processIncludes(AssetManager $assetManager, $vendor = false)
  {
    $assetManager->requireJs('assets/vendor/params/params');
    if($vendor)
    {
      $assetManager->requireJs('assets/js/ContentElements');
    }
    else
    {
      $assetManager->requireJs('assets/js/ContentElements/QueryBuilder');
    }
  }

  /**
   * @return SafeHtml|SafeHtml[]
   */
  protected function _produceHtml()
  {
    $div = Div::create('Query Builder Placeholder');
    if($this->_classes)
    {
      $div->addClass(...$this->_classes);
    }
    if($this->_optionsUrl)
    {
      $div->setAttribute('data-qb-options', $this->_optionsUrl);
    }
    if($this->_rulesUrl)
    {
      $div->setAttribute('data-qb-rules', $this->_rulesUrl);
    }
    return $div;
  }
}
