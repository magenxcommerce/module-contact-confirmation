<?php
/**
 * Copyright © Magenx. All rights reserved.
 */
declare(strict_types=1);

namespace Magenx\ContactConfirmation\Model;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Builds and sends the auto-reply confirmation email to the visitor.
 *
 * Kept separate from the plugin so the send logic is unit-testable and the
 * plugin stays a thin guard.
 */
class ConfirmationEmailSender
{
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly StateInterface $inlineTranslation,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send the confirmation to $email. Never throws — a failed auto-reply must
     * not break the (already-sent) store-owner notification.
     *
     * @param string $email    Visitor email address
     * @param string $name     Visitor name
     * @param string $comment  The message the visitor submitted
     * @param string $telephone
     */
    public function send(string $email, string $name, string $comment, string $telephone = ''): void
    {
        if ($email === '') {
            return;
        }

        // Only resumed if actually suspended: StateInterface::resume() enables
        // inline translation unconditionally, so calling it on an early return
        // would turn it on for the rest of the request.
        $suspended = false;

        try {
            $store = $this->storeManager->getStore();
            $storeId = (int) $store->getId();

            if (!$this->config->isEnabled($storeId)) {
                return;
            }

            $templateId = $this->config->getTemplateId($storeId);
            if ($templateId === '') {
                return;
            }

            $this->inlineTranslation->suspend();
            $suspended = true;

            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'name' => $name,
                    'comment' => $comment,
                    'telephone' => $telephone,
                    'store' => $store,
                    'store_name' => $store->getFrontendName(),
                ])
                ->setFromByScope($this->config->getSenderIdentity($storeId), $storeId)
                ->addTo($email, $name !== '' ? $name : $email);

            $this->transportBuilder->getTransport()->sendMessage();
        } catch (\Throwable $e) {
            $this->logger->error(
                'Magenx_ContactConfirmation: failed to send confirmation email: ' . $e->getMessage()
            );
        } finally {
            if ($suspended) {
                $this->inlineTranslation->resume();
            }
        }
    }
}
