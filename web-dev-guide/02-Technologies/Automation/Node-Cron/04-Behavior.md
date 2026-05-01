##### 5.9.4.4 Behavior

- A cron task is defined as a `CronJob` object that encapsulates the logic to execute and its schedule.
- Task execution is asynchronous, it does not block the Node.js main thread.
- In case of errors within the task function, Node-Cron will continue scheduling the next execution as defined, unless explicitly handled.
- Tasks run in the process where Node-Cron was instantiated; if the process stops, the tasks will also stop.
- Robust logging within each task is recommended to track its execution and possible failures.
