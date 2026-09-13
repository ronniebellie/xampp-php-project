'use strict';
const fs=require('fs'),vm=require('vm'),assert=require('assert/strict'),path=require('path');
const root=path.resolve(__dirname,'..');
(async()=>{
 const calls=[],window={location:new URL('https://example.invalid/retirement-plan/'),rbScenarioCsrfToken:'synthetic-token'};
 vm.runInNewContext(fs.readFileSync(path.join(root,'js/scenario-api.js'),'utf8'),{window,URL,Headers,Promise,fetch:async(url,options)=>{calls.push({url,options});return {ok:true};}});
 const input={method:'POST',headers:{'Content-Type':'application/json'},body:'{"results_summary":"0"}'};
 await window.rbExplainFetch('/api/explain_results.php',input);
 assert.equal(calls[0].options.headers.get('X-CSRF-Token'),'synthetic-token');
 assert.equal(calls[0].options.body,input.body);assert.equal(calls[0].options.redirect,'error');assert.equal(calls[0].options.credentials,'same-origin');
 for(const bad of ['https://other.invalid/api/explain_results.php','//other.invalid/api/explain_results.php','/api/other.php'])await assert.rejects(window.rbExplainFetch(bad,input));
 assert.equal(calls.length,1);
 let pages=0;
 function visit(dir){for(const ent of fs.readdirSync(dir,{withFileTypes:true})){if(['vendor','dev','.git'].includes(ent.name))continue;const file=path.join(dir,ent.name);if(ent.isDirectory())visit(file);else if(ent.name==='calculator.js'){
  const s=fs.readFileSync(file,'utf8');if(!s.includes('explain_results.php'))continue;
  assert.ok(s.includes('window.rbExplainFetch('),file);assert.ok(fs.readFileSync(path.join(dir,'index.php'),'utf8').includes('includes/calculator-footer.php'),file+' footer');pages++;
 }}}
 visit(root);assert.equal(pages,18);
 const inline=fs.readFileSync(path.join(root,'required-vs-desired/index.php'),'utf8');assert.ok(inline.includes('window.rbExplainFetch('));
 console.log('Phase 7 client CSRF tests passed (19 calculator integrations, same-origin/redirect/payload boundaries).');
})().catch(e=>{console.error(e);process.exitCode=1;});
