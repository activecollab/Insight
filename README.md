# Insight

Insight is build around concept of an account that can be enriched with following components:

1. Properties       - track different account properties and how they change through time
2. System logs      - keep track of system logs that are useful for supporting your users
3. Events           - track important (they can repeat)
4. Goals            - set goals and see which accounts reach them
5. Dataset timeline - track changes to the size of account's dataset (a la Git's ++ and --)
6. Active users     - track DAU, MAU, YAU

## Dateset timeline

Dataset timeline tracks four events:

1. Additions (or ++) - a new object has been added
2. Unarchives (or +) - something that was archived is active again
3. Archives (or -) - something active is now moved to archive (not deleted, but no loger active)
4. Deletions (or --) - permanent removal of an object from the database

When these numbers are collected, you should be able to create a timeline where you see how data set changed over time. These changes can indicate how engaged the users are with your product.

## Goals

The difference between goals and events is that goals can be reached only once, and at this point status of a user changes (for exactly, trial user becomes a customer by paying).