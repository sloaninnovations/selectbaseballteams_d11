<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Test\Exception\MissingGroupException;
use Drupal\Core\Test\TestDiscovery;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the TestDiscovery class.
 */
#[CoversClass(TestDiscovery::class)]
#[Group('Test')]
class AttributeBasedTestDiscoveryTest extends UnitTestCase {

  #[BeforeClass]
  public static function fixtureClassAutoloading(): void {
    $class_loader = new ClassLoader();
    $class_loader->addPsr4("Drupal\\TestDiscoveryFixture\\", __DIR__ . '/../../../../fixtures/test_test_discovery');
    $class_loader->addPsr4("Drupal\\Tests\\test_module\\", 'vfs://drupal/modules/test_module/tests/src');
    $class_loader->addPsr4("Drupal\\Tests\\test_profile_module\\", 'vfs://drupal/profiles/test_profile/modules/test_profile_module/tests/src');
    $class_loader->register(TRUE);
  }

  #[DataProvider('infoParserProvider')]
  public function testTestInfoParser(array $expected, string $classname): void {
    $info = TestDiscovery::getTestInfo($classname);
    $this->assertEquals($expected, $info);
  }

  public static function infoParserProvider(): \Generator {
    // A core unit test.
    yield 'phpunit-unit' => [
      // Expected result.
      [
        'name' => static::class,
        'group' => 'Test',
        'groups' => ['Test'],
        'description' => 'Tests \Drupal\Core\Test\TestDiscovery.',
        'type' => 'PHPUnit-Unit',
      ],
      // Classname.
      static::class,
    ];

    // Functional test.
    yield 'phpunit-functional' => [
      // Expected result.
      [
        'name' => 'Drupal\Tests\user\Functional\Rest\UserJsonAnonTest',
        'group' => 'rest',
        'groups' => ['rest'],
        'description' => '',
        'type' => 'PHPUnit-Functional',
      ],
      // Classname.
      'Drupal\Tests\user\Functional\Rest\UserJsonAnonTest',
    ];

    // Kernel test.
    yield 'phpunit-kernel' => [
      // Expected result.
      [
        'name' => 'Drupal\KernelTests\Core\Archiver\TarTest',
        'group' => 'tar',
        'groups' => ['tar'],
        'description' => 'Tests \Drupal\Core\Archiver\Tar.',
        'type' => 'PHPUnit-Kernel',
      ],
      // Classname.
      'Drupal\KernelTests\Core\Archiver\TarTest',
    ];
  }

  /**
   * @todo Remove the #[IgnoreDeprecations] attribute once all tests have been
   *   converted to use attributes instead of annotation. Until then, we need
   *   to accept going through TestDiscovery::getTestInfoFromAnnotation().
   */
  #[IgnoreDeprecations]
  public function testTestInfoParserMissingGroup(): void {
    $this->expectException(MissingGroupException::class);
    $this->expectExceptionMessage('Missing @group annotation in Drupal\TestDiscoveryFixture\NoGroupAttribute');
    TestDiscovery::getTestInfo('Drupal\TestDiscoveryFixture\NoGroupAttribute');
  }

  public function testTestInfoParserMissingSummary(): void {
    $info = TestDiscovery::getTestInfo('Drupal\TestDiscoveryFixture\NoDescription');
    $this->assertEmpty($info['description']);
  }

