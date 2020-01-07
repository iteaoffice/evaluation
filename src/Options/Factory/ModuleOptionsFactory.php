<?php
/**
 * ITEA Office all rights reserved
 *
 * PHP Version 7
 *
 * @category    Evaluation
 *
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2004-2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/evaluation for the canonical source repository
 */
declare(strict_types=1);

namespace Evaluation\Options\Factory;

use Interop\Container\ContainerInterface;
use Evaluation\Options\ModuleOptions;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Class ModuleOptionsFactory
 * @package Evaluation\Options\Factory
 */
final class ModuleOptionsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ModuleOptions
    {
        $config = $container->get('Config');

        return new ModuleOptions($config['evaluation_options'] ?? []);
    }
}
