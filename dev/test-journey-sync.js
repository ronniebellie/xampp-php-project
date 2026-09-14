'use strict';
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
const source=fs.readFileSync(path.join(__dirname,'../journey.ronbelisle.com/assets/js/journey-sync.js'),'utf8');
const PK='rbJourneyProgressV1',CK='rbJourneyCalculator:retirementSpendingPlan:v1',PENDING='rbJourneySyncPendingV1';
function storage(seed={}) {const m=new Map(Object.entries(seed));return {getItem:k=>m.get(k)||null,setItem:(k,v)=>m.set(k,String(v)),removeItem:k=>m.delete(k),key:i=>[...m.keys()][i],get length(){return m.size}};}
async function scenario({owner=1,local={},pending={},cloud=null}) {
 const calls=[];const localStorage=storage(local),sessionStorage=storage(pending);let wrote=false;
 const c={console,JSON,Promise,Date,Number,Blob,AbortSignal,localStorage,sessionStorage,navigator:{onLine:true},document:{getElementById:()=>({})},CustomEvent:function(type,args){this.type=type;this.detail=args.detail},setTimeout,clearTimeout,dispatchEvent(){},addEventListener(){}};c.window=c;
 c.fetch=async(url,opts)=>{calls.push({url,opts});let body;
 if(url.includes('journey-status'))body={authenticated:true,userId:owner,hasAccess:true,canCloudRead:true,canCloudWrite:true,cloudPlanExists:!!cloud};
 else if(url.includes('plan_load'))body={success:true,canWrite:true,readOnly:false,exists:!!cloud,plan:cloud,csrfToken:'fixture-token'};
 else {wrote=true;body={success:true,plan:{revision:'saved-revision',serverUpdatedAt:'2026-09-14T12:00:00Z'}};}
 return {ok:true,status:200,json:async()=>body};};vm.createContext(c);vm.runInContext(source,c);await c.rbJourneySync.whenReady();await new Promise(r=>setImmediate(r));return{c,calls,localStorage,sessionStorage,get wrote(){return wrote}};
}
(async()=>{
 const payload={schemaVersion:1,progress:{records:{example:{saved:true,amount:42}}},calculators:{}};
 let t=await scenario({cloud:{revision:'revision-a',payload}});assert.equal(JSON.parse(t.localStorage.getItem(PK)).records.example.amount,42);
 await t.c.rbJourneySync.saveNow('manual');let request=t.calls.find(x=>x.url.includes('plan_save'));assert.equal(JSON.parse(request.opts.body).baseRevision,'revision-a');assert.equal(request.opts.headers['X-CSRF-Token'],'fixture-token');
 t=await scenario({owner:2,local:{rbJourneyOwnerV1:'1',[PK]:JSON.stringify(payload.progress)}});assert.equal(t.wrote,false);assert.equal(t.localStorage.getItem(PK),null);assert.ok(t.localStorage.getItem('rbJourneyOwnerArchive:1'));
 const localProgress={records:{example:{saved:true,amount:99}}};
 t=await scenario({local:{rbJourneyOwnerV1:'1',[PK]:JSON.stringify(localProgress)},pending:{[PENDING]:JSON.stringify({ownerId:1,baseRevision:'old',payload:{progress:localProgress},reason:'retry'})},cloud:{revision:'new',payload}});
 assert.equal(t.c.rbJourneySync.getState().saveState,'conflict');assert.equal(JSON.parse(t.localStorage.getItem(PK)).records.example.amount,99);await t.c.rbJourneySync.saveNow('manual');assert.equal(t.wrote,false);
 t=await scenario({local:{rbJourneyOwnerV1:'1',[PK]:JSON.stringify(localProgress)},pending:{[PENDING]:JSON.stringify({ownerId:1,baseRevision:'same',payload:{progress:localProgress},reason:'retry'})},cloud:{revision:'same',payload}});assert.equal(t.wrote,true);request=t.calls.find(x=>x.url.includes('plan_save'));assert.equal(JSON.parse(request.opts.body).payload.progress.records.example.amount,99);
 assert.ok(t.calls.every(x=>x.opts.signal),'Network calls have deadlines');
 console.log('Journey sync regression passed: saved revision, account switch, offline preservation, conflict pause and retry.');
})().catch(e=>{console.error(e);process.exitCode=1});
