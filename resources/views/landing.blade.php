@verbatim
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
@endverbatim
<meta name="csrf-token" content="{{ csrf_token() }}">
@verbatim
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/eazypos-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="EazyPOS">
<meta name="theme-color" content="#d3ff12">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<title>EazyPOS — Simple POS for Small Businesses</title>
<meta name="description" content="EazyPOS is a simple point-of-sale platform for small businesses.">
<style>
:root{--lime:#d3ff12;--lime2:#ecff4b;--ink:#0b0d0c;--soft:#f6f8f4;--muted:#727a73;--line:#e2e7df;--dark:#070908}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--soft);color:var(--ink);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}button{font:inherit;cursor:pointer}.navbar{position:sticky;top:0;z-index:50;background:var(--lime);border-bottom:1px solid #b8dc00}.navin{max-width:1260px;height:76px;margin:auto;padding:0 22px;display:flex;align-items:center;justify-content:space-between}.brand{width:142px;height:auto;display:block}.navlinks{display:flex;gap:28px}.navlinks a{color:#0b0d0c;text-decoration:none;font-weight:850;font-size:14px}.nav-actions{display:flex;gap:8px;align-items:center}.pill{border:1px solid #0b0d0c22;background:#ffffff48;color:#0b0d0c;border-radius:999px;padding:11px 15px;font-weight:900}.get{border:0;background:#0b0d0c;color:#fff;border-radius:999px;padding:13px 18px;font-weight:950}.hero{background:var(--dark);color:#fff;position:relative;overflow:hidden;padding:84px max(22px,calc((100vw - 1260px)/2)) 82px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}.hero:before{content:"";position:absolute;width:520px;height:520px;right:-150px;top:-210px;background:radial-gradient(circle,#d3ff1240,transparent 68%);filter:blur(10px)}.hero-copy,.hero-stage{position:relative;z-index:1}.eyebrow{display:inline-block;background:#fff;color:#0b0d0c;border-radius:999px;padding:8px 12px;font-size:11px;font-weight:950;letter-spacing:.08em}.hero h1{font-size:clamp(52px,7vw,94px);line-height:.91;letter-spacing:-5px;margin:23px 0 22px}.hero h1 span{display:block}.hero h1 .highlight{width:max-content;max-width:100%;background:linear-gradient(105deg,var(--lime),var(--lime2),var(--lime));background-size:200% 100%;color:#0b0d0c;padding:4px 14px 8px;border-radius:16px;animation:shift 4s ease-in-out infinite}.hero p{max-width:650px;color:#aeb7ae;font-size:18px;line-height:1.55}.hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px}.hero-actions .get{background:linear-gradient(100deg,var(--lime),var(--lime2));color:#0b0d0c}.hero-actions .pill{background:transparent;border-color:#ffffff2a;color:#fff}.counterline{display:flex;gap:10px;align-items:center;margin-top:24px;color:#8d978f;font-size:13px}.counterline b{font-size:20px;color:#fff}.live-device{max-width:620px;margin:auto;position:relative;animation:float 5s ease-in-out infinite}.pc{background:#111512;border:1px solid #ffffff1c;border-radius:28px;padding:14px;box-shadow:0 35px 90px #000b;transform:perspective(1200px) rotateY(-5deg)}.pc-top{height:28px;display:flex;align-items:center;justify-content:space-between;color:#dce4dc;font-size:11px}.dots{display:flex;gap:5px}.dots i{width:7px;height:7px;background:#475049;border-radius:50%}.sales{background:linear-gradient(100deg,var(--lime),var(--lime2));color:#0b0d0c;border-radius:18px;padding:18px;margin-bottom:10px}.sales small{font-weight:950}.sales strong{display:block;font-size:39px;margin:8px 0 4px}.tiles{display:grid;grid-template-columns:1fr 1fr;gap:10px}.tile{background:#f7faf6;color:#0b0d0c;border-radius:16px;padding:16px;min-height:92px}.tile b{display:block;margin-bottom:7px}.tile span{font-size:11px;color:#717871}.phone{position:absolute;right:-12px;bottom:-35px;width:150px;background:#0d110e;border:6px solid #1d231f;border-radius:28px;padding:7px;box-shadow:0 20px 50px #000a;transform:rotate(6deg);animation:phone 4.5s ease-in-out infinite}.phone-screen{background:#f8faf7;border-radius:19px;overflow:hidden;color:#0b0d0c}.phone-head{background:#0b0d0c;color:#fff;font-size:8px;padding:9px}.phone-sales{margin:8px;background:var(--lime);padding:10px;border-radius:11px}.phone-sales strong{display:block;font-size:18px;margin-top:3px}.phone-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:0 8px 8px}.phone-grid div{background:#fff;border:1px solid #e4e8e1;border-radius:8px;padding:9px 5px;font-size:7px;font-weight:900}.cursor{position:absolute;width:12px;height:12px;border-radius:50%;background:#fff;border:2px solid #0b0d0c;right:76px;top:36px;animation:cursor 4s ease-in-out infinite}.tap{position:absolute;width:25px;height:25px;border:2px solid var(--lime);border-radius:50%;right:27%;top:46%;animation:tap 3.5s infinite}.section{padding:88px 22px}.alt{background:#eef1eb}.dark-section{background:#080b09;color:#fff}.in{max-width:1220px;margin:auto}.center{text-align:center}.kicker{font-size:11px;font-weight:950;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}h2{font-size:clamp(35px,5vw,58px);line-height:1;letter-spacing:-3px;margin:12px 0}.lead{max-width:700px;color:var(--muted);font-size:17px;line-height:1.55}.center .lead{margin:0 auto}.package{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}.mock{background:#080b09;border-radius:28px;padding:16px;box-shadow:0 25px 70px #0002}.mock-screen{background:#111512;color:#fff;border-radius:20px;padding:14px}.mock-sales{background:linear-gradient(100deg,var(--lime),var(--lime2));color:#0b0d0c;border-radius:17px;padding:18px}.mock-sales strong{display:block;font-size:34px;margin-top:7px}.mock-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:9px}.mock-grid div{background:#f7faf6;color:#0b0d0c;border-radius:14px;padding:14px;min-height:75px}.checks{display:grid;gap:13px;margin-top:25px}.check{display:flex;gap:11px;align-items:flex-start}.check i{width:28px;height:28px;border-radius:50%;background:var(--lime);color:#0b0d0c;display:grid;place-items:center;font-style:normal;font-weight:950;flex:0 0 auto}.check b{display:block;margin-bottom:3px}.check span{color:var(--muted);font-size:14px;line-height:1.45}.free{display:inline-block;background:var(--lime);color:#0b0d0c;border-radius:999px;padding:10px 14px;font-weight:950;margin-top:22px}.features{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:40px}.feature{background:#fff;border:1px solid var(--line);border-radius:20px;padding:24px}.dark-section .feature{background:#151a17;border-color:#ffffff12}.ico{width:45px;height:45px;border-radius:14px;background:var(--lime);display:grid;place-items:center;font-weight:950}.feature h3{margin:16px 0 7px}.feature p{color:var(--muted);line-height:1.5;font-size:14px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:16px}.stat{background:#0b0d0c;color:#fff;border-radius:20px;padding:24px}.stat b{font-size:40px}.stat span{display:block;color:#9da59e;font-size:13px;margin-top:6px}.businesses{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:36px}.business{background:#fff;border:1px solid var(--line);border-radius:18px;padding:22px;font-weight:900}.cta{background:linear-gradient(110deg,var(--lime),var(--lime2));border-radius:28px;padding:52px;display:flex;justify-content:space-between;align-items:center;gap:20px}.cta h2{margin:0}.footer{background:#070908;color:#fff;padding:72px 22px 28px}.footergrid{max-width:1220px;margin:auto;display:grid;grid-template-columns:1.5fr repeat(4,1fr);gap:35px;padding-bottom:45px}.footerbrand img{width:165px;height:auto;margin-bottom:17px}.footerbrand p{color:#8d968e;max-width:300px;font-size:13px;line-height:1.6}.socials{display:flex;gap:8px;margin-top:18px}.socials a{width:37px;height:37px;border:1px solid #ffffff1c;border-radius:10px;color:#fff;text-decoration:none;display:grid;place-items:center;font-size:12px;font-weight:900}.footercol h4{margin:0 0 14px}.footercol a{display:block;color:#8e988f;text-decoration:none;font-size:13px;margin:10px 0}.footercol a:hover{color:var(--lime)}.footerbrand img{object-fit:contain;object-position:left center;background:transparent}.footercontact{display:flex;flex-direction:column;gap:4px;margin-top:14px;font-size:12px}.footercontact span{color:#707970}.footercontact a,.footerbottom a{color:#d3ff12;text-decoration:none}.socials{gap:10px}.socials a{width:46px;height:46px;border:1px solid #ffffff30;background:#111512;color:#fff;border-radius:12px;transition:.2s ease}.socials a:hover{background:#d3ff12;color:#0b0d0c;border-color:#d3ff12;transform:translateY(-2px)}.socials svg{width:23px;height:23px;display:block}.footercol a{transition:.2s ease}.footercol a:hover{transform:translateX(2px)}.footerbottom{max-width:1220px;margin:auto;border-top:1px solid #ffffff12;padding-top:20px;color:#707970;font-size:11px;display:flex;justify-content:space-between;gap:20px}.hidden{display:none!important}.modal{position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;padding:24px}.modal.hidden{display:none}.modalbox{width:min(720px,calc(100vw - 48px));max-height:min(88vh,820px);overflow:auto;background:var(--soft);color:var(--ink);border-radius:28px;padding:38px 42px;box-shadow:0 30px 100px rgba(0,0,0,.35);position:relative}.modalbox h2{font-size:clamp(42px,5vw,64px);line-height:.98;letter-spacing:-3px;margin:12px 0 24px;max-width:600px}.modalbox .field{margin:16px 0}.modalbox .field input,.modalbox .field select{min-height:52px;padding:14px 16px;font-size:15px}.modalbox .formbtn{min-height:56px;font-size:16px}.modalbox .close{position:absolute;right:18px;top:18px;float:none;width:42px;height:42px;font-size:20px}.modalbox .switch{font-size:14px;margin-top:18px}.modalbox .kicker{font-size:12px}.close{float:right;border:0;background:#e9ece7;color:var(--ink);width:36px;height:36px;border-radius:10px}.field{margin:13px 0}.field label{display:block;font-size:10px;font-weight:950;margin-bottom:6px;color:var(--muted)}.field input,.field select{width:100%;padding:12px;border:1px solid var(--line);border-radius:12px;background:transparent;color:var(--ink)}.formbtn{width:100%;border:0;background:#0b0d0c;color:#fff;padding:14px;border-radius:12px;font-weight:950}.formbtn.lime{background:var(--lime);color:#0b0d0c}.switch{text-align:center;color:var(--muted);font-size:13px;margin-top:15px}.switch button{border:0;background:none;color:var(--ink);font-weight:900;text-decoration:underline}.error{color:#d43c32;font-size:11px;margin-top:5px}.suggestions{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.suggestions button{border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:999px;padding:6px 9px;font-size:11px}.reveal{opacity:0;transform:translateY(18px);transition:.65s ease}.reveal.show{opacity:1;transform:none}@keyframes float{50%{transform:translateY(-7px)}}@keyframes phone{50%{transform:rotate(4deg) translateY(-7px)}}@keyframes cursor{0%,100%{transform:translate(0,0)}35%{transform:translate(-32px,65px)}65%{transform:translate(14px,125px)}}@keyframes tap{0%,45%{opacity:0;transform:scale(.4)}55%{opacity:1;transform:scale(1)}80%,100%{opacity:0;transform:scale(1.7)}}@keyframes shift{50%{background-position:100% 0}}@media(max-width:760px){.modal{padding:12px}.modalbox{width:100%;max-height:94vh;padding:30px 22px;border-radius:22px}.modalbox h2{font-size:42px;letter-spacing:-2px}.modalbox .close{right:12px;top:12px}}@media(max-width:900px){.navlinks{display:none}.hero{grid-template-columns:1fr;gap:35px}.package{grid-template-columns:1fr}.features{grid-template-columns:1fr 1fr}.businesses{grid-template-columns:1fr 1fr}.footergrid{grid-template-columns:1.4fr 1fr 1fr}.footerbrand{grid-column:1/-1}.footerbrand img{object-fit:contain;object-position:left center;background:transparent}.footercontact{display:flex;flex-direction:column;gap:4px;margin-top:14px;font-size:12px}.footercontact span{color:#707970}.footercontact a,.footerbottom a{color:#d3ff12;text-decoration:none}.socials{gap:10px}.socials a{width:46px;height:46px;border:1px solid #ffffff30;background:#111512;color:#fff;border-radius:12px;transition:.2s ease}.socials a:hover{background:#d3ff12;color:#0b0d0c;border-color:#d3ff12;transform:translateY(-2px)}.socials svg{width:23px;height:23px;display:block}.footercol a{transition:.2s ease}.footercol a:hover{transform:translateX(2px)}.footerbottom{flex-direction:column}.stats{grid-template-columns:1fr}.hero h1{font-size:62px}}@media(max-width:560px){.navin{height:64px;padding:0 14px}.brand{width:120px}.hero{padding:60px 16px}.hero h1{font-size:48px;letter-spacing:-3px}.hero p{font-size:16px}.phone{right:-2px;width:120px}.features,.businesses{grid-template-columns:1fr}.section{padding:62px 16px}.cta{padding:32px 24px;display:block}.cta .get{margin-top:18px}.footergrid{grid-template-columns:1fr 1fr}.footerbrand{grid-column:1/-1}}

/* Final hero device mockup refinement */
.hero{grid-template-columns:minmax(0,.86fr) minmax(0,1.14fr);gap:42px}
.hero h1{font-size:clamp(48px,6.15vw,82px);line-height:.96;letter-spacing:-4px;margin:23px 0 24px}
.hero h1 .highlight{transform:rotate(-2deg);transform-origin:left center;padding:3px 15px 7px;border-radius:13px;line-height:.94;margin-top:5px}
.hero-stage{min-width:0}
.live-device{max-width:558px;margin-left:auto;margin-right:4px}
.pc{padding:11px;border-radius:25px;transform:perspective(1200px) rotateY(-5deg)}
.pc-top{height:24px;font-size:9px}
.pos-desktop{background:#101411;color:#fff;border-radius:18px;overflow:hidden;border:1px solid #ffffff10}
.pos-toolbar{display:flex;align-items:center;gap:8px;padding:9px 10px;border-bottom:1px solid #ffffff12}
.pos-logo-mini{font-size:9px;font-weight:950;min-width:52px}.pos-search{height:26px;flex:1;background:#171d19;border:1px solid #ffffff16;border-radius:8px;color:#aeb7ae;font-size:8px;padding:0 10px;display:flex;align-items:center}.pos-action{height:26px;padding:0 9px;border-radius:8px;border:1px solid #ffffff16;background:#171d19;color:#fff;font-size:8px;font-weight:800}.pos-body{display:grid;grid-template-columns:76px 1fr 132px;min-height:240px}.pos-side{padding:9px 6px;border-right:1px solid #ffffff12}.pos-side div{padding:7px 6px;border-radius:7px;font-size:7px;color:#aeb7ae;margin-bottom:4px}.pos-side .active{background:var(--lime);color:#0b0d0c;font-weight:950}.pos-main{padding:9px}.pos-cats{display:flex;gap:5px;overflow:hidden;margin-bottom:8px}.pos-cats span{white-space:nowrap;border:1px solid #ffffff16;background:#171d19;border-radius:999px;padding:5px 7px;font-size:6.5px;color:#dbe2dc}.pos-cats .sel{background:var(--lime);color:#0b0d0c;border-color:var(--lime);font-weight:900}.pos-products{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}.pos-product{background:#171d19;border:1px solid #ffffff10;border-radius:8px;padding:5px;min-height:69px;position:relative}.prod-img{height:31px;border-radius:6px;background:linear-gradient(135deg,#303b33,#111512);margin-bottom:4px;display:grid;place-items:center;font-size:13px}.pos-product b{font-size:6.5px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.pos-product small{font-size:6px;color:#9ca69e}.prod-add{position:absolute;right:4px;bottom:4px;width:13px;height:13px;border-radius:4px;background:var(--lime);color:#0b0d0c;display:grid;place-items:center;font-size:9px;font-weight:950}.pos-cart{padding:9px;border-left:1px solid #ffffff12;background:#0b0e0c}.cart-title{display:flex;justify-content:space-between;font-size:8px;font-weight:900;margin-bottom:7px}.cart-row{padding:7px 0;border-bottom:1px solid #ffffff10;font-size:6.5px;color:#dce4dc}.cart-row strong{display:block;font-size:7px;color:#fff}.cart-bottom{margin-top:9px;border-top:1px solid #ffffff12;padding-top:8px}.cart-total{display:flex;justify-content:space-between;font-size:8px;font-weight:950}.checkout{margin-top:7px;background:var(--lime);color:#0b0d0c;border:0;border-radius:7px;width:100%;height:28px;font-size:8px;font-weight:950}.phone{right:-7px;bottom:-29px;width:134px;border-width:5px;border-radius:25px;padding:6px}.phone-screen{border-radius:18px}.mobile-pos{background:#f6f8f4;min-height:225px}.mobile-head{background:#0b0d0c;color:#fff;padding:8px 7px;display:flex;justify-content:space-between;align-items:center;font-size:7px}.mobile-head b{font-size:8px}.mobile-content{padding:7px}.mobile-sales{background:linear-gradient(100deg,var(--lime),var(--lime2));color:#0b0d0c;border-radius:10px;padding:8px;margin-bottom:6px}.mobile-sales small{font-size:6px;font-weight:900}.mobile-sales strong{display:block;font-size:15px;margin-top:3px}.mobile-search{height:20px;border:1px solid #dce2db;border-radius:7px;background:#fff;color:#8b938d;font-size:6px;padding:0 7px;display:flex;align-items:center;margin-bottom:6px}.mobile-cats{display:flex;gap:4px;overflow:hidden;margin-bottom:6px}.mobile-cats span{font-size:5.5px;padding:4px 5px;background:#fff;border:1px solid #e0e5de;border-radius:999px;white-space:nowrap}.mobile-cats .sel{background:#0b0d0c;color:#fff}.mobile-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px}.mobile-card{background:#fff;border:1px solid #e0e5de;border-radius:7px;padding:6px;min-height:48px}.mobile-card b{font-size:6px;display:block}.mobile-card small{font-size:5.5px;color:#7c857e}.mobile-bottom{display:flex;justify-content:space-around;padding:6px 3px;border-top:1px solid #dfe4de;margin-top:6px;font-size:5.5px;color:#626b64}.mobile-bottom .on{color:#0b0d0c;font-weight:950}.hero-stage .cursor{right:27%;top:41%}.hero-stage .tap{right:31%;top:43%}
@media(max-width:760px){.modal{padding:12px}.modalbox{width:100%;max-height:94vh;padding:30px 22px;border-radius:22px}.modalbox h2{font-size:42px;letter-spacing:-2px}.modalbox .close{right:12px;top:12px}}@media(max-width:900px){.hero{grid-template-columns:1fr;gap:28px}.live-device{max-width:500px;margin:auto}.hero h1{font-size:clamp(45px,11vw,70px)}}

.hero-v2{min-height:760px;padding-top:70px;padding-bottom:55px;grid-template-columns:minmax(390px,.83fr) minmax(650px,1.17fr);gap:20px;background:#050706}
.hero-v2:after{content:"";position:absolute;right:-10%;bottom:-38%;width:78%;height:75%;background:radial-gradient(circle at 55% 35%,#d3ff1250,transparent 58%);filter:blur(22px);pointer-events:none}
.hero-v2 .hero-copy{padding-left:0;max-width:620px}
.hero-v2 .hero-copy p{font-size:18px;max-width:590px}
.hero-v2 h1{font-size:clamp(58px,6.3vw,88px);line-height:.9;letter-spacing:-5px;margin-top:28px;margin-bottom:26px}
.hero-v2 h1 span:first-child{white-space:normal}
.hero-v2 h1 .highlight{padding:2px 17px 9px;border-radius:16px;transform:rotate(-2deg);transform-origin:left center;position:relative;box-shadow:0 0 0 1px #d3ff1215}
.hero-v2 h1 .highlight:after{content:"";position:absolute;right:-42px;top:4px;width:22px;height:56px;border-right:7px solid var(--lime);border-top:7px solid transparent;border-bottom:7px solid transparent;transform:rotate(25deg);border-radius:50%}
.hero-stage-v2{min-height:650px;display:flex;align-items:center;justify-content:center;position:relative}
.v2-device{width:min(820px,100%);margin-right:-8px;position:relative;z-index:2}
.v2-pc{padding:15px 15px 18px;border-radius:31px;transform:perspective(1400px) rotateY(-4deg) rotateX(1deg);box-shadow:0 40px 90px #000c,0 0 0 1px #ffffff10;position:relative;z-index:2}
.v2-top{height:30px;padding:0 4px}
.v2-pos{border-radius:20px;overflow:hidden;background:#101512;padding:8px}
.v2-pos .pos-toolbar{min-height:43px}
.v2-pos .pos-body{min-height:330px}
.v2-pos .pos-side{width:112px}
.v2-pos .pos-main{padding:7px 8px}
.v2-pos .pos-cats{gap:6px;margin-bottom:8px;white-space:nowrap;overflow:hidden}
.v2-pos .pos-cats span{padding:5px 8px;font-size:8px}
.v2-pos .pos-products{grid-template-columns:repeat(4,1fr);gap:8px}
.v2-pos .pos-product{min-height:112px;padding:8px;border-radius:11px;position:relative}
.v2-pos .prod-img{height:55px;font-size:39px;display:grid;place-items:center;background:#1c211e;border-radius:8px;margin-bottom:5px}
.v2-pos .pos-product b{font-size:8px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.v2-pos .pos-product small{font-size:7px;color:#aab3ac}
.v2-pos .prod-add{width:18px;height:18px;font-size:13px}
.v2-pos .pos-cart{width:185px;padding:9px;border-radius:12px}
.v2-pos .cart-title{font-size:8px}.v2-pos .cart-row{font-size:7px;padding:8px 0}.v2-pos .cart-total{font-size:8px;margin-top:6px}.v2-pos .checkout{font-size:9px;padding:9px;border-radius:8px}
.v2-phone{width:190px;right:-8px;bottom:-22px;border-width:7px;border-radius:34px;z-index:4;transform:rotate(5deg);box-shadow:0 28px 60px #000c}
.v2-phone .mobile-content{padding-bottom:0}.v2-phone .mobile-sales{margin:8px;padding:10px;border-radius:11px}.v2-phone .mobile-sales strong{font-size:19px}.v2-phone .mobile-grid{gap:6px}.v2-phone .mobile-card{padding:10px 7px;border-radius:9px}.v2-phone .mobile-card b{font-size:8px}.v2-phone .mobile-card small{font-size:6px}.v2-phone .mobile-bottom{font-size:6px;padding:8px 5px}
.hero-orbit{position:absolute;border:1px solid #d3ff1240;border-radius:50%;pointer-events:none;z-index:0}.orbit-a{width:690px;height:520px;right:-40px;top:110px;transform:rotate(-9deg)}.orbit-b{width:810px;height:390px;right:-140px;top:205px;transform:rotate(7deg);border-color:#d3ff121f}
.v2-cursor{right:29%;top:24%;width:10px;height:10px}.v2-tap{right:21%;top:44%;width:34px;height:34px;border-color:#d3ff12;box-shadow:0 0 18px #d3ff1240}
@media(max-width:1050px){.hero-v2{grid-template-columns:1fr;min-height:auto;padding-top:55px}.hero-v2 .hero-copy{max-width:720px}.hero-stage-v2{min-height:600px}.v2-device{width:min(760px,96%)}.hero-v2 h1{font-size:clamp(58px,10vw,84px)}}
@media(max-width:680px){.hero-v2{padding:42px 18px 50px;gap:25px}.hero-v2 h1{font-size:clamp(48px,15vw,66px);letter-spacing:-3.5px}.hero-v2 h1 .highlight{padding:2px 11px 7px}.hero-v2 h1 .highlight:after{display:none}.hero-stage-v2{min-height:390px}.v2-device{width:100%;margin:0}.v2-pc{padding:7px;border-radius:19px}.v2-top{height:19px;font-size:7px}.v2-pos{padding:4px;border-radius:13px}.v2-pos .pos-body{min-height:190px}.v2-pos .pos-side{width:62px;font-size:6px}.v2-pos .pos-main{padding:4px}.v2-pos .pos-cats span{font-size:5px;padding:3px 5px}.v2-pos .pos-products{gap:4px}.v2-pos .pos-product{min-height:68px;padding:4px}.v2-pos .prod-img{height:31px;font-size:23px}.v2-pos .pos-product b{font-size:5px}.v2-pos .pos-product small{font-size:4.5px}.v2-pos .pos-cart{width:100px;padding:5px}.v2-pos .cart-title,.v2-pos .cart-row,.v2-pos .cart-total{font-size:5px}.v2-pos .checkout{font-size:6px;padding:5px}.v2-phone{width:92px;right:-4px;bottom:-10px;border-width:4px;border-radius:19px}.v2-phone .mobile-head{font-size:5px;padding:5px}.v2-phone .mobile-sales{margin:4px;padding:5px}.v2-phone .mobile-sales strong{font-size:11px}.v2-phone .mobile-grid{gap:3px;padding:0 4px 4px}.v2-phone .mobile-card{padding:5px 3px}.v2-phone .mobile-card b{font-size:5px}.v2-phone .mobile-card small{font-size:4px}.v2-phone .mobile-bottom{font-size:4px;padding:4px 2px}.orbit-a{width:390px;height:280px;right:-70px;top:55px}.orbit-b{width:460px;height:220px;right:-100px;top:120px}}

/* Homepage testimonials + inquiry */
.testimonial-strip{background:#080b09;color:#fff}
.testimonial-head{display:flex;justify-content:space-between;align-items:end;gap:24px}
.testimonial-head .lead{color:#9da79f}
.testimonial-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:34px}
.testimonial-card{background:#111512;border:1px solid #ffffff12;border-radius:20px;padding:22px;min-height:190px;transition:.25s ease}
.testimonial-card:hover{transform:translateY(-4px);border-color:#d3ff1240}
.testimonial-tag{display:inline-flex;background:#d3ff1218;color:#d3ff12;border:1px solid #d3ff1230;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:900;letter-spacing:.05em}
.testimonial-quote{font-size:15px;line-height:1.5;margin:18px 0;color:#f4f7f3}
.testimonial-person{display:flex;align-items:center;gap:10px}.testimonial-person img{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#202720}.testimonial-person strong{display:block;font-size:13px}.testimonial-person span{display:block;color:#8f9991;font-size:11px;margin-top:3px}
.home-inquiry{background:#eef1eb}
.inquiry-card{background:#fff;border:1px solid var(--line);border-radius:26px;padding:38px;display:grid;grid-template-columns:1.25fr .75fr;gap:30px;align-items:center;box-shadow:0 18px 50px #0b0d0c08}
.inquiry-mini{background:#0b0d0c;color:#fff;border-radius:20px;padding:24px}.inquiry-mini b{font-size:20px}.inquiry-mini p{color:#a5afa7;font-size:13px;line-height:1.5}.inquiry-mini a{display:inline-block;margin-top:8px;background:var(--lime);color:#0b0d0c;text-decoration:none;font-weight:950;border-radius:999px;padding:11px 15px}
@media(max-width:760px){.modal{padding:12px}.modalbox{width:100%;max-height:94vh;padding:30px 22px;border-radius:22px}.modalbox h2{font-size:42px;letter-spacing:-2px}.modalbox .close{right:12px;top:12px}}@media(max-width:900px){.testimonial-grid{grid-template-columns:1fr 1fr}.inquiry-card{grid-template-columns:1fr}}
@media(max-width:560px){.testimonial-head{display:block}.testimonial-grid{grid-template-columns:1fr}.inquiry-card{padding:24px}}
</style>

<style>
.hero-v2.hero-image-bg{
  background:#050706 url("/assets/eazypos-hero-devices.png") center center / cover no-repeat;
  min-height:760px;
  grid-template-columns:minmax(430px,.78fr) minmax(520px,1.22fr);
}
.hero-v2.hero-image-bg:after{display:none}
.hero-v2.hero-image-bg:before{display:none}
.hero-v2.hero-image-bg .legacy-hero-stage{display:none}
.hero-v2.hero-image-bg .hero-copy{max-width:650px}
@media(max-width:1050px){
  .hero-v2.hero-image-bg{grid-template-columns:1fr;min-height:700px;background-position:68% center;}
  .hero-v2.hero-image-bg .hero-copy{max-width:700px}
}
@media(max-width:680px){
  .navbar{display:block;position:sticky;top:0;z-index:60;background:#d3ff12;border-bottom:1px solid #b8dc00}
  .navbar .navin{height:58px;min-height:58px;padding:0 16px;display:flex;align-items:center;justify-content:space-between}
  .navbar .brand{display:block;width:105px;height:auto}
  .navbar .navlinks{display:none!important}
  .navbar .nav-actions{display:flex;align-items:center;margin-left:auto}
  .navbar .nav-actions .get{display:none}
  .navbar .nav-actions .pill{display:inline-flex;align-items:center;justify-content:center;padding:9px 15px;border:1px solid #0b0d0c30;background:#0b0d0c;color:#fff;border-radius:999px;font-size:13px}
  .hero-v2.hero-image-bg{min-height:760px;padding-top:48px;padding-bottom:48px;background-position:62% center;}
}
/* Home testimonial carousel */
.testimonial-carousel{position:relative;margin-top:34px;padding:0 58px}
.testimonial-viewport{overflow:hidden;border-radius:22px}
.testimonial-track{display:flex;transition:transform .75s cubic-bezier(.22,.61,.36,1);will-change:transform}
.testimonial-slide{flex:0 0 100%;display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:2px}
.testimonial-card{min-height:210px}
.testimonial-nav{position:absolute;top:50%;left:0;right:0;display:flex;justify-content:space-between;transform:translateY(-50%);pointer-events:none}
.testimonial-nav button{pointer-events:auto;width:44px;height:44px;border-radius:50%;border:1px solid #ffffff30;background:#111512;color:#fff;font-size:22px;display:grid;place-items:center;cursor:pointer;box-shadow:0 10px 30px #0004;transition:.2s ease}
.testimonial-nav button:hover{background:var(--lime);color:#0b0d0c;border-color:var(--lime);transform:scale(1.06)}
.testimonial-dots{display:flex;justify-content:center;gap:7px;margin-top:20px}
.testimonial-dots button{width:7px;height:7px;padding:0;border:0;border-radius:50%;background:#ffffff30;cursor:pointer;transition:.25s ease}
.testimonial-dots button.active{width:24px;border-radius:99px;background:var(--lime)}
.home-contact{background:#eef1eb}
.contact-home-card{background:#0b0d0c;color:#fff;border-radius:26px;padding:42px;display:flex;align-items:center;justify-content:space-between;gap:28px}
.contact-home-card p{color:#a5afa7;max-width:620px;line-height:1.55;margin:10px 0 0}
.contact-home-card .contact-email{color:var(--lime);font-weight:900;text-decoration:none}
@media(max-width:900px){.testimonial-slide{grid-template-columns:1fr 1fr}.contact-home-card{display:block}}
@media(max-width:560px){.testimonial-carousel{padding:0 46px}.testimonial-slide{grid-template-columns:1fr}.testimonial-nav button{width:38px;height:38px}.contact-home-card{padding:28px 24px}}



/* Mobile hero layout — desktop hero remains unchanged */
.mobile-hero-layout{display:none}
@media(max-width:680px){
  .hero-v2.hero-image-bg{
    display:block;
    min-height:0;
    padding:30px 16px 38px;
    background:#050706;
  }
  .hero-v2.hero-image-bg .hero-copy,
  .hero-v2.hero-image-bg .legacy-hero-stage{display:none!important}
  .mobile-hero-layout{
    display:flex;
    position:relative;
    z-index:2;
    width:100%;
    flex-direction:column;
    align-items:center;
    text-align:center;
  }
  .mobile-hero-logo{display:block;width:112px;height:auto;object-fit:contain;margin:0 auto 22px}
  .mobile-hero-eyebrow{color:#c4ccc5;font-size:9px;font-weight:600;letter-spacing:.16em;white-space:nowrap;margin-bottom:12px}
  .mobile-hero-title{width:100%;margin:0 0 14px;color:#f7f8f7;font-size:clamp(29px,8.2vw,38px);font-weight:950;line-height:1.04;letter-spacing:-1.65px}
  .mobile-hero-title>span{display:block;white-space:nowrap}
  .mobile-hero-highlight{
    display:inline-block!important;width:auto!important;max-width:max-content!important;
    margin:7px auto 0;padding:3px 12px 7px!important;
    color:#0b0d0c!important;background:linear-gradient(100deg,var(--lime),var(--lime2))!important;
    border:1px solid #d3ff1280;border-radius:5px!important;
    transform:rotate(-2deg);box-shadow:0 0 24px #d3ff1230;
    line-height:1!important;
  }
  .mobile-hero-description{max-width:100%;margin:0 auto 20px;color:#c5cbc6;font-size:13px;line-height:1.45}
  .mobile-hero-features{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));width:100%;gap:5px;margin:0 0 8px}
  .mobile-hero-features>div{display:flex;min-width:0;flex-direction:column;align-items:center;gap:5px;color:#f5f7f4}
  .mobile-feature-icon{display:grid;place-items:center;width:43px;height:43px;border:1px solid #d3ff1230;border-radius:11px;background:linear-gradient(145deg,#253400,#11190a);color:var(--lime);font-size:23px;line-height:1;box-shadow:0 5px 18px #0005}
  .mobile-hero-features b{font-size:9px;font-weight:800;white-space:nowrap}
  .mobile-hero-devices{display:block;width:calc(100% + 32px);max-width:none;height:auto;object-fit:contain;margin:0 -16px 10px}
  .mobile-hero-actions{display:flex;width:100%;flex-direction:column;gap:9px;padding:0 5px}
  .mobile-hero-actions button{width:100%;min-height:49px;border-radius:999px;font-size:15px;font-weight:950}
  .mobile-hero-actions .get{background:linear-gradient(100deg,var(--lime),var(--lime2));color:#0b0d0c}
  .mobile-hero-actions .pill{background:transparent;border:1px solid var(--lime);color:var(--lime)}
}
@media(max-width:680px){
  /* The mobile artwork already contains the full hero composition. Show it once. */
  .hero-v2.hero-image-bg .mobile-hero-layout{display:block!important;width:100%;padding:0;margin:0}
  .hero-v2.hero-image-bg .mobile-hero-logo,
  .hero-v2.hero-image-bg .mobile-hero-eyebrow,
  .hero-v2.hero-image-bg .mobile-hero-title,
  .hero-v2.hero-image-bg .mobile-hero-description,
  .hero-v2.hero-image-bg .mobile-hero-features,
  .hero-v2.hero-image-bg .mobile-hero-actions{display:none!important}
  .mobile-hero-art{position:relative;width:100%;max-width:100%;margin:0 auto}
  .mobile-hero-devices{display:block;width:100%;max-width:100%;height:auto;object-fit:contain;margin:0}
  .mobile-hero-hit{position:absolute;left:14%;width:72%;height:5.2%;padding:0;border:0;border-radius:999px;background:transparent;cursor:pointer}
  .mobile-hit-start{top:83.5%}
  .mobile-hit-login{top:88.7%}
}
@media(max-width:360px){
 .mobile-hero-title{font-size:28px;letter-spacing:-1.7px}
 .mobile-hero-eyebrow{font-size:8px;letter-spacing:.12em}
 .mobile-hero-features b{font-size:8px}
 .mobile-feature-icon{width:39px;height:39px}
}

/* Final mobile polish: keep desktop styling and behavior unchanged. */
@media(max-width:680px){
  .hero-v2.hero-image-bg{display:block!important;min-height:0!important;height:auto!important;padding:0 0 10px!important;overflow:hidden!important;background:#050706!important}
  .hero-v2.hero-image-bg .mobile-hero-layout{display:block!important;width:100%!important;margin:0!important;padding:0!important}
  .hero-v2.hero-image-bg .mobile-hero-art{width:100vw!important;max-width:100vw!important;margin:0!important;overflow:hidden!important}
  .hero-v2.hero-image-bg .mobile-hero-devices{display:block!important;width:100vw!important;max-width:100vw!important;height:auto!important;object-fit:contain!important;margin:0!important}
  .package h2,.center h2{letter-spacing:-1px!important;word-spacing:0!important}
  .home-contact{padding-top:22px!important;padding-bottom:26px!important}
  .contact-home-card{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:16px!important;padding:26px 22px!important;border-radius:22px!important}
  .contact-home-card h2{letter-spacing:-1px!important}
  .contact-home-card .get{display:inline-flex!important;align-items:center;justify-content:center;min-height:48px;padding:13px 20px!important;white-space:normal!important}
  .footerbrand img{content:url('/assets/eazypos-logo-green.png');object-fit:contain!important;object-position:left center!important}
}
@media(max-width:380px){.package h2,.center h2{letter-spacing:-.6px!important}}
</style>
<style id="landing-polish">
/* Final layer: one button system, one section rhythm, aligned grids. */
:root{--ink:#0e110f;--line:#e3e7df;--muted:#646d66;--radius-lg:24px;--radius-md:18px}
body{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
:where(a,button,input,select,textarea):focus-visible{outline:none;box-shadow:0 0 0 3px rgba(211,255,18,.6),0 0 0 5px var(--ink)}

/* Buttons */
.get,.pill,.formbtn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:48px;padding:0 22px;border-radius:999px;font-size:15px;font-weight:700;letter-spacing:-.01em;line-height:1;white-space:nowrap;text-decoration:none;cursor:pointer;transition:background-color .15s,color .15s,border-color .15s,filter .15s}
.get{background:var(--ink);color:#fff;border:1.5px solid var(--ink)}
.get:hover{filter:brightness(1.25)}
.pill{background:transparent;color:var(--ink);border:1.5px solid rgba(14,17,15,.35)}
.pill:hover{border-color:var(--ink);background:rgba(14,17,15,.06)}
.hero-actions .get,.mobile-hero-actions .get{background:var(--lime);border-color:var(--lime);color:var(--ink)}
.hero-actions .get:hover{filter:brightness(.95)}
.hero-actions .pill,.mobile-hero-actions .pill{color:#fff;border-color:rgba(255,255,255,.35)}
.hero-actions .pill:hover{border-color:#fff;background:rgba(255,255,255,.08)}
.formbtn{width:100%;height:50px;margin-top:6px;border-radius:14px}

/* Navigation: a matched pair, outline + solid, same size */
.navbar .navin{max-width:1180px;height:68px;min-height:68px;padding:0 24px}
.navbar .brand{width:118px;height:auto}
.navbar .navlinks{gap:28px}
.navbar .navlinks a{font-size:14px;font-weight:600;color:var(--ink);opacity:.82;text-decoration:none}
.navbar .navlinks a:hover{opacity:1}
.navbar .nav-actions{gap:10px}
.navbar .nav-actions .pill,.navbar .nav-actions .get{height:40px;min-width:108px;padding:0 18px;font-size:14px;font-weight:700;border-radius:999px}
.navbar .nav-actions .pill{background:transparent;color:var(--ink);border:1.5px solid var(--ink)}
.navbar .nav-actions .pill:hover{background:var(--ink);color:#fff}
.navbar .nav-actions .get{background:var(--ink);color:#fff;border:1.5px solid var(--ink)}

/* Section rhythm */
.section{padding:96px 24px}
.in{max-width:1180px}
.kicker{font-size:12px;font-weight:700;letter-spacing:.1em}
h2,.package h2,.center h2{font-size:clamp(32px,4vw,48px)!important;line-height:1.06!important;letter-spacing:-.03em!important;margin:12px 0 14px}
.lead{font-size:17px;line-height:1.6;max-width:640px}
.center{max-width:720px;margin-left:auto;margin-right:auto}

/* Everything you need */
.package{gap:72px;align-items:center;margin-bottom:96px}
.package .mock{max-width:520px;width:100%;justify-self:center;border-radius:var(--radius-lg)}
.checks{gap:16px;margin-top:28px}
.check{display:flex;gap:14px;align-items:flex-start}
.check i{width:26px;height:26px;font-size:13px;margin-top:1px}
.check b{font-size:15px;font-weight:700;margin-bottom:2px}

/* Feature and step cards: equal heights */
.features{gap:16px;margin-top:40px;align-items:stretch}
.feature{display:flex;flex-direction:column;padding:28px;border-radius:var(--radius-md);height:100%}
.feature h3{font-size:17px;font-weight:700;margin:18px 0 8px;letter-spacing:-.01em}
.feature p{font-size:14.5px;line-height:1.55;margin:0}
.ico{width:44px;height:44px;border-radius:12px;font-size:14px;font-weight:800}
.stats{gap:16px;margin-top:16px}
.stat{padding:26px 28px;border-radius:var(--radius-md)}
.stat b{font-size:40px;letter-spacing:-.03em;line-height:1}
.stat span{margin-top:10px}

/* Business types */
.businesses{gap:16px;margin-top:40px}
.business{display:flex;align-items:center;justify-content:center;min-height:72px;padding:18px;text-align:center;font-weight:700;font-size:15px;border-radius:var(--radius-md)}

/* Testimonials */
.testimonial-head{margin-bottom:8px}

/* Contact card and final call to action sit together */
.home-contact{padding-bottom:28px}
.home-contact + .section{padding-top:28px}
.contact-home-card,.cta{border-radius:var(--radius-lg);padding:48px 52px;gap:32px;align-items:center}
.contact-home-card h2,.cta h2{margin:8px 0 0}
.contact-home-card .get{flex:0 0 auto;background:var(--lime);border-color:var(--lime);color:var(--ink)}
.cta .get{margin-top:0;flex:0 0 auto}
.contact-home-card .get{height:48px!important;min-height:0!important;padding:0 24px!important;white-space:nowrap!important}

/* Footer */
.footer{padding:72px 24px 28px}
.footergrid{max-width:1180px;gap:40px}
.footercol h4{font-size:14px;font-weight:700}
.footercol a:hover{color:#fff}

/* Auth modal */
.modalbox .field input{height:48px;border-radius:12px}

/* Laptop widths: keep the hero copy readable over the device photo */
@media(min-width:681px) and (max-width:1279px){
  .hero-v2.hero-image-bg{background-image:linear-gradient(90deg,rgba(5,7,6,.97) 0%,rgba(5,7,6,.9) 40%,rgba(5,7,6,.35) 66%,rgba(5,7,6,0) 82%),url("/assets/eazypos-hero-devices.png");background-size:cover,cover;background-position:center,78% center;background-repeat:no-repeat}
  .hero-v2 .hero-copy,.hero-v2.hero-image-bg .hero-copy{max-width:520px}
  .hero-v2 h1{font-size:clamp(46px,6vw,70px);letter-spacing:-3px}
  .hero-v2 .hero-copy p{font-size:17px;max-width:470px}
}

/* Tablet */
@media(max-width:980px){
  .section{padding:80px 22px}
  .package{gap:48px;margin-bottom:80px}
  .contact-home-card,.cta{padding:40px 36px}
}

/* Phone */
@media(max-width:720px){
  .section{padding:64px 18px}
  .navbar .navin{height:60px;min-height:60px;padding:0 16px}
  .navbar .brand{width:100px}
  .navbar .nav-actions .pill,.navbar .nav-actions .get{height:38px;min-width:0;padding:0 16px;font-size:13.5px}
  /* On phones the solid sign-in button is the only header action */
  .navbar .nav-actions .pill{background:var(--ink);color:#fff}
  h2,.package h2,.center h2{font-size:clamp(30px,8.5vw,38px)!important}
  .lead{font-size:16px}
  .package{gap:36px;margin-bottom:64px}
  .feature{padding:22px}
  .stat{padding:22px}
  .stat b{font-size:34px}
  .business{min-height:60px}
  .home-contact{padding-bottom:20px}
  .home-contact + .section{padding-top:20px}
  .contact-home-card,.cta{padding:28px 22px!important;gap:20px!important}
  .contact-home-card .get,.cta .get{width:100%}
  .cta{display:flex;flex-direction:column;align-items:flex-start}
  .footer{padding:56px 18px 24px}
}

@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
}
</style>
</head>
<body>
<header class="navbar"><div class="navin"><img class="brand" src="/assets/eazypos-logo-transparent.png" alt="EazyPOS"><nav class="navlinks"><a href="#features">Features</a><a href="#how">How it works</a><a href="#businesses">For businesses</a><a href="#about">About</a><a href="/inquiry">Contact</a></nav><div class="nav-actions"><button class="pill" onclick="openLogin()">Log in</button><button class="get" onclick="openSignup()">Get Started</button></div></div></header>
<main>
<section class="hero hero-v2 hero-image-bg">
  <div class="hero-copy">
    <span class="eyebrow">SIMPLE POS FOR SMALL BUSINESS</span>
    <h1><span>Run your business.</span><span class="highlight">Simply.</span></h1>
    <p>Sales, stock, purchases, customers and business insights in one easy-to-use POS — designed to keep everyday selling fast and clear.</p>
    <div class="hero-actions"><button class="get" onclick="openSignup()">Get Started Free</button><button class="pill" onclick="document.getElementById('features').scrollIntoView()">Explore EazyPOS</button></div>
    <div class="counterline"><b id="userCount">0</b>+ business users getting started with EazyPOS</div>
  </div>
  <div class="mobile-hero-layout">
    <div class="mobile-hero-art">
      <img class="mobile-hero-devices" src="/assets/eazypos-mobile-hero.png" alt="EazyPOS mobile hero preview">
      <button class="mobile-hero-hit mobile-hit-start" type="button" aria-label="Get Started" onclick="openSignup()"></button>
      <button class="mobile-hero-hit mobile-hit-login" type="button" aria-label="Log In" onclick="openLogin()"></button>
    </div>
  </div>
  <div class="hero-stage hero-stage-v2 legacy-hero-stage">
    <div class="hero-orbit orbit-a"></div><div class="hero-orbit orbit-b"></div>
    <div class="live-device v2-device">
      <div class="pc v2-pc">
        <div class="pc-top v2-top"><div class="dots"><i></i><i></i><i></i></div><b>eazypos</b><span>Today · Live</span></div>
        <div class="pos-desktop v2-pos">
          <div class="pos-toolbar"><div class="pos-logo-mini">eazypos</div><div class="pos-search">⌕&nbsp; Search products...</div><button class="pos-action">⌘ Scan Barcode</button><button class="pos-action">▦</button></div>
          <div class="pos-body">
            <aside class="pos-side"><div class="active">▣ POS</div><div>▢ Products</div><div>↗ Sales</div><div>▤ Purchases</div><div>♙ Customers</div><div>♙ Suppliers</div><div>▣ Expenses</div><div>▥ Reports</div><div>⚙ Settings</div></aside>
            <div class="pos-main"><div class="pos-cats"><span class="sel">All</span><span class="sel">Burger</span><span>Sandwiches</span><span>Snacks &amp; Sweets</span><span>Sides</span><span>Drinks</span><span>Retail</span><span>PIZZA</span></div>
              <div class="pos-products">
                <div class="pos-product"><div class="prod-img">🍔</div><b>Classic Cheeseburger</b><small>Rs. 12.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Bacon Burger</b><small>Rs. 12.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Spicy Jalapeno</b><small>Rs. 13.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Texan Burger</b><small>Rs. 12.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Veggie Burger</b><small>Rs. 10.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Double Stack Bun</b><small>Rs. 14.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>Truffle Mushroom</b><small>Rs. 17.00</small><span class="prod-add">+</span></div>
                <div class="pos-product"><div class="prod-img">🍔</div><b>California Burger</b><small>Rs. 13.00</small><span class="prod-add">+</span></div>
              </div>
            </div>
            <aside class="pos-cart"><div class="cart-title"><span>Cart (3)</span><span>Clear 🗑</span></div><div class="cart-row"><strong>Classic Cheeseburger</strong><span>x1&nbsp; Rs. 12.00</span></div><div class="cart-row"><strong>Bacon Burger</strong><span>x1&nbsp; Rs. 12.00</span></div><div class="cart-row"><strong>Fries (Large)</strong><span>x1&nbsp; Rs. 6.00</span></div><div class="cart-bottom"><div class="cart-total"><span>Discount</span><span>Rs. 0.00</span></div><div class="cart-total"><strong>Total</strong><strong>Rs. 30.00</strong></div><button class="checkout">Checkout</button></div></aside>
          </div>
        </div>
      </div>
      <div class="phone v2-phone">
        <div class="mobile-pos"><div class="mobile-head"><b>eazypos</b><span>🛒 3&nbsp; ☰</span></div><div class="mobile-content"><div class="mobile-sales"><small>TODAY'S SALES</small><strong id="mobileSales">Rs. 0</strong><span style="font-size:5.5px">+12.4% from yesterday</span></div><div class="mobile-grid"><div class="mobile-card"><b>＋ SALE</b><small>Fast checkout</small></div><div class="mobile-card"><b>▣ STOCK</b><small>Stock &amp; pricing</small></div><div class="mobile-card"><b>▤ BUY</b><small>Suppliers &amp; GRN</small></div><div class="mobile-card"><b>▥ REPORT</b><small>Business insights</small></div></div><div class="mobile-bottom"><span class="on">Home</span><span>POS</span><span>Sales</span><span>Products</span><span>More</span></div></div></div>
      </div>
      <div class="cursor v2-cursor"></div><div class="tap v2-tap"></div>
    </div>
  </div>
</section>
<section class="section alt" id="features"><div class="in"><div class="package reveal"><div class="mock"><div class="mock-screen"><div style="display:flex;justify-content:space-between;font-size:10px;margin-bottom:10px"><b>eazypos</b><span>Live workspace</span></div><div class="mock-sales"><small>TODAY'S SALES</small><strong id="packageSales">Rs. 0</strong></div><div class="mock-grid"><div><b>＋ New Sale</b><br><small>Fast checkout</small></div><div><b>▣ Products</b><br><small>Stock & pricing</small></div><div><b>▤ Purchases</b><br><small>Suppliers & GRN</small></div><div><b>▥ Reports</b><br><small>Business insights</small></div></div></div></div><div><div class="kicker">Everything you need</div><h2>Everything you need. One simple POS.</h2><p class="lead" style="margin:0">One free POS workspace for the everyday jobs that keep your business moving.</p><div class="checks"><div class="check"><i>✓</i><div><b>Sell in seconds</b><span>Find products, add them to the cart and complete Cash, Card, Credit or Bank sales quickly.</span></div></div><div class="check"><i>✓</i><div><b>Manage stock & purchases</b><span>Track products, suppliers, costs, purchases and stock changes in one place.</span></div></div><div class="check"><i>✓</i><div><b>Run your register</b><span>Open cash, track activity, close the register and keep the cash balance clear.</span></div></div><div class="check"><i>✓</i><div><b>Understand your numbers</b><span>See sales, profit, expenses, stock, suppliers and register reports without the clutter.</span></div></div><div class="check"><i>✓</i><div><b>Use it across devices</b><span>Move between PC, tablet and mobile with the same simple EazyPOS experience.</span></div></div></div><span class="free">100% Free to get started</span></div></div><div class="center reveal" style="margin-top:85px"><div class="kicker">Built around your day</div><h2>Less clicking. More doing.</h2><p class="lead">A focused set of tools for sales, stock, purchases, registers and useful business insights.</p></div><div class="features reveal"><div class="feature"><div class="ico">＋</div><h3>Fast Sales</h3><p>Find a product, add it, choose a payment method and finish the sale.</p></div><div class="feature"><div class="ico">▣</div><h3>Stock & Purchases</h3><p>Record purchases, returns, suppliers, costs and stock adjustments.</p></div><div class="feature"><div class="ico">▥</div><h3>Know Your Numbers</h3><p>Sales, profit, expenses, register and supplier reports in one place.</p></div></div><div class="stats reveal"><div class="stat"><b id="statUsers">0</b><span>registered users in our early test community</span></div><div class="stat"><b>1</b><span>simple system for everyday business</span></div><div class="stat"><b>24/7</b><span>access from supported devices</span></div></div></div></section>
<section class="section dark-section" id="how"><div class="in"><div class="center"><div class="kicker" style="color:#9ca69e">How it works</div><h2>Start in minutes.</h2><p class="lead">Get your account, add your products, open your register and start selling.</p></div><div class="features"><div class="feature"><div class="ico">01</div><h3>Get your account</h3><p>Tell us about your business. Our team sets up your account and sends your sign-in details.</p></div><div class="feature"><div class="ico">02</div><h3>Add products & opening stock</h3><p>Add products, prices, stock, suppliers and low-stock alerts.</p></div><div class="feature"><div class="ico">03</div><h3>Open register & sell</h3><p>Set opening cash, sell, track the register and close it at the end of the day.</p></div></div></div></section>
<section class="section" id="businesses"><div class="in"><div class="center"><div class="kicker">Made for small business</div><h2>One POS. Many businesses.</h2><p class="lead">Retail shops, groceries, cafés, salons, service businesses and more can start with the same simple core.</p></div><div class="businesses"><div class="business">Retail & Stores</div><div class="business">Grocery & Mini Mart</div><div class="business">Cafés & Food</div><div class="business">Services & More</div></div></div></section>
<section class="section testimonial-strip" id="testimonials"><div class="in"><div class="testimonial-head"><div><div class="kicker" style="color:#9ca69e">Business stories</div><h2>Simple enough for everyday business.</h2><p class="lead">Illustrative customer stories showing the kinds of businesses EazyPOS is built to support.</p></div></div><div class="testimonial-carousel"><div class="testimonial-viewport"><div class="testimonial-track" id="testimonialTrack"><div class="testimonial-slide"><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · INDIA</span><p class="testimonial-quote">“The checkout and stock flow feels easy to understand, even on a busy day.”</p><div class="testimonial-person"><img src="/assets/testimonial-1.svg" alt="Profile illustration"><div><strong>Aarav Mehta</strong><span>Boutique Retail · India</span></div></div></article><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · UK</span><p class="testimonial-quote">“Staff can get through a sale without working through a complicated screen.”</p><div class="testimonial-person"><img src="/assets/testimonial-2.svg" alt="Profile illustration"><div><strong>Olivia Carter</strong><span>Salon &amp; Beauty · United Kingdom</span></div></div></article><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · CANADA</span><p class="testimonial-quote">“Sales, purchases and reports together give us a clearer view of the day.”</p><div class="testimonial-person"><img src="/assets/testimonial-3.svg" alt="Profile illustration"><div><strong>Daniel Nguyen</strong><span>Café &amp; Food · Canada</span></div></div></article></div><div class="testimonial-slide"><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · SRI LANKA</span><p class="testimonial-quote">“It keeps the important parts of the shop in one place without making the team feel overwhelmed.”</p><div class="testimonial-person"><img src="/assets/testimonial-4.svg" alt="Profile illustration"><div><strong>Fathima Nazeer</strong><span>Grocery Store · Sri Lanka</span></div></div></article><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · MALDIVES</span><p class="testimonial-quote">“The simple sales and stock workflow makes daily operations easier to follow.”</p><div class="testimonial-person"><img src="/assets/testimonial-5.svg" alt="Profile illustration"><div><strong>Ahmed Shafaz</strong><span>Island Retail · Maldives</span></div></div></article><article class="testimonial-card"><span class="testimonial-tag">BUSINESS STORY · BANGLADESH</span><p class="testimonial-quote">“Having sales, purchases and reports together gives the business a much clearer routine.”</p><div class="testimonial-person"><img src="/assets/testimonial-6.svg" alt="Profile illustration"><div><strong>Nusrat Jahan</strong><span>Fashion Retail · Bangladesh</span></div></div></article></div></div></div><div class="testimonial-nav"><button type="button" onclick="testimonialMove(-1)" aria-label="Previous testimonials">←</button><button type="button" onclick="testimonialMove(1)" aria-label="Next testimonials">→</button></div><div class="testimonial-dots" id="testimonialDots"><button class="active" type="button" onclick="testimonialGo(0)" aria-label="Testimonials 1"></button><button type="button" onclick="testimonialGo(1)" aria-label="Testimonials 2"></button></div></div></div></section>
<section class="section home-contact" id="contact"><div class="in"><div class="contact-home-card reveal"><div><div class="kicker" style="color:#9ca69e">Need a hand?</div><h2 style="margin-bottom:0">Let’s talk.</h2><p>Have a question about EazyPOS, getting started, or your business setup? Reach us at <a class="contact-email" href="mailto:help@eazyposs.store">help@eazyposs.store</a>.</p></div><a class="get" href="/inquiry" style="text-decoration:none;display:inline-block;white-space:nowrap">Contact EazyPOS</a></div></div></section>
<section class="section alt" id="about"><div class="in"><div class="cta"><div><div class="kicker" style="color:#566100">EazyPOS</div><h2>Ready to make selling easier?</h2></div><button class="get" onclick="openSignup()">Get Started Free</button></div></div></section>

<div class="modal hidden" id="inviteSetupModal"><div class="modalbox"><div class="kicker">INVITATION ACCEPTED</div><h2>Finish your EazyPOS account.</h2><p class="muted">Set your staff profile and password to join the business you were invited to.</p><div class="field"><label>FULL NAME *</label><input id="inviteFullName" placeholder="Your full name"></div><div class="field"><label>USERNAME</label><input id="inviteUsername" placeholder="e.g. testcashier"><div class="muted" style="font-size:11px;margin-top:5px">Optional. Use 3–32 letters, numbers, dot, underscore or hyphen.</div></div><div class="field"><label>NEW PASSWORD *</label><input id="invitePassword" type="password" placeholder="At least 8 characters" autocomplete="new-password"></div><div class="field"><label>CONFIRM PASSWORD *</label><input id="invitePassword2" type="password" placeholder="Repeat your password" autocomplete="new-password"></div><div id="inviteSetupErr" class="error"></div><button class="formbtn lime" id="inviteSetupBtn" onclick="completeStaffInvitation()">Finish account</button></div></div>
</main>
<footer class="footer"><div class="footergrid"><div class="footerbrand"><img src="/assets/eazypos-logo-green.png" alt="EazyPOS"><p>A simple, modern POS built to help small businesses sell, track and grow with less effort.</p><div class="footercontact"><span>Need help?</span><a href="mailto:help@eazyposs.store">help@eazyposs.store</a></div><div class="socials" aria-label="EazyPOS social media"><a href="https://www.instagram.com/eazyposs?stkn=MXM4dWo2b2FqYmtvNQ%3D%3D&amp;utm_source=qr" aria-label="Instagram" title="Instagram" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.4" cy="6.7" r="1.2" fill="currentColor"/></svg></a><a href="https://www.facebook.com/share/1J4kpjqima/?mibextid=wwXIfr" aria-label="Facebook" title="Facebook" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.2 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7c-.3 0-1.4-.1-2.6-.1-2.6 0-4.4 1.6-4.4 4.5v1.8H7.8V13h2.7v8h3.7Z" fill="currentColor"/></svg></a><a href="#" aria-label="TikTok" title="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.2 3c.3 2.1 1.5 3.4 3.6 3.6v3.2c-1.4-.1-2.6-.5-3.6-1.2v6.3c0 4-2.8 6.1-6 6.1-3 0-5.2-2.1-5.2-5 0-3.2 2.5-5.3 5.8-5.3.4 0 .8 0 1.2.1v3.2c-.4-.1-.7-.2-1.1-.2-1.2 0-2.3.7-2.3 2 0 1.1.8 2 2 2 1.4 0 2.1-.9 2.1-2.5V3h3.5Z" fill="currentColor"/></svg></a><a href="#" aria-label="LinkedIn" title="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.2 8.2H2V21h3.2V8.2ZM3.6 3C2.6 3 2 3.7 2 4.6s.6 1.6 1.6 1.6 1.6-.7 1.6-1.6S4.6 3 3.6 3ZM21 13.7c0-3.9-2.1-5.7-5-5.7-2.3 0-3.3 1.3-3.8 2.2V8.2H9V21h3.2v-6.4c0-1.7.3-3.4 2.5-3.4 2.2 0 2.2 2 2.2 3.5V21H21v-7.3Z" fill="currentColor"/></svg></a></div></div><div class="footercol"><h4>Product</h4><a href="#features">Features</a><a href="#how">How it works</a><a href="#businesses">For businesses</a><a href="#about">Get Started</a></div><div class="footercol"><h4>Why EazyPOS?</h4><a href="#features">Simple POS</a><a href="#features">Stock & Purchases</a><a href="#how">Register</a><a href="#features">Reports</a></div><div class="footercol"><h4>Company</h4><a href="#about">About Us</a><a href="/inquiry">Contact</a><a href="#how">How It Works</a></div><div class="footercol"><h4>Account</h4><a href="#" onclick="openLogin();return false">Log in</a><a href="#" onclick="openSignup();return false">Request an account</a><a href="#features">Free to start</a></div></div><div class="footerbottom"><span>© 2026 EazyPOS · Simple POS for small businesses</span><span>Questions? <a href="mailto:help@eazyposs.store">help@eazyposs.store</a></span></div></footer>
<div class="modal hidden" id="authModal"><div class="modalbox"><button class="close" onclick="closeModal()">×</button><div id="loginView"><div class="kicker">EAZY POS</div><h2>Welcome back.</h2><div class="field"><label>USERNAME OR EMAIL</label><input id="loginId" placeholder="Username or email" autocomplete="username" onkeydown="if(event.key==='Enter')document.getElementById('loginPass').focus()"></div><div class="field"><label>PASSWORD</label><input id="loginPass" type="password" placeholder="••••••••" autocomplete="current-password" onkeydown="if(event.key==='Enter')login()"></div><button class="formbtn" onclick="login()">Sign in</button><div id="loginErr" class="error" aria-live="polite"></div><div class="switch">New here? <button onclick="openSignup()">Request an account</button></div></div></div></div>
@endverbatim
<script>window.EAZY_PAGE=@json(['invite' => $invite ?? null, 'openLogin' => (bool) ($openLogin ?? false), 'loginError' => session('login_error')]);</script>
@verbatim
<script>
let authBusy=false;
const csrfToken=()=>document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')||'';
async function postJson(url,body){
 const res=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrfToken(),'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(body)});
 let data={};try{data=await res.json()}catch(_){}
 if(res.status===419)throw new Error('Your session expired. Please refresh the page and try again.');
 if(!res.ok)throw new Error(data.message||'Something went wrong. Please try again.');
 return data;
}
function openSignup(){location.href='/inquiry?topic=getting-started#message'}
function openLogin(){document.getElementById('authModal').classList.remove('hidden');showLogin();setTimeout(()=>document.getElementById('loginId').focus(),50)}
function closeModal(){document.getElementById('authModal').classList.add('hidden');clearMessages()}
function clearMessages(){const e=document.getElementById('loginErr');if(e){e.textContent='';e.style.color=''}}
function showLogin(){clearMessages();document.getElementById('loginView').classList.remove('hidden')}
function setAuthBusy(b){authBusy=b;document.querySelectorAll('#authModal .formbtn').forEach(x=>{x.disabled=b;x.style.opacity=b?.65:1})}
async function login(){
 if(authBusy)return;
 const id=document.getElementById('loginId').value.trim(), p=document.getElementById('loginPass').value, err=document.getElementById('loginErr');
 err.style.color='';
 if(!id||!p){err.textContent='Enter your email and password.';return}
 setAuthBusy(true);
 try{
  const result=await postJson('/login',{login:id,password:p,remember:true});
  location.href=result.redirect||'/app';
 }catch(error){err.textContent=error.message;setAuthBusy(false)}
}
document.getElementById('authModal').addEventListener('click',e=>{if(e.target.id==='authModal')closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
async function completeStaffInvitation(){
 const invite=window.EAZY_PAGE?.invite;
 const fullName=document.getElementById('inviteFullName')?.value.trim();
 const username=document.getElementById('inviteUsername')?.value.trim().toLowerCase()||null;
 const password=document.getElementById('invitePassword')?.value||'';
 const password2=document.getElementById('invitePassword2')?.value||'';
 const err=document.getElementById('inviteSetupErr');
 const btn=document.getElementById('inviteSetupBtn');
 err.textContent=''; err.style.color='';
 if(!invite?.valid){err.textContent=invite?.error||'This invitation link is not valid.';return}
 if(!fullName){err.textContent='Enter your full name.';return}
 if(password.length<8){err.textContent='Password must be at least 8 characters.';return}
 if(password!==password2){err.textContent='Passwords do not match.';return}
 if(username && !/^[a-z0-9][a-z0-9._-]{2,31}$/.test(username)){err.textContent='Username must be 3–32 characters and use only letters, numbers, dot, underscore or hyphen.';return}
 btn.disabled=true; btn.style.opacity='.65'; btn.textContent='Setting up…';
 try{
   const result=await postJson('/invitation/'+encodeURIComponent(invite.token),{full_name:fullName,username,password,password_confirmation:password2});
   err.style.color='#23804d'; err.textContent='Account ready. Opening EazyPOS…';
   setTimeout(()=>location.href=result.redirect||'/app',350);
 }catch(error){
   err.textContent=error?.message||'Unable to complete your invitation.';
   btn.disabled=false; btn.style.opacity='1'; btn.textContent='Finish account';
 }
}
(function initAuthState(){
 const page=window.EAZY_PAGE||{};
 if(page.invite){
   const modalEl=document.getElementById('inviteSetupModal');
   document.getElementById('inviteFullName').value=page.invite.fullName||'';
   document.getElementById('inviteUsername').value=page.invite.username||'';
   const err=document.getElementById('inviteSetupErr');
   if(!page.invite.valid&&err)err.textContent=page.invite.error;
   if(modalEl)modalEl.classList.remove('hidden');
   return;
 }
 if(page.openLogin||page.loginError){
   openLogin();
   if(page.loginError)document.getElementById('loginErr').textContent=page.loginError;
 }
})();
function anim(el,target,prefix='',duration=1300){if(!el)return;let s=performance.now();function f(t){let p=Math.min(1,(t-s)/duration),e=1-Math.pow(1-p,3);el.textContent=prefix+Math.round(target*e).toLocaleString();if(p<1)requestAnimationFrame(f)}requestAnimationFrame(f)}anim(document.getElementById('userCount'),107);anim(document.getElementById('statUsers'),107);anim(document.getElementById('heroSales'),48750,'Rs. ');anim(document.getElementById('mobileSales'),48750,'Rs. ');anim(document.getElementById('packageSales'),48750,'Rs. ');
const ob=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting)e.target.classList.add('show')}),{threshold:.12});document.querySelectorAll('.reveal').forEach(x=>ob.observe(x));
let testimonialIndex=0;
const testimonialTrack=document.getElementById('testimonialTrack');
const testimonialDots=[...document.querySelectorAll('#testimonialDots button')];
function testimonialGo(i){testimonialIndex=(i+2)%2;if(testimonialTrack)testimonialTrack.style.transform=`translateX(-${testimonialIndex*100}%)`;testimonialDots.forEach((d,n)=>d.classList.toggle('active',n===testimonialIndex));}
function testimonialMove(dir){testimonialGo(testimonialIndex+dir);}
let testimonialTimer=setInterval(()=>testimonialMove(1),7000);
const testimonialCarousel=document.querySelector('.testimonial-carousel');
if(testimonialCarousel){testimonialCarousel.addEventListener('mouseenter',()=>clearInterval(testimonialTimer));testimonialCarousel.addEventListener('mouseleave',()=>{clearInterval(testimonialTimer);testimonialTimer=setInterval(()=>testimonialMove(1),7000)});}

</script>
</body></html>
@endverbatim
