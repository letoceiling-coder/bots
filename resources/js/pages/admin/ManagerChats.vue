<template>
    <div class="manager-chats-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Диалоги с менеджерами</h1>
                <p class="text-muted-foreground mt-1">Просмотр всех диалогов между пользователями и менеджерами</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-lg border border-border p-4 flex items-center gap-4 flex-wrap">
            <div>
                <label for="botFilter" class="block text-xs font-medium text-muted-foreground mb-1">Бот</label>
                <select
                    id="botFilter"
                    v-model="filters.bot_id"
                    @change="fetchDialogues"
                    class="h-9 px-3 border border-border rounded bg-background text-sm"
                >
                    <option :value="null">Все боты</option>
                    <option v-for="bot in allBots" :key="bot.id" :value="bot.id">{{ bot.name }}</option>
                </select>
            </div>
            <div>
                <label for="managerFilter" class="block text-xs font-medium text-muted-foreground mb-1">Менеджер</label>
                <select
                    id="managerFilter"
                    v-model="filters.manager_id"
                    @change="fetchDialogues"
                    class="h-9 px-3 border border-border rounded bg-background text-sm"
                >
                    <option :value="null">Все менеджеры</option>
                    <option v-for="manager in managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="searchQuery" class="block text-xs font-medium text-muted-foreground mb-1">Поиск</label>
                <input
                    id="searchQuery"
                    v-model="filters.search"
                    @input="debouncedSearch"
                    type="text"
                    placeholder="Имя пользователя, username, ID"
                    class="w-full h-9 px-3 border border-border rounded bg-background text-sm"
                />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка диалогов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Dialogues List -->
        <div v-if="!loading && dialogues.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Пользователь</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Менеджер</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Бот</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Сообщений</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Начало</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Последнее сообщение</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="dialogue in dialogues" :key="dialogue.session_id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div class="font-medium">{{ dialogue.user_name || 'Неизвестно' }}</div>
                                <div class="text-xs text-muted-foreground">@{{ dialogue.user_username || 'нет username' }}</div>
                                <div class="text-xs text-muted-foreground">ID: {{ dialogue.user_chat_id }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div class="font-medium">{{ dialogue.manager_name || 'Неизвестно' }}</div>
                                <div class="text-xs text-muted-foreground">@{{ dialogue.manager_username || 'нет username' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ dialogue.bot_name || 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ dialogue.messages_count }}</td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ new Date(dialogue.first_message_at).toLocaleString('ru-RU') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ new Date(dialogue.last_message_at).toLocaleString('ru-RU') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button
                                    @click="openDialogue(dialogue.session_id)"
                                    class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                >
                                    Открыть
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex items-center justify-between p-4 border-t border-border">
                <button
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 text-sm bg-muted rounded disabled:opacity-50"
                >
                    Предыдущая
                </button>
                <span class="text-sm text-muted-foreground">
                    Страница {{ pagination.current_page }} из {{ pagination.last_page }}
                </span>
                <button
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 text-sm bg-muted rounded disabled:opacity-50"
                >
                    Следующая
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && dialogues.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Диалоги не найдены</p>
        </div>

        <!-- Media Viewer Modal -->
        <div v-if="showMediaViewer && currentMediaUrl" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 backdrop-blur-sm" @click.self="closeMediaViewer">
            <div class="relative max-w-5xl max-h-[90vh] p-4">
                <button
                    @click="closeMediaViewer"
                    class="absolute top-4 right-4 p-2 bg-black/50 hover:bg-black/70 rounded-full text-white transition-colors z-10"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img
                    v-if="currentMediaType === 'photo' || currentMediaType === 'animation' || currentMediaType === 'sticker'"
                    :src="currentMediaUrl"
                    alt="Медиа"
                    class="max-w-full max-h-[90vh] rounded-lg"
                    @error="handleImageError"
                />
                <video
                    v-else-if="currentMediaType === 'video' || currentMediaType === 'video_note'"
                    :src="currentMediaUrl"
                    controls
                    class="max-w-full max-h-[90vh] rounded-lg"
                    autoplay
                >
                    Ваш браузер не поддерживает видео.
                </video>
            </div>
        </div>

        <!-- Dialogue Modal -->
        <div v-if="showDialogueModal && selectedDialogue" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Диалог</h3>
                        <p class="text-sm text-muted-foreground">
                            Пользователь: {{ selectedDialogue.session?.user_name || 'Неизвестно' }}
                            <span v-if="selectedDialogue.manager"> | Менеджер: {{ selectedDialogue.manager.name }}</span>
                        </p>
                    </div>
                    <button
                        @click="closeDialogue"
                        class="p-2 hover:bg-muted rounded transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div
                        v-for="message in selectedDialogue.messages"
                        :key="message.id"
                        class="flex"
                        :class="message.direction === 'user_to_manager' ? 'justify-start' : 'justify-end'"
                    >
                        <div
                            class="max-w-[70%] rounded-lg p-3"
                            :class="message.direction === 'user_to_manager' ? 'bg-muted/50' : 'bg-blue-500/20'"
                        >
                            <div class="text-xs text-muted-foreground mb-1">
                                {{ message.direction === 'user_to_manager' ? 'Пользователь' : 'Менеджер' }}
                                <span class="ml-2">{{ new Date(message.created_at).toLocaleString('ru-RU') }}</span>
                            </div>
                            <div v-if="message.message_text" class="text-sm text-foreground mb-2">
                                {{ message.message_text }}
                            </div>
                            <div v-if="message.message_type !== 'text'" class="text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <svg v-if="message.message_type === 'photo'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="message.message_type === 'video'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="message.message_type === 'document'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <svg v-else-if="message.message_type === 'voice'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                    </svg>
                                    <span>{{ getMessageTypeLabel(message.message_type) }}</span>
                                </span>
                            </div>
                            <div v-if="message.telegram_data && message.message_type !== 'text'" class="mt-2 space-y-2">
                                <!-- Фото -->
                                <div v-if="message.message_type === 'photo' && message.telegram_data.photo" class="space-y-2">
                                    <div class="relative">
                                        <img
                                            v-if="getLargestPhotoFileId(message.telegram_data.photo)"
                                            :src="getMediaUrl(getLargestPhotoFileId(message.telegram_data.photo), 'photo')"
                                            alt="Фото"
                                            class="max-w-md rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                                            @click="openMediaViewer(getMediaUrl(getLargestPhotoFileId(message.telegram_data.photo), 'photo'), 'photo')"
                                            @error="handleImageError"
                                            loading="lazy"
                                        />
                                        <div v-else class="p-4 bg-muted/30 rounded-lg text-xs text-muted-foreground">
                                            Фото недоступно (file_id не найден)
                                        </div>
                                    </div>
                                    <p v-if="message.telegram_data.caption" class="text-xs text-muted-foreground italic">{{ message.telegram_data.caption }}</p>
                                </div>
                                <!-- Видео -->
                                <div v-else-if="message.message_type === 'video' && message.telegram_data.video" class="space-y-2">
                                    <video
                                        v-if="message.telegram_data.video.file_id"
                                        :src="getMediaUrl(message.telegram_data.video.file_id, 'video')"
                                        controls
                                        class="max-w-md rounded-lg"
                                        @error="handleVideoError"
                                        preload="metadata"
                                    >
                                        Ваш браузер не поддерживает видео.
                                    </video>
                                    <div v-else class="p-4 bg-muted/30 rounded-lg text-xs text-muted-foreground">
                                        Видео недоступно (file_id не найден)
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        <p>🎥 {{ message.telegram_data.video.file_name || 'Видео файл' }}</p>
                                        <p v-if="message.telegram_data.video.file_size">Размер: {{ formatFileSize(message.telegram_data.video.file_size) }}</p>
                                        <p v-if="message.telegram_data.video.duration">Длительность: {{ formatDuration(message.telegram_data.video.duration) }}</p>
                                        <p v-if="message.telegram_data.caption" class="italic mt-1">{{ message.telegram_data.caption }}</p>
                                    </div>
                                </div>
                                <!-- Документ -->
                                <div v-else-if="message.message_type === 'document' && message.telegram_data.document" class="space-y-2">
                                    <div class="flex items-center gap-2 p-2 bg-muted/30 rounded-lg">
                                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">{{ message.telegram_data.document.file_name || 'Документ' }}</p>
                                            <p v-if="message.telegram_data.document.file_size" class="text-xs text-muted-foreground">
                                                Размер: {{ formatFileSize(message.telegram_data.document.file_size) }}
                                            </p>
                                        </div>
                                        <a
                                            :href="getMediaUrl(message.telegram_data.document.file_id, 'document')"
                                            target="_blank"
                                            class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                            download
                                        >
                                            Скачать
                                        </a>
                                    </div>
                                    <p v-if="message.telegram_data.caption" class="text-xs text-muted-foreground italic">{{ message.telegram_data.caption }}</p>
                                </div>
                                <!-- Голосовое сообщение -->
                                <div v-else-if="message.message_type === 'voice' && message.telegram_data.voice" class="space-y-2">
                                    <audio
                                        :src="getMediaUrl(message.telegram_data.voice.file_id, 'voice')"
                                        controls
                                        class="w-full"
                                    >
                                        Ваш браузер не поддерживает аудио.
                                    </audio>
                                    <p class="text-xs text-muted-foreground">
                                        🎤 Голосовое сообщение
                                        <span v-if="message.telegram_data.voice.duration"> • {{ formatDuration(message.telegram_data.voice.duration) }}</span>
                                    </p>
                                </div>
                                <!-- Аудио -->
                                <div v-else-if="message.message_type === 'audio' && message.telegram_data.audio" class="space-y-2">
                                    <audio
                                        :src="getMediaUrl(message.telegram_data.audio.file_id, 'audio')"
                                        controls
                                        class="w-full"
                                    >
                                        Ваш браузер не поддерживает аудио.
                                    </audio>
                                    <div class="text-xs text-muted-foreground">
                                        <p>🎵 {{ message.telegram_data.audio.title || 'Аудио файл' }}</p>
                                        <p v-if="message.telegram_data.audio.performer">Исполнитель: {{ message.telegram_data.audio.performer }}</p>
                                        <p v-if="message.telegram_data.audio.duration">Длительность: {{ formatDuration(message.telegram_data.audio.duration) }}</p>
                                    </div>
                                </div>
                                <!-- Видеосообщение (круглое) -->
                                <div v-else-if="message.message_type === 'video_note' && message.telegram_data.video_note" class="space-y-2">
                                    <video
                                        :src="getMediaUrl(message.telegram_data.video_note.file_id, 'video_note')"
                                        controls
                                        class="max-w-xs rounded-full"
                                        @error="handleVideoError"
                                    >
                                        Ваш браузер не поддерживает видео.
                                    </video>
                                    <p class="text-xs text-muted-foreground">
                                        📹 Видеосообщение (круглое)
                                        <span v-if="message.telegram_data.video_note.duration"> • {{ formatDuration(message.telegram_data.video_note.duration) }}</span>
                                    </p>
                                </div>
                                <!-- GIF/Анимация -->
                                <div v-else-if="message.message_type === 'animation' && message.telegram_data.animation" class="space-y-2">
                                    <img
                                        :src="getMediaUrl(message.telegram_data.animation.file_id, 'animation')"
                                        :alt="message.telegram_data.animation.file_name || 'GIF'"
                                        class="max-w-md rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="openMediaViewer(getMediaUrl(message.telegram_data.animation.file_id, 'animation'), 'animation')"
                                        @error="handleImageError"
                                        loading="lazy"
                                    />
                                    <p class="text-xs text-muted-foreground">🎬 {{ message.telegram_data.animation.file_name || 'GIF/Анимация' }}</p>
                                </div>
                                <!-- Стикер -->
                                <div v-else-if="message.message_type === 'sticker' && message.telegram_data.sticker" class="space-y-2">
                                    <img
                                        :src="getMediaUrl(message.telegram_data.sticker.file_id, 'sticker')"
                                        alt="Стикер"
                                        class="max-w-xs cursor-pointer hover:opacity-90 transition-opacity"
                                        @click="openMediaViewer(getMediaUrl(message.telegram_data.sticker.file_id, 'sticker'), 'sticker')"
                                        @error="handleImageError"
                                        loading="lazy"
                                    />
                                    <p class="text-xs text-muted-foreground">😊 Стикер</p>
                                </div>
                                <div v-else-if="message.message_type === 'location' && message.telegram_data.location" class="text-xs text-muted-foreground">
                                    <a
                                        :href="`https://www.google.com/maps?q=${message.telegram_data.location.latitude},${message.telegram_data.location.longitude}`"
                                        target="_blank"
                                        class="text-blue-500 hover:underline inline-flex items-center gap-1"
                                    >
                                        📍 Локация: {{ message.telegram_data.location.latitude }}, {{ message.telegram_data.location.longitude }}
                                    </a>
                                </div>
                                <div v-else-if="message.message_type === 'venue' && message.telegram_data.venue" class="text-xs text-muted-foreground">
                                    <p>📍 Место: {{ message.telegram_data.venue.title }}</p>
                                    <p v-if="message.telegram_data.venue.address">{{ message.telegram_data.venue.address }}</p>
                                </div>
                                <div v-else-if="message.message_type === 'contact' && message.telegram_data.contact" class="text-xs text-muted-foreground">
                                    <p>📞 Контакт: {{ message.telegram_data.contact.first_name }} {{ message.telegram_data.contact.last_name || '' }}</p>
                                    <p>Телефон: {{ message.telegram_data.contact.phone_number }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiGet } from '../../utils/api'

// Простая реализация debounce
const debounce = (func, wait) => {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

const loading = ref(false)
const error = ref(null)
const dialogues = ref([])
const allBots = ref([])
const managers = ref([])
const showDialogueModal = ref(false)
const selectedDialogue = ref(null)
const showMediaViewer = ref(false)
const currentMediaUrl = ref(null)
const currentMediaType = ref(null)
const filters = ref({
    bot_id: null,
    manager_id: null,
    search: '',
    page: 1,
})
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
})

