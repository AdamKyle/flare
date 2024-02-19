import{a as yt,c as ht}from"./popper-CvwSYPO-.js";var bt="tippy-box",ze="tippy-content",Tt="tippy-backdrop",$e="tippy-arrow",Ye="tippy-svg-arrow",q={passive:!0,capture:!0},Ge=function(){return document.body};function ce(e,n,t){if(Array.isArray(e)){var o=e[n];return o??(Array.isArray(t)?t[n]:t)}return e}function ve(e,n){var t={}.toString.call(e);return t.indexOf("[object")===0&&t.indexOf(n+"]")>-1}function Ke(e,n){return typeof e=="function"?e.apply(void 0,n):e}function Ve(e,n){if(n===0)return e;var t;return function(o){clearTimeout(t),t=setTimeout(function(){e(o)},n)}}function Lt(e,n){var t=Object.assign({},e);return n.forEach(function(o){delete t[o]}),t}function wt(e){return e.split(/\s+/).filter(Boolean)}function H(e){return[].concat(e)}function He(e,n){e.indexOf(n)===-1&&e.push(n)}function Et(e){return e.filter(function(n,t){return e.indexOf(n)===t})}function At(e){return e.split("-")[0]}function te(e){return[].slice.call(e)}function Ne(e){return Object.keys(e).reduce(function(n,t){return e[t]!==void 0&&(n[t]=e[t]),n},{})}function Y(){return document.createElement("div")}function ne(e){return["Element","Fragment"].some(function(n){return ve(e,n)})}function St(e){return ve(e,"NodeList")}function Ot(e){return ve(e,"MouseEvent")}function Dt(e){return!!(e&&e._tippy&&e._tippy.reference===e)}function Mt(e){return ne(e)?[e]:St(e)?te(e):Array.isArray(e)?e:te(document.querySelectorAll(e))}function ue(e,n){e.forEach(function(t){t&&(t.style.transitionDuration=n+"ms")})}function Re(e,n){e.forEach(function(t){t&&t.setAttribute("data-state",n)})}function kt(e){var n,t=H(e),o=t[0];return o!=null&&(n=o.ownerDocument)!=null&&n.body?o.ownerDocument:document}function Ct(e,n){var t=n.clientX,o=n.clientY;return e.every(function(c){var d=c.popperRect,s=c.popperState,g=c.props,p=g.interactiveBorder,l=At(s.placement),m=s.modifiersData.offset;if(!m)return!0;var T=l==="bottom"?m.top.y:0,w=l==="top"?m.bottom.y:0,P=l==="right"?m.left.x:0,M=l==="left"?m.right.x:0,N=d.top-o+T>p,h=o-d.bottom-w>p,b=d.left-t+P>p,E=t-d.right-M>p;return N||h||b||E})}function de(e,n,t){var o=n+"EventListener";["transitionend","webkitTransitionEnd"].forEach(function(c){e[o](c,t)})}function Ue(e,n){for(var t=n;t;){var o;if(e.contains(t))return!0;t=t.getRootNode==null||(o=t.getRootNode())==null?void 0:o.host}return!1}var x={isTouch:!1},_e=0;function xt(){x.isTouch||(x.isTouch=!0,window.performance&&document.addEventListener("mousemove",Xe))}function Xe(){var e=performance.now();e-_e<20&&(x.isTouch=!1,document.removeEventListener("mousemove",Xe)),_e=e}function It(){var e=document.activeElement;if(Dt(e)){var n=e._tippy;e.blur&&!n.state.isVisible&&e.blur()}}function qt(){document.addEventListener("touchstart",xt,q),window.addEventListener("blur",It)}var Pt=typeof window<"u"&&typeof document<"u",Bt=Pt?!!window.msCrypto:!1,Vt={animateFill:!1,followCursor:!1,inlinePositioning:!1,sticky:!1},Ht={allowHTML:!1,animation:"fade",arrow:!0,content:"",inertia:!1,maxWidth:350,role:"tooltip",theme:"",zIndex:9999},S=Object.assign({appendTo:Ge,aria:{content:"auto",expanded:"auto"},delay:0,duration:[300,250],getReferenceClientRect:null,hideOnClick:!0,ignoreAttributes:!1,interactive:!1,interactiveBorder:2,interactiveDebounce:0,moveTransition:"",offset:[0,10],onAfterUpdate:function(){},onBeforeUpdate:function(){},onCreate:function(){},onDestroy:function(){},onHidden:function(){},onHide:function(){},onMount:function(){},onShow:function(){},onShown:function(){},onTrigger:function(){},onUntrigger:function(){},onClickOutside:function(){},placement:"top",plugins:[],popperOptions:{},render:null,showOnCreate:!1,touch:!0,trigger:"mouseenter focus",triggerTarget:null},Vt,Ht),Nt=Object.keys(S),Rt=function(n){var t=Object.keys(n);t.forEach(function(o){S[o]=n[o]})};function Je(e){var n=e.plugins||[],t=n.reduce(function(o,c){var d=c.name,s=c.defaultValue;if(d){var g;o[d]=e[d]!==void 0?e[d]:(g=S[d])!=null?g:s}return o},{});return Object.assign({},e,t)}function Ut(e,n){var t=n?Object.keys(Je(Object.assign({},S,{plugins:n}))):Nt,o=t.reduce(function(c,d){var s=(e.getAttribute("data-tippy-"+d)||"").trim();if(!s)return c;if(d==="content")c[d]=s;else try{c[d]=JSON.parse(s)}catch{c[d]=s}return c},{});return o}function je(e,n){var t=Object.assign({},n,{content:Ke(n.content,[e])},n.ignoreAttributes?{}:Ut(e,n.plugins));return t.aria=Object.assign({},S.aria,t.aria),t.aria={expanded:t.aria.expanded==="auto"?n.interactive:t.aria.expanded,content:t.aria.content==="auto"?n.interactive?null:"describedby":t.aria.content},t}var _t=function(){return"innerHTML"};function fe(e,n){e[_t()]=n}function Fe(e){var n=Y();return e===!0?n.className=$e:(n.className=Ye,ne(e)?n.appendChild(e):fe(n,e)),n}function We(e,n){ne(n.content)?(fe(e,""),e.appendChild(n.content)):typeof n.content!="function"&&(n.allowHTML?fe(e,n.content):e.textContent=n.content)}function pe(e){var n=e.firstElementChild,t=te(n.children);return{box:n,content:t.find(function(o){return o.classList.contains(ze)}),arrow:t.find(function(o){return o.classList.contains($e)||o.classList.contains(Ye)}),backdrop:t.find(function(o){return o.classList.contains(Tt)})}}function Qe(e){var n=Y(),t=Y();t.className=bt,t.setAttribute("data-state","hidden"),t.setAttribute("tabindex","-1");var o=Y();o.className=ze,o.setAttribute("data-state","hidden"),We(o,e.props),n.appendChild(t),t.appendChild(o),c(e.props,e.props);function c(d,s){var g=pe(n),p=g.box,l=g.content,m=g.arrow;s.theme?p.setAttribute("data-theme",s.theme):p.removeAttribute("data-theme"),typeof s.animation=="string"?p.setAttribute("data-animation",s.animation):p.removeAttribute("data-animation"),s.inertia?p.setAttribute("data-inertia",""):p.removeAttribute("data-inertia"),p.style.maxWidth=typeof s.maxWidth=="number"?s.maxWidth+"px":s.maxWidth,s.role?p.setAttribute("role",s.role):p.removeAttribute("role"),(d.content!==s.content||d.allowHTML!==s.allowHTML)&&We(l,e.props),s.arrow?m?d.arrow!==s.arrow&&(p.removeChild(m),p.appendChild(Fe(s.arrow))):p.appendChild(Fe(s.arrow)):m&&p.removeChild(m)}return{popper:n,onUpdate:c}}Qe.$$tippy=!0;var jt=1,ee=[],le=[];function Ft(e,n){var t=je(e,Object.assign({},S,Je(Ne(n)))),o,c,d,s=!1,g=!1,p=!1,l=!1,m,T,w,P=[],M=Ve(Me,t.interactiveDebounce),N,h=jt++,b=null,E=Et(t.plugins),A={isEnabled:!0,isVisible:!1,isDestroyed:!1,isMounted:!1,isShown:!1},r={id:h,reference:e,popper:Y(),popperInstance:b,props:t,state:A,plugins:E,clearDelayTimeouts:ct,setProps:ut,setContent:dt,show:lt,hide:ft,hideWithInteractivity:pt,enable:at,disable:st,unmount:mt,destroy:vt};if(!t.render)return r;var G=t.render(r),v=G.popper,ge=G.onUpdate;v.setAttribute("data-tippy-root",""),v.id="tippy-"+r.id,r.popper=v,e._tippy=r,v._tippy=r;var tt=E.map(function(i){return i.fn(r)}),nt=e.hasAttribute("aria-expanded");return Se(),W(),K(),O("onCreate",[r]),t.showOnCreate&&Pe(),v.addEventListener("mouseenter",function(){r.props.interactive&&r.state.isVisible&&r.clearDelayTimeouts()}),v.addEventListener("mouseleave",function(){r.props.interactive&&r.props.trigger.indexOf("mouseenter")>=0&&j().addEventListener("mousemove",M)}),r;function ye(){var i=r.props.touch;return Array.isArray(i)?i:[i,0]}function he(){return ye()[0]==="hold"}function C(){var i;return!!((i=r.props.render)!=null&&i.$$tippy)}function B(){return N||e}function j(){var i=B().parentNode;return i?kt(i):document}function F(){return pe(v)}function be(i){return r.state.isMounted&&!r.state.isVisible||x.isTouch||m&&m.type==="focus"?0:ce(r.props.delay,i?0:1,S.delay)}function K(i){i===void 0&&(i=!1),v.style.pointerEvents=r.props.interactive&&!i?"":"none",v.style.zIndex=""+r.props.zIndex}function O(i,a,u){if(u===void 0&&(u=!0),tt.forEach(function(f){f[i]&&f[i].apply(f,a)}),u){var y;(y=r.props)[i].apply(y,a)}}function Te(){var i=r.props.aria;if(i.content){var a="aria-"+i.content,u=v.id,y=H(r.props.triggerTarget||e);y.forEach(function(f){var L=f.getAttribute(a);if(r.state.isVisible)f.setAttribute(a,L?L+" "+u:u);else{var D=L&&L.replace(u,"").trim();D?f.setAttribute(a,D):f.removeAttribute(a)}})}}function W(){if(!(nt||!r.props.aria.expanded)){var i=H(r.props.triggerTarget||e);i.forEach(function(a){r.props.interactive?a.setAttribute("aria-expanded",r.state.isVisible&&a===B()?"true":"false"):a.removeAttribute("aria-expanded")})}}function re(){j().removeEventListener("mousemove",M),ee=ee.filter(function(i){return i!==M})}function X(i){if(!(x.isTouch&&(p||i.type==="mousedown"))){var a=i.composedPath&&i.composedPath()[0]||i.target;if(!(r.props.interactive&&Ue(v,a))){if(H(r.props.triggerTarget||e).some(function(u){return Ue(u,a)})){if(x.isTouch||r.state.isVisible&&r.props.trigger.indexOf("click")>=0)return}else O("onClickOutside",[r,i]);r.props.hideOnClick===!0&&(r.clearDelayTimeouts(),r.hide(),g=!0,setTimeout(function(){g=!1}),r.state.isMounted||ie())}}}function Le(){p=!0}function we(){p=!1}function Ee(){var i=j();i.addEventListener("mousedown",X,!0),i.addEventListener("touchend",X,q),i.addEventListener("touchstart",we,q),i.addEventListener("touchmove",Le,q)}function ie(){var i=j();i.removeEventListener("mousedown",X,!0),i.removeEventListener("touchend",X,q),i.removeEventListener("touchstart",we,q),i.removeEventListener("touchmove",Le,q)}function rt(i,a){Ae(i,function(){!r.state.isVisible&&v.parentNode&&v.parentNode.contains(v)&&a()})}function it(i,a){Ae(i,a)}function Ae(i,a){var u=F().box;function y(f){f.target===u&&(de(u,"remove",y),a())}if(i===0)return a();de(u,"remove",T),de(u,"add",y),T=y}function R(i,a,u){u===void 0&&(u=!1);var y=H(r.props.triggerTarget||e);y.forEach(function(f){f.addEventListener(i,a,u),P.push({node:f,eventType:i,handler:a,options:u})})}function Se(){he()&&(R("touchstart",De,{passive:!0}),R("touchend",ke,{passive:!0})),wt(r.props.trigger).forEach(function(i){if(i!=="manual")switch(R(i,De),i){case"mouseenter":R("mouseleave",ke);break;case"focus":R(Bt?"focusout":"blur",Ce);break;case"focusin":R("focusout",Ce);break}})}function Oe(){P.forEach(function(i){var a=i.node,u=i.eventType,y=i.handler,f=i.options;a.removeEventListener(u,y,f)}),P=[]}function De(i){var a,u=!1;if(!(!r.state.isEnabled||xe(i)||g)){var y=((a=m)==null?void 0:a.type)==="focus";m=i,N=i.currentTarget,W(),!r.state.isVisible&&Ot(i)&&ee.forEach(function(f){return f(i)}),i.type==="click"&&(r.props.trigger.indexOf("mouseenter")<0||s)&&r.props.hideOnClick!==!1&&r.state.isVisible?u=!0:Pe(i),i.type==="click"&&(s=!u),u&&!y&&J(i)}}function Me(i){var a=i.target,u=B().contains(a)||v.contains(a);if(!(i.type==="mousemove"&&u)){var y=oe().concat(v).map(function(f){var L,D=f._tippy,U=(L=D.popperInstance)==null?void 0:L.state;return U?{popperRect:f.getBoundingClientRect(),popperState:U,props:t}:null}).filter(Boolean);Ct(y,i)&&(re(),J(i))}}function ke(i){var a=xe(i)||r.props.trigger.indexOf("click")>=0&&s;if(!a){if(r.props.interactive){r.hideWithInteractivity(i);return}J(i)}}function Ce(i){r.props.trigger.indexOf("focusin")<0&&i.target!==B()||r.props.interactive&&i.relatedTarget&&v.contains(i.relatedTarget)||J(i)}function xe(i){return x.isTouch?he()!==i.type.indexOf("touch")>=0:!1}function Ie(){qe();var i=r.props,a=i.popperOptions,u=i.placement,y=i.offset,f=i.getReferenceClientRect,L=i.moveTransition,D=C()?pe(v).arrow:null,U=f?{getBoundingClientRect:f,contextElement:f.contextElement||B()}:e,Be={name:"$$tippy",enabled:!0,phase:"beforeWrite",requires:["computeStyles"],fn:function(Q){var _=Q.state;if(C()){var gt=F(),se=gt.box;["placement","reference-hidden","escaped"].forEach(function(Z){Z==="placement"?se.setAttribute("data-placement",_.placement):_.attributes.popper["data-popper-"+Z]?se.setAttribute("data-"+Z,""):se.removeAttribute("data-"+Z)}),_.attributes.popper={}}}},V=[{name:"offset",options:{offset:y}},{name:"preventOverflow",options:{padding:{top:2,bottom:2,left:5,right:5}}},{name:"flip",options:{padding:5}},{name:"computeStyles",options:{adaptive:!L}},Be];C()&&D&&V.push({name:"arrow",options:{element:D,padding:3}}),V.push.apply(V,(a==null?void 0:a.modifiers)||[]),r.popperInstance=ht(U,v,Object.assign({},a,{placement:u,onFirstUpdate:w,modifiers:V}))}function qe(){r.popperInstance&&(r.popperInstance.destroy(),r.popperInstance=null)}function ot(){var i=r.props.appendTo,a,u=B();r.props.interactive&&i===Ge||i==="parent"?a=u.parentNode:a=Ke(i,[u]),a.contains(v)||a.appendChild(v),r.state.isMounted=!0,Ie()}function oe(){return te(v.querySelectorAll("[data-tippy-root]"))}function Pe(i){r.clearDelayTimeouts(),i&&O("onTrigger",[r,i]),Ee();var a=be(!0),u=ye(),y=u[0],f=u[1];x.isTouch&&y==="hold"&&f&&(a=f),a?o=setTimeout(function(){r.show()},a):r.show()}function J(i){if(r.clearDelayTimeouts(),O("onUntrigger",[r,i]),!r.state.isVisible){ie();return}if(!(r.props.trigger.indexOf("mouseenter")>=0&&r.props.trigger.indexOf("click")>=0&&["mouseleave","mousemove"].indexOf(i.type)>=0&&s)){var a=be(!1);a?c=setTimeout(function(){r.state.isVisible&&r.hide()},a):d=requestAnimationFrame(function(){r.hide()})}}function at(){r.state.isEnabled=!0}function st(){r.hide(),r.state.isEnabled=!1}function ct(){clearTimeout(o),clearTimeout(c),cancelAnimationFrame(d)}function ut(i){if(!r.state.isDestroyed){O("onBeforeUpdate",[r,i]),Oe();var a=r.props,u=je(e,Object.assign({},a,Ne(i),{ignoreAttributes:!0}));r.props=u,Se(),a.interactiveDebounce!==u.interactiveDebounce&&(re(),M=Ve(Me,u.interactiveDebounce)),a.triggerTarget&&!u.triggerTarget?H(a.triggerTarget).forEach(function(y){y.removeAttribute("aria-expanded")}):u.triggerTarget&&e.removeAttribute("aria-expanded"),W(),K(),ge&&ge(a,u),r.popperInstance&&(Ie(),oe().forEach(function(y){requestAnimationFrame(y._tippy.popperInstance.forceUpdate)})),O("onAfterUpdate",[r,i])}}function dt(i){r.setProps({content:i})}function lt(){var i=r.state.isVisible,a=r.state.isDestroyed,u=!r.state.isEnabled,y=x.isTouch&&!r.props.touch,f=ce(r.props.duration,0,S.duration);if(!(i||a||u||y)&&!B().hasAttribute("disabled")&&(O("onShow",[r],!1),r.props.onShow(r)!==!1)){if(r.state.isVisible=!0,C()&&(v.style.visibility="visible"),K(),Ee(),r.state.isMounted||(v.style.transition="none"),C()){var L=F(),D=L.box,U=L.content;ue([D,U],0)}w=function(){var V;if(!(!r.state.isVisible||l)){if(l=!0,v.offsetHeight,v.style.transition=r.props.moveTransition,C()&&r.props.animation){var ae=F(),Q=ae.box,_=ae.content;ue([Q,_],f),Re([Q,_],"visible")}Te(),W(),He(le,r),(V=r.popperInstance)==null||V.forceUpdate(),O("onMount",[r]),r.props.animation&&C()&&it(f,function(){r.state.isShown=!0,O("onShown",[r])})}},ot()}}function ft(){var i=!r.state.isVisible,a=r.state.isDestroyed,u=!r.state.isEnabled,y=ce(r.props.duration,1,S.duration);if(!(i||a||u)&&(O("onHide",[r],!1),r.props.onHide(r)!==!1)){if(r.state.isVisible=!1,r.state.isShown=!1,l=!1,s=!1,C()&&(v.style.visibility="hidden"),re(),ie(),K(!0),C()){var f=F(),L=f.box,D=f.content;r.props.animation&&(ue([L,D],y),Re([L,D],"hidden"))}Te(),W(),r.props.animation?C()&&rt(y,r.unmount):r.unmount()}}function pt(i){j().addEventListener("mousemove",M),He(ee,M),M(i)}function mt(){r.state.isVisible&&r.hide(),r.state.isMounted&&(qe(),oe().forEach(function(i){i._tippy.unmount()}),v.parentNode&&v.parentNode.removeChild(v),le=le.filter(function(i){return i!==r}),r.state.isMounted=!1,O("onHidden",[r]))}function vt(){r.state.isDestroyed||(r.clearDelayTimeouts(),r.unmount(),Oe(),delete e._tippy,r.state.isDestroyed=!0,O("onDestroy",[r]))}}function k(e,n){n===void 0&&(n={});var t=S.plugins.concat(n.plugins||[]);qt();var o=Object.assign({},n,{plugins:t}),c=Mt(e),d=c.reduce(function(s,g){var p=g&&Ft(g,o);return p&&s.push(p),s},[]);return ne(e)?d[0]:d}k.defaultProps=S;k.setDefaultProps=Rt;k.currentInput=x;Object.assign({},yt,{effect:function(n){var t=n.state,o={popper:{position:t.options.strategy,left:"0",top:"0",margin:"0"},arrow:{position:"absolute"},reference:{}};Object.assign(t.elements.popper.style,o.popper),t.styles=o,t.elements.arrow&&Object.assign(t.elements.arrow.style,o.arrow)}});var Wt={mouseover:"mouseenter",focusin:"focus",click:"click"};function zt(e,n){var t=[],o=[],c=!1,d=n.target,s=Lt(n,["target"]),g=Object.assign({},s,{trigger:"manual",touch:!1}),p=Object.assign({touch:S.touch},s,{showOnCreate:!0}),l=k(e,g),m=H(l);function T(h){if(!(!h.target||c)){var b=h.target.closest(d);if(b){var E=b.getAttribute("data-tippy-trigger")||n.trigger||S.trigger;if(!b._tippy&&!(h.type==="touchstart"&&typeof p.touch=="boolean")&&!(h.type!=="touchstart"&&E.indexOf(Wt[h.type])<0)){var A=k(b,p);A&&(o=o.concat(A))}}}}function w(h,b,E,A){A===void 0&&(A=!1),h.addEventListener(b,E,A),t.push({node:h,eventType:b,handler:E,options:A})}function P(h){var b=h.reference;w(b,"touchstart",T,q),w(b,"mouseover",T),w(b,"focusin",T),w(b,"click",T)}function M(){t.forEach(function(h){var b=h.node,E=h.eventType,A=h.handler,r=h.options;b.removeEventListener(E,A,r)}),t=[]}function N(h){var b=h.destroy,E=h.enable,A=h.disable;h.destroy=function(r){r===void 0&&(r=!0),r&&o.forEach(function(G){G.destroy()}),o=[],M(),b()},h.enable=function(){E(),o.forEach(function(r){return r.enable()}),c=!1},h.disable=function(){A(),o.forEach(function(r){return r.disable()}),c=!0},P(h)}return m.forEach(N),l}k.setDefaultProps({render:Qe});const I=(e,n,t,o)=>{const c=document.querySelectorAll(e);for(const d of c)d.addEventListener(n,s=>{s.target.closest(t)&&o(s)})},Ze=()=>{window.innerWidth||document.documentElement.clientWidth};Ze();window.addEventListener("resize",()=>{Ze()},!1);const et=(e,n)=>{e.style.transitionProperty="height, opacity",e.style.transitionDuration="200ms",e.style.transitionTimingFunction="ease-in-out",setTimeout(()=>{e.style.height=e.scrollHeight+"px",e.style.opacity=1},200),e.addEventListener("transitionend",()=>{e.classList.add("open"),e.style.removeProperty("height"),e.style.removeProperty("opacity"),e.style.removeProperty("transition-property"),e.style.removeProperty("transition-duration"),e.style.removeProperty("transition-timing-function"),typeof n=="function"&&n()},{once:!0})},$t=()=>{const e='[data-toggle="collapse"]',n=t=>{t.classList.toggle("active"),document.querySelectorAll(t.dataset.target).forEach(d=>{d.classList.contains("open")?me(d):et(d)});const c=t.closest(".accordion");c&&(c.querySelectorAll(e).forEach(g=>{g!==t&&g.classList.remove("active")}),c.querySelectorAll(".collapse").forEach(g=>{g.classList.contains("open")&&me(g)}))};I("body","click",e,t=>{const o=t.target.closest(e);n(o)})};$t();const me=(e,n)=>{e.style.overflowY="hidden",e.style.height=e.scrollHeight+"px",e.style.transitionProperty="height, opacity",e.style.transitionDuration="200ms",e.style.transitionTimingFunction="ease-in-out",setTimeout(()=>{e.style.height=0,e.style.opacity=0},200),e.addEventListener("transitionend",()=>{e.classList.remove("open"),e.style.removeProperty("overflow-y"),e.style.removeProperty("height"),e.style.removeProperty("opacity"),e.style.removeProperty("transition-property"),e.style.removeProperty("transition-duration"),e.style.removeProperty("transition-timing-function"),typeof n=="function"&&n()},{once:!0})};document.addEventListener("DOMContentLoaded",()=>{let e=null;e=document.getElementById("sortable-style-1"),e&&Sortable.create(e,{animation:150}),e=document.getElementById("sortable-style-2"),e&&Sortable.create(e,{handle:".handle",animation:150}),e=document.getElementById("sortable-style-3"),e&&Sortable.create(e,{animation:150});const n=document.getElementById("ckeditor");n&&ClassicEditor.create(n);const t=document.getElementById("carousel-style-1");if(t){const o=()=>document.dir=="rtl"?"rtl":"ltr";new Glide(t,{direction:o(),type:"carousel",perView:4,gap:20,breakpoints:{640:{perView:1},768:{perView:2}}}).mount()}});const Yt=()=>{const e=document.documentElement,n=localStorage.getItem("menuType"),t=document.querySelector(".menu-bar"),o=document.querySelector(".menu-items");if(!t)return;n&&(e.classList.add(n),t.classList.add(n));const c=()=>{t.querySelectorAll(".menu-detail.open").forEach(l=>{$(),t.classList.contains("menu-wide")||l.classList.remove("open")})};document.addEventListener("click",l=>{!l.target.closest(".menu-items a")&&!l.target.closest(".menu-detail")&&!t.classList.contains("menu-wide")&&c()}),I(".menu-items","click",".link",l=>{const T=l.target.closest(".link").dataset.target,w=t.querySelector(T);t.classList.contains("menu-wide")||(w?(z(!0),w.classList.add("open")):$(),c(),w?(z(!0),w.classList.add("open")):$())});const d=()=>{t.classList.contains("menu-hidden")?(e.classList.remove("menu-hidden"),t.classList.remove("menu-hidden")):(e.classList.add("menu-hidden"),t.classList.add("menu-hidden"))};I(".top-bar","click","[data-toggle='menu']",l=>{d()});const s=l=>{const m=t.querySelector(".menu-detail.open");switch(e.classList.remove("menu-icon-only"),t.classList.remove("menu-icon-only"),e.classList.remove("menu-wide"),t.classList.remove("menu-wide"),p(),e.classList.remove("menu-hidden"),t.classList.remove("menu-hidden"),l){case"icon-only":e.classList.add("menu-icon-only"),t.classList.add("menu-icon-only"),localStorage.setItem("menuType","menu-icon-only"),m&&z(!0);break;case"wide":e.classList.add("menu-wide"),t.classList.add("menu-wide"),localStorage.setItem("menuType","menu-wide"),g(),m&&$();break;case"hidden":e.classList.add("menu-hidden"),t.classList.add("menu-hidden"),localStorage.setItem("menuType","menu-hidden"),c();break;default:localStorage.removeItem("menuType"),m&&z(!0)}},g=()=>{t.querySelector(".menu-header").classList.remove("hidden"),t.querySelectorAll(".menu-items .link").forEach(l=>{const m=l.dataset.target,T=t.querySelector(".menu-detail"+m);T&&(T.classList.add("collapse"),l.setAttribute("data-toggle","collapse"),l.after(T))})},p=()=>{e.classList.remove("menu-wide"),t.classList.remove("menu-wide"),t.querySelector(".menu-header").classList.add("hidden"),t.querySelectorAll(".menu-items .link").forEach(l=>{const m=l.dataset.target,T=t.querySelector(".menu-detail"+m);T&&(T.classList.remove("collapse"),l.removeAttribute("data-toggle","collapse"),o.after(T))})};t.classList.contains("menu-wide")&&g(),I(".menu-bar","click","[data-toggle='menu-type']",l=>{const m=l.target.closest("[data-toggle='menu-type']").dataset.value;s(m)}),I("#customizer","click","[data-toggle='menu-type']",l=>{const m=l.target.closest("[data-toggle='menu-type']").dataset.value;s(m)})};Yt();const Gt=()=>{const e=window.location.href.split(/[?#]/)[0],t=document.querySelectorAll(".menu-bar a");t&&t.forEach(o=>{if(o.href===e){o.classList.add("active");const c=o.closest(".menu-detail");if(!c)return;document.querySelector('.menu-items .link[data-target="[data-menu='+c.dataset.menu+']"]').classList.add("active")}})};Gt();const z=e=>{if(document.querySelector(".overlay"))return;document.body.classList.add("overlay-show");const n=document.createElement("div");e?n.setAttribute("class","overlay workspace"):n.setAttribute("class","overlay"),document.body.appendChild(n),n.classList.add("active")},$=()=>{const e=document.querySelector(".overlay");e&&(document.body.classList.remove("overlay-show"),e.classList.remove("active"),document.body.removeChild(e))},Kt=()=>{const e=()=>{const n=document.querySelector(".sidebar:not(.sidebar_customizer)");n.classList.contains("open")?(n.classList.remove("open"),$()):(n.classList.add("open"),z(!0))};I("body","click",'[data-toggle="sidebar"]',()=>{e()})};Kt();const Xt=()=>{let e=!1;I("body","click",'[data-toggle="tab"]',n=>{const t=n.target.closest('[data-toggle="tab"]'),o=t.closest(".tabs"),c=o.querySelector(".tab-nav .active"),d=o.querySelector(".collapse.open"),s=o.querySelector(t.dataset.target);e||c!==t&&(c.classList.remove("active"),t.classList.add("active"),e=!0,me(d,()=>{et(s,()=>{e=!1})}))}),I("body","click",'[data-toggle="wizard"]',n=>{const t=n.target.closest(".wizard"),o=n.target.dataset.direction,c=t.querySelectorAll(".nav-link"),d=t.querySelector(".nav-link.active");let s=0;switch(c.forEach((g,p)=>{g===d&&(s=p)}),o){case"next":c[s+1]&&c[s+1].click();break;case"previous":c[s-1]&&c[s-1].click();break}})};Xt();const Jt=()=>{const e=document.documentElement,n=localStorage.getItem("scheme"),t=document.getElementById("darkModeToggler");if(!t)return;n==="dark"&&(t.checked="checked");const o=()=>{e.classList.remove("light"),e.classList.add("dark"),localStorage.setItem("scheme","dark")},c=()=>{e.classList.remove("dark"),e.classList.add("light"),localStorage.removeItem("scheme")},d=()=>!!e.classList.contains("dark");I("body","change","#darkModeToggler",()=>{d()?c():o()})};Jt();const Qt=()=>{zt("body",{target:'.menu-icon-only [data-toggle="tooltip-menu"]',touch:["hold",500],theme:"light-border tooltip",offset:[0,12],interactive:!0,animation:"scale",placement:"right",appendTo:()=>document.body}),k('[data-toggle="tooltip"]',{theme:"light-border tooltip",touch:["hold",500],offset:[0,12],interactive:!0,animation:"scale"}),k('[data-toggle="popover"]',{theme:"light-border popover",offset:[0,12],interactive:!0,allowHTML:!0,trigger:"click",animation:"shift-toward-extreme",content:e=>{const n=e.dataset.popoverTitle,t=e.dataset.popoverContent;return"<h5>"+n+'</h5><div class="mt-5">'+t+"</div>"}}),k('[data-toggle="dropdown-menu"]',{theme:"light-border",zIndex:25,offset:[0,8],arrow:!1,placement:"bottom-start",interactive:!0,allowHTML:!0,animation:"shift-toward-extreme",content:e=>{let n=e.closest(".dropdown").querySelector(".dropdown-menu");return n=n.outerHTML,n}}),k('[data-toggle="custom-dropdown-menu"]',{theme:"light-border",zIndex:25,offset:[0,8],arrow:!1,placement:"bottom-start",interactive:!0,allowHTML:!0,animation:"shift-toward-extreme",content:e=>{let n=e.closest(".dropdown").querySelector(".custom-dropdown-menu");return n=n.outerHTML,n}}),k('[data-toggle="search-select"]',{theme:"light-border",offset:[0,8],maxWidth:"none",arrow:!1,placement:"bottom-start",trigger:"click",interactive:!0,allowHTML:!0,animation:"shift-toward-extreme",content:e=>{let n=e.closest(".search-select").querySelector(".search-select-menu");return n=n.outerHTML,n},appendTo(e){return e.closest(".search-select")}})};Qt();
