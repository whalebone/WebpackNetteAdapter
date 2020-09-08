<?php

declare(strict_types = 1);

namespace Oops\WebpackNetteAdapter\Manifest;

use Nette\Utils\Json;
use Oops\WebpackNetteAdapter\BuildDirectoryProvider;


/**
 * @internal
 */
class ManifestLoader
{

	/**
	 * @var BuildDirectoryProvider
	 */
	private $directoryProvider;

	/**
	 * @var ManifestMapper
	 */
	private $manifestMapper;


	public function __construct(BuildDirectoryProvider $directoryProvider, ?ManifestMapper $manifestMapper = null)
	{
		$this->directoryProvider = $directoryProvider;
		$this->manifestMapper = $manifestMapper ?? new IdentityMapper();
	}


	/**
	 * @throws CannotLoadManifestException
	 * @return array<string, string>
	 */
	public function loadManifest(string $fileName): array
	{
		$path = $this->getManifestPath($fileName);
		$context = \stream_context_create(['ssl' => ['verify_peer' => FALSE, 'verify_peer_name' => FALSE]]); // webpack-dev-server uses self-signed certificate
		$manifest = @\file_get_contents($path, FALSE, $context); // @ - errors handled by custom exception

		if ($manifest === FALSE) {
			throw new CannotLoadManifestException(\sprintf(
				"Manifest file '%s' could not be loaded: %s",
				$path, \error_get_last()['message'] ?? 'unknown error'
			));
		}

		return $this->manifestMapper->map(Json::decode($manifest, Json::FORCE_ARRAY));
	}


	public function getManifestPath(string $fileName): string
	{
		return $this->directoryProvider->getBuildDirectory() . '/' . $fileName;
	}

}
