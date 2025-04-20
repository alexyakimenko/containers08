# IWNO8: Непрерывная интеграция с помощью Github Actions

## Цель работы

В рамках данной работы студенты научатся настраивать непрерывную интеграцию с помощью Github Actions.

## Задание

Создать Web приложение, написать тесты для него и настроить непрерывную интеграцию с помощью Github Actions на базе контейнеров.

## Подготовка

Для выполнения данной работы необходимо иметь установленный на компьютере Docker.

## Выполнение

Создаю репозиторий с именем `containers08` и копирую его на компьютер.

В директории `containers08` создаю папку `./site`, в которой будет располагаться веб-приложение на базе PHP.

## Создание Web приложения

Создайте в директории `./site` Web приложение на базе PHP со следующей структурой:

site
├── modules/
│   ├── database.php
│   └── page.php
├── templates/
│   └── index.tpl
├── styles/
│   └── style.css
├── config.php
└── index.php

Файл `modules/database.php` содержит класс `Database` для работы с базой данных. Для работы с базой данных использую `SQLite`. Класс включает следующие методы:

- `__construct($path)` - конструктор класса, принимает путь к файлу базы данных SQLite;
- `Execute($sql)` - выполняет SQL запрос;
- `Fetch($sql)` - выполняет SQL запрос и возвращает результат в виде ассоциативного массива.
- `Create($table, $data)` - создает запись в таблице $table с данными из ассоциативного массива $data и возвращает идентификатор созданной записи;
- `Read($table, $id)` - возвращает запись из таблицы $table по идентификатору $id;
- `Update($table, $id, $data)` - обновляет запись в таблице $table по идентификатору $id данными из ассоциативного массива $data;
- `Delete($table, $id)` - удаляет запись из таблицы $table по идентификатору $id.
- `Count($table)` - возвращает количество записей в таблице $table.

Файл `modules/page.php` содержит класс `Page` для работы со страницами. Класс включает следующие методы:

- `__construct($template)` — конструктор, принимает путь к шаблону страницы;
- `Render($data)` — отображает страницу, подставляя в шаблон данные из ассоциативного массива `$data`.

Файл `templates/index.tpl` содержит шаблон страницы.  
Файл `styles/style.css` содержит стили для страницы.  
Файл `index.php` содержит код для отображения страницы.

Файл `config.php` содержит настройки для подключения к базе данных.

## Подготовка SQL файла для базы данных

Создаю в корневом каталоге директорию `./sql`. В этой директории создаю файл `schema.sql` со следующим содержимым:

```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

## Создание тестов

Создаю в корневом каталоге директорию `./tests`. В созданном каталоге создаю файл `testframework.php` 

Создаю в каталоге `./tests` файл `tests.php`

## Создание Dockerfile

Создаю в корневом каталоге файл `Dockerfile` со следующим содержимым:

```Dockerfile
FROM php:7.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

## Настройка Github Actions

Создаю в корневом каталоге репозитория файл `.github/workflows/main.yml` со следующим содержимым:

```yml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

## Запуск и тестирование

Отправляю изменения в репозиторий и перехожу во вкладку **Actions**, чтобы убедиться, что тесты прошли успешно. Ожидаю завершения выполнения задачи.

## Ответы на вопросы

> **Q:** Что такое непрерывная интеграция?  
> **A:** Непрерывная интеграция (CI) — это подход в разработке, при котором разработчики часто добавляют изменения в общий репозиторий. После каждого изменения автоматически запускаются сборка и тесты. Это помогает быстрее находить и устранять ошибки, делая разработку проще и быстрее.

--- 

> **Q:** Для чего нужны юнит-тесты? Как часто их нужно запускать?  
> **A:** Юнит-тесты (модульные тесты) проверяют работу отдельных частей программы отдельно от остальных. Они помогают рано находить ошибки, безопасно вносить изменения в код, делают код чище и понятнее, а также служат документацией по использованию компонентов. Юнит-тесты нужно запускать при изменении кода перед сохранением в репозитории, при слиянии в основную ветку и автоматически при каждом push в процессе CI.

--- 

> **Q:** Что нужно изменить в файле .github/workflows/main.yml для того, чтобы тесты запускались при каждом создании запроса на слияние (Pull Request)?  
> **A:** Чтобы запускать тесты при создании `Pull Request`, нужно добавить эту секцию в блок `on`
>
> ```yml
> on:
>  push:
>    branches:
>      - main
>  pull_request:
>    branches:
>      - main
>```

--- 

> **Q:** Что нужно добавить в файл `.github/workflows/main.yml` для того, чтобы удалять созданные образы после выполнения тестов?  
> **A:** Для удаления Docker-образов после выполнения тестов нужно добавить следующий шаг в конце `job`:
>```yml
>   - name: Clean up Docker
>     run: |
>       docker rmi containers08 --force
>       docker volume rm database || true
>```


## Вывод

**Вывод:**

В ходе выполнения работы мы научились настраивать непрерывную интеграцию с использованием Github Actions, контейнеров Docker и написания тестов для Web приложения на PHP. Настройка Docker-контейнеров и использование Github Actions для автоматического запуска тестов при изменениях в репозитории повысили эффективность разработки и тестирования. Это позволяет автоматически проверять корректность кода, выявлять ошибки на ранних этапах и ускорить процесс разработки.
