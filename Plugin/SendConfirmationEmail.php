<?php
/**
 * Copyright © Magenx. All rights reserved.
 */
declare(strict_types=1);

namespace Magenx\ContactConfirmation\Plugin;

use Magenx\ContactConfirmation\Model\ConfirmationEmailSender;
use Magento\Contact\Model\Mail;
use Magento\Framework\DataObject;

/**
 * After Magento has emailed the store owner, also email the visitor a
 * confirmation/auto-reply.
 *
 * Magento\Contact\Model\Mail::send(string $replyTo, array $variables) is the
 * shared entry point for the Luma controller and the GraphQL contactUs
 * resolver. $variables['data'] is a DataObject carrying the submitted
 * name/email/telephone/comment (Luma) or name/email/telephone/comment (GraphQL
 * input) — the same shape in both flows.
 */
class SendConfirmationEmail
{
    public function __construct(
        private readonly ConfirmationEmailSender $sender
    ) {
    }

    /**
     * @param Mail  $subject
     * @param mixed $result
     * @param mixed $replyTo   Visitor email (used as the notification Reply-To)
     * @param array $variables
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSend(Mail $subject, $result, $replyTo = '', array $variables = [])
    {
        $data = $variables['data'] ?? null;

        $email = '';
        $name = '';
        $comment = '';
        $telephone = '';

        if ($data instanceof DataObject) {
            $email = (string) $data->getData('email');
            $name = (string) $data->getData('name');
            $comment = (string) $data->getData('comment');
            $telephone = (string) $data->getData('telephone');
        }

        if ($email === '' && is_string($replyTo)) {
            $email = $replyTo;
        }

        if ($email !== '') {
            $this->sender->send($email, $name, $comment, $telephone);
        }

        return $result;
    }
}
