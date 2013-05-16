<?php

namespace Oro\Bundle\DataAuditBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\DataAuditBundle\DependencyInjection\OroDataAuditExtension;

class OroDataAuditExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadWithDefaults()
    {
        $configuration = new ContainerBuilder();

        $loader = new OroDataAuditExtension();
        $config = $this->getEmptyConfig();

        $loader->load(array($config), $configuration);

        $this->assertTrue($configuration instanceof ContainerBuilder);
        $this->assertTrue($configuration->has('oro_dataaudit.audit_datagrid.manager'));
    }

    /**
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml   = '';
        $parser = new Parser();

        return $parser->parse($yaml);
    }
}
