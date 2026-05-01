# Node.js + Express — Connectivity

**Technology**: Node.js + Express
**Dimension**: 3 of 7 - Connectivity

## Architecture Checklist

### Route Organization
- [ ] Routes are organized in modules (`routes/users.js`, `routes/products.js`)
- [ ] Routes use Express routers correctly
- [ ] There is a central point for route registration

### Middlewares
- [ ] Authentication/authorization middlewares are centralized
- [ ] Validation middlewares are implemented
- [ ] Global error handling exists with middleware `app.use((err, req, res, next)...)`

### External APIs
- [ ] Calls to external APIs use `axios` or `node-fetch` with timeouts
- [ ] Network errors are managed (timeout, connection refused, DNS failures)
- [ ] Retries are implemented for critical calls

## Quality Control

- [ ] All endpoints respond with correct HTTP codes (200, 400, 401, 404, 500)
- [ ] Authentication middleware rejects invalid tokens
- [ ] External API timeouts are configured (no indefinite hangs)
