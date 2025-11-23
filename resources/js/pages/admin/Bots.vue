<template>
    <div class="bots-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Telegram Bots</h1>
                <p class="text-muted-foreground mt-1">Управление телеграм ботами</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Добавить бота</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка ботов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Bots Table -->
        <div v-if="!loading && bots.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Токен</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Дата создания</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="bot in bots" :key="bot.id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">{{ bot.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ bot.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <span v-if="bot.username" class="text-accent">@{{ bot.username }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <span class="font-mono text-xs">{{ truncateToken(bot.token) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    :class="bot.is_active ? 'bg-green-500/10 text-green-600' : 'bg-red-500/10 text-red-600'"
                                    class="px-2 py-1 text-xs rounded-md font-medium"
                                >
                                    {{ bot.is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ new Date(bot.created_at).toLocaleDateString('ru-RU') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editBot(bot)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteBot(bot)"
                                        class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && bots.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Боты не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">
                    {{ showEditModal ? 'Редактировать бота' : 'Добавить бота' }}
                </h3>
                <form @submit.prevent="saveBot" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Название</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            placeholder="Название бота"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Токен</label>
                        <input
                            v-model="form.token"
                            type="text"
                            required
                            class="w-full h-10 px-3 border border-border rounded bg-background font-mono text-sm"
                            placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Токен от BotFather</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Username</label>
                        <input
                            v-model="form.username"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            placeholder="my_bot"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Без символа @</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-border rounded bg-background"
                            placeholder="Описание бота"
                        ></textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4"
                            />
                            <span class="text-sm font-medium">Активен</span>
                        </label>
                    </div>
                    <div class="flex gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { apiGet, apiPost, apiPut, apiDelete } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Bots',
    setup() {
        const loading = ref(false)
        const saving = ref(false)
        const error = ref(null)
        const bots = ref([])
        const showCreateModal = ref(false)
        const showEditModal = ref(false)
        const form = ref({
            id: null,
            name: '',
            token: '',
            username: '',
            description: '',
            is_active: true
        })

        const fetchBots = async () => {
            loading.value = true
            error.value = null
            try {
                const response = await apiGet('/bots')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки ботов')
                }
                const data = await response.json()
                bots.value = data.data || []
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки ботов'
            } finally {
                loading.value = false
            }
        }

        const truncateToken = (token) => {
            if (!token) return ''
            if (token.length <= 20) return token
            return token.substring(0, 10) + '...' + token.substring(token.length - 10)
        }

        const editBot = (bot) => {
            form.value = {
                id: bot.id,
                name: bot.name,
                token: bot.token,
                username: bot.username || '',
                description: bot.description || '',
                is_active: bot.is_active !== undefined ? bot.is_active : true
            }
            showEditModal.value = true
        }

        const deleteBot = async (bot) => {
            const result = await Swal.fire({
                title: 'Удалить бота?',
                html: `Вы уверены, что хотите удалить бота <strong>"${bot.name}"</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (!result.isConfirmed) return

            try {
                const response = await apiDelete(`/bots/${bot.id}`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка удаления бота')
                }
                await Swal.fire({
                    title: 'Бот удален',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
                await fetchBots()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления бота',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            }
        }

        const saveBot = async () => {
            saving.value = true
            error.value = null
            try {
                const botData = {
                    name: form.value.name,
                    token: form.value.token,
                    username: form.value.username || null,
                    description: form.value.description || null,
                    is_active: form.value.is_active
                }

                let response
                if (showEditModal.value) {
                    response = await apiPut(`/bots/${form.value.id}`, botData)
                } else {
                    response = await apiPost('/bots', botData)
                }

                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка сохранения бота')
                }

                await Swal.fire({
                    title: showEditModal.value ? 'Бот обновлен' : 'Бот создан',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                closeModal()
                await fetchBots()
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения бота'
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения бота',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                saving.value = false
            }
        }

        const closeModal = () => {
            showCreateModal.value = false
            showEditModal.value = false
            form.value = {
                id: null,
                name: '',
                token: '',
                username: '',
                description: '',
                is_active: true
            }
        }

        onMounted(() => {
            fetchBots()
        })

        return {
            loading,
            saving,
            error,
            bots,
            showCreateModal,
            showEditModal,
            form,
            editBot,
            deleteBot,
            saveBot,
            closeModal,
            truncateToken
        }
    }
}
</script>

