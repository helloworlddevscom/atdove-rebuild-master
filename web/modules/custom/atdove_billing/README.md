# AT DOVE BILLING

Provides stripe related functionality for the atdove site.

## Stripe Webhook Endpoint

The stripe webhook endpoint works by providing a controller that accepts POST http
requests. It will actually accept a request from anyone, but only will process
valid requests that meet the criteria for Stripe Webhook events and thier encryption
methodology(ies?).

Important documents to note/reference:
https://stripe.com/docs/webhooks/quickstart
https://stripe.com/docs/webhooks/test

You will likely want to install stripe locally. We may be able to install it on
lando so nobody needs to install it locally? TBD on that...

`brew install stripe` does the trick for now though.

### Important/useful commands

For local testing:

`stripe listen --forward-to https://atdove-rebuild.lando/dove-stripe/webhook --skip-verify`

This will begin the listener on your local. Next you will navigate to the Stripe dashboard
for the Hello World testing environment. Trigger events as necessary by making changes in the Dashboard UI.

Failures are currently captured in the log.

Success currently logs as well for you to see.

#####TODO:
1. Determine exactly what events we want to respond to (subscription).
1. Determine how to trigger them in the dashboard.
1. Write a switch for just those events.
1. Look at what data is returned, determine if we can adjust an organization.
1. Write behat tests that emulate the trigger of webhook events so we can verify accounts move as necessary.
