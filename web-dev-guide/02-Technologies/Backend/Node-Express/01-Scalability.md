# Node.js + Express — Scalability

**Technology**: Node.js + Express
**Dimension**: 1 of 7 - Scalability

---

## Architecture Checklist - Scalability

Evaluate the following aspects:

### Code Organization
- [ ] Code is organized into routes, controllers, services and models
- [ ] There is no business logic mixed in routes (thin routers)
- [ ] Services are separated by domain/functionality

### Asynchronous Operations
- [ ] Blocking operations use `async/await` or callbacks correctly
- [ ] There are no synchronous operations that block the event loop
- [ ] Promises are handled correctly (no promises without catch)

### Horizontal Scalability
- [ ] Workers or clusters are implemented for multiple CPUs when necessary
- [ ] The application is stateless (does not depend on state in a single server's memory)
- [ ] Sessions are stored in Redis/DB, not in process memory

### Database
- [ ] Database queries use connection pooling
- [ ] There are no obvious N+1 queries
- [ ] Pagination is implemented in endpoints that return lists

---

## Quality Control - Scalability

### Performance Tests
- [ ] Server handles at least 100 req/s on simple endpoints without degradation
- [ ] No memory leaks after 1 hour of operation under load
- [ ] Database queries do not block the event loop

### Architecture Validation
- [ ] The application can scale horizontally (multiple instances)
- [ ] CPU usage is distributed across multiple cores (if using cluster)
- [ ] There is no shared state in memory between requests

### Metrics to Measure
- P95 response time: < 200ms on typical endpoints
- Memory usage: stable (no continuous growth)
- Event loop delay: < 10ms under normal conditions
