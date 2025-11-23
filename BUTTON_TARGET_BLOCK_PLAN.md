# План реализации: Настройка целевого блока для каждой кнопки inline-клавиатуры

## Задача

Добавить возможность в настройках блока указывать для каждой кнопки inline-клавиатуры, на какой блок она должна переходить при нажатии.

---

## Текущая ситуация

### Что есть сейчас:
- В `BlockSettingsSidebar.vue` есть настройка inline-клавиатуры
- Каждая кнопка имеет поля: `text`, `callback_data`, `url`
- Есть общее поле `nextBlockId` для всего блока (не для каждой кнопки)
- Есть функция `availableBlocksForSelection` - список доступных блоков для выбора
- Есть функция `getBlockDisplayName` - получение отображаемого имени блока

### Проблема:
- Нельзя указать, куда ведет каждая конкретная кнопка
- Все кнопки ведут к одному блоку через общий `nextBlockId`

---

## План решения

### Этап 1: Расширение структуры данных кнопки

#### 1.1. Добавить поле `target_block_id` в структуру кнопки

**Текущая структура кнопки:**
```javascript
{
  text: "Текст кнопки",
  callback_data: "menu_1",
  url: null
}
```

**Новая структура кнопки:**
```javascript
{
  text: "Текст кнопки",
  callback_data: "menu_1",  // Или можно автоматически = target_block_id
  url: null,
  target_block_id: "3"  // ← НОВОЕ ПОЛЕ
}
```

#### 1.2. Обновить функцию инициализации кнопки

В `BlockSettingsSidebar.vue`:
- Метод `addInlineKeyboardButton()` - добавить `target_block_id: null`
- Метод `initMethodData()` - инициализировать новое поле для всех кнопок

---

### Этап 2: UI для настройки целевого блока

#### 2.1. Добавить поле выбора блока для каждой кнопки

**Место:** В секции настройки inline-клавиатуры (около строк 398-435)

**Что добавить:**
- Select/dropdown с списком доступных блоков
- Показывать рядом с полями `callback_data` и `url`
- Визуально группировать с другими настройками кнопки

**Структура UI:**
```
[Текст кнопки]
  ├─ callback_data: [input]
  ├─ URL: [input]
  └─ Целевой блок: [select] ← НОВОЕ
      ├─ (Не выбран)
      ├─ Блок 1: Приветствие (ID: 1)
      ├─ Блок 3: Бранч 1: Начало (ID: 3)
      └─ ...
```

#### 2.2. Улучшение UX

**Опции:**
1. Автозаполнение `callback_data` = `target_block_id` (если не указан)
2. Предупреждение, если кнопка не имеет ни `target_block_id`, ни `url`
3. Визуальная индикация кнопок без целевого блока
4. Возможность фильтровать список блоков (исключать текущий блок)

---

### Этап 3: Валидация и сохранение

#### 3.1. Валидация данных

**Правила:**
- Кнопка должна иметь либо `target_block_id`, либо `url`, либо `switch_inline_query`
- `target_block_id` должен указывать на существующий блок
- Нельзя указать `target_block_id` на сам текущий блок (защита от циклов)

#### 3.2. Сохранение в структуру блока

**При сохранении блока:**
- Сохранять `target_block_id` для каждой кнопки
- Если `target_block_id` указан, но `callback_data` пуст - автозаполнять `callback_data = target_block_id`
- Или наоборот: если `callback_data` = ID блока, автозаполнять `target_block_id`

---

### Этап 4: Обновление обработчика карты

#### 4.1. Изменить логику `findBlockByCallbackData()`

**Текущая логика:**
- Ищет блок с inline_keyboard, содержащим кнопку с callback_data
- Использует общий nextBlockId блока

**Новая логика:**
1. Найти блок с inline_keyboard, содержащим кнопку с callback_data
2. Извлечь `target_block_id` из метаданных этой кнопки
3. Если `target_block_id` найден → перейти к блоку с этим ID
4. Если нет → использовать старую логику (nextBlockId или поиск по ID)

#### 4.2. Альтернативный вариант обработки

**Вариант A:** Использовать `callback_data = target_block_id`
- При сохранении блока: если указан `target_block_id`, установить `callback_data = target_block_id`
- При обработке: `callback_data` напрямую = ID блока → искать блок по ID

**Вариант B:** Хранить маппинг в метаданных
- В блоке хранить `metadata.button_mapping = { "callback_data": "target_block_id" }`
- При обработке искать в маппинге

