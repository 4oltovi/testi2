# 🏛️ ДОНИШЁР — Системаи идоракунии донишгоҳ

**Системаи пурраи идоракунии раванди таълим барои муассисаҳои таҳсилоти олии касбии Ҷумҳурии Тоҷикистон**

## 📋 Тавсиф

Донишёр — платформаи мукаммали идоракунии донишгоҳ/коллеҷ, ки тамоми равандҳои таълимиро аз сабти донишҷӯ то содироти transcript пӯшонида медиҳад.

## 🛠️ Технологияҳо

- **Backend:** Laravel 12 / PHP 8.3+
- **Database:** MySQL 8.0
- **Cache/Session:** Redis
- **Frontend:** Bootstrap 5, Blade Templates, Alpine.js
- **Export:** PDF (DomPDF), Excel (Maatwebsite)

## 📊 Модулҳо

| # | Модул | Тавсиф |
|---|-------|--------|
| 1 | Authentication & RBAC | 10 нақш, 50+ иҷозат |
| 2 | Сохтори ташкилотӣ | Факултет, кафедра, ихтисос, гурӯҳ, фан |
| 3 | Донишҷӯён | CRUD, тағйири ҳолат, гузариш ба курс |
| 4 | Омӯзгорон | CRUD, борбандӣ, таърихи фаъолият |
| 5 | Журнали электронӣ | Давомот, баҳоҳо, рейтинг, имтиҳон |
| 6 | Рейтингҳо | Донишҷӯ, гурӯҳ, факултет, топ-10 |
| 7 | Имтиҳон/Тест | Онлайн бо таймер, автосабт, тасодуфӣ |
| 8 | Қарздории академӣ | Ошкор, такрорсупорӣ, ҳал |
| 9 | Transcript/GPA | Автоматикӣ ҳисоб, содирот |
| 10 | Ҳисоботҳо | 6 навъ бо содирот ба PDF/Excel |
| 11 | Аудит | Сабти тамоми амалҳо |

## 📐 Шкалаи баҳогузорӣ (Низоми кредитии Тоҷикистон)

| Баҳо | GPA | Фоиз | Анъанавӣ |
|:---:|:---:|:---:|:---:|
| A | 4.00 | 95-100 | Аъло |
| A- | 3.67 | 90-94 | Аъло |
| B+ | 3.33 | 85-89 | Хуб |
| B | 3.00 | 80-84 | Хуб |
| B- | 2.67 | 75-79 | Хуб |
| C+ | 2.33 | 70-74 | Қаноатбахш |
| C | 2.00 | 65-69 | Қаноатбахш |
| C- | 1.67 | 60-64 | Қаноатбахш |
| D+ | 1.33 | 55-59 | Қаноатбахш |
| D | 1.00 | 50-54 | Қаноатбахш |
| **Fx** | 0 | 45-49 | Ғайриқаноатбахш (такрорсупорӣ) |
| **F** | 0 | 0-44 | Ғайриқаноатбахш (дубора хондан) |

**Формулаи баҳои ниҳоӣ:**
```
Ниҳоӣ = R1×0.15 + R2×0.15 + КМ×0.30 + Имтиҳон×0.40
GPA = Σ(Gi × Ci) / Σ(Ci)
```

## 🚀 Насб кардан

```bash
# 1. Clone
git clone https://github.com/4oltovi/testi.git donishor
cd donishor

# 2. Dependencies
composer install
npm install && npm run build

# 3. Configuration
cp .env.example .env
php artisan key:generate

# 4. Database
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=InitialDataSeeder

# 5. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Start
php artisan serve
```

## 👤 Воридшавии аввалин

- **Логин:** `admin`
- **Парол:** `admin123456`

## 🔐 Нақшҳо

| Нақш | Дастрасӣ |
|------|----------|
| Super Admin | Ҳамаи модулҳо |
| Admin | Ҳама ба ғайр аз танзимоти система |
| Декан | Донишҷӯён, омӯзгорон, журнал, рейтинг, қарз, transcript, ҳисобот |
| Муовини декан | Мисли декан (танҳо хондан) |
| Мудири кафедра | Омӯзгорон, журнал, рейтинг, имтиҳон, ҳисобот |
| Бақайдгир | Сохтор, донишҷӯён, омӯзгорон, қарз, transcript |
| Омӯзгор | Журнал, имтиҳон, рейтинг |
| Муҳосиб | Донишҷӯён, қарздорон, ҳисобот |
| Донишҷӯ | Баҳоҳои худ, имтиҳон, transcript |
| Оператор | Дидани донишҷӯён, журнал, рейтинг |

## 📁 Сохтор

```
app/
├── Console/Commands/     # Artisan commands
├── Enums/               # PHP Enums (GradeScale, StudentStatus, etc.)
├── Http/
│   ├── Controllers/     # 26 controllers (Admin, Teacher, Student)
│   ├── Middleware/       # Role, Permission, Audit, Session
│   └── Requests/        # Form validation
├── Models/              # 40 Eloquent models
├── Services/            # Business logic (GPA, Grades, Debts, etc.)
└── Traits/              # Auditable, HasGradeCalculation
database/
├── migrations/          # 14 migration files (44+ tables)
└── seeders/             # Initial data + roles/permissions
resources/views/         # 52 Blade templates
routes/                  # web, admin, teacher, student
```

## ⚡ Оптимизатсия (500-600 корбари ҳамзамон)

- Redis барои кеш ва сессияҳо
- Queue workers барои ҳисобҳои вазнин
- Eager loading дар ҳама queries
- Pagination дар ҳама рӯйхатҳо
- Индексҳои оптималии DB
- OPcache + PHP-FPM tuning
- Nginx static files caching

## 📄 Лицензия

MIT License

---

**Сохта шуда бо ❤️ барои таълимоти Тоҷикистон**
