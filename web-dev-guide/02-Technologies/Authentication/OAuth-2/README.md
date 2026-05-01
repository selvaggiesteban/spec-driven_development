#### 5.9.3. Quality Control - OAuth 2.0

- [ ] The most secure and appropriate grant flow (grant type) is used for the client type.
- [ ] The client application's `redirect_uri`s are correctly registered and specific.
- [ ] `client_secret`s of confidential applications are managed securely.
- [ ] PKCE is implemented for public clients (SPA, mobile) to prevent code interception.
- [ ] Access tokens and refresh tokens have appropriate expiration times and are rotated.
- [ ] All communications are performed over HTTPS/TLS.
- [ ] The token issuer and `aud` (audience) are validated to ensure the token is for the correct resource.
- [ ] Protection against CSRF attacks in the authorization flow is in place.
- [ ] Implicit Grant Flow is avoided whenever possible.