  protected function setupVfsWithTestClasses(): void {
    vfsStream::setup('drupal');

    $test_file = <<<EOF
<?php

declare(strict_types=1);

namespace Drupal\\Tests\\test_module\\Functional;

use PHPUnit\\Framework\\Attributes\\Group;

/**
 * Test description
 */
#[Group('example')]
class FunctionalExampleTest {}
EOF;

    $test_profile_info = <<<EOF
name: Testing
type: profile
core_version_requirement: '*'
EOF;

    $test_module_info = <<<EOF
name: Testing
type: module
core_version_requirement: '*'
EOF;

    vfsStream::create([
      'modules' => [
        'test_module' => [
          'test_module.info.yml' => $test_module_info,
          'tests' => [
            'src' => [
              'Functional' => [
                'FunctionalExampleTest.php' => $test_file,
                'FunctionalExampleTest2.php' => str_replace(['FunctionalExampleTest', '#[Group(\'example\')]'], ['FunctionalExampleTest2', '#[Group(\'example2\')]'], $test_file),
              ],
              'Kernel' => [
                'KernelExampleTest3.php' => str_replace(['namespace Drupal\\Tests\\test_module\\Functional', 'FunctionalExampleTest', '#[Group(\'example\')]'], ['namespace Drupal\\Tests\\test_module\\Kernel', 'KernelExampleTest3', "#[Group('example2')]\n#[Group('kernel')]"], $test_file),
                'KernelExampleTestBase.php' => str_replace(['namespace Drupal\\Tests\\test_module\\Functional', 'FunctionalExampleTest', '#[Group(\'example\')]'], ['namespace Drupal\\Tests\\test_module\\Kernel', 'KernelExampleTestBase', '#[Group(\'example2\')]'], $test_file),
                'KernelExampleTrait.php' => str_replace(['namespace Drupal\\Tests\\test_module\\Functional', 'FunctionalExampleTest', '#[Group(\'example\')]'], ['namespace Drupal\\Tests\\test_module\\Kernel', 'KernelExampleTrait', '#[Group(\'example2\')]'], $test_file),
                'KernelExampleInterface.php' => str_replace(['namespace Drupal\\Tests\\test_module\\Functional', 'FunctionalExampleTest', '#[Group(\'example\')]'], ['namespace Drupal\\Tests\\test_module\\Kernel', 'KernelExampleInterface', '#[Group(\'example2\')]'], $test_file),
              ],
            ],
          ],
        ],
      ],
      'profiles' => [
        'test_profile' => [
          'test_profile.info.yml' => $test_profile_info,
          'modules' => [
            'test_profile_module' => [
              'test_profile_module.info.yml' => $test_module_info,
              'tests' => [
                'src' => [
                  'Kernel' => [
                    'KernelExampleTest4.php' => str_replace(['namespace Drupal\\Tests\\test_module\\Functional', 'FunctionalExampleTest', '#[Group(\'example\')]'], ['namespace Drupal\\Tests\\test_profile_module\\Kernel', 'KernelExampleTest4', '#[Group(\'example3\')]'], $test_file),
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
  }

  public function testGetTestClasses(): void {
    $this->setupVfsWithTestClasses();
    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
    ];
    $test_discovery = $this->getTestDiscoveryMock('vfs://drupal', $extensions);

    $result = $test_discovery->getTestClasses();
    $this->assertCount(3, $result);
    $this->assertEquals([
      'example' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest',
          'description' => 'Test description',
          'group' => 'example',
          'groups' => ['example'],
          'type' => 'PHPUnit-Functional',
        ],
      ],
      'example2' => [
        'Drupal\Tests\test_module\Functional\FunctionalExampleTest2' => [
          'name' => 'Drupal\Tests\test_module\Functional\FunctionalExampleTest2',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2'],
          'type' => 'PHPUnit-Functional',
        ],
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'kernel' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ], $result);
  }

  /**
   * Mock a TestDiscovery object to return specific extension values.
   */
  protected function getTestDiscoveryMock(string $app_root, array $extensions): TestDiscovery&MockObject {
    $class_loader = $this->prophesize(ClassLoader::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $test_discovery = $this->getMockBuilder(TestDiscovery::class)
      ->setConstructorArgs([$app_root, $class_loader->reveal(), $module_handler->reveal()])
      ->onlyMethods(['getExtensions'])
      ->getMock();

    $test_discovery->expects($this->any())
      ->method('getExtensions')
      ->willReturn($extensions);

    return $test_discovery;
  }

  public function testGetTestClassesWithSelectedTypes(): void {
    $this->setupVfsWithTestClasses();
    $extensions = [
      'test_module' => new Extension('vfs://drupal', 'module', 'modules/test_module/test_module.info.yml'),
      'test_profile_module' => new Extension('vfs://drupal', 'profile', 'profiles/test_profile/modules/test_profile_module/test_profile_module.info.yml'),
    ];
    $test_discovery = $this->getTestDiscoveryMock('vfs://drupal', $extensions);

    $result = $test_discovery->getTestClasses(NULL, ['PHPUnit-Kernel']);
    $this->assertCount(4, $result);
    $this->assertEquals([
      'example' => [],
      'example2' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'kernel' => [
        'Drupal\Tests\test_module\Kernel\KernelExampleTest3' => [
          'name' => 'Drupal\Tests\test_module\Kernel\KernelExampleTest3',
          'description' => 'Test description',
          'group' => 'example2',
          'groups' => ['example2', 'kernel'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
      'example3' => [
        'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4' => [
          'name' => 'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4',
          'description' => 'Test description',
          'group' => 'example3',
          'groups' => ['example3'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ], $result);
  }

  public function testGetTestsInProfiles(): void {
    $this->setupVfsWithTestClasses();
    $class_loader = $this->prophesize(ClassLoader::class);

    $container = new Container();
    $container->set('kernel', new DrupalKernel('prod', new ClassLoader()));
    $container->setParameter('site.path', 'sites/default');
    \Drupal::setContainer($container);

    $test_discovery = new TestDiscovery('vfs://drupal', $class_loader->reveal());

    $result = $test_discovery->getTestClasses('test_profile_module', ['PHPUnit-Kernel']);
    $expected = [
      'example3' => [
        'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4' => [
          'name' => 'Drupal\Tests\test_profile_module\Kernel\KernelExampleTest4',
          'description' => 'Test description',
          'group' => 'example3',
          'groups' => ['example3'],
          'type' => 'PHPUnit-Kernel',
        ],
      ],
    ];
    $this->assertEquals($expected, $result);
  }

  #[DataProvider('providerTestGetPhpunitTestSuite')]
  public function testGetPhpunitTestSuite(string $classname, string|FALSE $expected): void {
    $this->assertEquals($expected, TestDiscovery::getPhpunitTestSuite($classname));
  }

  public static function providerTestGetPhpunitTestSuite(): array {
    $data = [];
    $data['simpletest-web test'] = ['\Drupal\rest\Tests\NodeTest', FALSE];
    $data['module-unittest'] = [static::class, 'Unit'];
    $data['module-kernel test'] = ['\Drupal\KernelTests\Core\Theme\TwigMarkupInterfaceTest', 'Kernel'];
    $data['module-functional test'] = ['\Drupal\FunctionalTests\BrowserTestBaseTest', 'Functional'];
    $data['module-functional javascript test'] = ['\Drupal\Tests\toolbar\FunctionalJavascript\ToolbarIntegrationTest', 'FunctionalJavascript'];
    $data['core-unittest'] = ['\Drupal\Tests\ComposerIntegrationTest', 'Unit'];
    $data['core-unittest2'] = ['Drupal\Tests\Core\DrupalTest', 'Unit'];
    $data['core-unittest3'] = ['Drupal\Tests\Scripts\TestSiteApplicationTest', 'Unit'];
    $data['core-kernel test'] = ['\Drupal\KernelTests\KernelTestBaseTest', 'Kernel'];
    $data['core-functional test'] = ['\Drupal\FunctionalTests\ExampleTest', 'Functional'];
    $data['core-functional javascript test'] = ['\Drupal\FunctionalJavascriptTests\ExampleTest', 'FunctionalJavascript'];
    $data['core-build test'] = ['\Drupal\BuildTests\Framework\Tests\BuildTestTest', 'Build'];

    return $data;
  }

  /**
   * Ensure TestDiscovery::scanDirectory() ignores certain abstract file types.
   */
  public function testScanDirectoryNoAbstract(): void {
    $this->setupVfsWithTestClasses();
    $files = TestDiscovery::scanDirectory('Drupal\\Tests\\test_module\\Kernel\\', vfsStream::url('drupal/modules/test_module/tests/src/Kernel'));
    $this->assertNotEmpty($files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTestBase', $files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTrait', $files);
    $this->assertArrayNotHasKey('Drupal\Tests\test_module\Kernel\KernelExampleInterface', $files);
    $this->assertArrayHasKey('Drupal\Tests\test_module\Kernel\KernelExampleTest3', $files);
  }

}
