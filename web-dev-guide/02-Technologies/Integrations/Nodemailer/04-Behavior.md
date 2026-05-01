##### 5.9.6.4 Behavior

- Nodemailer creates a "transporter" that encapsulates the SMTP connection configuration.
- The transporter is used to send email messages defined with sender, recipients, subject, HTML/text body, and attachments options.
- Email sending is asynchronous and returns a promise or accepts a callback.
- Handle SMTP server responses to confirm successful sending or identify errors.
- Supports email template creation with libraries like Handlebars.
