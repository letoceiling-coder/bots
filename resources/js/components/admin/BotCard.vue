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
                        title="Уменьшить (Ctrl + колесико мыши)"
                        :disabled="zoom <= 0.5"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                        </svg>
                    </button>
                    <span class="text-xs text-muted-foreground px-2 min-w-[45px] text-center">{{ Math.round(zoom * 100) }}%</span>
                    <button
                        type="button"
                        @click="zoomIn"
                        class="p-2 hover:bg-muted/50 rounded transition-colors"
                        title="Увеличить (Ctrl + колесико мыши)"
                        :disabled="zoom >= 2"
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
                        title="Фокусировка всех блоков"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>

                <!-- Сохранение -->
                <div class="flex items-center gap-1 border-r border-border pr-2 mr-2">
                    <button
                        type="button"
                        @click="handleSaveBlocks"
                        :disabled="isSavingBlocks"
                        class="p-2 hover:bg-muted/50 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="{ 'bg-blue-500/20 text-blue-600': !isSavingBlocks }"
                        title="Сохранить блоки"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
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
            :pan-offset="panOffset"
            :show-button-connections="true"
            @block-move="handleBlockMove"
            @block-click="handleBlockClick"
            @block-settings="handleBlockSettings"
            @block-delete="handleBlockDelete"
            @zoom-change="zoom = $event"
            @pan-change="panOffset = $event"
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
            @update="handleBlockUpdate"
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
        const panOffset = ref({ x: 0, y: 0 })
        const blocks = ref([])
        const isSavingBlocks = ref(false)
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

        const handleBlockUpdate = (updatedBlock) => {
            // Обновление блока в реальном времени при изменении настроек
            const block = blocks.value.find(b => b.id === updatedBlock.id)
            if (block) {
                Object.assign(block, updatedBlock)
            }
            // Также обновляем selectedBlock для синхронизации
            if (selectedBlock.value && selectedBlock.value.id === updatedBlock.id) {
                Object.assign(selectedBlock.value, updatedBlock)
            }
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

        const handleSaveBlocks = async () => {
            if (!props.bot?.id) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: 'Бот не выбран',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
                return
            }

            isSavingBlocks.value = true

            try {
                const response = await apiPost(`/bots/${props.bot.id}/save-blocks`, {
                    blocks: blocks.value
                })

                await Swal.fire({
                    title: 'Успешно',
                    text: 'Блоки успешно сохранены',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
            } catch (error) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: error.message || 'Не удалось сохранить блоки',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                isSavingBlocks.value = false
            }
        }

        const loadBlocks = async () => {
            if (!props.bot?.id) {
                return
            }

            try {
                const response = await apiGet(`/bots/${props.bot.id}/blocks`)
                
                if (!response.ok) {
                    console.error('Ошибка загрузки блоков: HTTP', response.status)
                    blocks.value = []
                    return
                }
                
                const data = await response.json()
                
                console.log('Загружены блоки из API:', data)
                
                // Проверяем наличие блоков в ответе
                if (data?.data?.blocks) {
                    if (Array.isArray(data.data.blocks) && data.data.blocks.length > 0) {
                        console.log('Найдено блоков:', data.data.blocks.length)
                        // Преобразуем формат полей из snake_case в camelCase для Vue
                        blocks.value = data.data.blocks.map(block => {
                            const convertedBlock = {
                                ...block,
                                // Преобразуем method_data -> methodData
                                methodData: block.method_data || block.methodData || {},
                            }
                            // Удаляем method_data если есть (чтобы не было дублирования)
                            if (convertedBlock.method_data) {
                                delete convertedBlock.method_data
                            }
                            return convertedBlock
                        })
                        console.log('Блоки преобразованы и установлены:', blocks.value.length)
                    } else {
                        console.log('Блоки пустые или не массив')
                        // Если блоки пустые или не массив, очищаем
                        blocks.value = []
                    }
                } else {
                    console.log('Блоков нет в ответе API')
                    // Если блоков нет в ответе, очищаем
                    blocks.value = []
                }
            } catch (error) {
                console.error('Ошибка загрузки блоков:', error)
                // Не показываем ошибку пользователю, так как блоки могут быть не сохранены
                // Очищаем блоки при ошибке
                blocks.value = []
            }
        }

        const fitToScreen = () => {
            if (blocks.value.length === 0) {
                zoom.value = 1
                panOffset.value = { x: 0, y: 0 }
                return
            }

            // Константы размеров блока
            const BLOCK_WIDTH = 120
            const BLOCK_HEIGHT = 100

            // Вычисляем границы всех блоков
            let minX = Infinity
            let minY = Infinity
            let maxX = -Infinity
            let maxY = -Infinity

            blocks.value.forEach(block => {
                const x = block.x || 0
                const y = block.y || 0
                minX = Math.min(minX, x)
                minY = Math.min(minY, y)
                maxX = Math.max(maxX, x + BLOCK_WIDTH)
                maxY = Math.max(maxY, y + BLOCK_HEIGHT)
            })

            // Добавляем отступы
            const padding = 50
            const contentWidth = maxX - minX + padding * 2
            const contentHeight = maxY - minY + padding * 2

            // Получаем размеры контейнера диаграммы (примерные, можно улучшить через ref)
            const containerWidth = 1200 // Примерная ширина контейнера
            const containerHeight = 600 // Примерная высота контейнера

            // Вычисляем нужный zoom
            const zoomX = containerWidth / contentWidth
            const zoomY = containerHeight / contentHeight
            const newZoom = Math.min(zoomX, zoomY, 2) // Ограничиваем максимальный zoom до 2
            const finalZoom = Math.max(newZoom, 0.5) // Ограничиваем минимальный zoom до 0.5

            // Вычисляем центр контента
            const centerX = (minX + maxX) / 2
            const centerY = (minY + maxY) / 2

            // Вычисляем нужный panOffset для центрирования
            // Центрируем контент в видимой области
            const contentCenterX = (minX + maxX) / 2
            const contentCenterY = (minY + maxY) / 2
            
            // Вычисляем смещение для центрирования
            const newPanX = (containerWidth / 2) - (contentCenterX * finalZoom)
            const newPanY = (containerHeight / 2) - (contentCenterY * finalZoom)

            zoom.value = finalZoom
            panOffset.value = { x: newPanX, y: newPanY }
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

            const allBlocks = []
            let currentX = 50
            let currentY = 50
            const blockSpacingX = 200
            const blockSpacingY = 200
            const blocksPerRow = 5

            // Блок 1: Приветственное сообщение (/start)
            const block1 = {
                id: blockIdCounter++,
                label: '/start',
                x: currentX,
                y: currentY,
                method: 'sendMessage',
                methodData: {
                    text: '👋 Добро пожаловать в нашего бота!\n\nВыберите действие из меню ниже или используйте команды:\n/help - помощь\n/info - информация\n/settings - настройки',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null,
                command: '/start'
            }
            allBlocks.push(block1)
            currentX += blockSpacingX

            // Блок 2: Reply-кнопки
            const block2 = {
                id: blockIdCounter++,
                label: 'Reply-кнопки',
                x: currentX,
                y: currentY,
                method: 'replyKeyboard',
                methodData: {
                    keyboard: [
                        [{ text: '📋 Информация' }, { text: '❓ Помощь' }],
                        [{ text: '⚙️ Настройки' }, { text: '📞 Контакты' }],
                        [{ text: '🔙 Назад' }]
                    ],
                    resize_keyboard: true,
                    one_time_keyboard: false
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block2)
            currentX += blockSpacingX

            // Блок 3: Inline кнопки
            const block3 = {
                id: blockIdCounter++,
                label: 'Inline кнопки',
                x: currentX,
                y: currentY,
                method: 'inlineKeyboard',
                methodData: {
                    inline_keyboard: [
                        [{ text: '✅ Подтвердить', callback_data: 'confirm' }, { text: '❌ Отменить', callback_data: 'cancel' }],
                        [{ text: '🌐 Открыть сайт', url: 'https://example.com' }],
                        [{ text: '📱 Поделиться', switch_inline_query: 'Поделиться ботом' }]
                    ]
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block3)
            currentX += blockSpacingX

            // Блок 4: Опрос
            const block4 = {
                id: blockIdCounter++,
                label: 'Опрос',
                x: currentX,
                y: currentY,
                method: 'sendPoll',
                methodData: {
                    question: '📊 Оцените качество нашего сервиса',
                    options: ['⭐ Отлично', '👍 Хорошо', '😐 Нормально', '👎 Плохо', '💔 Очень плохо'],
                    is_anonymous: false,
                    type: 'regular'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block4)
            currentX += blockSpacingX

            // Блок 5: Кубик
            const block5 = {
                id: blockIdCounter++,
                label: 'Кубик',
                x: currentX,
                y: currentY,
                method: 'sendDice',
                methodData: {
                    emoji: '🎲'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block5)
            
            // Переход на новую строку
            currentX = 50
            currentY += blockSpacingY

            // Блок 6: Фото
            const block6 = {
                id: blockIdCounter++,
                label: 'Фото',
                x: currentX,
                y: currentY,
                method: 'sendPhoto',
                methodData: {
                    photo: '/upload/obshhaia/692030a474249_1763717284.png', // Тестовое изображение
                    caption: '📷 Пример фотографии\n\nЭто тестовое изображение для демонстрации функционала отправки фото.',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block6)
            currentX += blockSpacingX

            // Блок 7: Видео
            const block7 = {
                id: blockIdCounter++,
                label: 'Видео',
                x: currentX,
                y: currentY,
                method: 'sendVideo',
                methodData: {
                    video: '/upload/video/69233d131b3ad_1763917075.mp4', // Тестовое видео
                    caption: '🎥 Пример видео\n\nДемонстрация отправки видео файла через бота.',
                    parse_mode: 'HTML',
                    duration: 60,
                    width: 1280,
                    height: 720
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block7)
            currentX += blockSpacingX

            // Блок 8: Документ
            const block8 = {
                id: blockIdCounter++,
                label: 'Документ',
                x: currentX,
                y: currentY,
                method: 'sendDocument',
                methodData: {
                    document: '/upload/dokumenty/69233d3782780_1763917111.html', // Тестовый документ
                    caption: '📄 Пример документа\n\nИнструкция по использованию бота.',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block8)
            currentX += blockSpacingX

            // Блок 9: Аудио
            const block9 = {
                id: blockIdCounter++,
                label: 'Аудио',
                x: currentX,
                y: currentY,
                method: 'sendAudio',
                methodData: {
                    audio: null, // Аудио файлы отсутствуют, выберите через FilePickerButton
                    caption: '🎵 Пример аудио файла',
                    parse_mode: '',
                    duration: 180,
                    performer: 'Исполнитель',
                    title: 'Название трека'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block9)
            currentX += blockSpacingX

            // Блок 10: Голосовое
            const block10 = {
                id: blockIdCounter++,
                label: 'Голосовое',
                x: currentX,
                y: currentY,
                method: 'sendVoice',
                methodData: {
                    voice: null, // Голосовые файлы отсутствуют, выберите через FilePickerButton
                    caption: '🎤 Голосовое сообщение\n\nПривет! Это пример голосового сообщения.',
                    parse_mode: 'HTML',
                    duration: 30
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block10)
            
            // Переход на новую строку
            currentX = 50
            currentY += blockSpacingY

            // Блок 11: Видео-кружок
            const block11 = {
                id: blockIdCounter++,
                label: 'Видео-кружок',
                x: currentX,
                y: currentY,
                method: 'sendVideoNote',
                methodData: {
                    video_note: '/upload/video/69233d131b3ad_1763917075.mp4', // Используем то же видео
                    duration: 15,
                    length: 360
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block11)
            currentX += blockSpacingX

            // Блок 12: Анимация
            const block12 = {
                id: blockIdCounter++,
                label: 'Анимация',
                x: currentX,
                y: currentY,
                method: 'sendAnimation',
                methodData: {
                    animation: '/upload/obshhaia/692030bfe4a64_1763717311.png', // Тестовое изображение (можно использовать как GIF)
                    caption: '🎞️ Пример анимации GIF\n\nДемонстрация отправки анимированного изображения.',
                    parse_mode: 'HTML',
                    duration: 5,
                    width: 480,
                    height: 480
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block12)
            currentX += blockSpacingX

            // Блок 13: Стикер
            const block13 = {
                id: blockIdCounter++,
                label: 'Стикер',
                x: currentX,
                y: currentY,
                method: 'sendSticker',
                methodData: {
                    sticker: null // Введите file_id стикера из Telegram или выберите файл через FilePickerButton
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block13)
            currentX += blockSpacingX

            // Блок 14: Локация
            const block14 = {
                id: blockIdCounter++,
                label: 'Локация',
                x: currentX,
                y: currentY,
                method: 'sendLocation',
                methodData: {
                    latitude: 55.7558,
                    longitude: 37.6173,
                    horizontal_accuracy: 50,
                    live_period: 3600,
                    heading: 90,
                    proximity_alert_radius: 100
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block14)
            currentX += blockSpacingX

            // Блок 15: Место/Заведение
            const block15 = {
                id: blockIdCounter++,
                label: 'Место',
                x: currentX,
                y: currentY,
                method: 'sendVenue',
                methodData: {
                    latitude: 55.7558,
                    longitude: 37.6173,
                    title: '📍 Красная площадь',
                    address: 'Москва, Красная площадь, 1'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block15)
            
            // Переход на новую строку
            currentX = 50
            currentY += blockSpacingY

            // Блок 16: Контакт
            const block16 = {
                id: blockIdCounter++,
                label: 'Контакт',
                x: currentX,
                y: currentY,
                method: 'sendContact',
                methodData: {
                    phone_number: '+79991234567',
                    first_name: 'Иван',
                    last_name: 'Иванов'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block16)
            currentX += blockSpacingX

            // Блок 17: Группа медиа
            const block17 = {
                id: blockIdCounter++,
                label: 'Группа медиа',
                x: currentX,
                y: currentY,
                method: 'sendMediaGroup',
                methodData: {
                    media: [
                        { 
                            type: 'photo', 
                            media: '/upload/obshhaia/692030a474249_1763717284.png', // Тестовое фото 1
                            caption: 'Фото 1 из галереи'
                        },
                        { 
                            type: 'photo', 
                            media: '/upload/obshhaia/692030bfe4a64_1763717311.png', // Тестовое фото 2
                            caption: 'Фото 2 из галереи'
                        },
                        { 
                            type: 'photo', 
                            media: '/upload/obshhaia/692030bfe6723_1763717311.png', // Тестовое фото 3
                            caption: 'Фото 3 из галереи'
                        }
                    ]
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block17)
            currentX += blockSpacingX

            // Блок 18: Индикатор действия
            const block18 = {
                id: blockIdCounter++,
                label: 'Действие',
                x: currentX,
                y: currentY,
                method: 'sendChatAction',
                methodData: {
                    action: 'upload_photo'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block18)
            currentX += blockSpacingX

            // Блок 19: Задать вопрос
            const block19 = {
                id: blockIdCounter++,
                label: 'Вопрос',
                x: currentX,
                y: currentY,
                method: 'question',
                methodData: {
                    text: '❓ Какой у вас вопрос?\n\nОпишите вашу проблему или задайте вопрос, и мы постараемся помочь вам как можно скорее.',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block19)
            currentX += blockSpacingX

            // Блок 20: Чат с менеджером
            const block20 = {
                id: blockIdCounter++,
                label: 'Менеджер',
                x: currentX,
                y: currentY,
                method: 'managerChat',
                methodData: {
                    text: '💬 Переключение на менеджера...\n\nВаш запрос будет передан менеджеру. Ожидайте ответа в ближайшее время.',
                    manager_chat_id: '123456789'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block20)
            
            // Переход на новую строку
            currentX = 50
            currentY += blockSpacingY

            // Блок 21: Редактировать текст
            // ВАЖНО: Для редактирования необходимо указать message_id из предыдущего блока
            // Например, если предыдущий блок отправил сообщение с message_id: 214,
            // укажите это значение здесь
            const block21 = {
                id: blockIdCounter++,
                label: 'Редактировать',
                x: currentX,
                y: currentY,
                method: 'editMessageText',
                methodData: {
                    message_id: null, // ВАЖНО: Укажите message_id из ответа предыдущего блока отправки сообщения
                    text: '✏️ Обновленный текст сообщения\n\nЭто сообщение было отредактировано для демонстрации функционала редактирования.',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block21)
            currentX += blockSpacingX

            // Блок 22: Редактировать подпись
            // ВАЖНО: Для редактирования необходимо указать message_id из предыдущего блока с медиа
            const block22 = {
                id: blockIdCounter++,
                label: 'Редакт. подпись',
                x: currentX,
                y: currentY,
                method: 'editMessageCaption',
                methodData: {
                    message_id: null, // ВАЖНО: Укажите message_id из ответа предыдущего блока отправки медиа (photo, video и т.д.)
                    caption: '📝 Обновленная подпись к медиа\n\nПодпись была изменена.',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block22)
            currentX += blockSpacingX

            // Блок 23: Удалить сообщение
            // ВАЖНО: Для удаления необходимо указать message_id из предыдущего блока
            const block23 = {
                id: blockIdCounter++,
                label: 'Удалить',
                x: currentX,
                y: currentY,
                method: 'deleteMessage',
                methodData: {
                    message_id: null // ВАЖНО: Укажите message_id из ответа предыдущего блока отправки сообщения
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block23)
            currentX += blockSpacingX

            // Блок 24: Закрепить сообщение
            // ВАЖНО: Если message_id не указан, система автоматически использует последнее отправленное сообщение
            const block24 = {
                id: blockIdCounter++,
                label: 'Закрепить',
                x: currentX,
                y: currentY,
                method: 'pinChatMessage',
                methodData: {
                    message_id: null, // ВАЖНО: Если null, система автоматически использует последний message_id из кеша
                    disable_notification: false
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block24)
            currentX += blockSpacingX

            // Блок 25: Открепить сообщение
            // ВАЖНО: Для unpinChatMessage message_id необязателен (если не указан, открепляется последнее закрепленное сообщение)
            const block25 = {
                id: blockIdCounter++,
                label: 'Открепить',
                x: currentX,
                y: currentY,
                method: 'unpinChatMessage',
                methodData: {
                    message_id: null // ВАЖНО: Если null, открепляется последнее закрепленное сообщение
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block25)
            
            // Переход на новую строку
            currentX = 50
            currentY += blockSpacingY

            // Блок 26: AI Ассистент
            const block26 = {
                id: blockIdCounter++,
                label: 'AI Ассистент',
                x: currentX,
                y: currentY,
                method: 'assistant',
                methodData: {
                    text: '🤖 Привет! Я AI ассистент.\n\nЗадайте мне любой вопрос, и я постараюсь помочь вам. Я могу отвечать на вопросы, давать советы и помогать с различными задачами.',
                    model: 'gpt-3.5-turbo',
                    temperature: 0.7,
                    max_tokens: 1000
                },
                nextAction: 'end',
                nextBlockId: null
            }
            allBlocks.push(block26)
            currentX += blockSpacingX

            // Блок 27: API Запрос
            const block27 = {
                id: blockIdCounter++,
                label: 'API Запрос',
                x: currentX,
                y: currentY,
                method: 'apiRequest',
                methodData: {
                    url: 'https://api.example.com/data', // Замените на реальный URL вашего API
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer token123' // Замените на реальный токен
                    },
                    body: JSON.stringify({ key: 'value' }),
                    response_variable: 'api_response'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block27)
            currentX += blockSpacingX

            // Блок 28: API Кнопки
            const block28 = {
                id: blockIdCounter++,
                label: 'API Кнопки',
                x: currentX,
                y: currentY,
                method: 'apiButtons',
                methodData: {
                    url: 'https://api.example.com/buttons', // Замените на реальный URL вашего API
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    text: 'Выберите действие:',
                    parse_mode: 'HTML'
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block28)
            currentX += blockSpacingX

            // Блок 29: API Группа медиа
            const block29 = {
                id: blockIdCounter++,
                label: 'API Медиа',
                x: currentX,
                y: currentY,
                method: 'apiMediaGroup',
                methodData: {
                    url: 'https://api.example.com/media', // Замените на реальный URL вашего API
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                },
                nextAction: 'specific',
                nextBlockId: null
            }
            allBlocks.push(block29)

            // Устанавливаем связи между блоками (цепочка)
            for (let i = 0; i < allBlocks.length - 1; i++) {
                allBlocks[i].nextBlockId = allBlocks[i + 1].id
            }

            // Добавляем все блоки в массив
            blocks.value = allBlocks

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
        onMounted(async () => {
            // Загружаем сохраненные блоки из БД
            await loadBlocks()
            
            // Если у бота нет карты (блоков), показываем дефолтные тестовые блоки
            // Дефолтные блоки показываются ТОЛЬКО если карта полностью отсутствует
            if (blocks.value.length === 0) {
                createDefaultTestBlocks()
            }
            // Если блоки есть в БД - используем их, дефолтные НЕ создаем
        })

        return {
            showCommandModal,
            showSettingsSidebar,
            showTestModal,
            selectedBlock,
            zoom,
            panOffset,
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
            handleBlockUpdate,
            handleBlockSave,
            handleBlockDelete,
            zoomIn,
            zoomOut,
            fitToScreen,
            handleImport,
            handleExport,
            handleSaveBlocks,
            isSavingBlocks,
            runTest,
            closeTestModal,
            getChatId,
            isLoadingChatId,
            availableChatIds
        }
    }
}
</script>

