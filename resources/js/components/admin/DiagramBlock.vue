<template>
    <div
        :id="`block-${block.id}`"
        class="diagram-block absolute cursor-move select-none"
        :style="blockStyle"
        @mousedown="handleMouseDown"
        @click.stop="handleClick"
    >
        <div
            class="block-content rounded-lg border-2 p-3 shadow-lg transition-all hover:shadow-xl"
            :class="blockClasses"
        >
            <!-- Заголовок блока -->
            <div class="mb-2">
                <div class="text-xs font-semibold truncate">{{ blockLabel }}</div>
            </div>

            <!-- Визуальное представление метода -->
            <div class="text-xs">
                <!-- Кнопки - визуализация кнопок -->
                <div v-if="block.method === 'replyKeyboard' && block.methodData?.keyboard" class="space-y-1">
                    <div
                        v-for="(row, rowIndex) in block.methodData.keyboard.slice(0, 2)"
                        :key="rowIndex"
                        class="flex gap-1"
                    >
                        <div
                            v-for="(button, btnIndex) in row.slice(0, 2)"
                            :key="btnIndex"
                            class="flex-1 h-6 bg-green-200/50 border border-green-400 rounded text-[9px] flex items-center justify-center px-1 truncate"
                            :title="button.text || 'Кнопка'"
                        >
                            {{ button.text || 'Кнопка' }}
                        </div>
                    </div>
                </div>
                <div v-else-if="block.method === 'inlineKeyboard' && block.methodData?.inline_keyboard" class="space-y-1">
                    <div
                        v-for="(row, rowIndex) in block.methodData.inline_keyboard.slice(0, 2)"
                        :key="rowIndex"
                        class="flex gap-1"
                    >
                        <div
                            v-for="(button, btnIndex) in row.slice(0, 2)"
                            :key="btnIndex"
                            class="flex-1 h-6 bg-green-200/50 border border-green-400 rounded text-[9px] flex items-center justify-center px-1 truncate"
                            :title="button.text || 'Кнопка'"
                        >
                            {{ button.text || 'Кнопка' }}
                        </div>
                    </div>
                </div>
                <!-- Сообщение - визуализация текста -->
                <div v-else-if="block.method === 'sendMessage' && block.methodData?.text" class="p-2 bg-blue-100/30 border border-blue-300 rounded text-[10px] max-h-16 overflow-hidden">
                    <div class="line-clamp-2">{{ block.methodData.text }}</div>
                </div>
                <!-- Опрос - визуализация опроса -->
                <div v-else-if="block.method === 'sendPoll' && block.methodData?.question" class="space-y-1">
                    <div class="p-1 bg-yellow-100/30 border border-yellow-300 rounded text-[9px] truncate">
                        {{ block.methodData.question }}
                    </div>
                    <div class="space-y-0.5">
                        <div
                            v-for="(option, index) in (block.methodData.options || []).slice(0, 2)"
                            :key="index"
                            class="h-3 bg-yellow-200/50 border border-yellow-400 rounded text-[8px] flex items-center px-1 truncate"
                        >
                            {{ option }}
                        </div>
                    </div>
                </div>
                <!-- Контакт - визуализация контакта -->
                <div v-else-if="block.method === 'sendContact' && block.methodData?.first_name" class="p-2 bg-purple-100/30 border border-purple-300 rounded text-[10px]">
                    <div class="font-semibold">{{ block.methodData.first_name }}</div>
                    <div class="text-[9px] text-muted-foreground">{{ block.methodData.phone_number }}</div>
                </div>
                <!-- Локация - визуализация локации -->
                <div v-else-if="block.method === 'sendVenue' && block.methodData?.title" class="p-2 bg-orange-100/30 border border-orange-300 rounded text-[10px]">
                    <div class="font-semibold truncate">{{ block.methodData.title }}</div>
                    <div class="text-[9px] text-muted-foreground truncate">{{ block.methodData.address }}</div>
                </div>
                <!-- Кубик - визуализация кубика -->
                <div v-else-if="block.method === 'sendDice'" class="flex items-center justify-center p-2 text-2xl">
                    {{ block.methodData?.emoji || '🎲' }}
                </div>
                <!-- Удаление - визуализация удаления -->
                <div v-else-if="block.method === 'deleteMessage'" class="flex items-center justify-center p-2">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <!-- Другие методы -->
                <div v-else-if="block.method" class="text-muted-foreground text-center py-1">
                    {{ getMethodLabel(block.method) }}
                </div>
                <!-- Не выбран -->
                <div v-else class="text-muted-foreground text-center py-1">
                    Не выбран
                </div>
            </div>

            <!-- Точка подключения слева (для входящих стрелок) -->
            <div 
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-accent border-2 border-background z-10"
                style="pointer-events: none;"
            ></div>
            
            <!-- Точка подключения справа (для исходящих стрелок) -->
            <div 
                class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 w-3 h-3 rounded-full bg-accent border-2 border-background z-10"
                style="pointer-events: none;"
            ></div>

            <!-- Кнопки действий -->
            <div class="absolute top-1 right-1 flex gap-1 z-20">
                <button
                    @click.stop="handleSettingsClick"
                    class="p-1 rounded hover:bg-background/50 transition-colors bg-background/80"
                    title="Настройки"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
                <button
                    @click.stop="handleDeleteClick"
                    class="p-1 rounded hover:bg-destructive/20 transition-colors bg-background/80"
                    title="Удалить"
                >
                    <svg class="w-4 h-4 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'

