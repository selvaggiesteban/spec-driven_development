# Automation Scripts Department

## 1. Executive Summary
The Automation department is the force multiplier of the agency. It focuses on the "Automation First" doctrine, creating tools that eliminate repetitive tasks and ensure consistency across all projects.

## 2. Core Automation Tools

### 🤖 Plan Generation & Resource Distribution
- **`generate_plan.py`**: The primary orchestration engine. It scans the repository's source of truth (`agents/`, `skills/`, `checklist/`) and dynamically injects an "Automated Work Distribution" section into the functional plans of each project in `src/`. This ensures 100% project coverage and resource allocation.

### 🛠️ Content & System Maintenance
- **`upgrade_content.py`**: A structural synchronization tool used to maintain parity between the `agents/` and `skills/` directories. (Note: Use with caution as it enforces template standards).

## 3. Operational Protocols
- **Source of Truth**: All scripts must read from the root `agents/` and `plan/` directories to derive their logic.
- **Safety First**: Scripts operating on `src/` projects must never overwrite manual code without a backup or specific flag.
- **Semantic Logging**: Automation tools must provide clear console output regarding the resources they are mapping.

## 4. Key Performance Indicators (KPIs)
- **Token Efficiency**: Minimizing AI interaction for repetitive structural tasks.
- **Project Readiness**: Time taken to distribute a full strategic plan to a new project (< 5s).
- **Consistency Score**: 100% alignment between Agent definitions and Project Task lists.

---
*Last Updated: 2026-02-05 | Status: OPERATIONAL*
