# Node.js + Express — Roles and Responsibilities

**Technology**: Node.js + Express
**Dimension**: 7 of 7 - Roles and Responsibilities

---

## Purpose of this Dimension

This dimension defines **analysis perspectives** that the AI should adopt when auditing Node.js + Express code. Each role represents a set of specific concerns and criteria.

---

## Defined Roles

### 1. 👨‍💻 Senior Node.js Developer

**Focus**: Code quality, design patterns, maintainability

**Key questions**:
- [ ] Does the code use async/await correctly without mixing callbacks?
- [ ] Are routes separated from business logic?
- [ ] Is middleware used appropriately?
- [ ] Is there centralized error handling with `app.use((err, req, res, next)...)`?
- [ ] Do promises have error handling (.catch() or try/catch)?

**Warning signs**:
- Callback hell (deeply nested callbacks)
- Business logic inside routes
- Promises without error handling
- Use of `var` instead of `const`/`let`

---

### 2. 🔒 Security Analyst

**Focus**: Application security, OWASP Top 10 protection

**Key questions**:
- [ ] Is `helmet` used for security headers?
- [ ] Is rate limiting implemented (`express-rate-limit`)?
- [ ] Are passwords hashed with bcrypt (not stored in plain text)?
- [ ] Are user inputs validated and sanitized?
- [ ] Do JWT tokens have expiration and are correctly verified?
- [ ] Do sessions use `secure: true` and `httpOnly: true` in cookies?

**Warning signs**:
- Use of `eval()` or `Function()` with user input
- Direct SQL without prepared statements
- CORS configured with `origin: '*'` in production
- Hardcoded credentials in code
- No input validation

---

### 3. 🧪 QA Engineer

**Focus**: Testing quality, coverage, automation

**Key questions**:
- [ ] Do unit tests exist (Jest/Mocha)?
- [ ] Do integration tests exist for critical routes?
- [ ] Do tests cover error cases (not just happy path)?
- [ ] Are there mocks for external services?
- [ ] Does the `npm test` command run all tests?

**Warning signs**:
- Empty or non-existent `tests/` folder
- Commented out tests
- Code coverage < 60%
- Tests that fail in CI/CD

---

### 4. 🏗️ Software Architect

**Focus**: Project structure, scalability, architectural patterns

**Key questions**:
- [ ] Is the folder structure clear and scalable?
- [ ] Are responsibilities separated (routes/controllers/services/models)?
- [ ] Is there a single configuration point (`config/`)?
- [ ] Are dependencies well organized (dependencies vs devDependencies)?
- [ ] Can the application scale horizontally?

**Warning signs**:
- All files in root (no structure)
- Single `server.js` file with 1000+ lines
- Duplicated logic in multiple files
- Hardcoded configuration in code

---

### 5. 🚀 DevOps Engineer

**Focus**: Deployment, configuration, logs, monitoring

**Key questions**:
- [ ] Are environment variables used correctly (.env)?
- [ ] Does `.env.example` exist with all necessary variables?
- [ ] Do logs use appropriate levels (error, warn, info, debug)?
- [ ] Is graceful shutdown implemented?
- [ ] Does `package.json` have scripts for `start` in production?

**Warning signs**:
- Credentials in source code
- No handling of SIGTERM/SIGINT signals
- Logs with `console.log()` instead of logging library
- Hardcoded port in code

---

### 6. 🗄️ Database Administrator (DBA)

**Focus**: DB connections, queries, data optimization

**Key questions**:
- [ ] Does the DB connection use connection pooling?
- [ ] Are connections closed correctly?
- [ ] Do queries use prepared statements (SQL injection protection)?
- [ ] Is there connection error and reconnection handling?
- [ ] Are appropriate indexes used (visible in queries)?

**Warning signs**:
- Queries with string concatenation
- New connection per request (without pooling)
- N+1 queries (multiple queries in loops)
- No connection timeout handling

---

### 7. 📊 Performance Engineer

**Focus**: Optimization, load, bottlenecks

**Key questions**:
- [ ] Is response compression used (`compression` middleware)?
- [ ] Is caching implemented (Redis/in-memory)?
- [ ] Are heavy operations asynchronous?
- [ ] Are streams used for large files?
- [ ] Is there rate limiting to prevent abuse?

**Warning signs**:
- Synchronous blocking operations
- No response compression
- Queries that load entire DB into memory
- No pagination in list endpoints

---

## Using Roles in Analysis

### Assignment by Section

| Analysis Section | Main Roles |
|------------------|------------|
| Scalability | Architect, Performance Engineer |
| Execution Options | DevOps |
| Connectivity | Senior Developer, Architect |
| Behavior | Senior Developer, QA Engineer |
| Dependencies | DevOps, Security Analyst |
| Deliverable | Architect, DevOps |

### Prioritization by Context

When metadata specifies **"Auditor Role"**:
- **Security Audit**: Security Analyst leads, supported by DevOps
- **Performance Review**: Performance Engineer leads, supported by DBA
- **Code Quality**: Senior Developer leads, supported by Architect
- **Pre-Production**: DevOps leads, supported by Security Analyst

---

## Quality Control Checklist

- [ ] Each finding indicates which role detected it
- [ ] Recommendations are prioritized according to main role
- [ ] At least 3 different perspectives are considered per dimension
- [ ] Conflicts between roles are documented (e.g.: security vs performance)
