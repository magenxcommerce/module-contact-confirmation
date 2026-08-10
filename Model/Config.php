<?php
/**
 * Copyright © Magenx. All rights reserved.
 */
declare(strict_types=1);

namespace Magenx\ContactConfirmation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed accessor for the contact auto-reply configuration.
 */
class Config
{
    private const XML_PATH_ENABLED = 'contact/magenx_confirmation/enabled';
    private const XML_PATH_TEMPLATE = 'contact/magenx_confirmation/email_template';
    private const XML_PATH_SENDER = 'contact/magenx_confirmation/sender_email_identity';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getTemplateId($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSenderIdentity($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'general';
    }
}
