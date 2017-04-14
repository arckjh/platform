<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Manager;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class SegmentManagerTest extends WebTestCase
{
    /** @var SegmentManager */
    private $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
            LoadUserData::class
        ]);

        $this->manager = $this->getContainer()->get('oro_segment.segment_manager');
    }

    public function testGetSegmentTypeChoices()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $this->assertEquals([
            $dynamicSegment->getType()->getName() => $dynamicSegment->getType()->getLabel(),
            $staticSegment->getType()->getName() => $staticSegment->getType()->getLabel(),
        ], $this->manager->getSegmentTypeChoices());
    }

    public function testGetSegmentByEntityName()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        /** @var Segment $dynamicSegment */
        $dynamicSegmentWithFilter = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);

        $this->assertEquals(
            [
                'results' => [
                    [
                        'id'   => 'segment_' . $dynamicSegment->getId(),
                        'text' => $dynamicSegment->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id'   => 'segment_' . $dynamicSegmentWithFilter->getId(),
                        'text' => $dynamicSegmentWithFilter->getName(),
                        'type' => 'segment',
                    ],
                    [
                        'id'   => 'segment_' . $staticSegment->getId(),
                        'text' => $staticSegment->getName(),
                        'type' => 'segment',
                    ]
                ],
                'more' => false
            ],
            $this->manager->getSegmentByEntityName(WorkflowAwareEntity::class, null)
        );
    }

    public function testFindById()
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $this->assertEquals($segment, $this->manager->findById($segment->getId()));
    }

    public function testGetEntityQueryBuilder()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $this->assertCount(50, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetEntityQueryBuilderWithSorting()
    {
        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $this->assertCount(0, $this->manager->getEntityQueryBuilder($dynamicSegment)->getQuery()->getResult());
    }

    public function testGetFilterSubQueryDynamic()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $result = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        $this->assertContains('FROM Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity', $result);
    }

    public function testGetFilterSubQueryDynamicWithLimit()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $dynamicSegment->setRecordsLimit(10);
        $this->assertCount(10, $this->manager->getFilterSubQuery($dynamicSegment, $qb));
    }

    public function testGetFilterSubQueryStatic()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $this->assertEquals(
            sprintf('SELECT snp.integerEntityId FROM %s snp WHERE snp.segment = :segment', SegmentSnapshot::class),
            $this->manager->getFilterSubQuery($dynamicSegment, $qb)
        );
    }

    public function testGetFilterSubQueryDynamicWithLimitAndNoResults()
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(WorkflowAwareEntity::class);

        $qb = $repository->createQueryBuilder('w');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $dynamicSegment->setRecordsLimit(10);
        $result = $this->manager->getFilterSubQuery($dynamicSegment, $qb);
        $this->assertEquals([null], $result);
    }

    public function testGetSegmentQueryBuilder()
    {
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);

        $qb = $this->manager->getSegmentQueryBuilder($segment);
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testGetSegmentQueryBuilderNotExistingType()
    {
        $segment = new Segment();
        $segment->setType(new SegmentType('NotExistingType'));

        $result = $this->manager->getSegmentQueryBuilder($segment);
        $this->assertNull($result);
    }

    public function testFilterBySegmentWrongDefinition()
    {
        $segmentDefinition = ['Some wfong segment definition'];

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setEntity(User::class);
        $segment->setDefinition(json_encode($segmentDefinition));

        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $resultBeforeFilter = $qb->getQuery()->getResult();
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        $this->assertEquals($resultBeforeFilter, $result);
    }

    public function testFilterBySegment()
    {
        $filteredUserFirstName = 'Elley';
        $segmentDefinition = [
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'Id',
                    'sorting' => 'DESC',
                    'func' => null
                ]
            ],
            'filters' =>[
                [
                    'columnName' => 'firstName',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => $filteredUserFirstName,
                            'type' => 1,
                        ]
                    ]
                ]
            ]
        ];

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setEntity(User::class);
        $segment->setDefinition(json_encode($segmentDefinition));

        $registry = $this->getContainer()->get('doctrine');
        /** @var EntityRepository $repository */
        $repository = $registry->getRepository(User::class);

        $qb = $repository->createQueryBuilder('u');
        $this->manager->filterBySegment($qb, $segment);

        $result = $qb->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($filteredUserFirstName, reset($result)->getFirstName());
    }
}
