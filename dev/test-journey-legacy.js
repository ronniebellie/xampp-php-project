'use strict';
const fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const root=path.resolve(__dirname,'..');
for(const script of ['journey.ronbelisle.com/dev/phase-4-calibration/test-classifier-edge-cases.js','journey.ronbelisle.com/dev/phase-6-survivor/test-phase6-fixtures.js']){
 const c={console,JSON,Date,Number};c.window=c;vm.createContext(c);
 c.load=file=>vm.runInContext(fs.readFileSync(file.replace('/Applications/XAMPP/xamppfiles/htdocs',root),'utf8'),c);
 c.print=value=>{const report=JSON.parse(value);console.log(script,JSON.stringify(report));if((report.failed && (Array.isArray(report.failed)?report.failed.length:report.failed)) || report.failedCount)process.exitCode=1;};
 vm.runInContext(fs.readFileSync(path.join(root,script),'utf8'),c);
}
