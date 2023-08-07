<?php

namespace Drupal\mailer\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Message subscriber which sets the from and sender headers.
 */
class OriginatorSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new originator subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory
  ) {
  }

  /**
   * Sets the default from header and a sender header if necessary.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The message event.
   */
  public function onMessage(MessageEvent $event): void {
    $message = $event->getMessage();
    if ($message instanceof Email) {
      $this->setDefaultFrom($message);
      $this->setDefaultSender($message);
      $this->removeRedundantSender($message);
    }
  }

  /**
   * Sets the default from address.
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   */
  protected function setDefaultFrom(Email $message): void {
    $from = $message->getFrom();
    if (count($from) === 0) {
      $siteAddress = $this->getSiteAddress();
      $message->from($siteAddress);
    }
  }

  /**
   * Sets the default sender address.
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   */
  protected function setDefaultSender(Email $message): void {
    if (!$message->getSender()) {
      $siteAddress = $this->getSiteAddress();
      $message->sender($siteAddress);
    }
  }

  /**
   * Removes the Sender address if it is redundant.
   *
   * Rules according to RFC 5322 section 3.6.2 (Originator Fields):
   * * If the from field contains more than one mailbox specification in the
   *   mailbox-list, then the sender field, containing the field name
   *   "Sender" and a single mailbox specification, MUST appear in the
   *   message.
   * * The "Sender:" field specifies the mailbox of the agent responsible
   *   for the actual transmission of the message.
   * * If the originator of the message can be indicated by a single mailbox
   *   and the author and transmitter are identical, the "Sender:" field
   *   SHOULD NOT be used.
   *
   * @see https://www.rfc-editor.org/rfc/rfc5322.html#section-3.6.2
   *
   * @param \Symfony\Component\Mime\Email $message
   *   The email message.
   */
  protected function removeRedundantSender(Email $message): void {
    $from = $message->getFrom();
    $sender = $message->getSender();
    $senderRedundant = count($from) === 1 &&
      $sender !== NULL &&
      $from[0]->getAddress() === $sender->getAddress();
    if ($senderRedundant) {
      $message->getHeaders()->remove('Sender');
    }
  }

  /**
   * Returns the site email address.
   */
  protected function getSiteAddress(): Address {
    $config = $this->configFactory->get('system.site');
    return new Address($config->get('mail'), $config->get('name'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // should be the last one to allow header changes by other listeners.
      MessageEvent::class => ['onMessage', -255],
    ];
  }

}
