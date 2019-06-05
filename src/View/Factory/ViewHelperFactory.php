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

namespace Evaluation\View\Factory;

use Interop\Container\ContainerInterface;
use Evaluation\View\Helper\AbstractViewHelper;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class ViewHelperFactory
 * @package Evaluation\View\Factory
 */
final class ViewHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var AbstractViewHelper $viewHelper */
        $viewHelper = new $requestedName($options);
        $viewHelper->setServiceManager($container);
        $viewHelper->setHelperPluginManager($container->get('ViewHelperManager'));
        $viewHelper->setTranslator($container->get(TranslatorInterface::class));

        return $viewHelper;
    }
}
