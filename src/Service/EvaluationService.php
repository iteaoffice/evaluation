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

namespace Evaluation\Service;

use Affiliation\Entity\Affiliation;
use General\Entity\Country;
use Evaluation\Entity\Evaluation;
use Evaluation\Entity\Feedback;
use Evaluation\Entity\Type;
use Project\Entity\Funding\Funding;
use Project\Entity\Funding\Source;
use Project\Entity\Funding\Status;
use Project\Entity\Project;
use function in_array;
use function strlen;

/**
 * Class EvaluationService
 * @package Evaluation\Service
 */
class EvaluationService extends AbstractService
{
    public const TYPE_EVALUATION = 1;
    public const TYPE_FUNDING    = 2;

    public function findEvaluationByCountryAndTypeAndProject(
        Country $country,
        Type $type,
        Project $project
    ): ?Evaluation {
        return $this->entityManager->getRepository(Evaluation::class)->findOneBy(
            [
                'country' => $country,
                'type'    => $type,
                'project' => $project,
            ]
        );
    }

    public function findEvaluationByProject(Project $project): array
    {
        return $this->entityManager->getRepository(Evaluation::class)
            ->findBy(['project' => $project,], ['type' => 'ASC']);
    }



    public function isFundingDecisionGiven(Evaluation $evaluation): bool
    {
        return in_array(
            $evaluation->getStatus()->getId(),
            [
                Status::STATUS_ALL_GOOD,
                Status::STATUS_FAILED,
                Status::STATUS_SELF_FUNDED,
            ],
            true
        );
    }

    public function isFunding(Type $type): bool
    {
        return $this->parseMainEvaluationType($type) === self::TYPE_FUNDING;
    }

    /**
     * We have 2 different main evaluation types (evaluation and funding) We need to distinguish them both
     * and that can be done by this function.
     *
     * @param Type $type
     *
     * @return int
     */
    public function parseMainEvaluationType(Type $type): int
    {
        switch ($type->getId()) {
            case Type::TYPE_FUNDING_STATUS:
                return self::TYPE_FUNDING;
            case Type::TYPE_PO_EVALUATION:
            case Type::TYPE_FPP_EVALUATION:
                return self::TYPE_EVALUATION;
        }

        return 0;
    }

    public function isEvaluation(Type $type): bool
    {
        return $this->parseMainEvaluationType($type) === self::TYPE_EVALUATION;
    }

    public function isDecision(Status $status): bool
    {
        return in_array(
            $status->getId(),
            [Status::STATUS_ALL_GOOD, Status::STATUS_GOOD, Status::STATUS_SELF_FUNDED, Status::STATUS_BAD],
            false
        );
    }

    public function hasFeedbackFromProjectConsortium(Feedback $feedback): bool
    {
        return strlen((string)$feedback->getEvaluationFeedback()) > 12
            && (string)$feedback->getReviewFeedback() !== '';
    }

    public function getFundingStatusList(int $type = self::TYPE_EVALUATION): ?array
    {
        switch ($type) {
            case self::TYPE_EVALUATION:
                return $this->entityManager->getRepository(Status::class)
                    ->findBy(['isEvaluation' => Status::IS_EVALUATION], ['sequence' => 'ASC']);
            case self::TYPE_FUNDING:
            default:
                return $this->entityManager->getRepository(Status::class)->findBy([], ['sequence' => 'ASC']);
        }
    }
}
