##### 5.9.2.4 Behavior

- Tokens are issued by an authentication server after successful authentication (e.g., username/password).
- The client stores the token (e.g., in memory, local storage, HttpOnly cookies) and attaches it to each protected request.
- The resource server validates the token without needing to consult the authentication server (stateless design).
- Token expiration invalidates access, requiring a new authentication process or use of refresh token.
- The token signature guarantees integrity and authenticity, but not payload confidentiality.
