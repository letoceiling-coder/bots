<template>
    <div 
        v-if="show && selectedBlock"
        class="fixed right-0 top-0 h-full w-96 bg-card border-l border-border shadow-xl z-40 overflow-y-auto"
        :class="{ 'translate-x-0': show, 'translate-x-full': !show }"
    >
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Настройки блока</h3>
                <button
                    @click="$emit('close')"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Выбор метода -->
            <div>
                <label class="text-sm font-medium mb-2 block">Метод выполнения</label>
                <select
                    v-model="localBlock.method"
                    class="w-full h-10 px-3 border border-border rounded bg-background"
                    @change="handleMethodChange"
                >
                    <option value="">Выберите метод</option>
                    <optgroup label="Отправка сообщений">
                        <option value="sendMessage">Отправить сообщение</option>
                        <option value="sendDice">Отправить кубик</option>
                        <option value="sendPoll">Отправить опрос</option>
                        <option value="sendVenue">Отправить локацию</option>
                        <option value="sendContact">Отправить контакт</option>
                    </optgroup>
                    <optgroup label="Редактирование">
                        <option value="editMessageText">Редактировать текст</option>
                        <option value="editMessageCaption">Редактировать подпись</option>
                    </optgroup>
                    <optgroup label="Управление">
                        <option value="deleteMessage">Удалить сообщение</option>
                        <option value="pinChatMessage">Закрепить сообщение</option>
                        <option value="unpinChatMessage">Открепить сообщение</option>
                    </optgroup>
                    <optgroup label="Кнопки">
                        <option value="replyKeyboard">Клавиатура ответа</option>
                        <option value="inlineKeyboard">Inline клавиатура</option>
                    </optgroup>
                </select>
            </div>

            <!-- Поля для метода sendMessage -->
            <div v-if="localBlock.method === 'sendMessage'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры сообщения</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Текст сообщения <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="methodData.text"
                        rows="4"
                        class="w-full px-3 py-2 border rounded bg-background"
                        :class="{ 'border-destructive': errors.text }"
                        placeholder="Введите текст сообщения (до 4096 символов)"
                        @input="validateField('text')"
                    ></textarea>
                    <p v-if="errors.text" class="text-xs text-destructive mt-1">{{ errors.text }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ (methodData.text || '').length }}/4096 символов
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Режим парсинга</label>
                    <select
                        v-model="methodData.parse_mode"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="">Нет</option>
                        <option value="HTML">HTML</option>
                        <option value="Markdown">Markdown</option>
                        <option value="MarkdownV2">MarkdownV2</option>
                    </select>
                </div>
            </div>

            <!-- Поля для метода sendDice -->
            <div v-if="localBlock.method === 'sendDice'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры кубика</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">Эмодзи</label>
                    <select
                        v-model="methodData.emoji"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="🎲">🎲 Кубик</option>
                        <option value="🎯">🎯 Дартс</option>
                        <option value="🏀">🏀 Баскетбол</option>
                        <option value="⚽">⚽ Футбол</option>
                        <option value="🎳">🎳 Боулинг</option>
                        <option value="🎰">🎰 Слот</option>
                    </select>
                </div>
            </div>

            <!-- Поля для метода sendPoll -->
            <div v-if="localBlock.method === 'sendPoll'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры опроса</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Вопрос <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.question"
                        type="text"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.question }"
                        placeholder="Введите вопрос (до 300 символов)"
                        @input="validateField('question')"
                    />
                    <p v-if="errors.question" class="text-xs text-destructive mt-1">{{ errors.question }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ (methodData.question || '').length }}/300 символов
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Варианты ответа <span class="text-destructive">*</span>
                    </label>
                    <div class="space-y-2">
                        <div
                            v-for="(option, index) in methodData.options"
                            :key="index"
                            class="flex gap-2"
                        >
                            <input
                                v-model="methodData.options[index]"
                                type="text"
                                class="flex-1 h-10 px-3 border rounded bg-background"
                                :class="{ 'border-destructive': errors[`option_${index}`] }"
                                :placeholder="`Вариант ${index + 1} (до 100 символов)`"
                                @input="validatePollOptions"
                            />
                            <button
                                v-if="methodData.options.length > 2"
                                @click="removePollOption(index)"
                                class="px-2 text-destructive hover:bg-destructive/10 rounded"
                            >
                                ×
                            </button>
                        </div>
                        <button
                            v-if="methodData.options.length < 10"
                            @click="addPollOption"
                            class="w-full h-8 text-sm border border-border rounded hover:bg-muted/50"
                        >
                            + Добавить вариант
                        </button>
                    </div>
                    <p v-if="errors.options" class="text-xs text-destructive mt-1">{{ errors.options }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Минимум 2, максимум 10 вариантов
                    </p>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="methodData.is_anonymous"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span class="text-sm">Анонимный опрос</span>
                    </label>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Тип опроса</label>
                    <select
                        v-model="methodData.type"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="regular">Обычный</option>
                        <option value="quiz">Викторина</option>
                    </select>
                </div>
            </div>

            <!-- Поля для метода sendVenue -->
            <div v-if="localBlock.method === 'sendVenue'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры локации</h4>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-sm font-medium mb-1 block">
                            Широта <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="methodData.latitude"
                            type="number"
                            step="any"
                            class="w-full h-10 px-3 border rounded bg-background"
                            :class="{ 'border-destructive': errors.latitude }"
                            placeholder="-90 до 90"
                            @input="validateField('latitude')"
                        />
                        <p v-if="errors.latitude" class="text-xs text-destructive mt-1">{{ errors.latitude }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">
                            Долгота <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="methodData.longitude"
                            type="number"
                            step="any"
                            class="w-full h-10 px-3 border rounded bg-background"
                            :class="{ 'border-destructive': errors.longitude }"
                            placeholder="-180 до 180"
                            @input="validateField('longitude')"
                        />
                        <p v-if="errors.longitude" class="text-xs text-destructive mt-1">{{ errors.longitude }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Название <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.title"
                        type="text"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.title }"
                        placeholder="Название места (до 64 символов)"
                        @input="validateField('title')"
                    />
                    <p v-if="errors.title" class="text-xs text-destructive mt-1">{{ errors.title }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Адрес <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.address"
                        type="text"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.address }"
                        placeholder="Адрес (до 64 символов)"
                        @input="validateField('address')"
                    />
                    <p v-if="errors.address" class="text-xs text-destructive mt-1">{{ errors.address }}</p>
                </div>
            </div>

            <!-- Поля для метода sendContact -->
            <div v-if="localBlock.method === 'sendContact'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры контакта</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Номер телефона <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.phone_number"
                        type="tel"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.phone_number }"
                        placeholder="+1234567890"
                        @input="validateField('phone_number')"
                    />
                    <p v-if="errors.phone_number" class="text-xs text-destructive mt-1">{{ errors.phone_number }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Имя <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.first_name"
                        type="text"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.first_name }"
                        placeholder="Имя (до 255 символов)"
                        @input="validateField('first_name')"
                    />
                    <p v-if="errors.first_name" class="text-xs text-destructive mt-1">{{ errors.first_name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Фамилия</label>
                    <input
                        v-model="methodData.last_name"
                        type="text"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.last_name }"
                        placeholder="Фамилия (до 255 символов)"
                        @input="validateField('last_name')"
                    />
                    <p v-if="errors.last_name" class="text-xs text-destructive mt-1">{{ errors.last_name }}</p>
                </div>
            </div>

            <!-- Поля для метода replyKeyboard -->
            <div v-if="localBlock.method === 'replyKeyboard'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Клавиатура ответа</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Кнопки <span class="text-destructive">*</span>
                    </label>
                    <div class="space-y-3">
                        <div
                            v-for="(row, rowIndex) in methodData.keyboard"
                            :key="rowIndex"
                            class="border border-border rounded p-3 space-y-2"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-muted-foreground">Ряд {{ rowIndex + 1 }}</span>
                                <button
                                    v-if="methodData.keyboard.length > 1"
                                    @click="removeKeyboardRow(rowIndex)"
                                    class="text-xs text-destructive hover:bg-destructive/10 px-2 py-1 rounded"
                                >
                                    Удалить ряд
                                </button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(button, btnIndex) in row"
                                    :key="btnIndex"
                                    class="flex gap-2"
                                >
                                    <input
                                        v-model="methodData.keyboard[rowIndex][btnIndex].text"
                                        type="text"
                                        class="flex-1 h-10 px-3 border rounded bg-background"
                                        :class="{ 'border-destructive': errors[`keyboard_${rowIndex}_${btnIndex}`] }"
                                        placeholder="Текст кнопки (до 64 символов)"
                                        @input="validateKeyboard"
                                    />
                                    <button
                                        v-if="row.length > 1"
                                        @click="removeKeyboardButton(rowIndex, btnIndex)"
                                        class="px-2 text-destructive hover:bg-destructive/10 rounded"
                                    >
                                        ×
                                    </button>
                                </div>
                                <button
                                    v-if="row.length < 12"
                                    @click="addKeyboardButton(rowIndex)"
                                    class="w-full h-8 text-xs border border-border rounded hover:bg-muted/50"
                                >
                                    + Добавить кнопку
                                </button>
                            </div>
                        </div>
                        <button
                            v-if="methodData.keyboard.length < 8"
                            @click="addKeyboardRow"
                            class="w-full h-8 text-sm border border-border rounded hover:bg-muted/50"
                        >
                            + Добавить ряд
                        </button>
                    </div>
                    <p v-if="errors.keyboard" class="text-xs text-destructive mt-1">{{ errors.keyboard }}</p>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="methodData.resize_keyboard"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span class="text-sm">Автоматически изменять размер клавиатуры</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="methodData.one_time_keyboard"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span class="text-sm">Одноразовая клавиатура</span>
                    </label>
                </div>
            </div>

            <!-- Поля для метода inlineKeyboard -->
            <div v-if="localBlock.method === 'inlineKeyboard'" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Inline клавиатура</h4>
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Кнопки <span class="text-destructive">*</span>
                    </label>
                    <div class="space-y-3">
                        <div
                            v-for="(row, rowIndex) in methodData.inline_keyboard"
                            :key="rowIndex"
                            class="border border-border rounded p-3 space-y-2"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-muted-foreground">Ряд {{ rowIndex + 1 }}</span>
                                <button
                                    v-if="methodData.inline_keyboard.length > 1"
                                    @click="removeInlineKeyboardRow(rowIndex)"
                                    class="text-xs text-destructive hover:bg-destructive/10 px-2 py-1 rounded"
                                >
                                    Удалить ряд
                                </button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(button, btnIndex) in row"
                                    :key="btnIndex"
                                    class="space-y-2 border-b border-border pb-2 last:border-0"
                                >
                                    <input
                                        v-model="methodData.inline_keyboard[rowIndex][btnIndex].text"
                                        type="text"
                                        class="w-full h-10 px-3 border rounded bg-background"
                                        :class="{ 'border-destructive': errors[`inline_${rowIndex}_${btnIndex}_text`] }"
                                        placeholder="Текст кнопки (до 64 символов)"
                                        @input="validateInlineKeyboard"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="methodData.inline_keyboard[rowIndex][btnIndex].callback_data"
                                            type="text"
                                            class="h-8 px-2 text-xs border rounded bg-background"
                                            :class="{ 'border-destructive': errors[`inline_${rowIndex}_${btnIndex}_callback`] }"
                                            placeholder="callback_data (до 64 байт)"
                                            @input="validateInlineKeyboard"
                                        />
                                        <input
                                            v-model="methodData.inline_keyboard[rowIndex][btnIndex].url"
                                            type="url"
                                            class="h-8 px-2 text-xs border rounded bg-background"
                                            placeholder="URL"
                                            @input="validateInlineKeyboard"
                                        />
                                    </div>
                                    <button
                                        v-if="row.length > 1"
                                        @click="removeInlineKeyboardButton(rowIndex, btnIndex)"
                                        class="w-full h-8 text-xs text-destructive border border-destructive rounded hover:bg-destructive/10"
                                    >
                                        Удалить кнопку
                                    </button>
                                </div>
                                <button
                                    v-if="row.length < 13"
                                    @click="addInlineKeyboardButton(rowIndex)"
                                    class="w-full h-8 text-xs border border-border rounded hover:bg-muted/50"
                                >
                                    + Добавить кнопку
                                </button>
                            </div>
                        </div>
                        <button
                            v-if="methodData.inline_keyboard.length < 8"
                            @click="addInlineKeyboardRow"
                            class="w-full h-8 text-sm border border-border rounded hover:bg-muted/50"
                        >
                            + Добавить ряд
                        </button>
                    </div>
                    <p v-if="errors.inline_keyboard" class="text-xs text-destructive mt-1">{{ errors.inline_keyboard }}</p>
                </div>
            </div>

            <!-- Поля для других методов -->
            <div v-if="['editMessageText', 'editMessageCaption', 'deleteMessage', 'pinChatMessage'].includes(localBlock.method)" class="space-y-4 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground">Параметры метода</h4>
                <div v-if="localBlock.method === 'editMessageText'">
                    <label class="text-sm font-medium mb-1 block">
                        Текст сообщения <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="methodData.text"
                        rows="4"
                        class="w-full px-3 py-2 border rounded bg-background"
                        :class="{ 'border-destructive': errors.text }"
                        placeholder="Введите текст сообщения (до 4096 символов)"
                        @input="validateField('text')"
                    ></textarea>
                    <p v-if="errors.text" class="text-xs text-destructive mt-1">{{ errors.text }}</p>
                </div>
                <div v-if="localBlock.method === 'editMessageCaption'">
                    <label class="text-sm font-medium mb-1 block">Подпись</label>
                    <textarea
                        v-model="methodData.caption"
                        rows="3"
                        class="w-full px-3 py-2 border rounded bg-background"
                        :class="{ 'border-destructive': errors.caption }"
                        placeholder="Введите подпись (до 1024 символов)"
                        @input="validateField('caption')"
                    ></textarea>
                    <p v-if="errors.caption" class="text-xs text-destructive mt-1">{{ errors.caption }}</p>
                </div>
                <div v-if="['deleteMessage', 'pinChatMessage'].includes(localBlock.method)">
                    <label class="text-sm font-medium mb-1 block">
                        ID сообщения <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="methodData.message_id"
                        type="number"
                        class="w-full h-10 px-3 border rounded bg-background"
                        :class="{ 'border-destructive': errors.message_id }"
                        placeholder="Введите ID сообщения"
                        @input="validateField('message_id')"
                    />
                    <p v-if="errors.message_id" class="text-xs text-destructive mt-1">{{ errors.message_id }}</p>
                </div>
                <div v-if="localBlock.method === 'pinChatMessage'">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="methodData.disable_notification"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span class="text-sm">Отключить уведомление</span>
                    </label>
                </div>
            </div>

            <!-- Действие после выполнения -->
            <div class="border-t border-border pt-4">
                <label class="text-sm font-medium mb-2 block">Действие после выполнения</label>
                <select
                    v-model="localBlock.nextAction"
                    class="w-full h-10 px-3 border border-border rounded bg-background"
                >
                    <option value="">Нет действия</option>
                    <option value="next">Перейти к следующему блоку</option>
                    <option value="specific">Перейти к конкретному блоку</option>
                    <option value="end">Завершить выполнение</option>
                </select>
            </div>

            <!-- Выбор конкретного блока (если выбрано "specific") -->
            <div v-if="localBlock.nextAction === 'specific'">
                <label class="text-sm font-medium mb-2 block">Выберите блок</label>
                <select
                    v-model="localBlock.nextBlockId"
                    class="w-full h-10 px-3 border border-border rounded bg-background"
                >
                    <option value="">Выберите блок</option>
                    <option 
                        v-for="block in availableBlocks" 
                        :key="block.id" 
                        :value="block.id"
                    >
                        {{ block.label || `Блок #${block.id}` }}
                    </option>
                </select>
            </div>

            <!-- Кнопка сохранения -->
            <div class="pt-4 border-t border-border">
                <button
                    @click="handleSave"
                    :disabled="!isValid"
                    class="w-full h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Сохранить настройки
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { validateMethodData, telegramApiValidation } from '../../utils/telegramApiValidation.js'

export default {
    name: 'BlockSettingsSidebar',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        selectedBlock: {
            type: Object,
            default: null
        },
        availableBlocks: {
            type: Array,
            default: () => []
        }
    },
    emits: ['close', 'save'],
    setup(props, { emit }) {
        const localBlock = ref({
            method: '',
            nextAction: '',
            nextBlockId: null
        })

        const methodData = ref({})
        const errors = ref({})

        // Инициализация данных метода по умолчанию
        const initMethodData = (method) => {
            const defaults = {
                sendMessage: {
                    text: '',
                    parse_mode: ''
                },
                sendDice: {
                    emoji: '🎲'
                },
                sendPoll: {
                    question: '',
                    options: ['', ''],
                    is_anonymous: true,
                    type: 'regular'
                },
                sendVenue: {
                    latitude: '',
                    longitude: '',
                    title: '',
                    address: ''
                },
                sendContact: {
                    phone_number: '',
                    first_name: '',
                    last_name: ''
                },
                replyKeyboard: {
                    keyboard: [[{ text: '' }]],
                    resize_keyboard: false,
                    one_time_keyboard: false
                },
                inlineKeyboard: {
                    inline_keyboard: [[{ text: '', callback_data: '', url: '' }]]
                },
                editMessageText: {
                    text: '',
                    parse_mode: ''
                },
                editMessageCaption: {
                    caption: '',
                    parse_mode: ''
                },
                deleteMessage: {
                    message_id: ''
                },
                pinChatMessage: {
                    message_id: '',
                    disable_notification: false
                }
            }
            return defaults[method] || {}
        }

        watch(() => props.selectedBlock, (newBlock) => {
            if (newBlock) {
                localBlock.value = {
                    method: newBlock.method || '',
                    nextAction: newBlock.nextAction || '',
                    nextBlockId: newBlock.nextBlockId || null
                }
                // Загружаем данные метода из блока или инициализируем по умолчанию
                if (newBlock.method && newBlock.methodData) {
                    methodData.value = { ...newBlock.methodData }
                } else if (newBlock.method) {
                    methodData.value = initMethodData(newBlock.method)
                } else {
                    methodData.value = {}
                }
                errors.value = {}
            }
        }, { immediate: true })

        watch(() => localBlock.value.method, (newMethod) => {
            if (newMethod) {
                // Если метод изменился, инициализируем данные
                if (!props.selectedBlock?.methodData || props.selectedBlock.method !== newMethod) {
                    methodData.value = initMethodData(newMethod)
                }
                errors.value = {}
            }
        })

        const validateField = (field) => {
            if (!localBlock.value.method) return

            const validation = validateMethodData(localBlock.value.method, methodData.value)
            errors.value = validation.errors
        }

        const validatePollOptions = () => {
            validateField('options')
        }

        const validateKeyboard = () => {
            validateField('keyboard')
        }

        const validateInlineKeyboard = () => {
            validateField('inline_keyboard')
        }

        // Управление опросами
        const addPollOption = () => {
            if (methodData.value.options.length < 10) {
                methodData.value.options.push('')
            }
        }

        const removePollOption = (index) => {
            if (methodData.value.options.length > 2) {
                methodData.value.options.splice(index, 1)
                validatePollOptions()
            }
        }

        // Управление клавиатурой ответа
        const addKeyboardRow = () => {
            if (methodData.value.keyboard.length < 8) {
                methodData.value.keyboard.push([{ text: '' }])
            }
        }

        const removeKeyboardRow = (rowIndex) => {
            if (methodData.value.keyboard.length > 1) {
                methodData.value.keyboard.splice(rowIndex, 1)
                validateKeyboard()
            }
        }

        const addKeyboardButton = (rowIndex) => {
            if (methodData.value.keyboard[rowIndex].length < 12) {
                methodData.value.keyboard[rowIndex].push({ text: '' })
            }
        }

        const removeKeyboardButton = (rowIndex, btnIndex) => {
            if (methodData.value.keyboard[rowIndex].length > 1) {
                methodData.value.keyboard[rowIndex].splice(btnIndex, 1)
                validateKeyboard()
            }
        }

        // Управление inline клавиатурой
        const addInlineKeyboardRow = () => {
            if (methodData.value.inline_keyboard.length < 8) {
                methodData.value.inline_keyboard.push([{ text: '', callback_data: '', url: '' }])
            }
        }

        const removeInlineKeyboardRow = (rowIndex) => {
            if (methodData.value.inline_keyboard.length > 1) {
                methodData.value.inline_keyboard.splice(rowIndex, 1)
                validateInlineKeyboard()
            }
        }

        const addInlineKeyboardButton = (rowIndex) => {
            if (methodData.value.inline_keyboard[rowIndex].length < 13) {
                methodData.value.inline_keyboard[rowIndex].push({ text: '', callback_data: '', url: '' })
            }
        }

        const removeInlineKeyboardButton = (rowIndex, btnIndex) => {
            if (methodData.value.inline_keyboard[rowIndex].length > 1) {
                methodData.value.inline_keyboard[rowIndex].splice(btnIndex, 1)
                validateInlineKeyboard()
            }
        }

        const isValid = computed(() => {
            if (!localBlock.value.method) return true
            const validation = validateMethodData(localBlock.value.method, methodData.value)
            return validation.valid
        })

        const handleMethodChange = () => {
            methodData.value = initMethodData(localBlock.value.method)
            errors.value = {}
        }

        const handleSave = () => {
            if (!isValid.value) {
                // Валидируем все поля перед сохранением
                const validation = validateMethodData(localBlock.value.method, methodData.value)
                errors.value = validation.errors
                return
            }

            emit('save', {
                ...props.selectedBlock,
                ...localBlock.value,
                methodData: { ...methodData.value }
            })
            emit('close')
        }

        return {
            localBlock,
            methodData,
            errors,
            isValid,
            validateField,
            validatePollOptions,
            validateKeyboard,
            validateInlineKeyboard,
            addPollOption,
            removePollOption,
            addKeyboardRow,
            removeKeyboardRow,
            addKeyboardButton,
            removeKeyboardButton,
            addInlineKeyboardRow,
            removeInlineKeyboardRow,
            addInlineKeyboardButton,
            removeInlineKeyboardButton,
            handleMethodChange,
            handleSave
        }
    }
}
</script>

<style scoped>
.translate-x-0 {
    transform: translateX(0);
}

.translate-x-full {
    transform: translateX(100%);
}
</style>

