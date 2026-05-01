#### 5.9.2. Quality Control - JSON Web Tokens (JWT)

- [ ] The JWT secret key is managed securely (environment variables, Key Vault).
- [ ] A robust signature algorithm is used (e.g., HS256, RS256) and verified.
- [ ] The token has an appropriate expiration time (exp claim) and is validated.
- [ ] A token revocation mechanism is implemented (blacklists or refresh tokens).
- [ ] No sensitive information is stored directly in the JWT payload.
- [ ] Token validation includes the issuer (iss) and audience (aud) if applicable.
- [ ] Short-lived tokens with long-lived refresh tokens are used.
- [ ] The JWT is always transmitted over HTTPS/TLS.
- [ ] Token signature validation is performed on each protected request.
