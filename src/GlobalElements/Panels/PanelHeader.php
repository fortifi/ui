<?php
namespace Fortifi\Ui\GlobalElements\Panels;

use Fortifi\Ui\GlobalElements\Icons\FontIcon;
use Fortifi\Ui\Ui;
use Fortifi\Ui\UiElement;
use Packaged\Glimpse\Tags\Div;
use Packaged\Glimpse\Tags\Link;
use Packaged\Glimpse\Tags\Span;
use Packaged\Glimpse\Tags\Text\HeadingTwo;

class PanelHeader extends UiElement
{
  const BG_INFO = Ui::BG_INFO_LIGHT;
  const BG_SUCCESS = Ui::BG_SUCCESS_LIGHT;
  const BG_WARNING = Ui::BG_WARNING_LIGHT;
  const BG_DANGER = Ui::BG_DANGER_LIGHT;
  const BG_MUTED = '';

  const STATUS_OPEN = 'Open';
  const STATUS_CLOSED = 'Closed';
  const STATUS_HELD = 'Held';

  protected $_title;
  protected $_actions;
  protected $_icon;
  protected $_status;
  protected $_bgColour;

  public static function create($title = '')
  {
    $heading = new static;
    $heading->_title = $title;
    return $heading;
  }

  public function setTitle($title)
  {
    $this->_title = $title;
    return $this;
  }

  /**
   * Add singular action to PanelHeading
   *
   * @param Link $action
   *
   * @return $this
   */
  public function addAction(Link $action)
  {
    $this->_actions[] = $action->addClass(Ui::MARGIN_MEDIUM_LEFT);
    return $this;
  }

  /**
   * Process array of actions to add to PanelHeading
   *
   * @param array $actions
   *
   * @return $this
   * @throws \Exception
   */
  public function setActions(array $actions)
  {
    foreach($actions as $action)
    {
      if(!$action instanceof Link)
      {
        throw new \Exception('addActions() array must contain Link() objects');
      }
      else
      {
        $this->addAction($action);
      }
    }
    return $this;
  }

  public function setBgColour($colour = self::BG_MUTED)
  {
    $this->_bgColour = $colour;
    return $this;
  }

  /**
   * @param string $icon
   *
   * @return $this
   */
  public function addIcon($icon = FontIcon::EDIT)
  {
    $this->_icon = FontIcon::create($icon)
      ->addClass('heading-icon')
      ->addClass(Ui::FLOAT_LEFT)
      ->addClass(Ui::MARGIN_SMALL_TOP);
    return $this;
  }

  /**
   *
   *
   * @param string $text
   * @param null   $url
   * @param string $style
   *
   * @return $this
   */
  public function setStatus(
    $text = self::STATUS_OPEN, $url = null, $style = Ui::LABEL_SUCCESS
  )
  {
    switch($text)
    {
      case self::STATUS_CLOSED:
        $style = Ui::LABEL_DANGER;
        break;
      case self::STATUS_HELD:
        $style = Ui::LABEL_WARNING;
        break;
      case self::STATUS_OPEN:
        $style = Ui::LABEL_SUCCESS;
        break;
    }

    if($url !== null)
    {
      $status = new Link($url, $text);
    }
    else
    {
      $status = Span::create($text);
    }

    $status->addClass(
      'heading-status',
      Ui::FLOAT_RIGHT,
      Ui::MARGIN_MEDIUM_LEFT,
      'label ' . $style . ' ' . Ui::LABEL_AS_BADGE
    );
    $this->_status = $status;
    return $this;
  }

  public function getTitle()
  {
    return HeadingTwo::create($this->_title)
      ->addClass('heading-text', Ui::FLOAT_LEFT, Ui::MARGIN_NONE);
  }

  public function getIcon()
  {
    return $this->_icon;
  }

  public function getStatus()
  {
    return $this->_status;
  }

  public function getBgColour()
  {
    return $this->_bgColour;
  }

  /**
   * Builds the HTML output of the PanelHeading actions
   *
   * @return Div
   */
  public function getActions()
  {
    return Div::create($this->_actions)
      ->addClass('heading-action', Ui::FLOAT_RIGHT, Ui::MARGIN_MEDIUM_LEFT);
  }

  protected function _produceHtml()
  {
    return Div::create(
      [
        $this->getIcon(),
        $this->getTitle(),
        $this->getActions(),
        $this->getStatus()
      ]
    )->addClass(
      'panel-heading',
      $this->getBgColour(),
      Ui::CLEARFIX
    );
  }
}
