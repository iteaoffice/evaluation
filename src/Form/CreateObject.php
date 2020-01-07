<?php
/**
*
 * @author      Johan van der Heide <johan.van.der.heide@itea3.org>
 * @copyright   Copyright (c) 2019 ITEA Office (https://itea3.org)
 * @license     https://itea3.org/license.txt proprietary
 *
 * @link        http://github.com/iteaoffice/project for the canonical source repository
 */

declare(strict_types=1);

namespace Evaluation\Form;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Evaluation\Entity\AbstractEntity;
use Laminas\Form\Element;
use Laminas\Form\Form;

/**
 * Class CreateObject
 *
 * @package Project\Form
 */
final class CreateObject extends Form
{
    public function __construct(
        EntityManager           $entityManager,
        AbstractEntity          $object,
        ContainerInterface $container
    ) {
        parent::__construct($object->get('entity_name'));

        /**
         * There is an option to drag the fieldset from the serviceManager,
         * We then need to check if if an factory is present,
         * If not we will use the default ObjectFieldset
         */
        $objectSpecificFieldset = $object->get('entity_fieldset_name');

        /**
         * Load a specific fieldSet when present
         */
        if ($container->has($objectSpecificFieldset)) {
            $objectFieldset = $container->build($objectSpecificFieldset, ['object' => $object]);
        } elseif (\class_exists($objectSpecificFieldset)) {
            $objectFieldset = new $objectSpecificFieldset($entityManager, $object);
        } else {
            $objectFieldset = new ObjectFieldset($entityManager, $object);
        }

        $objectFieldset->setUseAsBaseFieldset(true);
        $this->add($objectFieldset);

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');

        $this->add(
            [
                'type' => Element\Csrf::class,
                'name' => 'csrf',
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'submit',
                'attributes' => [
                    'class' => 'btn btn-primary',
                    'value' => _('txt-submit'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'cancel',
                'attributes' => [
                    'class' => 'btn btn-warning',
                    'value' => _('txt-cancel'),
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'delete',
                'attributes' => [
                    'class'   => 'btn btn-danger',
                    'value'   => _('txt-delete'),
                    'onclick' => 'return confirm("Are you sure? This cannot be undone");'
                ],
            ]
        );

        $this->add(
            [
                'type'       => Element\Submit::class,
                'name'       => 'restore',
                'attributes' => [
                    'class' => 'btn btn-info',
                    'value' => _('txt-restore'),
                ],
            ]
        );
    }
}
