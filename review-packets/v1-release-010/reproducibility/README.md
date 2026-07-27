# TASK-010 revision reproducibility

The original TASK010_V1_RELEASE_FINAL bundle was preserved.
The runners in this directory are the exact bounded scripts used for the targeted review revision.
The revision changes only the full-release gate and handoff builder; the targeted, restore, browser, common, and resume runners are copied for inspection.
Task-011 was not started.

The full-release runner accepts both Docker Compose JSON arrays and the newline-delimited JSON emitted by Docker Compose v5.
