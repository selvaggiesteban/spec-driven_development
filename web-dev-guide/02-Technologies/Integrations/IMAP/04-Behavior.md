##### 5.9.5.4 Behavior

- The application connects to the IMAP server using user credentials.
- Once connected, it can select email folders (INBOX, etc.) and list messages.
- Messages can be marked as read, unread, or deleted directly on the server.
- Email download can be by headers (for filtering) or full body (including attachments).
- Event handling (e.g., new emails) may require the use of `IDLE` commands if the IMAP server supports them.