const fetchBots = async () => {
    try {
        const response = await apiGet('/bots')
        if (response.ok) {
            const data = await response.json()
            allBots.value = data.data || []
        }
    } catch (err) {
        console.error('Error fetching bots:', err)
    }
}

const fetchManagers = async () => {
    try {
        const response = await apiGet('/manager-chats/managers', filters.value.bot_id ? { bot_id: filters.value.bot_id } : {})
        if (response.ok) {
            const data = await response.json()
            managers.value = data.data || []
        }
    } catch (err) {
        console.error('Error fetching managers:', err)
    }
}

const fetchDialogues = async () => {
    loading.value = true
    error.value = null
    try {
        const params = {
            page: filters.value.page,
            ...(filters.value.bot_id && { bot_id: filters.value.bot_id }),
            ...(filters.value.manager_id && { manager_id: filters.value.manager_id }),
            ...(filters.value.search && { search: filters.value.search }),
        }
        const response = await apiGet('/manager-chats', params)
        if (!response.ok) {
            throw new Error('Ошибка загрузки диалогов')
        }
        const data = await response.json()
        dialogues.value = data.data || []
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
        }
    } catch (err) {
        error.value = err.message || 'Ошибка загрузки диалогов'
    } finally {
        loading.value = false
    }
}

const debouncedSearch = debounce(() => {
    filters.value.page = 1
    fetchDialogues()
}, 300)

