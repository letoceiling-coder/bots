<template>
    <div class="bot-sessions-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Прохождения ботов</h1>
                <p class="text-muted-foreground mt-1">Просмотр истории взаимодействий пользователей с ботами</p>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-card rounded-lg border border-border p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-medium mb-1 block">Бот</label>
                    <select
                        v-model="filters.bot_id"
                        @change="fetchSessions"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="">Все боты</option>
                        <option v-for="bot in bots" :key="bot.id" :value="bot.id">
                            {{ bot.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Статус</label>
                    <select
                        v-model="filters.status"
                        @change="fetchSessions"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="">Все статусы</option>
                        <option value="active">Активные</option>
                        <option value="completed">Завершенные</option>
                        <option value="abandoned">Заброшенные</option>
                        <option value="manager_chat">Чат с менеджером</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Поиск</label>
                    <input
                        v-model="filters.search"
                        @input="debounceSearch"
                        type="text"
                        placeholder="Chat ID, username..."
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Дата начала</label>
                    <input
                        v-model="filters.started_from"
                        @change="fetchSessions"
                        type="date"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div v-if="statistics" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Всего сессий</div>
                <div class="text-2xl font-semibold mt-1">{{ statistics.total_sessions }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Активные</div>
                <div class="text-2xl font-semibold mt-1 text-green-600">{{ statistics.active_sessions }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Завершенные</div>
                <div class="text-2xl font-semibold mt-1 text-blue-600">{{ statistics.completed_sessions }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Заброшенные</div>
                <div class="text-2xl font-semibold mt-1 text-orange-600">{{ statistics.abandoned_sessions }}</div>
            </div>
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="text-sm text-muted-foreground">Уникальных пользователей</div>
                <div class="text-2xl font-semibold mt-1">{{ statistics.unique_users }}</div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка сессий...</p>
        </div>

        <!-- Список сессий -->
        <div v-else-if="sessions.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Бот</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Пользователь</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Шагов</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Начало</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Последняя активность</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="session in sessions" :key="session.id" class="hover:bg-muted/30">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium">{{ session.bot?.name || 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div v-if="session.username || session.first_name">
                                        {{ session.first_name }} {{ session.last_name }}
                                        <span v-if="session.username" class="text-muted-foreground">(@{{ session.username }})</span>
                                    </div>
                                    <div class="text-muted-foreground text-xs">ID: {{ session.chat_id }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': session.status === 'active',
                                        'bg-blue-100 text-blue-800': session.status === 'completed',
                                        'bg-orange-100 text-orange-800': session.status === 'abandoned',
                                        'bg-purple-100 text-purple-800': session.status === 'manager_chat',
                                    }"
                                >
                                    {{ getStatusLabel(session.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">{{ session.steps?.length || 0 }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">{{ formatDate(session.started_at) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">{{ formatDate(session.last_activity_at) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    @click="openSessionDetail(session)"
                                    class="text-accent hover:text-accent/80 text-sm font-medium"
                                >
                                    Просмотр
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Показано {{ sessions.length }} из {{ pagination.total }}
                </div>
                <div class="flex gap-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-1 border border-border rounded disabled:opacity-50"
                    >
                        Назад
                    </button>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-1 border border-border rounded disabled:opacity-50"
                    >
                        Вперед
                    </button>
                </div>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Сессии не найдены</p>
        </div>

        <!-- Модальное окно детального просмотра -->
        <div
            v-if="selectedSession"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            @click.self="selectedSession = null"
        >
            <div class="bg-card rounded-lg border border-border max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Детали сессии #{{ selectedSession.id }}</h2>
                    <button
                        @click="selectedSession = null"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Информация о сессии -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Информация</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-muted-foreground">Бот</div>
                                <div class="font-medium">{{ selectedSession.bot?.name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Статус</div>
                                <div class="font-medium">{{ getStatusLabel(selectedSession.status) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Chat ID</div>
                                <div class="font-medium">{{ selectedSession.chat_id }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-muted-foreground">Пользователь</div>
                                <div class="font-medium">
                                    {{ selectedSession.first_name }} {{ selectedSession.last_name }}
                                    <span v-if="selectedSession.username">(@{{ selectedSession.username }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Хронология шагов -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Хронология</h3>
                        <div class="space-y-4">
                            <div
                                v-for="step in selectedSession.steps"
                                :key="step.id"
                                class="border border-border rounded-lg p-4"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <div class="font-medium">{{ step.block_label || 'Шаг ' + step.step_order }}</div>
                                        <div class="text-xs text-muted-foreground">{{ formatDateTime(step.timestamp) }}</div>
                                    </div>
                                    <span class="text-xs px-2 py-1 bg-muted rounded">{{ step.method }}</span>
                                </div>
                                <div v-if="step.bot_response" class="mb-2">
                                    <div class="text-xs text-muted-foreground mb-1">Ответ бота:</div>
                                    <div class="text-sm bg-muted/50 p-2 rounded">{{ step.bot_response }}</div>
                                </div>
                                <div v-if="step.user_input" class="mb-2">
                                    <div class="text-xs text-muted-foreground mb-1">Ответ пользователя:</div>
                                    <div class="text-sm bg-accent/10 p-2 rounded">{{ step.user_input }}</div>
                                </div>
                                <div v-if="step.files && step.files.length > 0">
                                    <div class="text-xs text-muted-foreground mb-1">Файлы:</div>
                                    <div class="flex gap-2">
                                        <span
                                            v-for="file in step.files"
                                            :key="file.id"
                                            class="text-xs px-2 py-1 bg-muted rounded"
                                        >
                                            {{ file.file_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Собранные данные -->
                    <div v-if="selectedSession.data && selectedSession.data.length > 0">
                        <h3 class="text-lg font-semibold mb-4">Собранные данные</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="data in selectedSession.data"
                                :key="data.id"
                                class="border border-border rounded p-3"
                            >
                                <div class="text-xs text-muted-foreground mb-1">{{ data.key }}</div>
                                <div class="font-medium">{{ data.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { apiGet } from '../../utils/api'

export default {
    name: 'BotSessions',
    setup() {
        const loading = ref(false)
        const sessions = ref([])
        const bots = ref([])
        const statistics = ref(null)
        const pagination = ref(null)
        const selectedSession = ref(null)
        
        const filters = reactive({
            bot_id: '',
            status: '',
            search: '',
            started_from: '',
            started_to: '',
        })

        let searchTimeout = null

        const fetchBots = async () => {
            try {
                const response = await apiGet('/bots')
                if (response.ok) {
                    const data = await response.json()
                    bots.value = data.data || []
                }
            } catch (error) {
                console.error('Error fetching bots:', error)
            }
        }

        const fetchStatistics = async () => {
            try {
                const params = {}
                if (filters.bot_id) params.bot_id = filters.bot_id
                if (filters.started_from) params.date_from = filters.started_from
                
                const queryString = new URLSearchParams(params).toString()
                const response = await apiGet(`/bot-sessions/statistics${queryString ? '?' + queryString : ''}`)
                
                if (response.ok) {
                    const data = await response.json()
                    statistics.value = data.data
                }
            } catch (error) {
                console.error('Error fetching statistics:', error)
            }
        }

        const fetchSessions = async () => {
            loading.value = true
            try {
                const params = {}
                if (filters.bot_id) params.bot_id = filters.bot_id
                if (filters.status) params.status = filters.status
                if (filters.search) params.search = filters.search
                if (filters.started_from) params.started_from = filters.started_from
                if (filters.started_to) params.started_to = filters.started_to

                const queryString = new URLSearchParams(params).toString()
                const response = await apiGet(`/bot-sessions${queryString ? '?' + queryString : ''}`)
                
                if (response.ok) {
                    const data = await response.json()
                    sessions.value = data.data || []
                    pagination.value = data.pagination || null
                    await fetchStatistics()
                }
            } catch (error) {
                console.error('Error fetching sessions:', error)
            } finally {
                loading.value = false
            }
        }

        const fetchSessionDetail = async (sessionId) => {
            try {
                const response = await apiGet(`/bot-sessions/${sessionId}`)
                if (response.ok) {
                    const data = await response.json()
                    selectedSession.value = data.data
                }
            } catch (error) {
                console.error('Error fetching session details:', error)
            }
        }

        const openSessionDetail = async (session) => {
            await fetchSessionDetail(session.id)
        }

        const changePage = (page) => {
            // TODO: реализовать пагинацию
            console.log('Change page to:', page)
        }

        const debounceSearch = () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout)
            }
            searchTimeout = setTimeout(() => {
                fetchSessions()
            }, 500)
        }

        const getStatusLabel = (status) => {
            const labels = {
                'active': 'Активная',
                'completed': 'Завершена',
                'abandoned': 'Заброшена',
                'manager_chat': 'Чат с менеджером',
            }
            return labels[status] || status
        }

        const formatDate = (date) => {
            if (!date) return '-'
            return new Date(date).toLocaleString('ru-RU')
        }

        const formatDateTime = (date) => {
            if (!date) return '-'
            return new Date(date).toLocaleString('ru-RU')
        }

        onMounted(async () => {
            await fetchBots()
            await fetchSessions()
        })

        return {
            loading,
            sessions,
            bots,
            statistics,
            pagination,
            filters,
            selectedSession,
            fetchSessions,
            openSessionDetail,
            changePage,
            debounceSearch,
            getStatusLabel,
            formatDate,
            formatDateTime,
        }
    }
}
</script>

