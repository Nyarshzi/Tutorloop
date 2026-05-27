import pathlib, re
root = pathlib.Path('c:/xampp/htdocs/tutorloop')
php_files = sorted(root.glob('**/*.php'))
missing = []
for p in php_files:
    text = p.read_text(encoding='utf-8', errors='ignore')
    has_sidebar = re.search(r'<(?:aside|div)\s+[^>]*class=("|\')sidebar("|\')', text)
    has_id = re.search(r'<(?:aside|div)\s+[^>]*id=("|\')sidebar("|\')', text)
    has_overlay = 'sidebar-overlay' in text
    has_menu = re.search(r'<button[^>]*class=("|\')menu-btn("|\')', text)
    has_js = bool(re.search(r"document\.querySelector\('#menuBtn'\)|document\.querySelector\('#sidebar'\)|\.classList\.toggle\('sidebar-open'\)|document\.querySelector\('#sidebarOverlay'\)", text))
    if has_sidebar and not (has_id and has_overlay and has_menu and has_js):
        missing.append((str(p.relative_to(root)), bool(has_id), has_overlay, bool(has_menu), has_js))
print('missing:', len(missing))
for row in missing:
    print(row)
print('checked', len(php_files))
