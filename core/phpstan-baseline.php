<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^The "module_installer\\.uninstall_validators" service is deprecated in drupal\\:11\\.1\\.0 and is removed from drupal\\:12\\.0\\.0\\. Inject "\\!tagged_iterator module_install\\.uninstall_validator" instead\\. See https\\://www\\.drupal\\.org/node/3432595$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Extension/ModuleInstaller.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Http/LinkRelationTypeManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/ImageToolkit/ImageToolkitManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Menu/MenuLinkManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Plugin/DefaultPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Plugin/DefaultPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/lib/Drupal/Core/Theme/ComponentPluginManager.php',
];
$ignoreErrors[] = [
	// identifier: missingType.return
	'message' => '#^Method class@anonymous/core/modules/filter/src/Plugin/Filter/FilterHtml\\.php\\:265\\:\\:setTextMode\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/filter/src/Plugin/Filter/FilterHtml.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateDestinationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateDestinationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateSourcePluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate/src/Plugin/MigrateSourcePluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/MigrationPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/src/Plugin/MigrateFieldPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrateFieldPluginManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/migrate_drupal/tests/src/Unit/MigrateFieldPluginManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/lazy_route_provider_install_test/src/PluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/module_test/src/PluginManagerCacheClearer.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/module_test/src/PluginManagerCacheClearer.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/DefaultsTestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/DefaultsTestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/MockBlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/MockBlockManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/modules/system/tests/modules/plugin_test/src/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubFallbackPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubPluginManagerBase.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubPluginManagerBaseWithMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Component/Plugin/StubPluginManagerBaseWithMapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Entity/EntityTypeManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Mail/MailManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/CategorizingPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/DefaultPluginManagerTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/FilteredPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/FilteredPluginManagerTraitTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Missing cache backend declaration for performance\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Plugin/TestPluginManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Plugin definitions cannot be altered\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/tests/Drupal/Tests/Core/Render/ElementInfoManagerTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
