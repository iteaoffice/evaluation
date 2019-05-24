<?php

/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Project
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2017 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\View\Helper;

use BjyAuthorize\Controller\Plugin\IsAllowed;
use BjyAuthorize\Service\Authorize;
use Exception;
use InvalidArgumentException;
use Evaluation\Entity\AbstractEntity;
use Zend\Router\Http\RouteMatch;
use Zend\View\Helper\ServerUrl;
use Zend\View\Helper\Url;
use function in_array;
use function is_null;

/**
 * Class AbstractLink
 * @package Evaluation\View\Helper
 */
abstract class AbstractLink extends AbstractViewHelper
{
    /**
     * @var RouteMatch
     */
    protected $routeMatch = null;
    /**
     * @var string Text to be placed as title or as part of the linkContent
     */
    protected $text;
    /**
     * @var string
     */
    protected $router;
    /**
     * @var string
     */
    protected $action;
    /**
     * @var string
     */
    protected $show;
    /**
     * @var string
     */
    protected $javaScript;
    /**
     * @var string
     */
    protected $alternativeShow;
    /**
     * @var array List of parameters needed to construct the URL from the router
     */
    protected $fragment = null;
    /**
     * @var array Url query params
     */
    protected $query = [];
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
     * @var array
     */
    protected $showOptions = [];

    public function createLink(): string
    {
        $url = $this->getHelperPluginManager()->get(Url::class);
        /** @var $serverUrl ServerUrl */
        $serverUrl = $this->getHelperPluginManager()->get(ServerUrl::class);

        // Init params and layout
        $this->fragment    = null;
        $this->query       = [];
        $this->linkContent = [];

        $this->parseAction();
        $this->parseShow();

        if ('social' === $this->getShow()) {
            return $serverUrl() . $url(
                $this->router,
                $this->routerParams,
                ['query' => $this->getQuery(), 'fragment' => $this->getFragment()]
            );
        }

        $uri = '<a href="%s" title="%s" %s class="%s">%s</a>';
        $specialLinks = ['icon', 'button', 'button-small', 'alternativeShow', 'code'];

        return sprintf(
            $uri,
            $serverUrl() . $url(
                $this->router,
                $this->routerParams,
                ['query' => $this->getQuery(), 'fragment' => $this->getFragment()]
            ),
            htmlentities((string)$this->text),
            $this->getJavaScript(),
            implode(' ', $this->classes),
            in_array($this->getShow(), $specialLinks, true)
                ? implode('', $this->linkContent)
                : htmlentities(implode('', $this->linkContent))
        );
    }


    /**
     * To be implemented by children
     */
    abstract public function parseAction(): void;

