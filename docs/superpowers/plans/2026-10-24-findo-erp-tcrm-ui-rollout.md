# تعميم شكل TCRM على الـERP كله — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline) to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** نكمّل تحويل كل صفحات الـERP (`/admin*`) لشكل TCRM (tcrmdemo.tamiyouz.com) بألوان Findo بس، على مستوى التفاصيل: الفلاتر، رؤوس الصفحات، أزرار الجداول، سجل العمليات، وبعدين e2e مفصل، وcommit وpush وdeploy.

**Architecture:** الـshell العام (sidebar، topbar، الكروت، الجداول، الحقول، الـbadges) اتعمل واتنشر في `2c2451a` كطبقة CSS في `public/admin-assets/operating.css`. اللي ناقص **أنماط على مستوى الـmarkup** مش CSS: لوحة فلاتر بهيدر وعناوين فوق الحقول، رقم في رأس الصفحة، أزرار صف بأيقونات، وتايملاين بصور رمزية. هنعمل partial واحد لكل نمط (`admin/filter-head`، `admin/head-stat`) ونستخدمه في كل الصفحات، ونضيف الـCSS والـJS في نفس الملفات الموجودة. مافيش أي تغيير في الـbackend أو الصلاحيات.

**Tech Stack:** Laravel 12 / PHP 8.4، Blade، CSS عادي (`operating.css`)، JS عادي (`operating.js`)، PHPUnit، Playwright MCP للـe2e.

