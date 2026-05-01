##### 5.9.3.3 Connectivity

- Secure communication between the client, authorization server, and resource server (always HTTPS/TLS).
- The authorization server exposes endpoints for authorization requests, token issuance, and token revocation.
- The resource server uses the access token to validate client requests and obtain user information if necessary.
- Integration with Identity Providers (IdP) such as Google, Facebook, Okta, for user authentication.
