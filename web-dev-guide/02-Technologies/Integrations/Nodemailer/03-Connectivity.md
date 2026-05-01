##### 5.9.6.3 Connectivity

- Nodemailer connects to an external SMTP server to send emails.
- The reliability of the SMTP connection is crucial for successful sending.
- Handle connection and authentication errors with the SMTP server robustly.
- Consider firewalls and network policies that may block the SMTP port (typically 587 or 465).
- Email sending services typically have rate limits that must be respected.
