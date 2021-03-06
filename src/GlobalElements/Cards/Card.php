<?php
namespace Fortifi\Ui\GlobalElements\Cards;

use Fortifi\FontAwesome\FaIcon;
use Fortifi\Ui\ContentElements\Avatar\Avatar;
use Fortifi\Ui\Enums\Cards\CardActionType;
use Fortifi\Ui\Enums\Colour;
use Fortifi\Ui\Enums\UiIcon;
use Fortifi\Ui\Interfaces\IColours;
use Fortifi\Ui\Traits\DataAttributesTrait;
use Fortifi\Ui\Traits\SetIdTrait;
use Fortifi\Ui\UiElement;
use Packaged\Dispatch\ResourceManager;
use Packaged\Glimpse\Core\HtmlTag;
use Packaged\Glimpse\Tags\Div;
use Packaged\Glimpse\Tags\Link;
use Packaged\Glimpse\Tags\Text\Paragraph;
use Packaged\Helpers\Strings;

class Card extends UiElement implements IColours, ICardActionType
{
  use SetIdTrait;
  use DataAttributesTrait;

  /** @var  mixed */
  protected $_title;
  /** @var  array */
  protected $_actions = [];
  /** @var  array */
  protected $_properties = [];
  /** @var  array */
  protected $_icons = [];
  /** @var  string */
  protected $_colour = self::COLOUR_DEFAULT;
  /** @var  null|string */
  protected $_label = null;
  /** @var  null|mixed */
  protected $_description = null;
  /** @var  null|mixed */
  protected $_avatar = null;
  /** @var  bool */
  protected $_isGridLayout = false;
  /** @var  bool */
  protected $_colourBackground = false;
  /** @var int */
  protected $_maxDescription = 512;

  public function processIncludes(ResourceManager $resourceManager, $vendor = false)
  {
    if($vendor)
    {
      $resourceManager->requireCss('assets/css/ContentElements.min.css');
      $resourceManager->requireJs('assets/js/GlobalElements.min.js');
    }
    else
    {
      $resourceManager->requireCss('assets/css/ContentElements/Cards.css');
      $resourceManager->requireJs('assets/js/GlobalElements/copy-to-clipboard.js');
    }
  }

  /**
   * @param $content
   *
   * @return $this
   */
  public function setTitle($content)
  {
    $this->_title = $content;
    return $this;
  }

  /**
   * @param string $text
   *
   * @return $this
   */
  public function setLabel($text)
  {
    $this->_label = $text;
    return $this;
  }

  /**
   * @param $content
   *
   * @return $this
   */
  public function setDescription($content)
  {
    $this->_description = $content;
    return $this;
  }

  /**
   * @param Avatar $avatar
   *
   * @return $this
   */
  public function setAvatar($avatar)
  {
    $this->_avatar = $avatar;
    return $this;
  }

  /**
   * $copyValue is functionality to copy string to clipboard on click of property object.
   * If $copyValue is_string, $copyValue content will be stored in clipboard on click.
   * If $copyValue is false OR options exist, click/copy functionality is disabled.
   * $copyValue is false (off) by default.
   *
   * @param             $label
   * @param             $value
   * @param bool|string $copyValue
   * @param array       $options
   *
   * @return $this
   */
  public function addProperty($label, $value, $copyValue = false, array $options = [])
  {
    if($value !== null && $value !== '')
    {
      $property = Div::create()->addClass('property');

      // stuff for copy-to-clipboard
      if(($copyValue !== false) && ($copyValue !== null) && !$options)
      {
        if($copyValue === true)
        {
          $copyValue = $value;
        }

        if(is_string($copyValue))
        {
          $property->setAttribute('data-copy', $copyValue);
          $property->appendContent(
            FaIcon::create(FaIcon::COPY)->styleRegular()->fixedWidth()->addClass('copy')
          );
        }
      }

      if(is_string($value))
      {
        $value = Paragraph::create($value)->addClass('value')->setAttribute('title', $value);
      }
      else
      {
        $value = Div::create($value)->addClass('value');
      }

      if(is_string($label))
      {
        $label = Paragraph::create($label)->addClass('label')->setAttribute('title', ucwords($label));
      }

      $property->prependContent([$value, $label]);

      $this->_properties[] = $property;
    }
    return $this;
  }

  public function addCustomProperty($property)
  {
    $this->_properties[] = Div::create($property)->addClass('property');
    return $this;
  }

  /**
   * @param string    $type
   * @param Link|null $link
   *
   * @return $this
   */
  public function addAction($type = self::ACTION_TYPE_VIEW, Link $link = null)
  {
    if(CardActionType::isValid($type))
    {
      $this->addCustomAction(CardAction::create($type, $link), $type);
    }
    return $this;
  }

  /**
   * @param CardAction $action
   * @param int        $sortOrder
   *
   * @return $this
   */
  public function addCustomAction(CardAction $action, $sortOrder = 50)
  {
    if($action instanceof CardAction)
    {
      // Define min/max value of custom sort order.
      // This is to ensure an element consistency for the most part.
      if(is_int($sortOrder))
      {
        $sortOrder = max(3, min($sortOrder, 399));
      }
      $this->_actions[$sortOrder] = $action;
    }
    return $this;
  }

  /**
   * @param $icon
   *
   * @return $this
   */
  public function addIcon($icon)
  {
    if(is_string($icon) && UiIcon::isValid($icon))
    {
      $icon = FaIcon::create($icon);
    }
    $this->_icons[] = $icon;
    return $this;
  }

  /**
   * @param string $colour
   *
   * @return $this
   */
  public function setColour($colour = self::COLOUR_DEFAULT)
  {
    $this->_colour = $colour;
    return $this;
  }

