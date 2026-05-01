# Audit Methodology

## 7-Step Audit Process

### 1. Read the metadata first
- Understand project context
- Identify client requirements
- Confirm deliverables needed

### 2. Read the original documentation/prompt
- What is promised
- What should exist
- Understand scope and acceptance criteria

### 3. Read the Changelogs if they exist
- Understand the project's change history
- Review version evolution
- Identify breaking changes

### 4. Analyze the code
- Verify if it fulfills what was promised
- Verify if it respects the style: code in English, comments in Spanish
- Check architecture and patterns
- Review implementation quality

### 5. Cross-reference everything with the requirements of the selected technology
- Mark what is met: `[x]`
- Mark what is not met: `[ ]`
- Mark what is doubtful or ambiguous as: `[?] Requires clarification`

### 6. Complete the analysis checklist
- For all applicable technologies
- Use 7 dimensions per technology (see below)
- Document findings and issues

### 7. Identify critical areas for improvement
- Security (OWASP Top 10)
- Architecture
- Testing
- Performance
- Documentation

## 7 Dimensions per Technology

Each technology is evaluated in 7 dimensions for internal audit quality control:

### 1. Scalability
- Ability to grow and handle load
- Horizontal and vertical scaling strategies
- Performance under increasing demand

### 2. Execution Options
- Configuration and environments
- Development vs Production settings
- Deployment options

### 3. Connectivity
- Integrations and communication
- API connections
- External service integration
- Inter-service communication

### 4. Behavior
- Logic, flows, and patterns
- Business logic implementation
- Error handling
- Edge cases

### 5. Dependencies
- Management of libraries and packages
- Version control
- Security vulnerabilities
- Update strategy

### 6. Deliverable
- Project documentation and structure
- Build artifacts
- Deployment packages
- Documentation completeness

### 7. Roles and Responsibilities
- Defines which **role perspective you should adopt** to evaluate each aspect
- Examples: Senior Developer, QA Engineer, Security Analyst, DevOps Engineer, Architect, DBA, etc.

## Important Note on Dimension 7: Role Perspective Switching

This dimension tells you which **role you should impersonate** when reviewing each aspect:

- **Example**: When reviewing "Scalability" in Node.js, you assume the role of "Node.js Senior Developer" with that level of expertise and rigor
- **Example**: When reviewing "Security", you assume the role of "Security Analyst" with deep knowledge of vulnerabilities
- **Example**: When reviewing "Database Dependencies", you assume the role of "DBA" with database optimization expertise

This ensures that each dimension is evaluated with the level of detail and criteria that an expert in that area would use.

⚠️ **Important**: It is NOT a real team, it is you (the AI) changing perspective according to the aspect to evaluate.
