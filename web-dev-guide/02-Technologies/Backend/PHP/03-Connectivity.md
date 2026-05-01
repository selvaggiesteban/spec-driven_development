##### 5.1.3.3 Connectivity

- Error scenarios with external APIs have been tested (timeouts, 4xx errors, 5xx).
- The app doesn't break if an external service fails; affected functions degrade gracefully.
- Externally received data is correctly validated and sanitized.
