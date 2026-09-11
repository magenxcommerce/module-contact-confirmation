<?php
/**
 * Copyright © Magenx. All rights reserved.
 */
declare(strict_types=1);

namespace Magenx\ContactConfirmation\Plugin;

use Magenx\ContactConfirmation\Model\ConfirmationEmailSender;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\DataObject;

/**
 * After Magento has emailed the store owner, also email the visitor a
 * confirmation/auto-reply.
 *
 * MailInterface::send(string $replyTo, array $variables) is the
 * shared entry point for the Luma controller and the GraphQL contactUs
 * resolver, but the two do NOT hand it the same $variables['data'] type:
 *
 * - Luma (Magento\Contact\Controller\Index\Post::sendEmail) passes
 *   `new DataObject($post)`.
 * - GraphQL (Magento\ContactGraphQl\Model\Resolver\ContactUs::resolve) passes
 *   the trimmed input **array** as-is.
 *
 * Core copes because it only ever uses `$variables['data']['name']`, which
 * works on both (DataObject implements ArrayAccess). Anything reading the
 * payload here has to handle both shapes too.
 */
class SendConfirmationEmail
{
    /**
     * Submitted fields copied into the auto-reply.
     */
    private const FIELDS = ['email', 'name', 'comment', 'telephone'];

    public function __construct(
        private readonly ConfirmationEmailSender $sender
    ) {
    }

    /**
     * @param MailInterface $subject
     * @param mixed $result
     * @param mixed $replyTo   Visitor email (used as the notification Reply-To)
     * @param array $variables
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSend(MailInterface $subject, $result, $replyTo = '', array $variables = [])
    {
        $data = $variables['data'] ?? null;

        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $this->readField($data, $field);
        }

        if ($values['email'] === '' && is_string($replyTo)) {
            $values['email'] = trim($replyTo);
        }

        // Called unconditionally, empty address included: the sender owns every
        // skip reason and logs it, so "no auto-reply arrived" is answerable
        // from var/log instead of guesswork.
        $this->sender->send(
            $values['email'],
            $values['name'],
            $values['comment'],
            $values['telephone']
        );

        return $result;
    }

    /**
     * Read one submitted field out of either payload shape.
     *
     * @param mixed  $data
     * @param string $field
     * @return string
     */
    private function readField($data, string $field): string
    {
        if ($data instanceof DataObject) {
            $value = $data->getData($field);
        } elseif (is_array($data)) {
            $value = $data[$field] ?? null;
        } elseif ($data instanceof \ArrayAccess) {
            $value = $data->offsetExists($field) ? $data->offsetGet($field) : null;
        } else {
            return '';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
