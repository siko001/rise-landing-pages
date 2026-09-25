const {chromium}=require('playwright');
const fs=require('fs');
const path=require('path');
const assert=require('assert/strict');
const root=path.join(__dirname,'../test-results');
const fixturePath=require('./fixture-path.cjs');
const fixture=JSON.parse(fs.readFileSync(fixturePath('browser-fixture')));
const site=fixture.baseURL.includes('fitness')?'fitness':'physio';
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 const errors=[];
 const checks=[];
 const check=(pass,label)=>{assert(pass,label);checks.push(label);console.log('PASS '+site+': '+label)};
 try {
  const context=await browser.newContext();
  const page=await context.newPage();
  page.on('pageerror',e=>errors.push(e.message));
  for(const [variant,url] of Object.entries(fixture.variants)){
   for(const width of [375,1440]){
    await page.setViewportSize({width,height:900});
    await page.goto(url,{waitUntil:'domcontentloaded'});
    await page.locator('.rise-lp__services-grid--slider').waitFor();
    await page.waitForFunction(()=>getComputedStyle(document.querySelector('.rise-lp__services-grid--slider')).display==='flex');
    await page.evaluate(()=>document.fonts.ready);
    const geometry=await page.evaluate(()=>({viewport:innerWidth,scroll:document.documentElement.scrollWidth,root:document.querySelector('.rise-lp').getBoundingClientRect().width,hero:document.querySelector('.rise-lp__hero').getBoundingClientRect().height,media:document.querySelector('.rise-lp__hero-media').getBoundingClientRect().height,position:getComputedStyle(document.querySelector('.rise-lp__hero-image')).objectPosition,spacer:document.querySelector('.rise-lp__spacer').getBoundingClientRect().height,bg:getComputedStyle(document.querySelector('.rise-lp__process')).backgroundColor}));
    check(geometry.scroll<=width,variant+' '+width+'px no overflow');
    check(geometry.root>=width-2,variant+' '+width+'px full-width plugin content');
    check(geometry.spacer===(width===375?24:80),variant+' responsive spacer');
    check(geometry.bg==='rgba(0, 0, 0, 0)',variant+' optional process background is off');
    check(await page.locator('.rise-lp__outline').count()===1,variant+' outline tokens rendered');
    check(geometry.position==='25% 70%',variant+' media position persisted');
    if(variant==='overlay'||variant==='video')check(geometry.hero>=900,variant+' fills viewport minimum');
    if(variant==='stacked')check(geometry.media>=900,'stacked media fills viewport');
    if(variant==='site'){
     if(site==='fitness')check(await page.locator('footer#footer').count()===1,'saved Fitness footer block reused once');
     if(site==='physio')check(await page.locator('#main-header,.et-l--header').count()>0,'Divi site header present');
     check(await page.locator('.rise-lp').count()===1,'one isolated content root in site mode');
    }
    const track=page.locator('[data-rise-slider]');
    await page.locator('[data-slide-next]').click();
    await page.waitForFunction(()=>Math.abs(document.querySelector('[data-rise-slider]').scrollLeft)>5);
    check(await track.evaluate(el=>Math.abs(el.scrollLeft)>5),'slider next button scrolls cards');
    await page.screenshot({path:path.join(root,`${site}-${variant}-${width}.png`),fullPage:variant==='site'});
   }
  }
  await page.goto(fixture.variants.video,{waitUntil:'domcontentloaded'});
  const video=page.locator('[data-rise-video]');
  await page.waitForFunction(()=>{const v=document.querySelector('[data-rise-video]');return v&&!v.paused&&v.currentTime>0;});
  check(await video.evaluate(v=>v.muted&&v.loop&&v.controls),'video muted autoplay, loop and controls work');
  check(await page.locator('.rise-lp__video-toggle').count()===0,'native video controls have no duplicate play button');
  await video.evaluate(v=>v.pause());
  check(await video.evaluate(v=>v.paused),'video can be paused');
  const reduced=await browser.newContext({reducedMotion:'reduce'});
  const still=await reduced.newPage();await still.goto(fixture.variants.video,{waitUntil:'networkidle'});
  check(await still.locator('[data-rise-video]').evaluate(v=>v.paused),'reduced motion suppresses video autoplay');
  await reduced.close();
  fs.writeFileSync(path.join(root,`${site}-layout-report.json`),JSON.stringify({checks,errors},null,2));
  console.log('Theme/script errors: '+JSON.stringify([...new Set(errors)]));
  console.log(checks.length+' layout checks passed.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
