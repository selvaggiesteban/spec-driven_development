# Node.js + Express — Behavior

**Dimension**: 4 of 7 - Behavior

## Checklist
- [ ] Business logic is in services, not in routes
- [ ] Validation middlewares are used (`express-validator`, `joi`)
- [ ] Error responses are consistent
- [ ] Tests exist with `jest` or `mocha`

## Guide
Keep routes thin, delegate to services, implement input validation.

## Control
- [ ] Tests pass
- [ ] Error handler catches exceptions without crashing
