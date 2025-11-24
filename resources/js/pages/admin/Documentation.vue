<template>
    <div class="documentation-page space-y-8">
        <!-- Заголовок -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h1 class="text-4xl font-bold text-foreground mb-2">📚 Документация системы управления ботами</h1>
            <p class="text-muted-foreground text-lg">
                Полное руководство по использованию админ-панели для создания и управления Telegram ботами
            </p>
        </div>

        <!-- Оглавление -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-2xl font-semibold text-foreground mb-4">📑 Содержание</h2>
            <nav class="space-y-2">
                <a
                    v-for="section in sections"
                    :key="section.id"
                    @click="scrollToSection(section.id)"
                    class="block text-accent hover:text-accent/80 cursor-pointer transition-colors"
                >
                    {{ section.title }}
                </a>
            </nav>
        </div>

        <!-- Раздел 1: Введение -->
        <section :id="sections[0].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[0].title }}</h2>
            <div class="prose prose-invert max-w-none">
                <p class="text-muted-foreground">
                    Система управления ботами позволяет создавать сложные интерактивные Telegram боты с визуальным редактором блоков.
                    Вы можете создавать диалоговые сценарии, собирать данные от пользователей, отправлять медиа-файлы и управлять взаимодействием.
                </p>
                <div class="bg-muted/30 rounded-lg p-4 mt-4">
                    <h3 class="text-lg font-semibold mb-2">Основные возможности:</h3>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                        <li>Визуальный редактор блоков с drag-and-drop</li>
                        <li>Создание сложных диалоговых сценариев</li>
                        <li>Сбор и сохранение данных пользователей</li>
                        <li>Отправка медиа-файлов (фото, видео, документы)</li>
                        <li>Интерактивные клавиатуры (inline и reply)</li>
                        <li>Чат с менеджером в реальном времени</li>
                        <li>Отслеживание прохождений пользователей</li>
                        <li>Управление ролями пользователей</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Раздел 2: Создание бота -->
        <section :id="sections[1].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[1].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Шаг 1: Получение токена бота</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Откройте Telegram и найдите <code class="bg-muted px-2 py-1 rounded">@BotFather</code></li>
                        <li>Отправьте команду <code class="bg-muted px-2 py-1 rounded">/newbot</code></li>
                        <li>Следуйте инструкциям для создания бота</li>
                        <li>Скопируйте полученный токен (формат: <code class="bg-muted px-2 py-1 rounded">123456789:ABCdefGHIjklMNOpqrsTUVwxyz</code>)</li>
                    </ol>
                </div>
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Шаг 2: Создание бота в системе</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Перейдите в раздел <strong>"Bots"</strong> в админ-панели</li>
                        <li>Нажмите кнопку <strong>"Создать бота"</strong></li>
                        <li>Заполните форму:
                            <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                <li><strong>Название:</strong> Имя бота (например, "Юридический помощник")</li>
                                <li><strong>Токен:</strong> Токен, полученный от BotFather</li>
                                <li><strong>Username:</strong> Username бота без @ (например, "lawyers_decision_bot")</li>
                            </ul>
                        </li>
                        <li>Нажмите <strong>"Сохранить"</strong></li>
                    </ol>
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 mt-4">
                        <p class="text-green-300">
                            <strong>✨ Автоматическая настройка:</strong> При создании нового бота автоматически создаются две команды:
                        </p>
                        <ul class="list-disc list-inside space-y-1 text-sm text-green-200/80 mt-2 ml-4">
                            <li><code class="bg-muted px-1 py-0.5 rounded">/start</code> — приветственное сообщение</li>
                            <li><code class="bg-muted px-1 py-0.5 rounded">/manager</code> — быстрая связь с менеджером</li>
                        </ul>
                        <p class="text-sm text-green-200/80 mt-2">
                            Вы можете изменить или удалить эти команды в редакторе диаграммы бота.
                        </p>
                    </div>
                </div>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                    <p class="text-blue-300">
                        <strong>💡 Совет:</strong> После создания бота система автоматически настроит webhook для получения обновлений от Telegram.
                    </p>
                </div>
            </div>
        </section>

        <!-- Раздел 3: Создание блоков -->
        <section :id="sections[2].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[2].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-6">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Что такое блок?</h3>
                    <p class="text-muted-foreground">
                        Блок — это единица логики бота, которая выполняет определенное действие. Блоки соединяются между собой,
                        образуя диалоговый сценарий (карту бота).
                    </p>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Создание блока</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Откройте бота в разделе <strong>"Bots"</strong></li>
                        <li>Нажмите на кнопку <strong>"Открыть диаграмму"</strong></li>
                        <li>В области диаграммы кликните правой кнопкой мыши или нажмите кнопку <strong>"Добавить блок"</strong></li>
                        <li>Выберите тип блока из списка</li>
                        <li>Заполните параметры блока в боковой панели</li>
                        <li>Переместите блок в нужное место на диаграмме (drag-and-drop)</li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Типы блоков</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="blockType in blockTypes" :key="blockType.value" class="bg-muted/30 rounded-lg p-4">
                            <h4 class="font-semibold text-foreground mb-2">{{ blockType.icon }} {{ blockType.label }}</h4>
                            <p class="text-sm text-muted-foreground">{{ blockType.description }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Соединение блоков</h3>
                    <p class="text-muted-foreground">
                        Блоки соединяются автоматически через поле <strong>"Следующий блок"</strong> (nextBlockId).
                        Вы также можете настроить переходы через кнопки inline-клавиатуры, указав <strong>"Целевой блок"</strong> (target_block_id) для каждой кнопки.
                    </p>
                    <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4">
                        <p class="text-yellow-300">
                            <strong>⚠️ Важно:</strong> Связи от кнопок отображаются желтыми стрелками на диаграмме.
                            Если целевой блок не найден, стрелка будет красной и пунктирной.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Раздел 4: Методы блоков -->
        <section :id="sections[3].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[3].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-6">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Группы методов</h3>
                    <div class="space-y-4">
                        <div v-for="group in methodGroups" :key="group.name" class="bg-muted/30 rounded-lg p-4">
                            <h4 class="font-semibold text-foreground mb-3">{{ group.icon }} {{ group.name }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div v-for="method in group.methods" :key="method.value" class="text-sm">
                                    <span class="text-foreground font-medium">{{ method.label }}</span>
                                    <span class="text-muted-foreground text-xs ml-2">({{ method.value }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Управление методами</h3>
                    <p class="text-muted-foreground">
                        В разделе <strong>"Настройки"</strong> → <strong>"Методы блоков"</strong> вы можете включать/отключать
                        доступные методы для использования в блоках. Это позволяет ограничить функциональность ботов или
                        скрыть неиспользуемые методы.
                    </p>
                </div>
            </div>
        </section>

        <!-- Раздел 5: Команды -->
        <section :id="sections[4].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[4].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Что такое команды?</h3>
                    <p class="text-muted-foreground">
                        Команды — это специальные блоки, которые активируются при отправке пользователем команды, начинающейся с <code class="bg-muted px-2 py-1 rounded">/</code>.
                        Например, <code class="bg-muted px-2 py-1 rounded">/start</code>, <code class="bg-muted px-2 py-1 rounded">/help</code>.
                    </p>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Создание команды</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>В диаграмме бота нажмите кнопку <strong>"Добавить команду"</strong></li>
                        <li>Введите название команды (например, <code class="bg-muted px-2 py-1 rounded">/start</code>)</li>
                        <li>Выберите метод выполнения (обычно <strong>"Отправить сообщение"</strong>)</li>
                        <li>Заполните параметры блока</li>
                        <li>Сохраните</li>
                    </ol>
                </div>

                <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                    <p class="text-green-300">
                        <strong>✅ Рекомендация:</strong> Каждый бот должен иметь команду <code class="bg-muted px-2 py-1 rounded">/start</code>,
                        которая является точкой входа для пользователей.
                    </p>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Команда /manager (по умолчанию)</h3>
                    <p class="text-muted-foreground">
                        При создании нового бота автоматически создается команда <code class="bg-muted px-2 py-1 rounded">/manager</code>,
                        которая позволяет пользователям быстро связаться с менеджером.
                    </p>
                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                        <p class="text-blue-300">
                            <strong>💡 Автоматическое создание:</strong> Команда <code class="bg-muted px-2 py-1 rounded">/manager</code>
                            создается автоматически при создании нового бота, если блоки не указаны вручную.
                            Вы можете изменить текст приветствия или удалить эту команду, если она не нужна.
                        </p>
                    </div>
                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="text-sm text-muted-foreground mb-2"><strong>Как это работает:</strong></p>
                        <ol class="list-decimal list-inside space-y-1 text-sm text-muted-foreground">
                            <li>Пользователь отправляет команду <code class="bg-muted px-1 py-0.5 rounded">/manager</code></li>
                            <li>Бот переключает пользователя в режим чата с менеджером</li>
                            <li>Все менеджеры получают уведомление о новом запросе</li>
                            <li>Менеджеры могут отвечать пользователю напрямую</li>
                            <li>Пользователь может выйти из режима чата командами: <code class="bg-muted px-1 py-0.5 rounded">/exit</code>, <code class="bg-muted px-1 py-0.5 rounded">/back</code> или <code class="bg-muted px-1 py-0.5 rounded">/menu</code></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Раздел 6: Сбор данных -->
        <section :id="sections[5].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[5].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Блок "Вопрос"</h3>
                    <p class="text-muted-foreground">
                        Блок типа <strong>"Вопрос"</strong> позволяет собирать данные от пользователей. При создании блока укажите:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                        <li><strong>Текст вопроса:</strong> Сообщение, которое увидит пользователь</li>
                        <li><strong>Ключ данных:</strong> Уникальный ключ для сохранения ответа (например, <code class="bg-muted px-2 py-1 rounded">fio</code>, <code class="bg-muted px-2 py-1 rounded">phone</code>)</li>
                        <li><strong>Тип ответа:</strong> Текст, число, телефон, email и т.д.</li>
                        <li><strong>Валидация:</strong> Правила проверки введенных данных</li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Пример использования</h3>
                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="text-sm text-muted-foreground mb-2"><strong>Сценарий:</strong> Сбор ФИО пользователя</p>
                        <ol class="list-decimal list-inside space-y-1 text-sm text-muted-foreground">
                            <li>Создайте блок типа <strong>"Вопрос"</strong></li>
                            <li>Текст вопроса: <code class="bg-muted px-1 py-0.5 rounded">"Введите ваше ФИО:"</code></li>
                            <li>Ключ данных: <code class="bg-muted px-1 py-0.5 rounded">fio</code></li>
                            <li>Тип ответа: <code class="bg-muted px-1 py-0.5 rounded">text</code></li>
                            <li>Укажите следующий блок для продолжения диалога</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Раздел 7: Чат с менеджером -->
        <section :id="sections[6].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[6].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Как это работает</h3>
                    <p class="text-muted-foreground">
                        Блок типа <strong>"Чат с менеджером"</strong> переключает пользователя в режим прямого общения с менеджером.
                        Все сообщения пользователя пересылаются менеджерам, а ответы менеджеров — пользователю.
                        Вся переписка автоматически сохраняется в базе данных для последующего просмотра.
                    </p>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Настройка</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Создайте блок типа <strong>"Чат с менеджером"</strong></li>
                        <li>Укажите приветственное сообщение для пользователя</li>
                        <li>В разделе <strong>"Пользователи ботов"</strong> назначьте пользователям роль <strong>"Менеджер"</strong></li>
                        <li>Все менеджеры автоматически получат уведомление о новом запросе на чат</li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Поддерживаемые типы медиа</h3>
                    <p class="text-muted-foreground">
                        В чате с менеджером поддерживаются все типы медиа, которые можно отправлять в Telegram:
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                        <div v-for="mediaType in mediaTypes" :key="mediaType" class="bg-muted/30 rounded p-2 text-center text-sm">
                            {{ mediaType }}
                        </div>
                    </div>
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 mt-4">
                        <p class="text-green-300">
                            <strong>✨ Новое:</strong> Все медиа-файлы (фото, видео, документы, аудио, голосовые сообщения, стикеры, анимации)
                            автоматически сохраняются и отображаются в разделе <strong>"Диалоги"</strong> для просмотра полной истории переписки.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Просмотр диалогов</h3>
                    <p class="text-muted-foreground">
                        В разделе <strong>"Диалоги"</strong> вы можете просмотреть полную историю всех переписок с менеджерами:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                        <li><strong>Список диалогов:</strong> Все активные и завершенные диалоги с фильтрацией по боту и менеджеру</li>
                        <li><strong>Полная история:</strong> Все сообщения в хронологическом порядке с указанием направления (пользователь ↔ менеджер)</li>
                        <li><strong>Медиа-файлы:</strong> Просмотр всех отправленных фото, видео, документов, аудио и других файлов</li>
                        <li><strong>Информация о пользователе:</strong> Данные пользователя, с которым ведется диалог</li>
                        <li><strong>Информация о менеджере:</strong> Данные менеджера, который отвечает на запросы</li>
                    </ul>
                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4 mt-4">
                        <p class="text-blue-300">
                            <strong>💡 Совет:</strong> Используйте фильтры в разделе "Диалоги" для быстрого поиска нужной переписки.
                            Вы можете фильтровать по боту, менеджеру или искать по имени пользователя.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Выход из режима чата</h3>
                    <p class="text-muted-foreground">
                        Пользователь может выйти из режима чата с менеджером, отправив одну из команд:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-muted-foreground ml-4">
                        <li><code class="bg-muted px-2 py-1 rounded">/exit</code> — Выйти из чата</li>
                        <li><code class="bg-muted px-2 py-1 rounded">/back</code> — Вернуться назад</li>
                        <li><code class="bg-muted px-2 py-1 rounded">/menu</code> — Вернуться в меню</li>
                    </ul>
                    <p class="text-sm text-muted-foreground mt-2">
                        При выходе из режима чата пользователю автоматически удаляется временная клавиатура с командами,
                        и он возвращается к обычному взаимодействию с ботом.
                    </p>
                </div>
            </div>
        </section>

        <!-- Раздел 8: Роли пользователей -->
        <section :id="sections[7].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[7].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Типы ролей</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="role in roles" :key="role.value" class="bg-muted/30 rounded-lg p-4">
                            <h4 class="font-semibold text-foreground mb-2">{{ role.icon }} {{ role.name }}</h4>
                            <p class="text-sm text-muted-foreground">{{ role.description }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Назначение ролей</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Перейдите в раздел <strong>"Пользователи ботов"</strong></li>
                        <li>Найдите пользователя или создайте нового</li>
                        <li>Выберите нужную роль из выпадающего списка</li>
                        <li>Роль сохранится автоматически</li>
                    </ol>
                </div>

                <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                    <p class="text-blue-300">
                        <strong>💡 Примечание:</strong> Новые пользователи автоматически получают роль <strong>"Пользователь"</strong>.
                        Менеджеры могут отвечать на запросы в чате с менеджером.
                    </p>
                </div>
            </div>
        </section>

        <!-- Раздел 9: Прохождения ботов -->
        <section :id="sections[8].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[8].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Что записывается?</h3>
                    <p class="text-muted-foreground">
                        Система автоматически отслеживает и сохраняет все взаимодействия пользователей с ботом:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                        <li><strong>Сессии:</strong> Информация о каждом пользователе и его сессии (дата начала, последняя активность, статус)</li>
                        <li><strong>Шаги:</strong> Каждый блок, который прошел пользователь, с указанием времени и типа взаимодействия</li>
                        <li><strong>Данные:</strong> Все собранные данные (ответы на вопросы, введенные значения)</li>
                        <li><strong>Файлы:</strong> Все загруженные пользователем файлы (документы, фото, видео) с метаданными</li>
                        <li><strong>Сообщения чата с менеджером:</strong> Полная история переписки с сохранением всех медиа-файлов</li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Просмотр прохождений</h3>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Перейдите в раздел <strong>"Прохождения ботов"</strong></li>
                        <li>Выберите бота из фильтра (или оставьте "Все боты")</li>
                        <li>Нажмите на сессию для просмотра деталей</li>
                        <li>Вы увидите:
                            <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                <li>Информацию о пользователе (имя, username, ID чата)</li>
                                <li>Хронологию всех шагов с указанием блоков и времени</li>
                                <li>Собранные данные с ключами и значениями</li>
                                <li>Загруженные файлы с возможностью скачивания</li>
                                <li>Ссылку на историю чата с менеджером (если был)</li>
                            </ul>
                        </li>
                    </ol>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Раздел "Диалоги"</h3>
                    <p class="text-muted-foreground">
                        В разделе <strong>"Диалоги"</strong> вы можете просмотреть все переписки с менеджерами отдельно от общих прохождений:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                        <li><strong>Список всех диалогов:</strong> Активные и завершенные диалоги с информацией о пользователе и менеджере</li>
                        <li><strong>Фильтрация:</strong> Поиск по боту, менеджеру или имени пользователя</li>
                        <li><strong>Просмотр медиа:</strong> Все фото, видео, документы, аудио и другие файлы отображаются прямо в диалоге</li>
                        <li><strong>Хронология:</strong> Сообщения отображаются в порядке отправки с указанием направления</li>
                        <li><strong>Метаданные:</strong> Информация о размере файлов, длительности видео/аудио, типах медиа</li>
                    </ul>
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4 mt-4">
                        <p class="text-green-300">
                            <strong>✨ Улучшение:</strong> Все медиа-файлы в диалогах теперь загружаются через безопасный прокси-сервер,
                            что обеспечивает корректное отображение даже для файлов с особыми символами в идентификаторах.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Статистика</h3>
                    <p class="text-muted-foreground">
                        В разделе <strong>"Прохождения ботов"</strong> доступна статистика:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground ml-4">
                        <li>Общее количество сессий</li>
                        <li>Активные сессии</li>
                        <li>Завершенные сессии</li>
                        <li>Среднее время прохождения</li>
                        <li>Популярные блоки</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Раздел 10: Советы и рекомендации -->
        <section :id="sections[9].id" class="bg-card rounded-lg border border-border p-6 space-y-4">
            <h2 class="text-3xl font-semibold text-foreground">{{ sections[9].title }}</h2>
            <div class="prose prose-invert max-w-none space-y-4">
                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Лучшие практики</h3>
                    <div class="space-y-3">
                        <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                            <h4 class="font-semibold text-green-300 mb-2">✅ Рекомендуется:</h4>
                            <ul class="list-disc list-inside space-y-1 text-sm text-green-200/80">
                                <li>Всегда создавайте команду <code class="bg-muted px-1 py-0.5 rounded">/start</code> как точку входа</li>
                                <li>Используйте понятные названия для блоков и ключей данных</li>
                                <li>Тестируйте бота перед публикацией</li>
                                <li>Сохраняйте блоки после каждого изменения</li>
                                <li>Используйте inline-клавиатуры для навигации</li>
                                <li>Добавляйте валидацию для полей ввода</li>
                            </ul>
                        </div>
                        <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
                            <h4 class="font-semibold text-red-300 mb-2">❌ Избегайте:</h4>
                            <ul class="list-disc list-inside space-y-1 text-sm text-red-200/80">
                                <li>Создания циклов без выхода (бесконечные циклы)</li>
                                <li>Использования одинаковых ключей данных в разных блоках</li>
                                <li>Оставления блоков без следующего блока (тупики)</li>
                                <li>Слишком длинных текстов в сообщениях</li>
                                <li>Игнорирования ошибок валидации</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xl font-semibold">Отладка</h3>
                    <p class="text-muted-foreground">
                        Если бот не работает как ожидается:
                    </p>
                    <ol class="list-decimal list-inside space-y-2 text-muted-foreground">
                        <li>Проверьте, что все блоки сохранены</li>
                        <li>Убедитесь, что команда <code class="bg-muted px-2 py-1 rounded">/start</code> существует</li>
                        <li>Проверьте логи в разделе "Прохождения ботов"</li>
                        <li>Убедитесь, что webhook настроен правильно</li>
                        <li>Проверьте, что токен бота верный</li>
                    </ol>
                </div>
            </div>
        </section>

        <!-- Кнопка "Наверх" -->
        <div class="flex justify-center">
            <button
                @click="scrollToTop"
                class="px-6 py-3 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors"
            >
                ↑ Наверх
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const sections = ref([
    { id: 'introduction', title: '1. Введение' },
    { id: 'creating-bot', title: '2. Создание бота' },
    { id: 'creating-blocks', title: '3. Создание блоков' },
    { id: 'block-methods', title: '4. Методы блоков' },
    { id: 'commands', title: '5. Команды' },
    { id: 'data-collection', title: '6. Сбор данных' },
    { id: 'manager-chat', title: '7. Чат с менеджером' },
    { id: 'user-roles', title: '8. Роли пользователей' },
    { id: 'bot-sessions', title: '9. Прохождения ботов' },
    { id: 'tips', title: '10. Советы и рекомендации' },
])

const blockTypes = ref([
    { value: 'sendMessage', label: 'Отправить сообщение', icon: '💬', description: 'Отправка текстового сообщения пользователю' },
    { value: 'inlineKeyboard', label: 'Inline клавиатура', icon: '⌨️', description: 'Интерактивные кнопки под сообщением' },
    { value: 'replyKeyboard', label: 'Reply клавиатура', icon: '⌨️', description: 'Клавиатура вместо стандартной' },
    { value: 'question', label: 'Вопрос', icon: '❓', description: 'Сбор данных от пользователя' },
    { value: 'sendDocument', label: 'Отправить документ', icon: '📄', description: 'Отправка файла пользователю' },
    { value: 'managerChat', label: 'Чат с менеджером', icon: '💬', description: 'Переключение на общение с менеджером' },
])

const methodGroups = ref([
    {
        name: 'Сообщения',
        icon: '💬',
        methods: [
            { value: 'sendMessage', label: 'Отправить сообщение' },
            { value: 'sendDice', label: '🎲 Отправить кубик' },
            { value: 'sendPoll', label: '📊 Отправить опрос' },
            { value: 'sendVenue', label: '📍 Отправить локацию' },
            { value: 'sendContact', label: '👤 Отправить контакт' },
        ]
    },
    {
        name: 'Медиа',
        icon: '🖼️',
        methods: [
            { value: 'sendPhoto', label: '📷 Фото' },
            { value: 'sendVideo', label: '🎥 Видео' },
            { value: 'sendDocument', label: '📄 Документ' },
            { value: 'sendAudio', label: '🎵 Аудио' },
            { value: 'sendVoice', label: '🎤 Голосовое' },
            { value: 'sendVideoNote', label: '🎬 Видео-кружок' },
            { value: 'sendAnimation', label: '🎞️ Анимация/GIF' },
            { value: 'sendSticker', label: '😊 Стикер' },
            { value: 'sendLocation', label: '📍 Локация' },
            { value: 'sendMediaGroup', label: '🖼️ Группа медиа' },
        ]
    },
    {
        name: 'Клавиатуры',
        icon: '⌨️',
        methods: [
            { value: 'inlineKeyboard', label: 'Inline клавиатура' },
            { value: 'replyKeyboard', label: 'Reply клавиатура' },
        ]
    },
    {
        name: 'Действия',
        icon: '⚙️',
        methods: [
            { value: 'question', label: 'Вопрос (сбор данных)' },
            { value: 'managerChat', label: 'Чат с менеджером' },
        ]
    },
])

const roles = ref([
    {
        value: 'admin',
        name: 'Администратор',
        icon: '👑',
        description: 'Полный доступ ко всем функциям системы и ботам'
    },
    {
        value: 'manager',
        name: 'Менеджер',
        icon: '👔',
        description: 'Может отвечать на запросы в чате с менеджером, просматривать прохождения'
    },
    {
        value: 'user',
        name: 'Пользователь',
        icon: '👤',
        description: 'Обычный пользователь бота, взаимодействует с ботом'
    },
])

const mediaTypes = ref([
    'Текст',
    'Фото',
    'Видео',
    'Документы',
    'Аудио',
    'Голосовые',
    'Видео-кружки',
    'Анимации',
    'Стикеры',
    'Контакты',
    'Локации',
    'Места',
])

const scrollToSection = (id) => {
    const element = document.getElementById(id)
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
    // Плавная прокрутка к разделу при загрузке с якорем
    const hash = window.location.hash
    if (hash) {
        const id = hash.substring(1)
        setTimeout(() => scrollToSection(id), 100)
    }
})
</script>

<style scoped>
.documentation-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.prose code {
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.prose ul,
.prose ol {
    margin-left: 1.5rem;
}

.prose li {
    margin-top: 0.5rem;
}
</style>
