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

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\EntityManager;
use DoctrineORMModule\Form\Element\EntityRadio;
use Evaluation\Entity;
use Laminas\Form\Annotation\AnnotationBuilder;
use Laminas\Form\Element;
use Laminas\Form\Element\Radio;
use Laminas\Form\Fieldset;
use Laminas\Form\FieldsetInterface;

/**
 * Class ObjectFieldset
 *
 * @package Event\Form
 */
class ObjectFieldset extends Fieldset
{
    public function __construct(EntityManager $entityManager, Entity\AbstractEntity $object)
    {
        parent::__construct($object->get('underscore_entity_name'));
        $doctrineHydrator = new DoctrineObject($entityManager);
        $this->setHydrator($doctrineHydrator)->setObject($object);
        $builder = new AnnotationBuilder();
        // createForm() already creates a proper form, so attaching its elements
        // to $this is only for backward compatibility
        $data = $builder->createForm($object);
        $this->addElements($data, $entityManager, $object, $this);
    }

    protected function addElements(
        Fieldset $dataFieldset,
        EntityManager $entityManager,
        ?Entity\AbstractEntity $object,
        Fieldset $baseFieldset = null
    ): void {
        /** @var Element $element */
        foreach ($dataFieldset->getElements() as $element) {
            $this->parseElement($element, $object, $entityManager);
            // Add only when a type is provided
            if (! \array_key_exists('type', $element->getAttributes())) {
                continue;
            }

            if ($baseFieldset instanceof Fieldset) {
                $baseFieldset->add($element);
            } else {
                $dataFieldset->add($element);
            }
        }
        // Prepare the target element of a form collection
        if ($dataFieldset instanceof Element\Collection) {
            /** @var Element\Collection $dataFieldset */
            $targetFieldset = $dataFieldset->getTargetElement();
            // Collections have "container" fieldsets for their items, they must have the hydrator set too
            if ($targetFieldset instanceof FieldsetInterface) {
                $targetFieldset->setHydrator($this->getHydrator());
            }
            /** @var Fieldset $targetFieldset */
            foreach ($targetFieldset->getElements() as $element) {
                $this->parseElement($element, $targetFieldset->getObject(), $entityManager);
            }
        }

        // Add sub-fieldsets
        foreach ($dataFieldset->getFieldsets() as $subFieldset) {
            /** @var Fieldset $subFieldset */
            $subFieldset->setHydrator($this->getHydrator());
            $this->addElements($subFieldset, $entityManager, $subFieldset->getObject());
            $this->add($subFieldset);
        }
    }

    protected function parseElement(Element $element, ?Entity\AbstractEntity $object, EntityManager $entityManager): void
    {
        if (
            ($element instanceof Radio) && ! ($element instanceof EntityRadio)
            && ($object instanceof Entity\AbstractEntity)
        ) {
            $attributes        = $element->getAttributes();
            $valueOptionsArray = \sprintf('get%s', \ucfirst($attributes['array']));

            $element->setOptions(\array_merge(
                $element->getOptions(),
                ['value_options' => $object::$valueOptionsArray()]
            ));
        }

        $element->setOptions(\array_merge($element->getOptions(), ['object_manager' => $entityManager]));
    }
}
