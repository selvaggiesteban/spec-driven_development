import os
import shutil
from typing import Dict, List, Any

def get_repo_root() -> str:
    """
    Retorna el directorio raíz del repositorio.
    """
    return os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def list_available_resources(root_dir: str, area_name: str) -> Dict[str, List[str]]:
    """
    Escanea Agentes, Habilidades (Skills), Listas de verificación (Checklists) y Scripts en un área específica.
    """
    resources = {"agents": [], "skills": [], "checklists": [], "scripts": []}
    
    agent_area_path = os.path.join(root_dir, "agents", area_name)
    if os.path.exists(agent_area_path):
        for item in os.listdir(agent_area_path):
            if os.path.isdir(os.path.join(agent_area_path, item)):
                resources["agents"].append(item)

    skill_area_path = os.path.join(root_dir, "skills", area_name)
    if os.path.exists(skill_area_path):
        for item in os.listdir(skill_area_path):
            if os.path.isdir(os.path.join(skill_area_path, item)):
                resources["skills"].append(item)

    checklist_area_path = os.path.join(root_dir, "checklist", area_name)
    if os.path.exists(checklist_area_path):
        for item in os.listdir(checklist_area_path):
            resources["checklists"].append(item)

    script_area_path = os.path.join(root_dir, "scripts", area_name)
    if os.path.exists(script_area_path):
        for root, dirs, files in os.walk(script_area_path):
            for file in files:
                if file.endswith((".py", ".sh", ".ps1")):
                     rel_path = os.path.relpath(os.path.join(root, file), os.path.join(root_dir, "scripts"))
                     resources["scripts"].append(rel_path)
    return resources

def generate_enhanced_plan_content(base_content: str, area_name: str, resources: Dict[str, List[str]]) -> str:
    """
    Añade la asignación de recursos al contenido markdown base.
    """
    new_section = [
        "", "---", "## 🚀 Automated Work Distribution & Resource Allocation",
        "This section is automatically generated to ensure 100% project coverage.", "",
        "### 🤖 Active Agents", "The following agents are assigned to execute tasks in this domain:", ""
    ]
    if resources["agents"]:
        new_section.extend(["| Agent Role | Responsibility | Context Source |", "|------------|----------------|----------------|"])
        for agent in resources["agents"]:
            new_section.append(f"| **{agent}** | Autonomous execution of {area_name.replace('_', ' ')} tasks. | `agents/{area_name}/{agent}/agent.md` |")
    else:
        new_section.append("*No specific agents detected.*")

    new_section.extend(["", "### ✅ Compliance Checklists", "Audits required for task finalization:", ""])
    if resources["checklists"]:
        for chk in resources["checklists"]:
            new_section.append(f"- [ ] **{chk}**: Validate against `checklist/{area_name}/{chk}`.")
    else:
        new_section.append("*No specific checklists detected.*")

    new_section.extend(["", "### 🧠 Required Skills", "Competencies required:", ""])
    if resources["skills"]:
        new_section.extend(["| Skill Name | Mastery Definition |", "|------------|--------------------|"])
        for skill in resources["skills"]:
            new_section.append(f"| {skill} | `skills/{area_name}/{skill}/skill.md` |")
    else:
        new_section.append("*No specific skills definitions found.*")

    new_section.extend(["", "### 🛠️ Automation Scripts", "Tools to accelerate execution:", ""])
    if resources["scripts"]:
        for script in resources["scripts"]:
            new_section.append(f"- `python scripts/{script}`")
    else:
        new_section.append("*No automation scripts found.*")

    return base_content + "\n" + "\n".join(new_section)

def main() -> None:
    """
    Función principal para ejecutar el motor de generación de planes.
    """
    root_dir = get_repo_root()
    src_dir = os.path.join(root_dir, "src")
    base_plan_dir = os.path.join(root_dir, "plan")
    
    print("🔧 Starting Plan Generation Engine...")
    
    if not os.path.exists(src_dir):
        print(f"⚠️ El directorio 'src' no existe en {src_dir}. Abortando.")
        return

    projects = [d for d in os.listdir(src_dir) if os.path.isdir(os.path.join(src_dir, d))]
    
    for project in projects:
        print(f"🏗️ Processing Project: {project}")
        project_plan_dir = os.path.join(src_dir, project, "plan")
        if not os.path.exists(project_plan_dir):
            os.makedirs(project_plan_dir)

        plan_files = [f for f in os.listdir(base_plan_dir) if f.endswith(".md") and f != "00_MASTER_PLAN.md"]
        for plan_file in plan_files:
            area_name = os.path.splitext(plan_file)[0]
            with open(os.path.join(base_plan_dir, plan_file), "r", encoding="utf-8") as f:
                base_content = f.read()
            resources = list_available_resources(root_dir, area_name)
            enhanced_content = generate_enhanced_plan_content(base_content, area_name, resources)
            with open(os.path.join(project_plan_dir, plan_file), "w", encoding="utf-8") as f:
                f.write(enhanced_content)

    print("✅ Plan Generation Complete.")

if __name__ == "__main__":
    main()