const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const script = fs.readFileSync(process.argv[2], 'utf8');
function simulate({value = '1', min = '1', max = '', step = '1', readonly = false, hidden = false}, events) {
  function el(attrs = {}) { return {attrs, listeners: {}, value: attrs.value || '', textContent:'', disabled: readonly,
    getAttribute(k) { return this.attrs[k] ?? null; }, setAttribute(k,v) { this.attrs[k] = String(v); },
    hasAttribute(k) { return k in this.attrs; },
    addEventListener(k,fn) { (this.listeners[k] ||= []).push(fn); },
    dispatchEvent(e) { for (const fn of this.listeners[e.type] || []) fn(e); return true; }}; }
  const input = el({value,min,max,step,type:hidden?'hidden':'number', ...(readonly?{readonly:''}:{})});
  input.readOnly = readonly;
  const plus = el(), minus = el(), live = el(), root = el();
  root.querySelector = s => ({'[data-alz-qty-input]':input,'[data-alz-qty-plus]':plus,'[data-alz-qty-minus]':minus,'[data-alz-qty-live]':live}[s] || null);
  const document = {listeners: {}, querySelectorAll:s => s==='[data-alz-qty]'?[root]:[],
    addEventListener(k,fn) {this.listeners[k]=fn;}};
  const window = {clearTimeout(){},setTimeout(){}};
  const Event = function(type, opts) {this.type=type;Object.assign(this,opts)};
  vm.runInNewContext(script,{document,window,Event,Number,Promise,AbortController});
  document.listeners.DOMContentLoaded();
  for (const event of events) {
    if (event.type === 'type') input.value=event.value;
    else if (event.type === 'plus') plus.dispatchEvent(new Event('click'));
    else if (event.type === 'minus') minus.dispatchEvent(new Event('click'));
    else input.dispatchEvent(Object.assign(new Event(event.type),{key:event.key,preventDefault(){}}));
  }
  return input.value;
}
const cases = [
  [{value:'0',min:'0'},[{type:'blur'}],'0','grouped unselected child stays zero'],
  [{value:'1',min:'0'},[{type:'minus'}],'0','grouped child can be omitted'],
  [{value:'2',min:'2',step:'2'},[{type:'plus'}],'4','custom step is respected'],
  [{value:'0.5',min:'0.5',step:'0.25'},[{type:'plus'}],'0.75','fractional step is respected'],
  [{value:'3',readonly:true},[{type:'type',value:''},{type:'blur'}],'','readonly controls are not edited'],
  [{value:'3',hidden:true},[{type:'type',value:''},{type:'blur'}],'','hidden controls are not edited'],
  [{value:'1'},[{type:'type',value:''},{type:'plus'}],'2','empty input steps from previous'],
  [{value:'2',max:'2'},[{type:'plus'}],'2','stock maximum is respected'],
  [{value:'3'},[{type:'type',value:'abc'},{type:'blur'}],'3','invalid input restores previous'],
];
let failed=0;
for (const [options,events,expected,name] of cases) {
  const actual=simulate(options,events);
  if (actual!==expected) failed++;
  console.log(JSON.stringify({name,actual,expected,status:actual===expected?'Passed':'Failed'}));
}
process.exitCode=failed?1:0;