const changePage = (page) => {
    if (page > 0 && page <= pagination.value.last_page) {
        filters.value.page = page
        fetchDialogues()
    }
}

const openDialogue = async (sessionId) => {
    try {
        const response = await apiGet(`/manager-chats/${sessionId}`)
        if (!response.ok) {
            throw new Error('Ошибка загрузки диалога')
        }
        const data = await response.json()
        selectedDialogue.value = data
        showDialogueModal.value = true
    } catch (err) {
        error.value = err.message || 'Ошибка загрузки диалога'
    }
}

const closeDialogue = () => {
    showDialogueModal.value = false
    selectedDialogue.value = null
}

const getMessageTypeLabel = (type) => {
    const labels = {
        text: 'Текст',
        photo: 'Фото',
        video: 'Видео',
        document: 'Документ',
        audio: 'Аудио',
        voice: 'Голосовое сообщение',
        video_note: 'Видеосообщение',
        animation: 'GIF',
        sticker: 'Стикер',
        contact: 'Контакт',
        location: 'Локация',
        venue: 'Место',
    }
    return labels[type] || type
}


const formatFileSize = (bytes) => {
    if (!bytes) return 'Неизвестно'
    const sizes = ['Б', 'КБ', 'МБ', 'ГБ']
    if (bytes === 0) return '0 Б'
    const i = Math.floor(Math.log(bytes) / Math.log(1024))
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDuration = (seconds) => {
    if (!seconds) return 'Неизвестно'
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

const handleImageError = (event) => {
    console.error('Image load error:', {
        src: event.target.src,
        error: event,
    })
    event.target.style.display = 'none'
}

const handleVideoError = (event) => {
    console.error('Video load error:', {
        src: event.target.src,
        error: event,
        target: event.target,
    })
    // Показываем сообщение об ошибке вместо скрытия
    const errorMsg = document.createElement('div')
    errorMsg.className = 'text-xs text-red-500 mt-2'
    errorMsg.textContent = 'Ошибка загрузки видео'
    event.target.parentNode?.appendChild(errorMsg)
}

const getLargestPhotoFileId = (photos) => {
    if (!photos || !Array.isArray(photos) || photos.length === 0) return null
    // Telegram возвращает массив фото разных размеров, берем последнее (самое большое)
    return photos[photos.length - 1].file_id
}

const getMediaUrl = (fileId, type) => {
    if (!fileId) {
        console.warn('getMediaUrl: missing fileId', { type })
        return null
    }
    
    if (!selectedDialogue.value?.session?.id) {
        console.warn('getMediaUrl: missing session', {
            fileId,
            type,
        })
        return null
    }
    
    // Используем прокси endpoint для получения файла
    // Для изображений и видео используем прямой URL через прокси
    const baseUrl = window.location.origin
    // Кодируем file_id для безопасной передачи в URL
    const encodedFileId = encodeURIComponent(fileId)
    const url = `${baseUrl}/api/v1/manager-chats/file/${encodedFileId}?session_id=${selectedDialogue.value.session.id}&redirect=1`
    
    // Логируем только в режиме разработки
    if (import.meta.env.DEV) {
        console.log('getMediaUrl:', { 
            fileId: fileId.substring(0, 50) + '...', 
            encodedFileId: encodedFileId.substring(0, 50) + '...', 
            url: url.substring(0, 100) + '...', 
            type 
        })
    }
    
    return url
}

const openMediaViewer = (url, type) => {
    currentMediaUrl.value = url
    currentMediaType.value = type
    showMediaViewer.value = true
}

const closeMediaViewer = () => {
    showMediaViewer.value = false
    currentMediaUrl.value = null
    currentMediaType.value = null
}

onMounted(async () => {
    await Promise.all([fetchBots(), fetchManagers(), fetchDialogues()])
})
</script>

