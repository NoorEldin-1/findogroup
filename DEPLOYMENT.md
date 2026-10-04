# الاتصال بالسيرفر، السحب، والنشر

## الاتصال

السيرفر: `76.13.14.225` (Hostinger، AlmaLinux 9، CyberPanel + OpenLiteSpeed).
الدخول بمفتاح SSH عبر الاسم المختصر `findo` في `~/.ssh/config`:

```
Host findo
    HostName 76.13.14.225
    User root
    IdentityFile ~/.ssh/findo_deploy
    IdentitiesOnly yes
    ServerAliveInterval 30
```

جرّب الاتصال: `ssh findo hostname`. على جهاز جديد: أنشئ مفتاحًا وأضف الـpublic key إلى `/root/.ssh/authorized_keys` على السيرفر.

| | المسار / القيمة |
|---|---|
| المشروع | `/home/findogroup.com/app` (الـdocRoot هو `app/public`) |
| PHP | `/usr/local/lsws/lsphp84/bin/php` (8.4). أمر `php` في النظام هو 8.3، فلا تستخدمه |
| قاعدة البيانات | PostgreSQL 17، قاعدة `findo_production` |
| Redis | خدمة `findo-redis` على المنفذ 56389 |
| الطوابير | `findo-queue.service` (systemd) |
| المهام المجدولة | `findo-scheduler.timer` (كل دقيقة) |
| النسخ الاحتياطي | `findo-backup.timer` يوميًا 01:15 UTC، ويحفظ في `/home/findogroup.com/backups` (قاعدة البيانات + private + .env، بدون الكود) |
| إعدادات الـvhost | `/usr/local/lsws/conf/vhosts/findogroup.com/vhost.conf` |

## الفروع

- `production`: الكود الموجود على السيرفر بالضبط. لا تعدّل فيه يدويًا.
- `main`: التطوير.

## الأوامر (تعمل من Git Bash)

| الأمر | ماذا يفعل | هل يعدّل السيرفر؟ |
|---|---|---|
| `bin/server-status` | يقارن ملفات السيرفر بفرع `production`، ويعرض حالة الخدمات والموقع | لا |
| `bin/server-pull` | يسحب أي تعديل حصل على السيرفر مباشرة، ويسجله على `production`، ثم يدمجه في فرعك | لا |
| `bin/server-pull-data --db --cms --media` | ينسخ dump لقاعدة البيانات، وملفات `storage/app/cms`، والوسائط إلى جهازك | لا |
| `bin/server-deploy --dry-run` | يشغّل كل الفحوصات والـbuild بدون رفع أي شيء | لا |
| `bin/server-deploy` | ينشر `HEAD` | **نعم** |
| `bin/server-rollback <stamp>` | يرجّع الكود لما كان عليه قبل نشر معيّن | **نعم** |

### ما يفعله `server-deploy`

1. يرفض النشر لو فيه تعديلات غير محفوظة في git، أو لو السيرفر عليه تعديلات لم تُسحب بعد (شغّل `bin/server-pull` أولًا).
2. يبني الـassets على جهازك (`npm run build`)، لأن `public/build` غير موجود في git.
3. يرفع الكود (`git archive HEAD`) ونسخة الـbuild إلى `/var/lib/findo-deploy/releases/<stamp>`.
4. على السيرفر، بالترتيب:
   - ياخد نسخة احتياطية من الكود الحالي في `/var/lib/findo-deploy/backups/<stamp>-before.tgz`.
   - ينسخ الملفات ويحذف الملفات اللي اتشالت من git.
   - يرجّع ملكية الملفات للمستخدم `findo`.
   - يشغّل `composer install --no-dev`، ثم `migrate --force`، ثم `optimize`، ثم `queue:restart`.
   - يعيد تشغيل LiteSpeed (graceful).
5. يتأكد إن الموقع بيرجّع HTTP 200، وينقل فرع `production` إلى `HEAD`، ويعمل tag باسم `deploy-<stamp>`.

الـrollback يرجّع الكود فقط. **الـmigrations لا تُعكس**، فاعمل نسخة من قاعدة البيانات قبل أي نشر فيه migrations.

## ما ليس في git

| | المكان | كيف تحصل عليه |
|---|---|---|
| الوسائط القديمة من WordPress (حوالي 1.4GB) | `public/wp-content` | `bin/server-pull-data --media` |
| حزمة عرض الـCMS وبيانات الـseed (حوالي 98MB) | `storage/app/cms` | `bin/server-pull-data --cms` |
| قاعدة البيانات | PostgreSQL | `bin/server-pull-data --db`، ثم `pg_restore` |
| الأسرار | `.env` | تُنسخ يدويًا. انسخ `.env.example` واملأه |

## التشغيل المحلي

المتطلبات: PHP 8.4 (المشروع يحتاج 8.3 على الأقل)، وComposer، وNode 20.19 أو أحدث، وPostgreSQL، وامتدادات PHP: `pdo_pgsql` و`redis`.

```bash
composer install
cp .env.example .env && php artisan key:generate
bin/server-pull-data --db --cms --media
createdb findo_local && pg_restore --no-owner -d findo_local .server-data/db/<file>.dump
npm ci && npm run build
php artisan serve --port=8080
```
