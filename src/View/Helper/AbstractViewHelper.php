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

use Interop\Container\ContainerInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Router\Http\RouteMatch;
use Zend\View\Helper\AbstractHelper;
use Zend\View\HelperPluginManager;
use ZfcTwig\View\TwigRenderer;

/**
 * Class AbstractViewHelper
 * @package Evaluation\View\Helper
 */
abstract class AbstractViewHelper extends AbstractHelper
{
    /**
     * @var ContainerInterface
     */
    protected $serviceManager;
    /**
     * @var HelperPluginManager
     */
    protected $helperPluginManager;
    /**
     * @var RouteMatch
     */
    protected $routeMatch = null;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * RouteInterface match returned by the router.
     * Use a test on is_null to have the possibility to overrule the serviceLocator lookup for unit tets reasons.
     *
     * @return RouteMatch.
     */
    public function getRouteMatch(): RouteMatch
    {
        if (null === $this->routeMatch) {
            $this->routeMatch = $this->getServiceManager()->get('application')->getMvcEvent()->getRouteMatch();
        }

        return $this->routeMatch;
    }

    /**
     * @return ContainerInterface
     */
    public function getServiceManager(): ContainerInterface
    {
        return $this->serviceManager;
    }

    /**
     * @param ContainerInterface $serviceManager
     *
     * @return AbstractViewHelper
     */
    public function setServiceManager($serviceManager): AbstractViewHelper
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return TwigRenderer
     */
    public function getRenderer(): TwigRenderer
    {
        return $this->getServiceManager()->get('ZfcTwigRenderer');
    }

    /**
     * @return HelperPluginManager
     */
    public function getHelperPluginManager()
    {
        return $this->helperPluginManager;
    }

    /**
     * @param HelperPluginManager $helperPluginManager
     *
     * @return AbstractViewHelper
     */
    public function setHelperPluginManager($helperPluginManager): AbstractViewHelper
    {
        $this->helperPluginManager = $helperPluginManager;

        return $this;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }
}
