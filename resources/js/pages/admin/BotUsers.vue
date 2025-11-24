<template>
    <div class="bot-users-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Пользователи ботов</h1>
                <p class="text-muted-foreground mt-1">Управление пользователями Telegram ботов</p>
            </div>
            <button
                @click="openCreateModal"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Добавить пользователя</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium mb-1 block">Бот</label>
                    <select
                        v-model="filters.bot_id"
                        @change="fetchUsers"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="">Все боты</option>
                        <option v-for="bot in bots" :key="bot.id" :value="bot.id">
                            {{ bot.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Роль</label>
                    <select
                        v-model="filters.role"
                        @change="fetchUsers"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    >
                        <option value="">Все роли</option>
                        <option value="admin">Администратор</option>
                        <option value="manager">Менеджер</option>
                        <option value="user">Пользователь</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium mb-1 block">Поиск</label>
                    <input
                        v-model="filters.search"
                        @input="debouncedSearch"
                        type="text"
                        placeholder="Имя, username, ID..."
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                    />
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка пользователей...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Users Table -->
        <div v-if="!loading && users.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Бот</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Пользователь</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Telegram ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Роль</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Дата создания</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">{{ user.id }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <span v-if="user.bot" class="font-medium">{{ user.bot.name }}</span>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <div>
                                    <div class="font-medium">
                                        {{ user.first_name }} {{ user.last_name || '' }}
                                    </div>
                                    <div v-if="user.username" class="text-xs text-muted-foreground">
                                        @{{ user.username }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ user.telegram_user_id }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <select
                                    :value="user.role"
                                    @change="updateRole(user, $event.target.value)"
                                    class="px-2 py-1 text-xs rounded-md border border-border bg-background"
                                    :class="{
                                        'bg-red-500/10 text-red-600 border-red-500/20': user.role === 'admin',
                                        'bg-yellow-500/10 text-yellow-600 border-yellow-500/20': user.role === 'manager',
                                        'bg-blue-500/10 text-blue-600 border-blue-500/20': user.role === 'user',
                                    }"
                                >
                                    <option value="admin">Администратор</option>
                                    <option value="manager">Менеджер</option>
                                    <option value="user">Пользователь</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ new Date(user.created_at).toLocaleDateString('ru-RU') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="editUser(user)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
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
        <div v-if="!loading && users.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Пользователи не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ showEditModal ? 'Редактировать пользователя' : 'Добавить пользователя' }}
                </h3>
                <form @submit.prevent="saveUser" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Бот <span class="text-destructive">*</span></label>
                        <select
                            v-model="form.bot_id"
                            required
                            :disabled="showEditModal"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        >
                            <option value="">Выберите бота</option>
                            <option v-for="bot in bots" :key="bot.id" :value="bot.id">
                                {{ bot.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Telegram User ID <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.telegram_user_id"
                            type="text"
                            required
                            :disabled="showEditModal"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            placeholder="123456789"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Chat ID <span class="text-destructive">*</span></label>
                        <input
                            v-model="form.chat_id"
                            type="text"
                            required
                            :disabled="showEditModal"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            placeholder="123456789"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Username</label>
                        <input
                            v-model="form.username"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                            placeholder="@username"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Имя</label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Фамилия</label>
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Роль <span class="text-destructive">*</span></label>
                        <select
                            v-model="form.role"
                            required
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        >
                            <option value="user">Пользователь</option>
                            <option value="manager">Менеджер</option>
                            <option value="admin">Администратор</option>
                        </select>
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
    name: 'BotUsers',
    setup() {
        const loading = ref(false)
        const saving = ref(false)
        const error = ref(null)
        const users = ref([])
        const bots = ref([])
        const showCreateModal = ref(false)
        const showEditModal = ref(false)
        const filters = ref({
            bot_id: '',
            role: '',
            search: ''
        })
        const form = ref({
            id: null,
            bot_id: '',
            telegram_user_id: '',
            chat_id: '',
            username: '',
            first_name: '',
            last_name: '',
            role: 'user'
        })

        let searchTimeout = null

        const fetchBots = async () => {
            try {
                const response = await apiGet('/bots')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки ботов')
                }
                const data = await response.json()
                bots.value = data.data || []
            } catch (err) {
                console.error('Error fetching bots:', err)
            }
        }

        const fetchUsers = async () => {
            loading.value = true
            error.value = null
            try {
                const params = new URLSearchParams()
                if (filters.value.bot_id) params.append('bot_id', filters.value.bot_id)
                if (filters.value.role) params.append('role', filters.value.role)
                if (filters.value.search) params.append('search', filters.value.search)

                const response = await apiGet(`/bot-users?${params.toString()}`)
                if (!response.ok) {
                    throw new Error('Ошибка загрузки пользователей')
                }
                const data = await response.json()
                users.value = data.data || []
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки пользователей'
            } finally {
                loading.value = false
            }
        }

        const debouncedSearch = () => {
            clearTimeout(searchTimeout)
            searchTimeout = setTimeout(() => {
                fetchUsers()
            }, 500)
        }

        const openCreateModal = () => {
            form.value = {
                id: null,
                bot_id: '',
                telegram_user_id: '',
                chat_id: '',
                username: '',
                first_name: '',
                last_name: '',
                role: 'user'
            }
            showCreateModal.value = true
        }

        const editUser = (user) => {
            form.value = {
                id: user.id,
                bot_id: user.bot_id,
                telegram_user_id: user.telegram_user_id,
                chat_id: user.chat_id,
                username: user.username || '',
                first_name: user.first_name || '',
                last_name: user.last_name || '',
                role: user.role
            }
            showEditModal.value = true
        }

        const updateRole = async (user, newRole) => {
            try {
                const response = await apiPut(`/bot-users/${user.id}`, {
                    role: newRole,
                    username: user.username,
                    first_name: user.first_name,
                    last_name: user.last_name
                })

                if (!response.ok) {
                    throw new Error('Ошибка обновления роли')
                }

                await Swal.fire({
                    title: 'Роль обновлена',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                await fetchUsers()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка обновления роли',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
                await fetchUsers() // Перезагружаем для отката изменений
            }
        }

        const deleteUser = async (user) => {
            const result = await Swal.fire({
                title: 'Удалить пользователя?',
                html: `Вы уверены, что хотите удалить пользователя <strong>"${user.first_name || user.username || user.telegram_user_id}"</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (!result.isConfirmed) return

            try {
                const response = await apiDelete(`/bot-users/${user.id}`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка удаления пользователя')
                }
                await Swal.fire({
                    title: 'Пользователь удален',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
                await fetchUsers()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления пользователя',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            }
        }

        const saveUser = async () => {
            saving.value = true
            error.value = null
            try {
                let response
                if (showEditModal.value) {
                    response = await apiPut(`/bot-users/${form.value.id}`, {
                        username: form.value.username,
                        first_name: form.value.first_name,
                        last_name: form.value.last_name,
                        role: form.value.role
                    })
                } else {
                    response = await apiPost('/bot-users', form.value)
                }

                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка сохранения пользователя')
                }

                await Swal.fire({
                    title: showEditModal.value ? 'Пользователь обновлен' : 'Пользователь создан',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                closeModal()
                await fetchUsers()
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения пользователя'
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения пользователя',
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
                bot_id: '',
                telegram_user_id: '',
                chat_id: '',
                username: '',
                first_name: '',
                last_name: '',
                role: 'user'
            }
        }

        onMounted(async () => {
            await Promise.all([fetchBots(), fetchUsers()])
        })

        return {
            loading,
            saving,
            error,
            users,
            bots,
            filters,
            showCreateModal,
            showEditModal,
            form,
            openCreateModal,
            editUser,
            updateRole,
            deleteUser,
            saveUser,
            closeModal,
            fetchUsers,
            debouncedSearch
        }
    }
}
</script>