**Рекомендация:** Вариант A проще и надежнее

---

### Этап 5: Визуализация в диаграмме

#### 5.1. Отображение связей кнопок

**Что добавить:**
- Показывать связи от кнопок к целевым блокам
- Разные цвета для разных кнопок
- Подписи на связях (название кнопки)

**Сложность:** Средняя (нужно обновить `BotDiagram.vue`)

#### 5.2. Индикация в блоке

**В `DiagramBlock.vue`:**
- Показывать рядом с кнопками стрелки к целевым блокам
- Или иконки с подсказкой "ведет к блоку X"

---

## Детальный план реализации

### Шаг 1: Обновление структуры данных

**Файл:** `resources/js/components/admin/BlockSettingsSidebar.vue`

1. **Обновить метод `initMethodData()`:**
   - При инициализации inline_keyboard добавлять поле `target_block_id: null` для каждой кнопки

2. **Обновить метод `addInlineKeyboardButton()`:**
   - Добавлять новую кнопку с полем `target_block_id: null`

---

### Шаг 2: Добавление UI поля

**Файл:** `resources/js/components/admin/BlockSettingsSidebar.vue`

**Место:** Внутри цикла кнопок (около строк 398-435)

**Добавить:**
```vue
<!-- После полей callback_data и url -->
<div>
    <label class="text-xs text-muted-foreground mb-1 block">
        Целевой блок (переход по нажатию)
    </label>
    <select
        v-model="methodData.inline_keyboard[rowIndex][btnIndex].target_block_id"
        @change="updateCallbackDataFromTarget(rowIndex, btnIndex)"
        class="w-full h-8 px-2 text-xs border rounded bg-background"
    >
        <option :value="null">(Не выбран)</option>
        <option 
            v-for="block in availableBlocksForButton(rowIndex, btnIndex)" 
            :key="block.id" 
            :value="block.id"
        >
            {{ getBlockDisplayName(block) }} (ID: {{ block.id }})
        </option>
    </select>
    <p class="text-xs text-muted-foreground mt-1">
        При нажатии на кнопку произойдет переход к выбранному блоку
    </p>
</div>
```

---

### Шаг 3: Компьютированные свойства и методы

**Файл:** `resources/js/components/admin/BlockSettingsSidebar.vue`

1. **Создать функцию `availableBlocksForButton()`:**
   ```javascript
   const availableBlocksForButton = (rowIndex, btnIndex) => {
       // Получить список всех блоков, исключая текущий
       // Можно получить из props (нужно передать blocks из BotCard)
       return availableBlocksForSelection.value.filter(block => 
           block.id !== localBlock.value.id
       )
   }
   ```

2. **Создать функцию `updateCallbackDataFromTarget()`:**
   ```javascript
   const updateCallbackDataFromTarget = (rowIndex, btnIndex) => {
       const button = methodData.value.inline_keyboard[rowIndex][btnIndex]
       // Если target_block_id указан, но callback_data пуст
       if (button.target_block_id && !button.callback_data) {
           button.callback_data = button.target_block_id
       }
   }
   ```

3. **Создать функцию автозаполнения обратного:**
   ```javascript
   const updateTargetFromCallbackData = (rowIndex, btnIndex) => {
       const button = methodData.value.inline_keyboard[rowIndex][btnIndex]
       // Если callback_data = ID блока (число или строка с числом)
       if (button.callback_data && /^\d+$/.test(button.callback_data)) {
           // Проверить, существует ли блок с таким ID
           const blockExists = availableBlocksForSelection.value.some(
               b => b.id === button.callback_data || b.id === parseInt(button.callback_data)
           )
           if (blockExists && !button.target_block_id) {
               button.target_block_id = button.callback_data
           }
       }
   }
   ```

---

### Шаг 4: Передача списка блоков в компонент

**Файл:** `resources/js/components/admin/BotCard.vue`

**Статус:** ✅ УЖЕ РЕАЛИЗОВАНО

Список блоков уже передается через prop `available-blocks`:
```vue
<!-- В BotCard.vue (строка 163) -->
<BlockSettingsSidebar
    :show="showSettingsSidebar"
    :selected-block="selectedBlock"
    :available-blocks="blocks"  // ← Уже передается
    @close="showSettingsSidebar = false"
    @save="handleBlockSave"
/>
```

