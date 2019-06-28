<?php

/**
 * Jield copyright message placeholder.
 *
 * @category   Application
 *
 * @author     Johan van der Heide <info@jield.nl>
 * @copyright  Copyright (c) 2004-2015 Jield (http://jield.nl)
 * @license    http://jield.nl/license.txt proprietary
 *
 * @link       http://jield.nl
 */

declare(strict_types=1);

namespace Evaluation\View\Helper;

use BjyAuthorize\View\Helper\IsAllowed;
use Evaluation\Entity\AbstractEntity;
use Evaluation\ValueObject\Link;
use function in_array;

/**
 * Class AbstractLink
 *
 * @package Calendar\View\Helper
 */
abstract class AbstractLink extends AbstractViewHelper
{
    protected const SHOW_ICON = 'icon';
    protected const SHOW_BUTTON = 'button';
    protected const SHOW_TEXT = 'text';

    protected static $linkIcons
        = [
            'new'              => 'fa-plus',
            'view'             => 'fa-calendar',
            'edit'             => 'fa-pencil-square-o',
            'select-attendees' => 'fa-users',

        ];
    /**
     * @var string Text to be placed as title or as part of the linkContent
     */
    protected $text = '';
    /**
     * @var string|null
     */
    protected $javascript;
    /**
     * @var string
     */
    protected $router;
    /**
     * @var string
     */
    protected $action;
    /**
     * @var bool
     */
    protected $selected;
    /**
     * @var string
     */
    protected $show;
    /**
     * @var string
     */
    protected $linkIcon;
    /**
     * @var string
     */
    protected $alternativeShow;
    /**
     * @var array List of parameters needed to construct the URL from the router
     */
    protected $routerParams = [];
    /**
     * @var array content of the link (will be imploded during creation of the link)
     */
    protected $linkContent = [];
    /**
     * @var array Classes to be given to the link
     */
    protected $classes = [];
    /**
     * @var array Query params
     */
    protected $query = [];
    /**
     * @var array Fragment
     */
    protected $fragment = [];
    /**
     * @var array
     */
    protected $showOptions = [];

    public function addClasses(array $classes)
    {
        foreach ($classes as $class) {
            $this->classes[] = $class;
        }

        return $this;
    }

    public function hasAccess(AbstractEntity $entity, string $assertionName, string $action): bool
    {
        $this->assertionService->addResource($entity, $assertionName);

        if (!$this->isAllowed($entity, $action)) {
            return false;
        }

        return true;
    }

    public function isAllowed(AbstractEntity $resource, string $privilege = null): bool
    {
        /**
         * @var IsAllowed $isAllowed
         */
        $isAllowed = $this->helperPluginManager->get('isAllowed');

        return $isAllowed($resource, $privilege);
    }

    public function addLinkIcon(string $action, string $icon): void
    {
        self::$linkIcons[$action] = $icon;
    }

    public function setRouter(string $router): void
    {
        $this->router = $router;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function translate(string $translate): string
    {
        return $this->translator->translate($translate);
    }

    public function addShowOption(string $key, string $value): void
    {
        $this->showOptions[$key] = $value;
    }

    public function addQueryParam(string $key, string $value): void
    {
        $this->query[$key] = $value;
    }

    public function addRouteParam(string $key, $value): void
    {
        $this->routerParams[$key] = $value;
    }

    public function setJavascript(?string $javascript): void
    {
        $this->javascript = $javascript;
    }

    public function setAlternativeShow(?string $alternativeShow): void
    {
        $this->alternativeShow = $alternativeShow;
    }

    protected function createLink(string $show): string
    {
        $this->show = $show;

        $this->parseShow();

        if ('social' === $this->show) {
            return $this->getServerUrl() . $this->getUrl()(
                $this->router,
                $this->routerParams,
                ['query' => $this->query, 'fragment' => $this->fragment]
            );
        }

        $link = new Link(
            $this->getServerUrl() . $this->getUrl()(
                $this->router,
                $this->routerParams,
                ['query' => $this->query, 'fragment' => $this->fragment]
            ),
            $this->text,
            $this->classes,
            $this->linkContent,
            $this->javascript
        );

        return (string)$link;
    }

    public function parseShow(): void
    {
        switch ($this->show) {
            case 'icon':
            case 'button':
            case 'icon-and-text':
                $this->addLinkContent(sprintf('<i class="fa %s fa-fw"></i>', $this->getLinkIcon()));

                if ($this->show === 'button') {
                    $this->addLinkContent(' ' . $this->text);
                    if (in_array($this->action, ['cancel', 'delete', 'decline'], true)) {
                        $this->addClass('btn btn-danger');
                    } else {
                        $this->addClass('btn btn-primary');
                    }
                }

                if ($this->show === 'icon-and-text') {
                    $this->addLinkContent(' ' . $this->text);
                }

                break;
            case 'text':
                $this->addLinkContent($this->text);
                break;
            case 'alternativeShow':
                $this->addLinkContent($this->alternativeShow);
                break;
            case 'social':
                /*
                 * Social is treated in the createLink function, no content needs to be created
                 */
                return;
            default:
                $this->addLinkContent($this->showOptions[$this->show] ?? $this->show);
                break;
        }
    }

    public function addLinkContent($linkContent): void
    {
        $this->linkContent[] = $linkContent;
    }

    public function getLinkIcon(): string
    {
        if (null === $this->linkIcon) {
            return self::$linkIcons[$this->action] ?? 'fa-exclamation';
        }

        return $this->linkIcon;
    }

    public function setLinkIcon(string $linkIcon): void
    {
        $this->linkIcon = $linkIcon;
    }

    public function addClass(string $class): void
    {
        $this->classes[] = $class;
    }

    protected function reset(): void
    {
        $this->linkContent = [];
        $this->query = [];
        $this->fragment = [];
        $this->classes = [];
        $this->javascript = '';
    }

    protected function extractRouterParams(?AbstractEntity $abstractEntity, array $params): void
    {
        if (null === $abstractEntity) {
            return;
        }

        foreach ($params as $param) {
            $this->routerParams[$param] = $abstractEntity->$param;
        }
    }

    protected function extractLinkContentFromEntity(?AbstractEntity $abstractEntity, array $params): void
    {
        if (null === $abstractEntity) {
            return;
        }

        foreach ($params as $param) {
            $this->showOptions[$param] = (string)$abstractEntity->$param;
        }
    }
}
