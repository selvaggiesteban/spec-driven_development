# Hands-on AI Framework

![Status](https://img.shields.io/badge/Status-Operational-green?style=flat-square)
![Architecture](https://img.shields.io/badge/Architecture-Domain--Driven-blue?style=flat-square)
![Version](https://img.shields.io/badge/Version-2026.1.0-blue?style=flat-square)

## 📖 Executive Summary

Welcome to the **Hands-on AI Framework**, a hyper-structured, domain-driven operating system designed to orchestrate workflows and development using artificial intelligence. This repository provides a rigorous **"Architecture as a Service"** model for collaborating effectively with LLMs and AI tools.

Every functional area—from Software Engineering to Marketing and SEO—is governed by a symmetrical ecosystem of autonomous **Agents**, rigorous **Checklists**, specialized **Skills**, and strategic **Plans**.

**Primary Goal:** To provide a standardized, scalable, and automated environment for deploying high-performance digital projects using seamless AI-human collaboration.

---

## 🏗️ System Architecture (The 5 Pillars)

The repository is built upon a strictly symmetrical, domain-driven folder structure (`snake_case`). Each domain (e.g., `web_development_and_technology`) exists equally across the five core pillars:

| Pillar | Directory | Purpose |
| :--- | :--- | :--- |
| **🤖 Intelligence** | `agents/` | Defines the personas, capabilities, and strict mandates for specialized AI agents. |
| **🧠 Capability** | `skills/` | Detailed technical guides, SOPs, and reference materials required by agents to execute tasks. |
| **✅ Assurance** | `checklist/` | Passive validation gates and compliance audits that **must** be passed before any deliverable is approved. |
| **⚡ Automation** | `scripts/` | Executable code (Python/Bash/PowerShell) to automate workflows, orchestrate agents, and manage the domain. |
| **🗺️ Strategy** | `plan/` | Strategic Operational Plans (Markdown) connecting agents, skills, and checklists into actionable roadmaps. |

### The Workspace (`src/`)
The `src/` directory acts as the container for isolated, independent client projects (e.g., `src/client-alpha`, `src/chat-vision`). Each folder inside `src/` represents a distinct project that inherits the agency's global intelligence.

---

## 🌐 The Agency Domains

The framework is categorized into overarching business and technical domains. Every pillar shares this exact structure:

1. `advanced_content_and_ai`
2. `automation`
3. `automation_tooling`
4. `core`
5. `creative_and_design`
6. `crm_and_automation`
7. `direction_and_strategy`
8. `marketing_and_media`
9. `multimedia_production`
10. `sales_and_growth`
11. `security_infrastructure_and_support`
12. `seo_and_content`
13. `social_media`
14. `strategy_and_analytics`
15. `talent_and_administration`
16. `user_experience`
17. `web_development_and_technology`

---

## 🚀 Quick Start: Project Orchestration

This framework uses a **dynamic, non-destructuve plan generation engine** to assign resources to new projects.

### 1. Initialize a Project Workspace
Create a new directory for your project inside `src/`:
```bash
mkdir src/my-new-project
```

### 2. Generate the Project Plan
Run the core automation engine. This script dynamically scans the entire repository for available Agents, Skills, Checklists, and Scripts, and injects a custom *Resource Allocation & Work Distribution* matrix into the strategic plans for your project.

```bash
python scripts/automation/generate_plan.py
```

> **What happens?** The engine reads the base templates in the global `plan/` folder, cross-references them with the active resources in the agency domains, and generates project-specific execution plans inside `src/my-new-project/plan/`.

### 3. Execution
Navigate to `src/my-new-project/plan/` and follow the generated markdown plans to deploy agents and execute the project systematically.

---

## 📜 Engineering Standards & "Absolute Truths"

All agents, scripts, and human operators must strictly adhere to the agency's global engineering mandates:

- **Architecture:** SOLID Principles and Clean Architecture are non-negotiable. Twelve-Factor App methodology is required for all services.
- **Security:** Zero Trust Architecture (ZTA) by default. Secrets must never touch the filesystem.
- **AI Operations:** Human-in-the-Loop (HITL) validation is mandatory for critical deployments. Prompt Engineering follows the Context-Task-Constraint (CTC) framework.
- **Quality Assurance:** Checklists are absolute. Deliverables must pass automated testing and peer review before finalization.

---

**Author:** Esteban Selvaggi  
**Website:** [selvaggiesteban.dev](https://selvaggiesteban.dev)  
*Built for the future of autonomous digital delivery.*