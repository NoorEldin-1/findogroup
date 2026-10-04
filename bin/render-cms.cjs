const fs=require('node:fs');
global.localStorage={getItem:()=>null,setItem:()=>{}};
const renderer=require('../storage/app/cms/renderer.cjs');
const {withRelatedResources}=require('./related-resources.cjs');
const input=JSON.parse(fs.readFileSync(0,'utf8'));
renderer.configure(input.data);
const output=input.pages.map(p=>{
 const rendered=renderer.render(p.name,p.renderUrl||p.url,p.pattern,p.locale);
 return {...p,...rendered,html:withRelatedResources(rendered.html,p,input.data)};
});
process.stdout.write(JSON.stringify(output));
