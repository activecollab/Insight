# Insight

[![Build Status](https://travis-ci.org/activecollab/Insight.svg?branch=master)](https://travis-ci.org/activecollab/insight)

## Account Status

An account can have following statuses:

1. **Trial** - User is in trial mode. Conversion or cancelation is expected. If not, we'll consider an account as retired.
2. **Free** - User is using an account in free mode. What this means to your app may very, but from Insight's perspective, it means that you have 0 MRR from this account,
3. **Paid** - User is paying for the service. MRR must exist (and we all know that MRR is good for business),
4. **Retired** - Account got archived because of "neglect" (user stopped testing the software, or paying, but did not convert or cancel; payment failed and user did not update billing info etc),
5. **Canceled** - Account is archived (or even completely removed) because user request that.

## Metrics

By adding account data as important account events happen, Insight will be able to provide following numbers:

1. Conversion rate (visitors to trial as first step, and trial to paid as second step),
2. Number of trial, free, paid, retired and canceled accounts on each day,
3. Various timelines for accounts (status changes, MRR changes, plan and billing period changes)
4. Monthly Recurring Revenue (MMR)
5. Average Revenue per User (ARPU)
6. Churn Rate (coming soon)
7. Customer Lifetime (coming soon)
8. Customer Lifetime Value (coming soon)

### MRR

Monthly Recurring Revenue (MMR) shows how much revenue are you getting each month from your users. It's calculated as sum of MRR values of each paying account on a given day. To get the value, simply call:

```php
$insight->accounts->mrr->getOnDay(); // Today
$insight->accounts->mrr->getOnDay(new DateValue('2016-02-22')); // Specific day
```

### ARPU

Average Revenue per User (ARPU) shows you how much monthly revenue do you get per user on average. You can grow your business quite a bit by focusing on increase of ARPU (using upgrades, plan changes etc), so it's good to keep an eye on this number. To get the value, call:

```php
$insight->accounts->arpu->getOnDay(); // Today
$insight->accounts->arpu->getOnDay(new DateValue('2016-02-22')); // Specific day
```

## Running Tests

`cd` to this directory and run:

```bash
phpunit
```
