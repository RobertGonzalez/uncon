<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

namespace Sugarcrm\Sugarcrm\custom\Elasticsearch\Provider\DataAnalytics;

use Sugarcrm\Sugarcrm\Elasticsearch\Container;
use Sugarcrm\Sugarcrm\Elasticsearch\ContainerAwareInterface;
use Sugarcrm\Sugarcrm\Elasticsearch\Provider\AbstractProvider;
use Sugarcrm\Sugarcrm\Elasticsearch\Analysis\AnalysisBuilder;
use Sugarcrm\Sugarcrm\Elasticsearch\Mapping\Property\MultiFieldProperty;
use Sugarcrm\Sugarcrm\Elasticsearch\Mapping\Mapping;
use Sugarcrm\Sugarcrm\Elasticsearch\Adapter\Document;
use Sugarcrm\Sugarcrm\Elasticsearch\Query\MatchAllQuery;
use SugarBean;
use Opportunity;


/**
 *
 * Custom Data Analytics Provider:
 *
 * Demo aggregation analytics for Opportunity metrics dashboard
 * for Account record views.
 *
 */
class DataAnalytics extends AbstractProvider implements ContainerAwareInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Opportunity status field to use
     * @var string
     */
    protected $oppStatusField = 'sales_stage';

    /**
     * Status mapping
     * @var array
     */
    protected $oppStatusMap = array(
        'Closed Lost' => 'lost',
        'Closed Won' => 'won',
    );

    /**
     * Default status aggregate
     * @var string
     */
    protected $oppStatusDefault = 'active';

    /**
     * Ctor
     */
    public function __construct()
    {
        // determine the status field to use for opportunities
        $this->oppStatusField = $this->getOppStatusField();
    }

    /**
     * {@inheritdoc}
     */
    public function buildAnalysis(AnalysisBuilder $analysisBuilder)
    {
        // no special analyzers required
    }

    /**
     * {@inheritdoc}
     */
    public function buildMapping(Mapping $mapping)
    {
        if ($mapping->getModule() !== 'Opportunities') {
            return;
        }

        // store the status for aggregation
        $status = new MultiFieldProperty();
        $status->setType('keyword');
        $mapping->addModuleField('custom_da_status', 'da_agg', $status);

        // store the dollar amount for aggregation
        $dollar = new MultiFieldProperty();
        $dollar->setType('float');
        $mapping->addModuleField('custom_da_dollar', 'da_agg', $dollar);

        // store the related account id for filtering
        $account = new MultiFieldProperty();
        $account->setType('keyword');
        $mapping->addModuleField('custom_da_account', 'da_filter', $account);
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentPreIndex(Document $document, SugarBean $bean)
    {
        if ($document->getType() !== 'Opportunities') {
            return;
        }

        // normalized status field
        $document->setDataField(
            'custom_da_status',
            $this->getOppStatus($bean->{$this->oppStatusField})
        );

        // amount in dollar
        $document->setDataField(
            'custom_da_dollar',
            $bean->amount_usdollar
        );

        // load account relationship
        if (!$bean->load_relationship('accounts')) {
            return;
        }

        // attach related account(s)
        $document->setDataField(
            'custom_da_account',
            $bean->accounts->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBeanIndexFields($module, $fromQueue = false)
    {
        if ($module !== 'Opportunities') {
            return array();
        }

        return array($this->oppStatusField, 'amount_usdollar');
    }

    /**
     * Get opportunity metrics for given Account id
     * @param string $accountId
     * @return array
     */
    public function getOppMetrics($accountId)
    {
        // filter by account id
        $accountFilter = new \Elastica\Query\Term();
        $accountFilter->setTerm('Opportunities' . Mapping::PREFIX_SEP . 'custom_da_account.da_filter', $accountId);

        // main aggregation bucket
        $agg = new \Elastica\Aggregation\Terms('status');
        $agg->setField('Opportunities' . Mapping::PREFIX_SEP . 'custom_da_status.da_agg');
        $agg->setSize(3);

        // sum aggregation
        $amount = new \Elastica\Aggregation\Sum('amount');
        $amount->setField('Opportunities' . Mapping::PREFIX_SEP . 'custom_da_dollar.da_agg');
        $agg->addAggregation($amount);

        // query builder
        $builder = new AggQueryBuilder($this->container);
        $builder
            ->setUser($this->user)
            ->setModules(array('Opportunities'))
            ->setQuery(new MatchAllQuery())
            ->addFilter($accountFilter)
            ->setLimit(0)
            ->addAggregation($agg)
        ;

        return $this->getOppMetricsResult($builder->executeSearch()->getResultSet());
    }

    /**
     * Get normalized opportunity metrics from Elastica response
     * @param \Elastica\ResultSet $resultSet
     * @return array
     */
    protected function getOppMetricsResult(\Elastica\ResultSet $resultSet)
    {
        $metrics = array(
            'won' => array('amount_usdollar' => 0, 'count' => 0),
            'lost' => array('amount_usdollar' => 0, 'count' => 0),
            'active' => array('amount_usdollar' => 0, 'count' => 0),
        );

        $result = $resultSet->getAggregation('status');

        foreach ($result['buckets'] as $bucket) {
            $metric = $bucket['key'];
            $metrics[$metric]['amount_usdollar'] = $bucket['amount']['value'];
            $metrics[$metric]['count'] = $bucket['doc_count'];
        }

        return $metrics;
    }

    /**
     * Get opportunity status field which depends on the configuration and
     * the availability of the RevenueLineItems module (flavor dependent).
     * @return string
     */
    protected function getOppStatusField()
    {
        $config = Opportunity::getSettings();
        return $config['opps_view_by'] === 'RevenueLineItems' ? 'sales_status' : 'sales_stage';
    }

    /**
     * Get normalized opportunity status
     * @param string $status
     * @return string
     */
    protected function getOppStatus($status)
    {
        return isset($this->oppStatusMap[$status]) ?
            $this->oppStatusMap[$status] :
            $this->oppStatusDefault;
    }
}
