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

namespace Evaluation\View\Helper\Report\Criterion;

use Evaluation\Entity\Report\Criterion\Type;
use Evaluation\View\Helper\AbstractLink;

/**
 * Class TypeLink
 *
 * @package Evaluation\View\Helper\Report\Criterion
 */
final class TypeLink extends AbstractLink
{
    public function __invoke(
        Type $type = null,
        string $action = 'view',
        string $show = 'name'
    ): string {
        $this->reset();

        $this->extractRouteParams($type, ['id']);
        if (null !== $type) {
            $this->addShowOption('name', $type->getType());
        }

        $this->parseAction($action, $type ?? new Type());
        return $this->createLink($show);
    }

    public function parseAction(string $action, Type $type): void
    {
        $this->action = $action;
        switch ($action) {
            case 'new':
                $this->setRoute('zfcadmin/evaluation/report/criterion/type/new');
                $this->setText($this->translator->translate('txt-new-evaluation-report-criterion-type'));
                break;
            case 'list':
                $this->setRoute('zfcadmin/evaluation/report/criterion/type/list');
                $this->setText($this->translator->translate('txt-list-evaluation-report-criterion-type-list'));
                break;
            case 'view':
                $this->setRoute('zfcadmin/evaluation/report/criterion/type/view');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-view-evaluation-report-criterion-type-%s'),
                        $type->getType()
                    )
                );
                break;
            case 'edit':
                $this->setRoute('zfcadmin/evaluation/report/criterion/type/edit');
                $this->setText(
                    sprintf(
                        $this->translator->translate('txt-edit-evaluation-report-criterion-type-%s'),
                        $type->getType()
                    )
                );
                break;
        }
    }
}
