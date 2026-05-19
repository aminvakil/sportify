<?php

namespace Devlabs\SportifyBundle\Controller\Api;

use Devlabs\SportifyBundle\Controller\Base\BaseApiController;
use Devlabs\SportifyBundle\Entity\Score;

/**
 * Class ScoreController
 * @package Devlabs\SportifyBundle\Controller\Api
 */
class ScoreController extends BaseApiController
{
    protected $entityName = 'Score';
    protected $fqEntityClass = Score::class;
    protected $repositoryName = Score::class;
    protected $fqEntityFormClass = ScoreEntityType::class;
}
