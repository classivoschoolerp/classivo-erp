# Demo

A public demo runs at https://demo.classivoerp.com. It uses a made-up school with made-up students, parents and staff.

## Logins

The password for every account is `password`.

| Role | Email | Where it goes |
|---|---|---|
| Admin | admin@example.com | Admin panel (`/admin`) |
| Student | student@example.com | Student portal (`/student`) |
| Parent | parent@example.com | Parent portal (`/parent`) |

The student login is linked to the first student in the demo data. The parent login is that student's main guardian.

## Reset

The demo is reset every night. Anything you add or change is removed at the next reset.

## Safeguards

The `demo-guard` package is active only when `APP_ENV=demo`. It blocks destructive edits, limits uploads and shows a banner about the nightly reset.

## Run your own demo

See [Install](/install). The demo school data comes from `DemoSchoolSeeder` in the demo app.
