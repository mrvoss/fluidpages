<?php
namespace FluidTYPO3\Fluidpages\Service;

/*
 * This file is part of the FluidTYPO3/Fluidpages project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidpages\Provider\PageProvider;
use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Service\FluxService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 */
class ConfigurationService extends FluxService implements SingletonInterface {

	/**
	 * @var ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * @param ResourceFactory $resourceFactory
	 * @return void
	 */
	public function injectResourceFactory(ResourceFactory $resourceFactory) {
		$this->resourceFactory = $resourceFactory;
	}

	/**
	 * @param string $reference
	 * @return string
	 */
	public function convertFileReferenceToTemplatePathAndFilename($reference) {
		$filename = array_pop(explode(':', $reference));
		if (TRUE === ctype_digit($filename)) {
			return $this->resourceFactory->getFileObjectFromCombinedIdentifier($reference);
		}
		$reference = GeneralUtility::getFileAbsFileName($reference);
		return $reference;
	}

	/**
	 * @param string $reference
	 * @return array
	 */
	public function getViewConfigurationByFileReference($reference) {
		$extensionKey = 'fluidpages';
		if (0 === strpos($reference, 'EXT:')) {
			$extensionKey = substr($reference, 4, strpos($reference, '/') - 4);
		}
		$configuration = $this->getViewConfigurationForExtensionName($extensionKey);
		return $configuration;
	}

	/**
	 * Get definitions of paths for Page Templates defined in TypoScript
	 *
	 * @param string $extensionName
	 * @return array
	 * @api
	 */
	public function getPageConfiguration($extensionName = NULL) {
		if (NULL !== $extensionName && TRUE === empty($extensionName)) {
			// Note: a NULL extensionName means "fetch ALL defined collections" whereas
			// an empty value that is not null indicates an incorrect caller. Instead
			// of returning ALL paths here, an empty array is the proper return value.
			// However, dispatch a debug message to inform integrators of the problem.
			$this->message('Template paths have been attempted fetched using an empty value that is NOT NULL in ' . get_class($this) .
				'. This indicates a potential problem with your TypoScript configuration - a value which is expected to be ' .
			    'an array may be defined as a string. This error is not fatal but may prevent the affected collection (which cannot ' .
				'be identified here) from showing up', GeneralUtility::SYSLOG_SEVERITY_NOTICE);
			return array();
		}
		if (NULL !== $extensionName) {
			return $this->getViewConfigurationForExtensionName($extensionName);
		}
		$configurations = array();
		$registeredExtensionKeys = Core::getRegisteredProviderExtensionKeys('Page');
		foreach ($registeredExtensionKeys as $registeredExtensionKey) {
			$configurations[$registeredExtensionKey] = $this->getViewConfigurationForExtensionName($registeredExtensionKey);
		}
		return $configurations;
	}

	/**
	 * Resolve fluidpages specific configuration provider.
	 *
	 * @param array $row
	 *
	 * @return ProviderInterface|NULL
	 */
	public function resolvePageProvider($row) {
		$hasMainAction = FALSE === empty($row[PageProvider::FIELD_ACTION_MAIN]);
		$fieldName = TRUE === $hasMainAction ? PageProvider::FIELD_NAME_MAIN : PageProvider::FIELD_NAME_SUB;
		$provider = $this->resolvePrimaryConfigurationProvider('pages', $fieldName, $row);

		return $provider;
	}


}
