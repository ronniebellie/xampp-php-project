"""Static consumer inventory/accessibility verification; no HTTP or browser."""
import json,pathlib,subprocess,os
from html.parser import HTMLParser
root=pathlib.Path(__file__).resolve().parents[1]
php=os.environ.get('PHP_BIN','php')
catalog=json.loads(subprocess.check_output([php,'-r','require "includes/calculator_catalog.php"; echo json_encode(rb_active_calculators());'],cwd=root))
class Page(HTMLParser):
 def __init__(self):super().__init__();self.labels=set();self.controls=[];self.in_label=0
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='label':
   self.in_label+=1
   if 'for' in a:self.labels.add(a['for'])
  if tag in ['input','textarea','select'] and a.get('type') not in ['hidden','submit','button']:self.controls.append((a,self.in_label))
 def handle_endtag(self,tag):
  if tag=='label':self.in_label=max(0,self.in_label-1)
checks=0
for calc in catalog.values():
 page=root/calc['route'].strip('/')/'index.php';source=page.read_text();p=Page();p.feed(source)
 assert '<title>' in source and 'description' in source,calc['route']
 checks+=2
 for a,wrapped in p.controls:
  assert wrapped or a.get('id') in p.labels or a.get('aria-label') or a.get('aria-labelledby'),(calc['route'],a)
  checks+=1
 assert 'calculator-footer.php' in source,calc['route'];checks+=1
 assert 'og-twitter-meta.php' in source,calc['route'];checks+=1
print(f'Consumer inventory: {len(catalog)} routes, accessible input names and common metadata/footer: {checks} checks passed.')