    /**
     * @throws Exception
     */
    public function parseShow(): void
    {
        switch ($this->getShow()) {
            case 'icon':
            case 'button':
            case 'button-small':
            case 'help-button':
                switch ($this->getAction()) {
                    case 'new':
                    case 'add-admin':
                    case 'add':
                    case 'create':
                        $this->addLinkContent('<i class="fa fa-plus"></i>');
                        break;
                    case 'list-community':
                        $this->addLinkContent('<i class="fa fa-bars"></i>');
                        break;
                    case 'view-community':
                        $this->addLinkContent('<i class="fa fa-link"></i>');
                        break;
                    case 'edit':
                    case 'edit-admin':
                    case 'edit-community':
                        $this->addLinkContent('<i class="fa fa-pencil-square-o"></i>');
                        break;
                    case 'submit':
                        $this->addLinkContent('<i class="fa fa-chevron-circle-right"></i>');
                        break;
                    case 'import':
                    case 'import-admin':
                        $this->addLinkContent('<i class="fa fa-upload"></i>');
                        break;
                    case 'funding-status':
                        $this->addLinkContent('<i class="fa fa-eur"></i>');
                        break;
                    case 'download':
                    case 'export':
                    case 'download-community':
                    case 'download-offline-form':
                    case 'download-distributable':
                        $this->addLinkContent('<i class="fa fa-download"></i>');
                        break;
                    case 'create-merged-document':
                        $this->addLinkContent('<i class="fa fa-indent"></i>');
                        break;
                    case 'delete':
                        $this->addLinkContent('<i class="fa fa-trash-o"></i>');
                        break;
                    case 'download-overview':
                    case 'download-excel':
                    case 'download-excel-combined':
                    case 'statistics':
                        $this->addLinkContent('<i class="fa fa-file-excel-o"></i>');
                        break;
                    case 'accept':
                    case 'finalize':
                    case 'finalise':
                        $this->addLinkContent('<i class="fa fa-thumbs-o-up"></i>');
                        break;
                    case 'download-pdf':
                    case 'download-distributable-pdf':
                    case 'download-consolidated-feedback-pdf':
                        $this->addLinkContent('<i class="fa fa-file-pdf-o"></i>');
                        break;
                    case 'list':
                        $this->addLinkContent('<i class="fa fa-bars"></i>');
                        break;
                    case 'view':
                    case 'view-admin':
                        $this->addLinkContent('<i class="fa fa-link"></i>');
                        break;
                    case 'copy':
                        $this->addLinkContent('<i class="fa fa-copy"></i>');
                        break;
                    default:
                        $this->addLinkContent('<i class="fa fa-file-o"></i>');
                        break;
                }

                if ($this->getShow() === 'button-small') {
                    $this->addLinkContent(' ' . $this->getText());
                    $this->addClasses("btn btn-primary btn-sm");
                }
                if ($this->getShow() === 'button') {
                    $this->addLinkContent(' ' . $this->getText());
                    $this->addClasses('btn btn-primary');
                }
                break;
            case 'text':
                $this->addLinkContent($this->getText());
                break;
            case 'paginator':
                if (null === $this->getAlternativeShow()) {
                    throw new InvalidArgumentException(
                        sprintf("this->alternativeShow cannot be null for a paginator link")
                    );
                }
                $this->addLinkContent($this->getAlternativeShow());
                break;
            case 'social':
                /*
                 * Social is treated in the createLink function, no content needs to be created
                 */
                return;
            default:
                if (!array_key_exists($this->getShow(), $this->showOptions)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "The option \"%s\" should be available in the showOptions array, only \"%s\" are available",
                            $this->getShow(),
                            implode(', ', array_keys($this->showOptions))
                        )
                    );
                }
                $this->addLinkContent($this->showOptions[$this->getShow()]);
                break;
        }
    }

    /**
     * @return string
     */
    public function getShow()
    {
        return $this->show;
    }

    /**
     * @param string $show
     */
    public function setShow($show)
    {
        $this->show = $show;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action): void
    {
        // Temporarily put this here as it usually gets called before addClasses()
        $this->classes = [];
        $this->action = $action;
    }

    /**
     * @param string|array $linkContent
     *
     * @return $this
     */
    public function addLinkContent($linkContent): AbstractLink
    {
        foreach ((array)$linkContent as $content) {
            $this->linkContent[] = $content;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    public function addClasses($classes): AbstractLink
    {
        foreach ((array)$classes as $class) {
            $this->classes[] = $class;
        }

        return $this;
    }

    public function getAlternativeShow()
    {
        return $this->alternativeShow;
    }

    /**
     * @param string $alternativeShow
     */
    public function setAlternativeShow($alternativeShow)
    {
        $this->alternativeShow = $alternativeShow;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param array $query
     *
     * @return void
     */
    public function setQuery(array $query): void
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param string $fragment
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
    }

    public function getJavaScript(): ?string
    {
        return $this->javaScript;
    }

    public function setJavaScript(string $javaScript): AbstractLink
    {
        $this->javaScript = $javaScript;

        return $this;
    }

    public function setClasses(array $classes): AbstractLink
    {
        $this->classes = $classes;

        return $this;
    }

    /**
     * @param array $showOptions
     */
    public function setShowOptions(array $showOptions): void
    {
        $this->showOptions = $showOptions;
    }

    /**
     * @param AbstractEntity $entity
     * @param string         $assertion
     * @param string         $action
     *
     * @return bool
     */
    public function hasAccess(AbstractEntity $entity, $assertion, $action): bool
    {
        $assertion = $this->getAssertion($assertion);
        if (!is_null($entity) && !$this->getAuthorizeService()->getAcl()->hasResource($entity)) {
            $this->getAuthorizeService()->getAcl()->addResource($entity);
            $this->getAuthorizeService()->getAcl()->allow([], $entity, [], $assertion);
        }
        if (!$this->isAllowed($entity, $action)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $assertion
     *
     * @return mixed
     */
    public function getAssertion($assertion)
    {
        return $this->getServiceManager()->get($assertion);
    }

    /**
     * @return Authorize
     */
    public function getAuthorizeService()
    {
        return $this->getServiceManager()->get(Authorize::class);
    }

    /**
     * @param null|AbstractEntity $resource
     * @param string              $privilege
     *
     * @return bool
     */
    public function isAllowed($resource, $privilege = null)
    {
        /**
         * @var $isAllowed IsAllowed
         */
        $isAllowed = $this->getHelperPluginManager()->get('isAllowed');

        return $isAllowed($resource, $privilege);
    }

    /**
     * @return string
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param string $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getRouterParams()
    {
        return $this->routerParams;
    }

    /**
     * Add a parameter to the list of parameters for the router.
     *
     * @param string $key
     * @param        $value
     * @param bool   $allowNull
     */
    public function addRouterParam($key, $value, $allowNull = true): void
    {
        if (!$allowNull && null === $value) {
            throw new InvalidArgumentException(sprintf("null is not allowed for %s", $key));
        }
        if (null !== $value) {
            $this->routerParams[$key] = $value;
        }
    }
}