  /**
   * @return string
   */
  public function getColour()
  {
    return $this->_colour;
  }

  /**
   * @return bool
   */
  public function hasColourBackground()
  {
    return $this->_colourBackground;
  }

  /**
   * @param bool $colourBackground
   *
   * @return Card
   */
  public function setColourBackground($colourBackground)
  {
    $this->_colourBackground = $colourBackground;
    return $this;
  }

  /**
   * @return CardAction[]
   */
  protected function _getSortedActions()
  {
    $sorted = [];
    $sortOrder = [
      self::ACTION_TYPE_VIEW         => 1,
      self::ACTION_TYPE_DOWNLOAD     => 2,
      self::ACTION_TYPE_EDIT         => 3,
      // custom actions can appear after here
      self::ACTION_TYPE_IS_DEFAULT   => 100,
      self::ACTION_TYPE_MAKE_DEFAULT => 110,
      self::ACTION_TYPE_LOCK         => 200,
      self::ACTION_TYPE_UNLOCK       => 210,
      self::ACTION_TYPE_PAUSE        => 300,
      self::ACTION_TYPE_RESUME       => 310,
      self::ACTION_TYPE_COMPLETE     => 320,
      self::ACTION_TYPE_DROP         => 330,
      // custom actions can appear before here
      self::ACTION_TYPE_ADD          => 400,
      self::ACTION_TYPE_REMOVE       => 410,
      self::ACTION_TYPE_APPROVE      => 700,
      self::ACTION_TYPE_VERIFY       => 700,
      self::ACTION_TYPE_DECLINE      => 710,
      self::ACTION_TYPE_DISABLE      => 800,
      self::ACTION_TYPE_ENABLE       => 810,
      self::ACTION_TYPE_RESTORE      => 997,
      self::ACTION_TYPE_CREATE       => 998,
      self::ACTION_TYPE_DELETE       => 999,
    ];

    /** @var CardAction $action */
    foreach($this->_actions as $idx => $action)
    {
      $order = ($action->getType() ? $sortOrder[$action->getType()] : $idx);
      $sorted[$order] = $action;
    }
    ksort($sorted);

    return $this->_actions = $sorted;
  }

  /**
   * @return array
   */
  public function getActionTypes()
  {
    return array_keys($this->_actions);
  }

  /**
   * @return int
   */
  public function getPropertyCount()
  {
    return count($this->_properties);
  }

  /**
   * @param HtmlTag $container
   * @param HtmlTag $card
   *
   * @return HtmlTag
   */
  protected function _applyIcons(HtmlTag $container, HtmlTag $card)
  {
    if($this->_icons)
    {
      $container->appendContent(Div::create($this->_icons)->addClass('icons'));
      $card->addClass('has-icons');
    }

    return $card;
  }

  /**
   * @return Div
   */
  protected function _produceHtml()
  {
    // create Card
    $card = Div::create()->addClass('ui-card');

    // Avatar, Label, Title and Description
    $primary = Div::create()->addClass('primary');
    $card->appendContent($primary);

    $heading = Div::create()->addClass('ui-c-head');
    $primary->appendContent($heading);
    //Avatar
    if($this->_avatar)
    {
      $avatar = Div::create($this->_avatar)->addClass('avatar');
      $heading->appendContent($avatar);

      $card->addClass('has-avatar');
    }
    else
    {
      $card->addClass('no-avatar');
    }

    // Title, Label, Description content
    $text = Div::create()->addClass('text');
    $heading->appendContent($text);

    // add icons to Card
    $this->_applyIcons($text, $card);

    if($this->_label)
    {
      $label = Paragraph::create($this->getLabel())->addClass('label');
      $text->appendContent($label);

      $card->addClass('has-label');
    }
    else
    {
      $card->addClass('no-label');
    }

    if($this->_title)
    {
      $title = Div::create($this->getTitle())->addClass('title');
      $text->appendContent($title);

      $card->addClass('has-title');
    }

    if($this->_description !== null && $this->_description !== '')
    {
      $description = Div::create($this->_produceDescription())->addClass('description');
      $this->_setDescription($description, $primary, $heading, $text);

      $card->addClass('has-description');
    }
    else
    {
      $card->addClass('no-description');
    }

    // add border colour class
    if(Colour::isValid($this->_colour))
    {
      $card->addClass($this->_colour);
    }
    else
    {
      $card->setAttribute('style', '--card-color: ' . $this->_colour . ';');
    }

    // append properties
    if($this->_properties)
    {
      // Properties
      $card->appendContent(Div::create($this->_properties)->addClass('properties'));
      $card->addClass('has-properties');
    }
    else
    {
      $card->addClass('no-properties');
    }

    // append actions
    if($this->_actions)
    {
      $card->appendContent(Div::create($this->_getSortedActions())->addClass('actions'));
      $card->addClass('has-actions');
    }
    else
    {
      $card->addClass('no-actions');
    }

    if($this->_colourBackground)
    {
      $card->addClass("colour-bg");
    }

    // apply data attributes
    $this->_applyDataAttributes($card);
    $this->_applyId($card);

    return $card;
  }

  protected function _setDescription(HtmlTag $description, HtmlTag $primary, HtmlTag $heading, HtmlTag $text)
  {
    $text->appendContent($description);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getTitle()
  {
    return $this->_title;
  }

  /**
   * @return null|string
   */
  public function getLabel()
  {
    return $this->_label;
  }

  /**
   * @return mixed|null
   */
  public function getDescription()
  {
    return $this->_description;
  }

  protected function _produceDescription()
  {
    if(is_string($this->getDescription()))
    {
      return Strings::excerpt($this->getDescription(), $this->_maxDescription);
    }
    return $this->getDescription();
  }

}