**Spec:** طلب المستخدم 2026-10-24: "نحول الـERP كله للديزاين الجديد … من الأول للآخر لحد الـcommits والـpush والـdeploy"، و"e2e مفصل مش سطحي قبل الـdeploy". المرجع البصري: صور TCRM في `C:\Users\hp\AppData\Local\Temp\claude\c--work-projects-findogroup\3f8d879a-6512-419d-8cbe-6b9bdeefaf14\scratchpad\tcrm\` (`00-dashboard.jpg`، `10-_leads.jpg`، `06-_audit_log.jpg`).

## Global Constraints

- ألوان Findo بس (navy + coral، `--admin-accent`، `--gradient-coral`). **ممنوع** بنفسجي TCRM.
- البانر الداكن للوحة CRM بس، وباقي الصفحات رأسها كارت فاتح (قرار السيشن اللي فاتت، زي TCRM).
- RTL عربي أساسي، والإنجليزي بيشتغل (`?ui_lang=en`). أي نص جديد بيتلف بـ`__()` ويتضاف لـ`lang/en.json`.
- الوضع الداكن والموبايل (375px) لازم يشتغلوا. مافيش scroll أفقي في أي صفحة.
- أسماء الحقول (`name=`) وقيم الفلاتر **ما تتغيرش**، عشان الروابط والتستات والـbookmarks.
- PHP عن طريق `/c/dev-tools/php84/php.exe`. وبعد أي تعديل PHP نشغل `vendor/bin/pint --dirty --format agent`.
- مفيش dependencies جديدة.

## تحليل الفجوة (الحالي مقابل TCRM)

| النمط | TCRM | عندنا دلوقتي | القرار |
|---|---|---|---|
| Shell (sidebar/topbar/user card) | ✓ | ✓ اتعمل في `2c2451a` | مفيش شغل |
| رأس الصفحة | عنوان + وصف، وعلى الناحية التانية رقم في pill وأزرار | عنوان + وصف، والعدد جوه النص ("18 عميل") | Task 2: partial `head-stat` |
| الفلاتر | كارت بهيدر (أيقونة + "الفلاتر والبحث" + وصف + زرار إخفاء)، وعنوان فوق كل حقل | grid حقول، والعنوان جوه أول option ("المرحلة: الكل") | Task 1 + 3 |
| تصدير Excel | زرار في رأس الصفحة | كارت `<details>` منفصل | Task 3 |
| أزرار الصف في الجداول | أزرار أيقونات | لينكات نص ("تعديل · الأقسام · معاينة") | Task 4 |
| سجل العمليات | تايملاين بصورة رمزية وtags | نص سطر واحد + badge | Task 5 |
| الحقول والأزرار والجداول والـstat cards | ✓ | ✓ اتعمل | مفيش شغل |

**باج حقيقي اتلقى أثناء التحليل:** في `resources/views/admin/contents.blade.php:5` الـoption بتاع "مقالات" مكسور (`</option value="post" …>`)، فلتر النوع مش بيقدر يختار المقالات. بيتصلح في Task 3.

## File Structure

- Create: `resources/views/admin/filter-head.blade.php`، هيدر لوحة الفلاتر (أيقونة، عنوان، وصف، زرار إخفاء/إظهار). مسؤوليته الهيدر بس.
- Create: `resources/views/admin/head-stat.blade.php`، الرقم في رأس الصفحة (`value` و`label`).
- Modify: `public/admin-assets/operating.css`، طبقة جديدة في الآخر: `.filter-panel` و`.field` و`.head-stat` و`.head-menu` و`.row-actions` في الجداول و`.timeline` بالصور الرمزية.
- Modify: `public/admin-assets/operating.js`، تبديل إخفاء الفلاتر وحفظه في `localStorage` لكل صفحة.
- Modify (الفلاتر + رأس الصفحة): `admin/operating/listing`، `crm-leads`، `audit-log`، `activities-followups`، `crm-calendar`، `marketing-performance`، `milestones-tracker`، `opportunities-pipeline`، `projects-portfolio`، و`admin/contents`.
- Modify (head-stat بس): `admin/media`، `admin/redirects`، `admin/submissions`، `admin/users`.
- Modify: `lang/en.json`، النصوص الجديدة.
- Test: `tests/Feature/Admin/AdminUiPatternsTest.php` (جديد).

---

### Task 1: partials لوحة الفلاتر ورأس الصفحة + CSS + JS

**Files:** Create `admin/filter-head.blade.php` و`admin/head-stat.blade.php`، وتعديل `operating.css` و`operating.js` و`lang/en.json`.

**Interfaces — Produces:**
- `@include('admin.filter-head', ['subtitle' => ?string])`، أول عنصر جوه `<form class="card filters filter-panel" data-filter-panel>`.
- `@include('admin.head-stat', ['value' => int|string, 'label' => string])`، جوه `.row-actions` بتاع `.page-heading`.
- `<label class="field"><span>{{ __('X') }}</span><select …></label>`، عنوان فوق الحقل.

- [ ] **Step 1:** `admin/filter-head.blade.php`:
```blade
<div class="filter-head"><span class="filter-icon">@include('admin.icon',['name'=>'filter'])</span><div><b>{{ __('الفلاتر والبحث') }}</b><small>{{ $subtitle ?? __('ابحث وفلتر للوصول للبيانات المطلوبة بسرعة') }}</small></div><button type="button" class="button small secondary" data-filter-toggle aria-expanded="true">{{ __('إخفاء الفلاتر') }}</button></div>
```
- [ ] **Step 2:** `admin/head-stat.blade.php`:
```blade
<span class="head-stat"><b>{{ is_numeric($value) ? number_format($value) : $value }}</b><small>{{ $label }}</small></span>
```
- [ ] **Step 3:** نضيف أيقونة `filter` و`download` و`edit` و`eye` و`layers` في `admin/icon.blade.php`:
```php
'filter' => 'M4 6h16 M7 12h10 M10 18h4',
'download' => 'M12 3v12 M7 10l5 5 5-5 M5 21h14',
'edit' => 'M4 20h4L19 9l-4-4L4 16z M14 6l4 4',
'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6',
'layers' => 'M12 3l9 5-9 5-9-5z M3 13l9 5 9-5',
```
- [ ] **Step 4:** CSS في آخر `operating.css`:
```css
/* TCRM page patterns: filter panel, head stat, row actions, avatar timeline */
.filter-panel{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.85rem;align-items:end}
.filter-panel>.filter-head{grid-column:1/-1;display:flex;align-items:center;gap:.8rem;padding:.2rem 0 .9rem;border-bottom:1px solid var(--admin-border)}
.filter-head>div{display:grid;flex:1;min-width:0}.filter-head small{color:var(--admin-muted);font-size:12px}
.filter-icon{display:grid;place-items:center;width:2.4rem;height:2.4rem;border-radius:12px;background:var(--admin-accent-soft);color:var(--admin-accent);flex:none}
.filter-panel .field{display:grid;gap:.35rem;font-size:12.5px;font-weight:700;color:var(--admin-text)}
.filter-panel .field>span{padding-inline-start:.15rem}
.filter-panel .field.wide{grid-column:span 2}
.filter-panel>.button,.filter-panel>.text-button,.filter-panel>.checkbox,.filter-panel>details{align-self:end}
.filter-panel.collapsed>:not(.filter-head){display:none}
.filter-panel.collapsed>.filter-head{border-bottom:0;padding-bottom:0}
.head-stat{display:inline-grid;justify-items:center;min-width:5.5rem;padding:.45rem .9rem;border:1px solid var(--admin-border);border-radius:16px;background:var(--admin-surface);box-shadow:0 6px 18px -14px var(--admin-accent)}
.head-stat b{font-size:1.25rem;font-variant-numeric:tabular-nums;line-height:1.2}.head-stat small{font-size:11px;color:var(--admin-muted)}
.head-menu{position:relative}.head-menu>summary{list-style:none;cursor:pointer}.head-menu>summary::-webkit-details-marker{display:none}
.head-menu>.head-menu-panel{position:absolute;inset-inline-end:0;top:calc(100% + .4rem);z-index:20;width:min(22rem,90vw);display:grid;gap:.6rem;padding:1rem;border:1px solid var(--admin-border);border-radius:16px;background:var(--admin-surface);box-shadow:0 18px 40px -20px rgb(0 0 0 / .35)}
td .row-actions{display:flex;flex-wrap:wrap;gap:.35rem}
td .row-actions>a{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .6rem;border:1px solid var(--admin-border);border-radius:10px;font-size:12px;font-weight:600;color:var(--admin-text);text-decoration:none;white-space:nowrap}
td .row-actions>a:hover{border-color:var(--admin-accent);color:var(--admin-accent);background:var(--admin-accent-soft)}
td .row-actions .nav-icon{width:14px;height:14px}
.timeline.people>li{display:grid;grid-template-columns:2.4rem 1fr;gap:.75rem;align-items:start}
.timeline.people .avatar{display:grid;place-items:center;width:2.4rem;height:2.4rem;border-radius:50%;background:var(--gradient-coral);color:#fff;font-weight:800}
.timeline.people .event-tags{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center}
@media (max-width:700px){.filter-panel{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-panel .field.wide{grid-column:1/-1}.filter-head .button{padding-inline:.6rem}}
```
- [ ] **Step 5:** JS في `operating.js` (نفس أسلوب الملف، IIFE/`document.querySelectorAll`)، ونلف `localStorage` بـtry/catch:
```js
document.querySelectorAll('[data-filter-panel]').forEach((panel) => {
    const toggle = panel.querySelector('[data-filter-toggle]');
    if (!toggle) return;
    const key = 'findo-filters-hidden:' + location.pathname;
    const t = window.findoAdminTranslations || {};
    const apply = (hidden) => {
        panel.classList.toggle('collapsed', hidden);
        toggle.setAttribute('aria-expanded', String(!hidden));
        toggle.textContent = hidden ? (t['إظهار الفلاتر'] || 'إظهار الفلاتر') : (t['إخفاء الفلاتر'] || 'إخفاء الفلاتر');
    };
    let hidden = false;
    try { hidden = localStorage.getItem(key) === '1'; } catch (e) {}
    apply(hidden);
    toggle.addEventListener('click', () => {
        hidden = !hidden;
        apply(hidden);
        try { hidden ? localStorage.setItem(key, '1') : localStorage.removeItem(key); } catch (e) {}
    });
});
```
- [ ] **Step 6:** `lang/en.json`: "الفلاتر والبحث"→"Filters & search"، "ابحث وفلتر للوصول للبيانات المطلوبة بسرعة"→"Search and filter to reach the data you need quickly"، "إخفاء الفلاتر"→"Hide filters"، "إظهار الفلاتر"→"Show filters"، "إجمالي السجلات"→"Total records"، "إجمالي العملاء"→"Total leads"، "إجمالي العناصر"→"Total items"، "إجمالي العمليات"→"Total events"، والعناوين الجديدة في Task 3.

### Task 2: tests تفشل أولا (TDD)

**Files:** Create `tests/Feature/Admin/AdminUiPatternsTest.php` (`php artisan make:test --phpunit Admin/AdminUiPatternsTest --no-interaction`)، بنفس traits الموجودة (`BuildsOperatingRecords`، `RefreshDatabase`).

- [ ] **Step 1:** التستات (سلوك بيتكسر لو حد شال النمط أو كسر الفلتر):
  1. `test_entity_listing_shows_filter_panel_with_labels_and_total`: owner يفتح `/admin/operations/projects`، ونتأكد من `data-filter-panel`، وعنوان "الحالة" كـ`field`، و`head-stat` فيه إجمالي السجلات، وإن `?status=…` لسه بيفلتر (سجل بحالة تانية مش ظاهر).
  2. `test_leads_page_moves_export_into_header_and_keeps_filters_working`: sales_manager يفتح `/admin/operations/crm/leads?stage=…`، ونتأكد إن فورم `crm/leads/export` جوه `.page-heading`، وإن الـfilters بتفلتر.
  3. `test_contents_type_filter_can_select_posts`: owner يفتح `/admin/contents?kind=post`، والـresponse فيه `<option value="post" selected>` (ده الباج).
  4. `test_audit_log_renders_avatar_timeline`: event واحد بيظهر بالحرف الأول من اسم المستخدم في `.avatar`.
- [ ] **Step 2:** `php artisan test --compact tests/Feature/Admin/AdminUiPatternsTest.php`، والمتوقع: 4 FAIL.

### Task 3: الفلاتر ورؤوس الصفحات في كل الصفحات

لكل فورم فلاتر: `class="filters"` بتبقى `class="card filters filter-panel" data-filter-panel`، وأول عنصر `@include('admin.filter-head')`، وكل `select`/`input` بيتلف في `<label class="field"><span>…</span>…</label>`. الـoption الأول بيبقى `{{ __('الكل') }}` لما العنوان بقى فوق الحقل. **الأسماء والقيم ما تتغيرش.**

- [ ] **Step 1:** `listing.blade.php`: بحث (field wide) + الحالة، وزرار "JSON" يفضل زرار ثانوي، و`head-stat` بـ`$items->total()` / "إجمالي السجلات" أول عنصر في `.row-actions`.
- [ ] **Step 2:** `crm-leads.blade.php`: كل الـselects بعنوان فوقها (`$label` الموجود)، و"نوع التاريخ" + من + إلى، والبحث `wide`، و`head-stat` بـ`$page->total()` / "إجمالي العملاء" مكان النص في الوصف. والتصدير ينتقل لرأس الصفحة كـ`<details class="head-menu"><summary class="button secondary">@include('admin.icon',['name'=>'download']) تصدير Excel</summary><div class="head-menu-panel"> …نفس الفورم بالـhidden inputs… </div></details>`.
- [ ] **Step 3:** `audit-log.blade.php`: العمليات، الكيانات، المستخدمين، من، إلى، بحث (wide)، و`head-stat` بـ`$page->total()` / "إجمالي العمليات".
- [ ] **Step 4:** `contents.blade.php`: نصلح الـoption بتاع "مقالات"، وبحث/النوع/الحالة بعناوين، و`head-stat` بـ`$items->total()` / "إجمالي العناصر"، والزرار يتلف في `.row-actions`.
- [ ] **Step 5:** `activities-followups`، `crm-calendar`، `marketing-performance` (الـlabels الموجودة تتحول لـ`field`)، `milestones-tracker`، `opportunities-pipeline`، `projects-portfolio`: نفس النمط.
- [ ] **Step 6:** `media`، `redirects`، `submissions`، `users`: `head-stat` بالإجمالي اللي بيتعرض فعلا في الوصف.
- [ ] **Step 7:** `project-sprints.blade.php:18` (فورم نقل مهام، مش فلاتر): **مش هيتلمس**، لأنه action مش filter.

### Task 4: أزرار الصف بأيقونات

- [ ] **Step 1:** `contents.blade.php`: `تعديل`→أيقونة `edit`، `الأقسام`→`layers`، `معاينة`→`eye`، مع النص (الأيقونة جنب النص مش بدل منه، عشان الوضوح والـa11y).
- [ ] **Step 2:** نفس الشيء لأي `td .row-actions` تاني من الـgrep (`collection`، `seo`، `media`، `redirects`): نستخدم أيقونات `edit`/`eye`/`external` حسب المعنى، والـCSS في Task 1 بيخلي كل اللينكات chips حتى من غير أيقونة.

### Task 5: سجل العمليات كتايملاين بصور رمزية

- [ ] **Step 1:** في `audit-log.blade.php`: `<ol class="timeline people">`، وكل `li` = `<span class="avatar">{{ mb_strtoupper(mb_substr($event->user_name ?? __('النظام'),0,1)) }}</span><div>` + `<div class="event-tags">` (الاسم، badge العملية، نوع الكيان كـbadge) + السجل + السبب + التغييرات + الوقت.
- [ ] **Step 2:** `php artisan test --compact tests/Feature/Admin/AdminUiPatternsTest.php`، والمتوقع: 4 PASS. وبعدين `php artisan test --compact` كله.
- [ ] **Step 3:** `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 4:** commit: `feat(admin): TCRM filter panels, header stats, row action chips and audit timeline across the ERP`.

### Task 6: e2e مفصل قبل الـdeploy (مش سطحي)

على `localhost:8080`، بـPlaywright MCP، ونكرر بعد أي إصلاح.

- [ ] **Step 1 — مسح بصري لكل صفحة:** لكل دور (owner، sales_manager، sales، hr، finance، designer) نجيب كل لينكات الـsidebar ونفتح كل صفحة بعرض 1366 و375، في الوضع الفاتح والداكن: status 200 (أو 403 متوقع)، مافيش `scrollWidth > clientWidth`، مافيش console errors، ومافيش زرار نصه بيتكسر حرف حرف.
- [ ] **Step 2 — الفلاتر وظيفيا:** في كل صفحة من صفحات Task 3: نملا فلتر حقيقي ونتأكد إن عدد الصفوف أو `head-stat` اتغير صح، و"إعادة ضبط" بترجع الكل، وزرار "إخفاء الفلاتر" بيخفي، وبعد reload بيفضل مخفي (localStorage)، وبيتفتح تاني.
- [ ] **Step 3 — CRUD حقيقي (owner):** إنشاء سجل مهمة من `/admin/operations/tasks/create`، ونلاقيه في الليستة بالبحث، ونعدّله، ونغير حالته، ونتأكد إنه ظهر في سجل العمليات بالصورة الرمزية.
- [ ] **Step 4 — CRM (sales_manager ثم sales):** إنشاء عميل، وفلترته بالمرحلة، وتصدير Excel من زرار الهيدر الجديد (الملف بيتنزل، والـevent بيتسجل)، ومشاركته مع المندوب، والمندوب يشوفه read-only ومحاولات التعديل ترجع 403.
- [ ] **Step 5 — CMS (owner):** فلتر "مقالات" في `/admin/contents?kind=post` بيرجع مقالات بس (الباج)، وأزرار تعديل/الأقسام/معاينة بتفتح الصفحات الصح.
- [ ] **Step 6 — الإنجليزي:** `?ui_lang=en` على leads وaudit وlisting: كل النصوص الجديدة مترجمة.
- [ ] **Step 7:** نوثق النتايج (عدد الصفحات، الأدوار، أي إصلاح) في قسم "نتايج التنفيذ" آخر الخطة دي.

### Task 7: commit وpush وdeploy

- [ ] **Step 1:** `php artisan test --compact` (كله)، و`node --test` لو موجود زي المرة اللي فاتت، والمتوقع كله يعدي.
- [ ] **Step 2:** `git status`: نتأكد مافيش ملفات untracked. ملف السيشن `rr2026-10-24-154708-e.txt` يتنقل للـscratchpad (فيه سياق محادثة، مش للـrepo، و`bin/server-deploy` بيرفض الـuntracked).
- [ ] **Step 3:** commit للخطة: `docs(admin): ERP-wide TCRM UI rollout plan and results`، و`git push origin main`.
- [ ] **Step 4:** `bin/server-pull` لو الـdry-run قال إن السيرفر فيه تعديلات، وبعدها نعيد التستات.
- [ ] **Step 5:** `PATH=/c/dev-tools/php84:$PATH bin/server-deploy --dry-run`، وبعدين `bin/server-deploy`.
- [ ] **Step 6:** smoke للقراءة بس على الإنتاج: `/admin/operations/crm/leads` و`/admin/operations/audit-log` و`/admin/contents` بيرجعوا 302 للـlogin (مش 500)، و`operating.css` الجديد فيه `.filter-panel`.
- [ ] **Step 7:** نحدّث الميموري `crm-tcrm-parity-plan.md`.

## المرحلة 2: أنماط TCRM العميقة (بعد دراسة TCRM من جوه، بطلب المستخدم)

المستخدم شاف إن المرحلة 1 لسه سطحية، وطلب ندخل TCRM نفسه ونشوف الـUI/UX. اتسجل الدخول على tcrmdemo.tamiyouz.com واتدرست 20 صفحة + فورمين (عميل جديد، طلب دعم). بيانات الدخول **مش** متسجلة في أي ملف. الأنماط اللي طلعت (لسه ناقصة عندنا):

| نمط TCRM | فين اتشاف | عندنا | Task |
|---|---|---|---|
| الفورم أقسام: كل قسم كارت بأيقونة + عنوان + وصف، والحفظ/الإلغاء في شريط ثابت تحت | "عميل جديد"، "طلب جديد" | فورم مسطح، وزرار الحفظ في الآخر | 8 |
| رأس الصفحة فيه مربع أيقونة ملوّن جنب العنوان | Client Pool، مركز الإشعارات | عنوان من غير أيقونة | 9 |
| كروت أرقام + "اختصارات تشغيلية" (فلاتر جاهزة) فوق الليستة | Client Pool | `stage-strip` لينكات نص في 5 كيانات بس | 10 |
| الصفحات الفرعية في الـsidebar على خط رأسي بنقط | كل الصفحات | لينكات من غير خط | 11 |
| Empty state: أيقونة في مربع + عنوان + وصف | كل الصفحات | مربع فاضي | 11 |
| تابات segmented (pill group) | THRS، Inbox | لينكات `view-tabs` | 11 |
| بحث شامل في الـtopbar (Ctrl/⌘+K) | Inbox، BD | مفيش | 12 |

### Task 8: الفورم العام بأقسام + شريط حفظ ثابت

**Files:** `resources/views/admin/operating/form.blade.php:26-41`، و`operating.css`، وtest في `AdminUiPatternsTest`.
- الحقول بتتقسم تلقائيا حسب نوعها، **بنفس ترتيبها جوه كل قسم**، من غير config جديد لـ100 كيان:
  1. "البيانات الأساسية" (أيقونة `pages`): العنوان + `select`/`relation`/`user`.
  2. "التواريخ والأرقام" (أيقونة `calendar`): `date`/`datetime-local`/`time`/`number`.
  3. "التفاصيل" (أيقونة `tasks`): `text`/`url`/`email`/`textarea`/`lines`/أي نوع تاني.
  4. "خيارات" (أيقونة `check`): `checkbox`.
  القسم الفاضي ما بيظهرش.
- `<div class="form-actions">` ثابت تحت (sticky): سبب الحفظ + "حفظ السجل" + "إلغاء" (يرجع للقسم).
- أسماء الحقول (`name="data[...]"`) زي ما هي، فالتستات الموجودة بتأكد إن الحفظ شغال.
- Test: فورم إنشاء `tasks` فيه `form-section` ×≥2 وكل `data[...]` موجودة.

### Task 9: مربع أيقونة في رأس كل صفحة

- Partial `admin/page-icon.blade.php` (`name`)، بيتحط أول عنصر في أول `div` جوه `.page-heading`، والـCSS بيعمل grid (أيقونة + نصوص).
- الأيقونة: الليستة العامة بتاخد أيقونة المجموعة (`$groupIcons` من الـlayout تتنقل لـ`config('operating.group_icons')` عشان تتشارك)، وباقي الصفحات أيقونة مناسبة لكل واحدة.

### Task 10: أرقام واختصارات الحالات في الليستة العامة

- `OperatingController::listing`: `$statusCounts` من نفس الـquery المحكوم بالصلاحيات (قبل فلتر `q`/`status`): `status => count`.
- الـview: كروت `stat` (الإجمالي + أكبر الحالات) لينكات لـ`?status=`، والحالة المختارة active. الـ`stage-strip` القديم بيتشال.
- Test: rep ما بيشوفش أرقام سجلات مش مسموحله بيها (الأرقام من الـquery المحكوم).

### Task 11: الـsidebar والتابات والـempty state (CSS)

- `details>a` الفرعية: خط رأسي + نقطة، والنقطة active بلون coral.
- `.view-tabs` و`.seg-tabs`: pill group.
- `.empty-state:before`: أيقونة (mask SVG) جوه المربع.

### Task 12: بحث شامل Ctrl/⌘+K

- Route `GET /admin/search?q=` (اسم `admin.search`) في `OperatingController@search`: لكل نوع المستخدم يقدر يشوفه، `access->query($user,$type)->where('title','ilike',…)->limit(5)`، لحد 20 نتيجة إجمالا، وبيرجع JSON `{results:[{title,type_label,url}]}`. أقل من حرفين = نتايج فاضية.
- الـtopbar: input بحث + قائمة نتايج (JS)، وCtrl/⌘+K بيعمل focus، وEsc بيقفل، والأسهم + Enter.
- Tests: النتيجة بتحترم الصلاحيات (rep ما يشوفش سجل HR)، وأقل من حرفين فاضي.

## Self-Review

- **التغطية:** كل صف "ناقص" في جدول الفجوة ليه Task (2/3 رأس الصفحة، 1/3 الفلاتر، 3 التصدير، 4 أزرار الصف، 5 التايملاين)، والـe2e في 6 والـdeploy في 7.
- **الأسماء:** `filter-head`، `head-stat`، `data-filter-panel`، `data-filter-toggle`، `.field`، `.head-menu`، `.timeline.people` نفس الأسماء في Tasks 1 و3 و4 و5 والتستات.
- **المخاطر:** (1) `.filters` لسه مستخدم في `project-sprints` لحاجة مش فلتر، فالـCSS الجديد على `.filter-panel` بس. (2) تغيير أول option لـ"الكل" ما بيغيرش القيمة (`value=""`). (3) الـpopover بتاع التصدير على الموبايل: `width:min(22rem,90vw)` و`inset-inline-end:0`، وبيتراجع في e2e Step 1.

## نتايج التنفيذ

**Commits:** `35315a4` (التست اللي من غير `<?php`)، `54df977` (المرحلة 1)، `ce0b116` (المرحلة 2)، وfix القراءة بس (بعد الـe2e).

**التستات:** 235 تست PHP كلها بتعدي (كانت 225 قبل كده)، و3/3 تستات JS. اتضاف `AdminUiPatternsTest` (8 تستات). و`CrmNavigationTest` كان من غير `<?php` من أيام `847530e`، فتستاته الاتنين عمرهم ما اشتغلوا، ودلوقتي بيشتغلوا وبيعدوا.

**e2e طبقة 1 (مسح كل الصفحات):** 6 أدوار (owner، sales_manager، sales، hr، finance، designer)، 247 صفحة من الـsidebar، كل صفحة بـ1366 فاتح و375 فاتح و1366 داكن = **741 تحميل**: مافيش 500، ومافيش 403 غير متوقع، ومافيش scroll أفقي، ومافيش أخطاء JS. التنبيه الوحيد (`/admin/collections/trainingProgram`) طلع أزرار جوه `details` مقفولة، مش كسر حقيقي (اتأكدنا بفتحها).

**e2e طبقة 2 (CRUD حقيقي من الواجهة):**
- owner، **17/17**: إنشاء مهمة من الفورم الجديد، ولقاها بالبحث، والإجمالي +1، وكروت الحالات بتفلتر، والتعديل بيرفع الإصدار، وCtrl+K بيلاقيها ويفتحها، وسجل العمليات فيه الحدثين بالصور الرمزية، وفلتر "مقالات" شغال (الباج اتصلح)، وأزرار الصف بتفتح، والإنجليزي.
- CRM (مدير مبيعات + مندوب)، **18/18**: رفض العميل من غير شركة/تليفون برسالة مع الاحتفاظ بالمكتوب، والإنشاء، والفلتر بالعناوين، وفلتر المرحلة، وتصدير Excel من الهيدر (الملف اتنزل)، والمشاركة، وتسجيلها في السجل، والمندوب بيشوفه في "مشترك معي" وفي البحث، والمندوب: 403 على تغيير المرحلة و403 على التعديل، والعنوان ما اتغيرش.

**باجات اتلقت في الـe2e واتصلحت:**
1. الزميل اللي اتشارك معاه عميل كان بيشوف شريط الحفظ وفورم الانتقال (والسيرفر بيرفض بـ403). السبب إن الصلاحية كانت بتتشيك على نوع السجل مش على السجل نفسه، ودلوقتي بتتشيك على السجل. عليها test.
2. قائمة تصدير Excel كانت بتتقص جوه رأس الصفحة، وبتخرج برا الشاشة على الموبايل.
3. نتايج البحث كانت بتظهر ورا رأس الصفحة، والـfocus ring كان متكرر جوه خانة البحث.
4. شريط الحفظ الثابت كان واخد ربع الشاشة على الموبايل، فبقى عادي على الموبايل.

**ملاحظة صلاحيات (مش باج):** مجموعة "الأفراد" (دليل الموظفين) مقروءة لكل الأدوار في الـconfig، فالبحث الشامل بيرجّع الموظفين لأي حد، زي ما الليستة بتعرضهم بالظبط.