export default {
    name: 'DiagramBlock',
    props: {
        block: {
            type: Object,
            required: true
        }
    },
    emits: ['click', 'settings', 'move', 'delete'],
    setup(props, { emit }) {
        const blockStyle = computed(() => ({
            left: `${props.block.x || 0}px`,
            top: `${props.block.y || 0}px`,
            width: '120px',
            minHeight: '100px'
        }))

        // Цветовая схема по типам методов
        const blockClasses = computed(() => {
            const method = props.block.method
            const baseClasses = 'bg-card border-2'
            
            if (!method) {
                return `${baseClasses} border-muted`
            }

            // Отправка сообщений - синий
            if (['sendMessage', 'sendDice', 'sendPoll', 'sendVenue', 'sendContact', 'question'].includes(method)) {
                return `${baseClasses} border-blue-500 bg-blue-50/10`
            }

            // Медиа - фиолетовый
            if (['sendPhoto', 'sendVideo', 'sendDocument', 'sendAudio', 'sendVoice', 'sendVideoNote', 'sendAnimation', 'sendSticker', 'sendLocation', 'sendMediaGroup'].includes(method)) {
                return `${baseClasses} border-purple-500 bg-purple-50/10`
            }

            // Редактирование - желтый
            if (['editMessageText', 'editMessageCaption'].includes(method)) {
                return `${baseClasses} border-yellow-500 bg-yellow-50/10`
            }

            // Управление - красный
            if (['deleteMessage', 'pinChatMessage', 'unpinChatMessage', 'sendChatAction'].includes(method)) {
                return `${baseClasses} border-red-500 bg-red-50/10`
            }

            // Кнопки - зеленый
            if (['replyKeyboard', 'inlineKeyboard'].includes(method)) {
                return `${baseClasses} border-green-500 bg-green-50/10`
            }

            // Специальные функции - оранжевый
            if (['managerChat', 'apiRequest', 'apiButtons', 'apiMediaGroup', 'assistant'].includes(method)) {
                return `${baseClasses} border-orange-500 bg-orange-50/10`
            }

            return `${baseClasses} border-muted`
        })

        const blockLabel = computed(() => {
            return props.block.label || `Блок #${props.block.id}`
        })

        const getMethodLabel = (method) => {
            const labels = {
                sendMessage: 'Сообщение',
                sendDice: '🎲 Кубик',
                sendPoll: '📊 Опрос',
                sendVenue: '📍 Локация',
                sendContact: '👤 Контакт',
                sendPhoto: '📷 Фото',
                sendVideo: '🎥 Видео',
                sendDocument: '📄 Документ',
                sendAudio: '🎵 Аудио',
                sendVoice: '🎤 Голосовое',
                sendVideoNote: '🎬 Видео-кружок',
                sendAnimation: '🎞️ Анимация',
                sendSticker: '😊 Стикер',
                sendLocation: '📍 Локация',
                sendMediaGroup: '🖼️ Группа медиа',
                sendChatAction: '⏳ Действие',
                editMessageText: 'Редактировать',
                editMessageCaption: 'Редактировать',
                deleteMessage: 'Удалить',
                pinChatMessage: 'Закрепить',
                unpinChatMessage: 'Открепить',
                replyKeyboard: 'Reply-кнопки',
                inlineKeyboard: 'Inline кнопки',
                question: 'Задать вопрос',
                managerChat: '💬 Менеджер',
                apiRequest: '🌐 API',
                apiButtons: '🔘 API Кнопки',
                apiMediaGroup: '🖼️ API Медиа',
                assistant: '🤖 AI'
            }
            return labels[method] || method
        }

        let isDragging = false
        let startX = 0
        let startY = 0
        let startLeft = 0
        let startTop = 0

        const handleMouseDown = (e) => {
            isDragging = true
            startX = e.clientX
            startY = e.clientY
            startLeft = props.block.x || 0
            startTop = props.block.y || 0

            document.addEventListener('mousemove', handleMouseMove)
            document.addEventListener('mouseup', handleMouseUp)
        }

        const handleMouseMove = (e) => {
            if (!isDragging) return

            const deltaX = e.clientX - startX
            const deltaY = e.clientY - startY

            emit('move', {
                id: props.block.id,
                x: startLeft + deltaX,
                y: startTop + deltaY
            })
        }

        const handleMouseUp = () => {
            isDragging = false
            document.removeEventListener('mousemove', handleMouseMove)
            document.removeEventListener('mouseup', handleMouseUp)
        }

        const handleClick = () => {
            emit('click', props.block)
        }

        const handleSettingsClick = () => {
            emit('settings', props.block)
        }

        const handleDeleteClick = () => {
            emit('delete', props.block.id)
        }

        return {
            blockStyle,
            blockClasses,
            blockLabel,
            getMethodLabel,
            handleMouseDown,
            handleClick,
            handleSettingsClick,
            handleDeleteClick
        }
    }
}
</script>

<style scoped>
.diagram-block {
    z-index: 10;
}

.block-content {
    min-width: 120px;
}
</style>

