<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Controller\UserController;
use Drupal\user\Form\UserCancelForm;
use Drupal\user\Form\UserMultipleCancelConfirm;

/**
 * Tests deprecation of procedural custom user account cancelling method.
 *
 * @group user
 * @group legacy
 */
class UserDeprecatedCancelCustomMethodTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'user_cancel_deprecated_test',
    // In these modules, the hook has been previously implemented.
    'comment',
    'history',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests hook_user_cancel() hook deprecation.
   */
  public function testHookDeprecation(): void {
    $this->expectDeprecation('The deprecated hook hook_user_cancel() is implemented in these functions: user_cancel_deprecated_test_user_cancel(). The hook is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. In order to act on user account cancellation provide an event subscriber that listens to the \Drupal\user\Event\AccountCancelEvent event. The event subscriber can be defined with a priority higher than the core subscribers in order to cancel them by using AccountCancelEvent::stopPropagation(). See https://www.drupal.org/node/3279455');
    $this->container->get('user.account_cancellation')->cancel($this->createUser(), 'user_cancel_test_deprecated');

    $batch =& batch_get();
    // Bypass the progress bar and redirect.
    $batch['progressive'] = FALSE;
    $this->expectDeprecation("Using Drupal\user\EventSubscriber\AccountCancelSubscriber::doCancelAccount() subscriber to handle user account cancellation methods other than user_cancel_block, user_cancel_block_unpublish, user_cancel_reassign and user_cancel_delete is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. The 'user_cancel_test_deprecated' user account cancellation method has been used. Third-party modules should add their own subscriber to handle custom cancellation methods. See https://www.drupal.org/node/3279455");
    batch_process();
  }

  /**
   * Tests deprecation of procedural code.
   *
   * @covers \user_cancel
   * @covers \_user_cancel
   * @covers \_user_cancel_session_regenerate
   */
  public function testProceduralCodeDeprecations(): void {
    $this->expectDeprecation("user_cancel is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. Use the method ::cancel() from the 'user.account_cancellation' service instead. See https://www.drupal.org/node/3279455");
    user_cancel([], $this->createUser()->id(), 'abc');
    $this->expectDeprecation('_user_cancel is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. Instead, use \Drupal\user\EventSubscriber\AccountCancelSubscriber::doCancelAccount(). See https://www.drupal.org/node/3279455');
    _user_cancel([], $this->createUser(), 'abc');
    $this->expectDeprecation('_user_cancel_session_regenerate() is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. Instead, use \Drupal\user\AccountCancellation::regenerateSession(). See https://www.drupal.org/node/3279455');
    _user_cancel_session_regenerate();
  }

  /**
   * Tests constructor parameter additions deprecation messages.
   *
   * @covers \Drupal\user\Controller\UserController::__construct
   * @covers \Drupal\user\Form\UserCancelForm::__construct
   * @covers \Drupal\user\Form\UserMultipleCancelConfirm::__construct
   */
  public function testConstructorParamAdditionsDeprecationMessages(): void {
    $this->expectDeprecation('Calling Drupal\user\Controller\UserController::__construct() without the $account_cancellation argument is deprecated in drupal:10.0.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3279455');
    new UserController(
      $this->container->get('date.formatter'),
      $this->container->get('entity_type.manager')->getStorage('user'),
      $this->container->get('user.data'),
      $this->container->get('logger.factory')->get('user'),
      $this->container->get('flood')
    );

    $this->expectDeprecation('Calling Drupal\user\Form\UserCancelForm::__construct() without the $account_cancellation argument is deprecated in drupal:10.0.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3279455');
    new UserCancelForm(
      $this->container->get('entity.repository'),
      $this->container->get('entity_type.bundle.info'),
      $this->container->get('datetime.time')
    );

    $this->expectDeprecation('Calling Drupal\user\Form\UserMultipleCancelConfirm::__construct() without the $account_cancellation argument is deprecated in drupal:10.0.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3279455');
    new UserMultipleCancelConfirm(
      $this->container->get('tempstore.private'),
      $this->container->get('entity_type.manager')->getStorage('user'),
      $this->container->get('entity_type.manager')
    );
  }

}
