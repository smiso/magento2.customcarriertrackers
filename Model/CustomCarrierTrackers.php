<?php
/**
 * smile solutions Custom Carrier Trackers
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author      Daniel Kradolfer <kra@smilesolutions.ch>
 * @package     SmileSolutions_CustomCarrierTrackers
 * @copyright   Copyright (c) 2020 Daniel Kradolfer, smile solutions gmbh <kra@smilesolutions.ch>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace SmileSolutions\CustomCarrierTrackers\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;
use Psr\Log\LoggerInterface;

abstract class CustomCarrierTrackers extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'smiso_carrier_x';

    /**
     * @var ResultFactory
     */
    protected $trackFactory;

    /**
     * @var StatusFactory
     */
    protected $trackStatusFactory;

    /**
     * CustomCarrierTrackers constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param ResultFactory $trackFactory
     * @param StatusFactory $trackStatusFactory
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(ScopeConfigInterface $scopeConfig,
                                ErrorFactory $rateErrorFactory,
                                ResultFactory $trackFactory,
                                StatusFactory $trackStatusFactory,
                                LoggerInterface $logger,
                                array $data = [])
    {
        $this->trackFactory = $trackFactory;
        $this->trackStatusFactory = $trackStatusFactory;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritDoc
     */
    public function collectRates(RateRequest $request)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * All \Magento\Usa carriers have shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get tracking information
     *
     * @param string $tracking
     * @return string|false
     * @api
     */
    public function getTrackingInfo($tracking)
    {
        $result = $this->getTracking($tracking);

        if ($result instanceof \Magento\Shipping\Model\Tracking\Result) {
            $trackings = $result->getAllTrackings();
            if ($trackings) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     * Get tracking information
     *
     * @param string $tracking
     * @return string|false
     * @api
     */
    public function getTracking($tracking)
    {
        return $this->trackFactory->create()
            ->append(
                $this->trackStatusFactory->create()
                    ->setCarrier($this->_code)
                    ->setCarrierTitle($this->getConfigData('title'))
                    ->setTracking($tracking)
                    ->setUrl(
                        str_replace(
                            '{{trackingNumber}}', $tracking, $this->getConfigData('tracking_url')
                        )
                    )
            );
    }
}
