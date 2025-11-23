# 🔧 Ручное исправление baseUrl на сервере

## Проблема
Файл не обновился через Git, нужно исправить вручную.

## Решение

Выполните на сервере:

```bash
cd /home/d/dsc23ytp/parser-auto.site-access.ru/public_html

# Сделайте backup
cp app/Services/ExtendedTelegraph.php app/Services/ExtendedTelegraph.php.bak

# Исправьте файл
sed -i 's/protected string \$baseUrl/protected ?string $baseUrl/' app/Services/ExtendedTelegraph.php

# Проверьте результат
grep "protected.*baseUrl" app/Services/ExtendedTelegraph.php
```

Должно быть:
```php
protected ?string $baseUrl = 'https://api.telegram.org/bot';
```

## Альтернатива: Редактирование через nano

```bash
nano app/Services/ExtendedTelegraph.php
```

Найдите строку 18 и измените:
- Было: `protected string $baseUrl = 'https://api.telegram.org/bot';`
- Стало: `protected ?string $baseUrl = 'https://api.telegram.org/bot';`

Сохраните: Ctrl+O, Enter, Ctrl+X

## После исправления

Очистите кеш еще раз:

```bash
php8.2 artisan config:clear
php8.2 artisan cache:clear
```

## Проверка

Проверьте, что исправление применено:

```bash
grep -A 1 "protected.*baseUrl" app/Services/ExtendedTelegraph.php
```

Должно быть `?string`, а не просто `string`.

