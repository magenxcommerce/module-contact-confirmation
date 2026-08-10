# Magenx_ContactConfirmation

Sends an **auto-reply confirmation email to the visitor** who submits the
contact form — in addition to the store-owner notification that stock
`Magento_Contact` already sends.

## How it works

`Magento\Contact\Model\Mail::send()` is the single method both submission paths
funnel through:

- **Luma:** `Magento\Contact\Controller\Index\Post`
- **Headless / GraphQL:** `Magento\ContactGraphQl\Model\Resolver\ContactUs`

An `afterSend` plugin (`Plugin/SendConfirmationEmail`) reads the submitted
`name / email / telephone / comment` from the `$variables['data']` DataObject
and hands them to `Model/ConfirmationEmailSender`, which sends a store-scoped
frontend email template to the visitor. The send is wrapped in try/catch and
never rethrows — a failed auto-reply must not break the (already-sent)
store-owner notification.

The storefront's `ContactUs` mutation is unchanged, so the `/api/graphql`
persisted-query allowlist is **not** affected.

## Configuration

**Stores → Configuration → General → Contacts → Auto-Reply Confirmation Email**

| Field | Config path | Default |
| --- | --- | --- |
| Send Confirmation Email | `contact/magenx_confirmation/enabled` | No |
| Email Template | `contact/magenx_confirmation/email_template` | `contact_magenx_confirmation_email_template` |
| Email Sender | `contact/magenx_confirmation/sender_email_identity` | `general` |

Off by default — a merchant opts in. All settings are store-view scoped, so
different store views can enable/translate the auto-reply independently.

## Template

`view/frontend/email/contact_confirmation.html` (id
`contact_magenx_confirmation_email_template`) — greets the visitor, confirms
receipt, and echoes their message back. Edit or override it in the admin
(**Marketing → Email Templates**, load the default) or per theme.

## Install

```
bin/magento module:enable Magenx_ContactConfirmation
bin/magento setup:upgrade
```

No schema changes; DI/config only.