И в `BlockSettingsSidebar.vue` уже есть prop `availableBlocks` и computed `availableBlocksForSelection`, который фильтрует блоки, исключая текущий.

**Что нужно сделать:**
- Использовать существующий `availableBlocksForSelection` для выбора целевых блоков в кнопках
- Функция `availableBlocksForButton()` может быть просто алиасом для `availableBlocksForSelection`

---

### Шаг 5: Обновление логики обработки

**Файл:** `app/Services/BotMapHandler.php`

**Метод:** `findBlockByCallbackData()` (строки 486-509)

**Текущая логика:**
1. Ищет блок с inline_keyboard, содержащим кнопку с callback_data
2. Если находит кнопку, берет `nextBlockId` всего блока (не кнопки)
3. Если не находит, пытается найти блок с ID = callback_data

**Новая логика:**
1. **Сначала:** Проверить, является ли `callback_data` прямым ID блока
2. **Затем:** Перебрать все блоки с `method === 'inlineKeyboard'`
3. Для каждого блока проверить кнопки в `inline_keyboard`
4. Найти кнопку с нужным `callback_data`
5. **Извлечь `target_block_id` из найденной кнопки**
6. Если `target_block_id` указан → найти и вернуть блок с этим ID
7. Если нет `target_block_id` → использовать старую логику (nextBlockId блока или ID блока)

**Обновленный код:**
```php
protected function findBlockByCallbackData(array $blocks, string $callbackData): ?array
{
    // Шаг 1: Проверяем, является ли callback_data прямым ID блока
    $directBlock = $this->findBlockById($blocks, $callbackData);
    if ($directBlock) {
        Log::debug('Found block by direct ID match', [
            'callback_data' => $callbackData,
            'block_id' => $directBlock['id'] ?? null,
        ]);
        return $directBlock;
    }

    // Шаг 2: Ищем в кнопках inline-клавиатуры
    foreach ($blocks as $block) {
        if ($block['method'] === 'inlineKeyboard') {
            $inlineKeyboard = $block['method_data']['inline_keyboard'] ?? [];
            foreach ($inlineKeyboard as $row) {
                foreach ($row as $button) {
                    if (($button['callback_data'] ?? null) === $callbackData) {
                        // Приоритет 1: Проверяем target_block_id кнопки
                        $targetBlockId = $button['target_block_id'] ?? null;
                        if ($targetBlockId) {
                            $targetBlock = $this->findBlockById($blocks, $targetBlockId);
                            if ($targetBlock) {
                                Log::debug('Found block by button target_block_id', [
                                    'callback_data' => $callbackData,
                                    'target_block_id' => $targetBlockId,
                                ]);
                                return $targetBlock;
                            }
                        }

                        // Приоритет 2: Используем nextBlockId блока (старая логика)
                        $nextBlockId = $block['nextBlockId'] ?? null;
                        if ($nextBlockId) {
                            $nextBlock = $this->findBlockById($blocks, $nextBlockId);
                            if ($nextBlock) {
                                Log::debug('Found block by parent nextBlockId', [
                                    'callback_data' => $callbackData,
                                    'next_block_id' => $nextBlockId,
                                ]);
                                return $nextBlock;
                            }
                        }

                        // Приоритет 3: Возвращаем сам блок с меню
                        Log::debug('Returning parent block (no target specified)', [
                            'callback_data' => $callbackData,
                            'block_id' => $block['id'] ?? null,
                        ]);
                        return $block;
                    }
                }
            }
        }
    }

    // Если ничего не найдено
    Log::warning('Block not found by callback_data', [
        'callback_data' => $callbackData,
    ]);
    return null;
}
```

---

### Шаг 6: Валидация и предупреждения

**Файл:** `resources/js/components/admin/BlockSettingsSidebar.vue`

1. **Валидация при сохранении:**
   - Проверять, что каждая кнопка имеет либо `target_block_id`, либо `url`
   - Показывать предупреждение для кнопок без целевого действия

2. **Визуальные индикаторы:**
   - Подсвечивать кнопки без целевого блока красной рамкой
   - Показывать иконку предупреждения

---

## Структура данных после реализации

### Пример блока с настроенными кнопками:

