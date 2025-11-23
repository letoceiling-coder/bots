<template>
    <div class="bot-card space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-foreground">Карта бота</h3>
                <p v-if="bot" class="text-sm text-muted-foreground mt-1">{{ bot.name }}</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="bg-card rounded-lg border border-border p-3">
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Создание элементов -->
                <div class="flex items-center gap-1 border-r border-border pr-2 mr-2">
                    <button
                        type="button"
                        @click="showCommandModal = true"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Создать команду"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="createBlock"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Новый блок"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>
                </div>

                <!-- Импорт/Экспорт -->
                <div class="flex items-center gap-1 border-r border-border pr-2 mr-2">
                    <button
                        type="button"
                        @click="handleImport"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Импорт"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="handleExport"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Экспорт"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                    </button>
                </div>

                <!-- Масштаб -->
                <div class="flex items-center gap-1 border-r border-border pr-2 mr-2">
                    <button
                        type="button"
                        @click="zoomOut"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Уменьшить"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                        </svg>
                    </button>
                    <span class="text-xs text-muted-foreground px-2">{{ Math.round(zoom * 100) }}%</span>
                    <button
                        type="button"
                        @click="zoomIn"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Увеличить"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                        </svg>
                    </button>
                </div>

                <!-- На весь экран -->
                <div class="flex items-center gap-1 border-r border-border pr-2 mr-2">
                    <button
                        type="button"
                        @click="fitToScreen"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="На весь экран"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>

                <!-- Запуск теста -->
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="showTestModal = true"
                        :disabled="!canRunTest"
                        class="p-2 hover:bg-muted/50 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="{ 'bg-green-500/20 text-green-600': canRunTest }"
                        title="Запуск теста"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Diagram Area -->
        <BotDiagram 
            :bot="bot"
            :blocks="blocks"
            :zoom="zoom"
            @block-move="handleBlockMove"
            @block-click="handleBlockClick"
            @block-settings="handleBlockSettings"
            @block-delete="handleBlockDelete"
        />

        <!-- Command Create Modal -->
        <CommandCreateModal
            :show="showCommandModal"
            @close="showCommandModal = false"
            @create="handleCommandCreate"
        />

        <!-- Block Settings Sidebar -->
        <BlockSettingsSidebar
            :show="showSettingsSidebar"
            :selected-block="selectedBlock"
            :available-blocks="blocks"
            @close="showSettingsSidebar = false"
            @save="handleBlockSave"
        />

        <!-- Test Run Modal -->
        <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">Запуск теста</h3>
                
                <div class="space-y-4">
                    <!-- Инструкция по получению Chat ID -->
                    <div class="bg-blue-50/50 border border-blue-200 rounded-lg p-4 space-y-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2">Как узнать Chat ID?</h4>
                                <div class="text-xs text-blue-800 space-y-2">
                                    <p><strong>Способ 1 (Автоматически):</strong></p>
                                    <p>Нажмите кнопку "Получить Chat ID" ниже. Бот должен получить хотя бы одно сообщение от вас.</p>
                                    
                                    <p class="mt-2"><strong>Способ 2 (Вручную):</strong></p>
                                    <ol class="list-decimal list-inside space-y-1 ml-2">
                                        <li>Найдите бота в Telegram: <code class="bg-blue-100 px-1 rounded">@userinfobot</code> или <code class="bg-blue-100 px-1 rounded">@getidsbot</code></li>
                                        <li>Отправьте боту команду <code class="bg-blue-100 px-1 rounded">/start</code></li>
                                        <li>Бот вернет ваш Chat ID</li>
                                    </ol>
                                    
                                    <p class="mt-2"><strong>Способ 3 (Для групп):</strong></p>
                                    <ol class="list-decimal list-inside space-y-1 ml-2">
                                        <li>Добавьте бота в группу</li>
                                        <li>Дайте боту права администратора</li>
                                        <li>Отправьте сообщение в группу</li>
                                        <li>Используйте кнопку "Получить Chat ID"</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <label class="text-sm font-medium block flex-1">
                                Chat ID <span class="text-destructive">*</span>
                            </label>
                            <button
                                type="button"
                                @click="getChatId"
                                :disabled="isLoadingChatId"
                                class="text-xs px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isLoadingChatId ? 'Загрузка...' : 'Получить Chat ID' }}
                            </button>
                        </div>
                        <input
                            v-model="testChatId"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            :class="{ 'border-blue-500': availableChatIds.length > 0 }"
                            placeholder="Введите Chat ID для тестирования"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            ID чата, куда будут отправляться тестовые сообщения
                        </p>
                        
                        <!-- Список доступных Chat ID -->
                        <div v-if="availableChatIds.length > 0" class="mt-2 space-y-1">
                            <p class="text-xs font-medium text-muted-foreground">Доступные чаты:</p>
                            <div
                                v-for="chat in availableChatIds"
                                :key="chat.chat_id"
                                @click="testChatId = chat.chat_id.toString()"
                                class="p-2 border border-border rounded hover:bg-muted/50 cursor-pointer transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium">{{ chat.title }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ chat.type === 'private' ? 'Личный чат' : chat.type === 'group' ? 'Группа' : 'Канал' }}
                                            <span v-if="chat.username"> • @{{ chat.username }}</span>
                                        </p>
                                    </div>
                                    <code class="text-xs bg-muted px-2 py-1 rounded">{{ chat.chat_id }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="testStatus" class="p-3 rounded border" :class="testStatus.type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                        <p class="text-sm font-medium" :class="testStatus.type === 'success' ? 'text-green-800' : 'text-red-800'">
                            {{ testStatus.message }}
                        </p>
                        <ul v-if="testStatus.recommendations && testStatus.recommendations.length > 0" class="mt-2 text-xs space-y-1" :class="testStatus.type === 'success' ? 'text-green-700' : 'text-red-700'">
                            <li v-for="(rec, index) in testStatus.recommendations" :key="index">• {{ rec }}</li>
                        </ul>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeTestModal"
                            class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            type="button"
                            @click="runTest"
                            :disabled="!testChatId || isRunningTest"
                            class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isRunningTest ? 'Выполнение...' : 'Запустить тест' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import BotDiagram from './BotDiagram.vue'
import CommandCreateModal from './CommandCreateModal.vue'
import BlockSettingsSidebar from './BlockSettingsSidebar.vue'
import { apiPost, apiGet } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'BotCard',
    components: {
        BotDiagram,
        CommandCreateModal,
        BlockSettingsSidebar
    },
    props: {
        bot: {
            type: Object,
            default: null
        }
    },
    emits: ['close'],
    setup(props) {
        const showCommandModal = ref(false)
        const showSettingsSidebar = ref(false)
        const showTestModal = ref(false)
        const selectedBlock = ref(null)
        const zoom = ref(1)
        const blocks = ref([])
        const commands = ref([])
        const testChatId = ref('')
        const testStatus = ref(null)
        const isRunningTest = ref(false)
        const isLoadingChatId = ref(false)
        const availableChatIds = ref([])
        let blockIdCounter = 1

        // Проверка возможности запуска теста
        const canRunTest = computed(() => {
            return props.bot?.is_active && blocks.value.length > 0 && blocks.value.some(b => b.method)
        })

        const createBlock = () => {
            const newBlock = {
                id: blockIdCounter++,
                label: `Блок #${blockIdCounter - 1}`,
                x: Math.random() * 300 + 50,
                y: Math.random() * 300 + 50,
                method: '',
                methodData: {},
                nextAction: '',
                nextBlockId: null
            }
            blocks.value.push(newBlock)
        }

        const handleCommandCreate = (commandData) => {
            commands.value.push({
                id: commands.value.length + 1,
                ...commandData
            })
            showCommandModal.value = false
            
            // Создаем блок для команды
            const commandBlock = {
                id: blockIdCounter++,
                label: commandData.command,
                x: 50,
                y: 50,
                method: '',
                methodData: {},
                nextAction: '',
                nextBlockId: null,
                command: commandData.command
            }
            blocks.value.push(commandBlock)
        }

        const handleBlockMove = ({ id, x, y }) => {
            const block = blocks.value.find(b => b.id === id)
            if (block) {
                block.x = x
                block.y = y
            }
        }

        const handleBlockClick = (block) => {
            // Можно добавить логику при клике на блок
        }

        const handleBlockSettings = (block) => {
            selectedBlock.value = block
            showSettingsSidebar.value = true
        }

        const handleBlockSave = (updatedBlock) => {
            const block = blocks.value.find(b => b.id === updatedBlock.id)
            if (block) {
                Object.assign(block, updatedBlock)
            }
        }

        const handleBlockDelete = async (blockId) => {
            const result = await Swal.fire({
                title: 'Удалить блок?',
                html: 'Вы уверены, что хотите удалить этот блок?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (result.isConfirmed) {
                blocks.value = blocks.value.filter(b => b.id !== blockId)
            }
        }

        const zoomIn = () => {
            zoom.value = Math.min(zoom.value + 0.1, 2)
        }

        const zoomOut = () => {
            zoom.value = Math.max(zoom.value - 0.1, 0.5)
        }

        const fitToScreen = () => {
            zoom.value = 1
        }

        const handleImport = () => {
            // Логика импорта будет добавлена позже
            console.log('Import')
        }

        const handleExport = () => {
            // Логика экспорта будет добавлена позже
            const data = {
                blocks: blocks.value,
                commands: commands.value
            }
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
            const url = URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `bot-map-${Date.now()}.json`
            a.click()
            URL.revokeObjectURL(url)
        }

        // Создание тестовых блоков по умолчанию
        const createDefaultTestBlocks = () => {
            if (blocks.value.length > 0) {
                return // Не создаем, если уже есть блоки
            }

            // Блок 1: Приветственное сообщение
            const block1 = {
                id: blockIdCounter++,
                label: '/start',
                x: 50,
                y: 50,
                method: 'sendMessage',
                methodData: {
                    text: 'Добро пожаловать! Выберите действие:',
                    parse_mode: ''
                },
                nextAction: 'specific',
                nextBlockId: null, // Будет установлен после создания block2
                command: '/start'
            }

            // Блок 2: Клавиатура ответа
            const block2 = {
                id: blockIdCounter++,
                label: 'Клавиатура',
                x: 250,
                y: 50,
                method: 'replyKeyboard',
                methodData: {
                    keyboard: [
                        [
                            { text: 'Информация' },
                            { text: 'Помощь' }
                        ],
                        [
                            { text: 'Настройки' }
                        ]
                    ],
                    resize_keyboard: true,
                    one_time_keyboard: false
                },
                nextAction: 'specific',
                nextBlockId: null // Будет установлен после создания block3
            }

            // Блок 3: Сообщение с информацией
            const block3 = {
                id: blockIdCounter++,
                label: 'Информация',
                x: 450,
                y: 50,
                method: 'sendMessage',
                methodData: {
                    text: 'Это информационное сообщение. Здесь может быть любая полезная информация для пользователей.',
                    parse_mode: ''
                },
                nextAction: 'specific',
                nextBlockId: null // Будет установлен после создания block4
            }

            // Блок 4: Опрос
            const block4 = {
                id: blockIdCounter++,
                label: 'Опрос',
                x: 250,
                y: 200,
                method: 'sendPoll',
                methodData: {
                    question: 'Как вам наш бот?',
                    options: [
                        'Отлично!',
                        'Хорошо',
                        'Нормально',
                        'Плохо'
                    ],
                    is_anonymous: false,
                    type: 'regular'
                },
                nextAction: 'specific',
                nextBlockId: null // Будет установлен после создания block5
            }

            // Блок 5: Финальное сообщение
            const block5 = {
                id: blockIdCounter++,
                label: 'Завершение',
                x: 450,
                y: 200,
                method: 'sendMessage',
                methodData: {
                    text: 'Спасибо за использование бота! Если у вас есть вопросы, используйте команду /help',
                    parse_mode: ''
                },
                nextAction: 'end',
                nextBlockId: null
            }

            // Устанавливаем связи между блоками
            block1.nextBlockId = block2.id
            block2.nextBlockId = block3.id
            block3.nextBlockId = block4.id
            block4.nextBlockId = block5.id

            // Добавляем блоки в массив
            blocks.value = [block1, block2, block3, block4, block5]

            // Добавляем команду
            commands.value.push({
                id: 1,
                command: '/start',
                description: 'Команда для начала работы с ботом'
            })
        }

        // Найти начальный блок (блок без входящих связей или первый блок с методом)
        const findStartBlock = () => {
            const blocksWithMethods = blocks.value.filter(b => b.method)
            if (blocksWithMethods.length === 0) return null

            // Ищем блок, на который не ссылаются другие блоки
            const referencedBlockIds = new Set()
            blocks.value.forEach(block => {
                if (block.nextBlockId) {
                    referencedBlockIds.add(block.nextBlockId)
                }
            })

            const startBlock = blocksWithMethods.find(b => !referencedBlockIds.has(b.id))
            return startBlock || blocksWithMethods[0]
        }

        // Выполнить метод блока
        const executeBlockMethod = async (block, chatId) => {
            if (!block.method || !block.methodData) {
                throw new Error(`Блок "${block.label || `#${block.id}`}" не имеет настроенного метода`)
            }

            const response = await apiPost(`/bots/${props.bot.id}/execute-block-method`, {
                method: block.method,
                method_data: block.methodData,
                chat_id: chatId
            })

            const data = await response.json()

            if (!response.ok) {
                throw {
                    message: data.message || 'Ошибка выполнения метода',
                    error: data.error,
                    recommendations: data.recommendations || []
                }
            }

            return data
        }

        // Запуск теста
        const runTest = async () => {
            if (!testChatId.value) {
                testStatus.value = {
                    type: 'error',
                    message: 'Введите Chat ID для тестирования'
                }
                return
            }

            isRunningTest.value = true
            testStatus.value = null

            try {
                // Проверка активности бота
                if (!props.bot?.is_active) {
                    throw {
                        message: 'Бот неактивен',
                        error: 'Для выполнения теста бот должен быть активен',
                        recommendations: [
                            'Активируйте бота в настройках',
                            'Проверьте статус бота в списке ботов'
                        ]
                    }
                }

                // Находим начальный блок
                const startBlock = findStartBlock()
                if (!startBlock) {
                    throw {
                        message: 'Нет блоков для выполнения',
                        error: 'Создайте хотя бы один блок с настроенным методом',
                        recommendations: [
                            'Создайте блок и выберите метод выполнения',
                            'Настройте параметры метода в настройках блока'
                        ]
                    }
                }

                // Выполняем блоки последовательно
                const executedBlocks = []
                let currentBlock = startBlock

                while (currentBlock) {
                    try {
                        const result = await executeBlockMethod(currentBlock, testChatId.value)
                        executedBlocks.push({
                            block: currentBlock,
                            success: true,
                            result
                        })

                        // Определяем следующий блок
                        if (currentBlock.nextAction === 'specific' && currentBlock.nextBlockId) {
                            currentBlock = blocks.value.find(b => b.id === currentBlock.nextBlockId)
                        } else if (currentBlock.nextAction === 'next') {
                            // Находим следующий блок по связям
                            currentBlock = blocks.value.find(b => b.id === currentBlock.nextBlockId)
                        } else if (currentBlock.nextAction === 'end') {
                            currentBlock = null
                        } else {
                            // По умолчанию ищем следующий блок по связям
                            currentBlock = blocks.value.find(b => b.id === currentBlock.nextBlockId)
                        }
                    } catch (error) {
                        executedBlocks.push({
                            block: currentBlock,
                            success: false,
                            error
                        })
                        throw error
                    }
                }

                // Успешное выполнение
                testStatus.value = {
                    type: 'success',
                    message: `Успешно выполнено ${executedBlocks.length} блок(ов)`,
                    recommendations: [
                        'Проверьте чат с указанным Chat ID',
                        'Убедитесь, что все сообщения доставлены корректно'
                    ]
                }

                await Swal.fire({
                    title: 'Тест выполнен успешно',
                    html: `Выполнено блоков: ${executedBlocks.length}`,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
            } catch (error) {
                const errorMessage = error.message || error.error || 'Неизвестная ошибка'
                const recommendations = error.recommendations || [
                    'Проверьте настройки бота',
                    'Убедитесь, что бот активен',
                    'Проверьте правильность Chat ID',
                    'Проверьте параметры методов в блоках'
                ]

                testStatus.value = {
                    type: 'error',
                    message: errorMessage,
                    recommendations
                }

                await Swal.fire({
                    title: 'Ошибка выполнения теста',
                    html: `
                        <p class="mb-2">${errorMessage}</p>
                        <ul class="text-left text-sm mt-2">
                            ${recommendations.map(r => `<li>• ${r}</li>`).join('')}
                        </ul>
                    `,
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                isRunningTest.value = false
            }
        }

        const closeTestModal = () => {
            showTestModal.value = false
            testChatId.value = ''
            testStatus.value = null
            availableChatIds.value = []
        }

        // Получить Chat ID из обновлений бота
        const getChatId = async () => {
            if (!props.bot?.id) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: 'Бот не выбран',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
                return
            }

            isLoadingChatId.value = true
            availableChatIds.value = []

            try {
                const response = await apiGet(`/bots/${props.bot.id}/updates`)
                const data = await response.json()

                if (!response.ok) {
                    throw new Error(data.error || 'Ошибка получения обновлений')
                }

                if (data.data?.chat_ids && data.data.chat_ids.length > 0) {
                    availableChatIds.value = data.data.chat_ids
                    
                    // Автоматически выбираем первый chat_id
                    if (availableChatIds.value.length === 1) {
                        testChatId.value = availableChatIds.value[0].chat_id.toString()
                    }

                    await Swal.fire({
                        title: 'Chat ID получены',
                        html: `Найдено чатов: ${availableChatIds.value.length}`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    })
                } else {
                    await Swal.fire({
                        title: 'Chat ID не найдены',
                        html: `
                            <p class="mb-2">Бот еще не получал сообщений.</p>
                            <p class="text-sm">Для получения Chat ID:</p>
                            <ol class="text-sm text-left mt-2 space-y-1">
                                <li>1. Откройте бота в Telegram</li>
                                <li>2. Отправьте боту любое сообщение</li>
                                <li>3. Нажмите "Получить Chat ID" снова</li>
                            </ol>
                        `,
                        icon: 'info',
                        confirmButtonText: 'ОК'
                    })
                }
            } catch (error) {
                await Swal.fire({
                    title: 'Ошибка',
                    html: `
                        <p class="mb-2">${error.message || 'Не удалось получить Chat ID'}</p>
                        <p class="text-sm mt-2">Убедитесь, что:</p>
                        <ul class="text-sm text-left mt-1 space-y-1">
                            <li>• Бот активен</li>
                            <li>• Токен бота правильный</li>
                            <li>• Бот получал сообщения</li>
                        </ul>
                    `,
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                isLoadingChatId.value = false
            }
        }

        // Инициализация тестовых блоков при монтировании
        onMounted(() => {
            createDefaultTestBlocks()
        })

        return {
            showCommandModal,
            showSettingsSidebar,
            showTestModal,
            selectedBlock,
            zoom,
            blocks,
            commands,
            testChatId,
            testStatus,
            isRunningTest,
            canRunTest,
            createBlock,
            handleCommandCreate,
            handleBlockMove,
            handleBlockClick,
            handleBlockSettings,
            handleBlockSave,
            handleBlockDelete,
            zoomIn,
            zoomOut,
            fitToScreen,
            handleImport,
            handleExport,
            runTest,
            closeTestModal,
            getChatId,
            isLoadingChatId,
            availableChatIds
        }
    }
}
</script>

