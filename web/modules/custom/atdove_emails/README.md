# AtDove Email Notes

This module contains some custom functionality
allowing the sending of many important emails
to AtDove.

It however **DOES NOT** manage all of the emails
we send out.

## Debugging Notes
Your local should be using "Devel Logging Mailer" per config split settings.

To see emails sent, navigate to (project-root)/tmp/devel-mails


## Places to Look in Drupal For System Emails

| Email           | Location |
| ----------------| -------- |
| Password Reset  | /admin/config/people/accounts |
| New Assignment | atDoveEmailFactory:: emailUserAboutNewAssignment |
| Quiz Completed - Admin Notice | atDoveEmailFactory::emailOrgAdminAssignentComplete |
| Invitation to New User | /admin/group/content/manage/organization-group_invitation |
| Invitation to a current user | /admin/group/content/manage/organization-group_invitation |
| Free Trial Activated (Requires creation) | atDoveEmailFactory::emailOrgAdminAboutNewTrial |

## Stripe Emails

| Email           | Location |
| ----------------| -------- |
| Free Trial is Ending | Stripe |
| Renewal Reactivation Notice (CLIENT) | Stripe |
| Renewal Reactivation Notice - (AtDove Team) | Stripe |
| Confirm Cancellation | Stripe |
| Renewal Failed - #1 to atdove admins | Stripe |
| Renewal Failed - #2 to atdove admins | Stripe |
| Renewal Stop Notification to atdove admins| Stripe |


