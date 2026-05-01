# Node.js + Express — Execution Options

**Technology**: Node.js + Express
**Dimension**: 2 of 7 - Execution Options

---

## Architecture Checklist - Execution Options

### Environment Variables
- [ ] Environment variables are managed with `dotenv` and `.env` files
- [ ] `.env.example` exists with all necessary variables (without real values)
- [ ] No hardcoded credentials in code

### NPM Scripts
- [ ] NPM scripts exist for `dev`, `start`, `test`, `lint`
- [ ] The `start` script is appropriate for production
- [ ] The `dev` script uses nodemon or similar for hot-reload

### Multi-Environment Configuration
- [ ] The server can start on different ports (configurable)
- [ ] Separate configuration exists per environment (dev, staging, prod)
- [ ] Logs use libraries like `winston` or `pino` with configurable levels

---

## Quality Control

### Validations
- [ ] `npm install && npm start` works in clean environment
- [ ] Missing environment variables generate clear errors at startup
- [ ] Logs are written correctly according to configured level
- [ ] Server starts on specified port without conflicts
