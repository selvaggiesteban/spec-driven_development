##### 5.9.3.7 Roles and Responsibilities

**Frontend/Client Developer**:
- Implements authorization flows in the client application.
- Manages secure storage and use of access tokens and refresh tokens.
- Handles user redirection to the authorization server.

**Backend/Resource Server Developer**:
- Configures validation of access tokens received from the client application.
- Protects API endpoints based on token scopes and claims.
- Integrates OAuth 2.0 client/server libraries.

**Security Analyst**:
- Reviews the configuration of registered `redirect_uri`s and `scope`s.
- Audits secure management of `client_secret`s and PKCE implementation.
- Verifies protection against attacks such as CSRF in the authorization flow.

**Authorization Server Administrator (IdP)**:
- Manages registration of client applications and their permissions.
- Monitors token issuance and revocation.
- Secures the authorization server infrastructure.
