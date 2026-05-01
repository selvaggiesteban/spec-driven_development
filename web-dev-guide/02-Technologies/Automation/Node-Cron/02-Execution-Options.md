##### 5.9.4.2 Execution Options

- Cron tasks are defined using cron syntax (`* * * * * *` for seconds, minutes, hours, day of month, month, day of week).
- Tasks can start immediately (`start: true`) or be paused/resumed as needed.
- Specific timezones can be configured for task execution (`timeZone`).
- Handling overlapping tasks: Node-Cron does not automatically stop a task if the previous one is still running. It is the developer's responsibility to manage this.
- Tasks can register for `start` and `stop` events.
