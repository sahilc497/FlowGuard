from fpdf import FPDF
import os
from datetime import datetime

class PDF(FPDF):
    def header(self):
        self.set_font('Arial', 'B', 15)
        self.cell(0, 10, 'FlowGuard Project Report', 0, 1, 'C')
        self.ln(10)

    def footer(self):
        self.set_y(-15)
        self.set_font('Arial', 'I', 8)
        self.cell(0, 10, f'Page {self.page_no()}/{{nb}}', 0, 0, 'C')

    def chapter_title(self, num, label):
        self.set_font('Arial', '', 12)
        self.set_fill_color(200, 220, 255)
        self.cell(0, 6, f'Section {num} : {label}', 0, 1, 'L', 1)
        self.ln(4)

    def chapter_body(self, body):
        self.set_font('Times', '', 12)
        self.multi_cell(0, 5, body)
        self.ln()

    def print_chapter(self, num, title, body):
        self.add_page()
        self.chapter_title(num, title)
        self.chapter_body(body)

pdf = PDF()
pdf.alias_nb_pages()
pdf.add_page()

# 1. Executive Summary
summary = """
FlowGuard is an advanced Smart Irrigation & Water Monitoring System designed to empower farmers with real-time data and remote control capabilities. The platform addresses critical challenges in agriculture such as water scarcity, inefficient irrigation, and leak detection.

Key functionalities include:
- Remote Motor Control via MQTT (IoT)
- Real-time Sensor Monitoring (Soil Moisture, Water Flow)
- AI-Driven Leak Detection
- Comprehensive Farmer & Admin Dashboards
"""
pdf.chapter_title(1, 'Executive Summary')
pdf.chapter_body(summary)

# 2. UI/UX Redesign (Bauhaus Theme)
design = """
Ver 2.0 introduces a complete UI overhaul based on 'Cool Bauhaus' and Industrial design principles. This aesthetic separates FlowGuard from generic dashboards, offering a distinct, professional, and high-readability interface.

Design Language:
- Palette: Deep Teal (#0F4C5C), Aqua Blue (#1CA7EC), Olive Green (#4F772D).
- Typography: 'Inter' (Sans-serif) & 'Space Mono' (Data) for clarity.
- Components: Flat geometric cards, sharp borders (0px radius), hard shadows, and dot-grid backgrounds.
- Experience: High-contrast inputs, animated news tickers, and distinct split-screen authentication flows.
"""
pdf.print_chapter(2, 'UI/UX Redesign (Bauhaus Theme)', design)

# 3. Technical Architecture
tech = """
Frontend:
- Core: Native PHP 8.2
- Styling: Custom CSS3 (Variables, Flexbox/Grid, Animations)
- Scripting: Vanilla JavaScript (ES6+)

Backend:
- Web Server: Apache (XAMPP)
- Database: MySQL (Referencing 'flowguard' schema)
- Real-time: MQTT (HiveMQ Cloud via WebSockets)

AI/ML Service:
- Python Flask API (server/server.py)
- Scikit-learn (Random Forest Classifier for leak detection)
"""
pdf.print_chapter(3, 'Technical Architecture', tech)

# 4. Key Modules & Files
modules = """
Authentication:
- client/farmer_login.php & register.php (Split-screen Bauhaus)
- client/admin_login.php

Dashboards:
- client/farmer_dashboard.php (Motor control, Live status)
- client/admin_dashboard.php (User management, System analytics)
- client/add_motor.php (Hardware registration)

Public Pages:
- client/index.php (Landing page with ticker)
- client/about.php, contact.php, article.php

Core Logic:
- client/config.php (DB & API Configuration)
- client/header.php & footer.php (Global include templates)
"""
pdf.print_chapter(4, 'Key Modules & Files', modules)

# 5. Deployment Instructions
setup = """
1. Infrastructure:
   - Ensure XAMPP (Apache/MySQL) is running.
   - Import 'flowguard.sql' into your MySQL database.

2. Configuration:
   - Update 'client/config.php' with local DB credentials.
   - Verify MQTT credentials in 'client/farmer_dashboard.php'.

3. Python Services:
   - Install requirements: `pip install flask pandas scikit-learn paho-mqtt`
   - Start the ML API: `python server/server.py`

4. Access:
   - Web: http://localhost/15K/client/
"""
pdf.print_chapter(5, 'Deployment & Setup', setup)

# Output
timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
filename = f"FlowGuard_Project_Report_{timestamp}.pdf"
# Save to project root for easy access
filepath = os.path.join("c:/xampp/htdocs/15K", filename)

pdf.output(filepath, 'F')
print(f"PDF generated: {filepath}")