```json
{
  "id": "2",
  "method": "inlineKeyboard",
  "method_data": {
    "text": "Выберите раздел:",
    "inline_keyboard": [
      [
        {
          "text": "Создать/изменить/закрыть бизнес",
          "callback_data": "3",
          "target_block_id": "3"
        },
        {
          "text": "Бухгалтерия и отчетность",
          "callback_data": "25",
          "target_block_id": "25"
        }
      ],
      [
        {
          "text": "Связаться с менеджером",
          "callback_data": "21",
          "target_block_id": "21"
        }
      ]
    ]
  },
  "label": "Главное меню"
}
```

---

## Альтернативный подход: callback_data как ID блока

Если используем подход, где `callback_data = ID блока`, то структура упрощается:

```json
{
  "inline_keyboard": [
    [
      {
        "text": "Создать/изменить/закрыть бизнес",
        "callback_data": "3"  // Автоматически = target_block_id
      }
    ]
  ]
}
```

**Преимущества:**
- Не нужно хранить `target_block_id` отдельно
- Обработчик просто ищет блок с `id = callback_data`
- Меньше данных в структуре

**Недостатки:**
- `callback_data` должен быть ID блока (не семантичным)
- Ограничение Telegram: callback_data максимум 64 байта

---

## Рекомендуемый подход

**Гибридный вариант:**

1. **В UI настройки:**
   - Поле "Целевой блок" (select) для каждой кнопки
   - Автоматическое заполнение `callback_data = target_block_id` (если не указан)

2. **В структуре данных:**
   - Хранить оба поля: `callback_data` и `target_block_id`
   - `callback_data` может быть любым (для совместимости)
   - `target_block_id` - ID целевого блока (для навигации)

3. **В обработчике:**
   - Сначала проверять `target_block_id` из кнопки
   - Если нет - проверять, является ли `callback_data` ID блока
   - Если нет - использовать старую логику

---

## Дополнительные улучшения

### 1. Автозаполнение callback_data

При выборе целевого блока:
- Если `callback_data` пуст → заполнить `callback_data = target_block_id`
- Если `callback_data` уже заполнен → не перезаписывать

### 2. Предупреждения

Показывать предупреждение, если:
- Кнопка не имеет ни `target_block_id`, ни `url`
- `target_block_id` указывает на несуществующий блок
- `target_block_id` указывает на сам текущий блок

### 3. Визуализация связей

В диаграмме:
- Показывать стрелки от кнопок к целевым блокам
- Разные цвета для разных кнопок одного меню
- При наведении показывать название кнопки

---

## Обратная совместимость

### Загрузка старых блоков

При загрузке блоков из базы данных, где кнопки не имеют `target_block_id`, нужно:

1. **В `BlockSettingsSidebar.vue` при инициализации:**
   - Проверить, существует ли `target_block_id` для каждой кнопки
   - Если нет → установить `target_block_id: null`
   - Если `callback_data` является числом (ID блока), можно автозаполнить `target_block_id`

2. **В методе `initMethodData()` или `watch` на `selectedBlock`:**
   ```javascript
   watch(() => props.selectedBlock, (newBlock) => {
       if (newBlock && newBlock.method === 'inlineKeyboard') {
           const keyboard = methodData.value.inline_keyboard || [];
           keyboard.forEach((row) => {
               row.forEach((button) => {
                   // Добавляем target_block_id, если его нет
                   if (!('target_block_id' in button)) {
                       button.target_block_id = null;
                   }
                   // Автозаполнение, если callback_data = ID блока
                   if (button.callback_data && !button.target_block_id) {
                       const blockExists = availableBlocksForSelection.value.some(
                           b => String(b.id) === String(button.callback_data)
                       );
                       if (blockExists) {
                           button.target_block_id = button.callback_data;
                       }
                   }
               });
           });
       }
   }, { immediate: true });
   ```

---

## Порядок реализации

1. ✅ Обновить структуру данных (добавить `target_block_id`)
2. ✅ Добавить UI поле выбора блока
3. ✅ Реализовать автозаполнение `callback_data`
4. ✅ Обеспечить обратную совместимость при загрузке
5. ✅ Обновить обработчик `findBlockByCallbackData()`
6. ✅ Добавить валидацию
7. ⏳ (Опционально) Визуализация связей в диаграмме

---

## Ожидаемый результат

После реализации администратор сможет:

1. Открыть настройки блока с inline-клавиатурой
2. Для каждой кнопки выбрать целевой блок из выпадающего списка
3. Система автоматически установит `callback_data = target_block_id`
4. При нажатии на кнопку пользователем бот перейдет к выбранному блоку
5. Все связи будут видны в настройках и логироваться

