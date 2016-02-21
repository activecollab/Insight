# Insight

[![Build Status](https://travis-ci.org/activecollab/Insight.svg?branch=master)](https://travis-ci.org/activecollab/Insight)

## Snapshots

Data snapshots are created daily, weekly, monhtly, and yearly. When using Insight, you should provide a mechanism for snapshot builders to be called at:

1. Daily - at 00:00:00 each day,
2. Weekly - on Sunday or Monday each week, at 00:00:00,
3. Monthly - on 1st day of the month, at 00:00:00,
4. Yearly - on January 1st of each year, at 00:00:00.

## Running Tests

`cd` to this directory and run:

```bash
phpunit
```
