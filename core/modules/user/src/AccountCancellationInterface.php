<?php

namespace Drupal\user;

/**
 * Interface of 'user.account_cancellation' service.
 */
interface AccountCancellationInterface {

  /**
   * Cancels a user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to be cancelled.
   * @param string $method
   *   The account cancellation method to use.
   * @param array $context
   *   (optional) Context array. Typically, an array of submitted form values as
   *   this service is mostly consumed via form API.
   */
  public function cancel(UserInterface $account, string $method, array $context = []): void;

}
