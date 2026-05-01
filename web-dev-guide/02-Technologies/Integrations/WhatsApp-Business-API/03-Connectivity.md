##### 5.9.9.3 Connectivity

- The application connects to WhatsApp Business API endpoints via HTTPS.
- Webhooks configure an HTTPS endpoint in the application to receive messages and delivery statuses.
- Ensure the application has outbound access to WhatsApp/Facebook domains and that the Webhook endpoint is accessible from the Internet.
- Cloud API connectivity is direct via HTTPS; for On-Premise API, a Docker container is managed.
- Implement retries with exponential backoff for API calls and robust network error handling.
