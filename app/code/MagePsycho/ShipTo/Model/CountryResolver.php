<?php

namespace MagePsycho\ShipTo\Model;

use Magento\Directory\Model\CountryFactory;
use MagePsycho\ShipTo\Helper\Data as ShipToHelper;
use Magento\Directory\Model\AllowedCountries;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Amasty\Geoip\Model\Geolocation;

/**
 * @category   MagePsycho
 * @package    MagePsycho_ShipTo
 * @author     Raj KB <magepsycho@gmail.com>
 * @website    https://www.magepsycho.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CountryResolver
{
    /**
     * @var ShipToHelper
     */
    private $shipToHelper;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var AllowedCountries
     */
    private $allowedCountries;

    /**
     * @var CountryCollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Geolocation
     */
    private $geolocation;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(
        ShipToHelper $shipToHelper,
        CountryFactory $countryFactory,
        AllowedCountries $allowedCountries,
        CountryCollectionFactory $countryCollectionFactory,
        Session $session,
        Geolocation $geolocation,
        RemoteAddress $remoteAddress
    ) {
        $this->shipToHelper = $shipToHelper;
        $this->countryFactory = $countryFactory;
        $this->allowedCountries = $allowedCountries;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->session = $session;
        $this->geolocation = $geolocation;
        $this->remoteAddress = $remoteAddress;
    }

    public function getAllowedCountryIds()
    {
        return $this->allowedCountries->getAllowedCountries();
    }

    public function getAllowedCountryOptions()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $collection = $this->countryCollectionFactory->create();
        $collection->addCountryIdFilter($this->getAllowedCountryIds())->load();

        $options = [];
        foreach ($collection as $item) {
            $options[$item->getCountryId()] = $item->getName();
        }

        asort($options);
        $this->options = $options;
        return $this->options;
    }

    public function getShipToCountryId()
    {
        $country = $this->session->getMpShipToCountry();
        if (!$country) {
            // Get country from GeoIP
            $ip = $this->remoteAddress->getRemoteAddress();
            $geolocationData = $this->geolocation->locate($ip);

            $country = $geolocationData->getData('country');
            if (!$country) {
                $country = $this->shipToHelper->getConfigHelper()->getDefaultCountryId();
            }
        }
        return $country;
    }

    /**
     * @return array
     */
    public function getShipToCountryInfo()
    {
        $id = $this->getShipToCountryId();
        $country = $this->countryFactory->create()->loadByCode($id);
        $name = $country && $country->getId() ? $country->getName() : '';
        return [
            'id' => $id,
            'name' => $name
        ];
    }
}
