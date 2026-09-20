importScripts('stress-engine.js?v=1');
self.onmessage=function(event){
  try {self.postMessage({result:PASStressEngine.run(event.data)});}
  catch(error){self.postMessage({error:error.message});}
};
