<template>
    <div class="bot-menu-settings bg-card rounded-lg border border-border p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-foreground">Управление кнопкой меню бота</h2>
                <p class="text-sm text-muted-foreground mt-1">Включение/отключение кнопки меню для выбранного бота</p>
            </div>
        </div>

        <!-- Выбор бота -->
        <div class="mb-6">
            <label for="botSelect" class="block text-sm font-medium text-foreground mb-2">
                Выберите бота
            </label>
            <select
                id="botSelect"
                v-model="selectedBotId"
                @change="loadSettings"
                class="w-full h-10 px-3 border border-border rounded-lg bg-background text-foreground"
                :disabled="loading"
            >
                <option :value="null" disabled>Выберите бота...</option>
                <option v-for="bot in bots" :key="bot.id" :value="bot.id">
                    {{ bot.name }} ({{ bot.username || 'без username' }})
                </option>
            </select>
        </div>

        <!-- Настройки -->
        <div v-if="selectedBotId && settings" class="space-y-4">
            <!-- Включение/отключение меню -->
            <div class="flex items-center justify-between p-4 bg-muted/30 rounded-lg">
                <div class="flex-1">
                    <label class="text-sm font-medium text-foreground block mb-1">
                        Кнопка меню
                    </label>
                    <p class="text-xs text-muted-foreground">
                        Показывать кнопку меню в поле ввода бота
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="settings.menu_enabled"
                        @change="updateSettings"
                        :disabled="saving"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-foreground">
                        {{ settings.menu_enabled ? 'Включено' : 'Выключено' }}
                    </span>
                </label>
            </div>

            <!-- Тип меню -->
            <div v-if="settings.menu_enabled" class="p-4 bg-muted/30 rounded-lg">
                <label for="menuType" class="block text-sm font-medium text-foreground mb-2">
                    Тип кнопки меню
                </label>
                <select
                    id="menuType"
                    v-model="settings.menu_type"
                    @change="updateSettings"
                    :disabled="saving"
                    class="w-full h-10 px-3 border border-border rounded-lg bg-background text-foreground"
                >
                    <option value="commands">Команды (открывает список команд)</option>
                    <option value="web_app">Веб-приложение (требует настройки)</option>
                    <option value="default">По умолчанию (скрывает кнопку)</option>
                </select>
                <p class="text-xs text-muted-foreground mt-2">
                    <strong>Команды</strong> — кнопка открывает список команд бота<br>
                    <strong>Веб-приложение</strong> — кнопка открывает веб-приложение (требует дополнительной настройки)<br>
                    <strong>По умолчанию</strong> — скрывает кнопку меню
                </p>
            </div>

            <!-- Статус сохранения -->
            <div v-if="saving" class="flex items-center gap-2 text-sm text-muted-foreground">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Сохранение...
            </div>

            <div v-if="error" class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{{ error }}</p>
            </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else-if="selectedBotId && !loading" class="text-center py-8 text-muted-foreground">
            <p>Загрузка настроек...</p>
        </div>

        <div v-else-if="!selectedBotId" class="text-center py-8 text-muted-foreground">
            <p>Выберите бота для управления настройками кнопки меню</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiGet, apiPut } from '../../utils/api'
import Swal from 'sweetalert2'

const bots = ref([])
const selectedBotId = ref(null)
const settings = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref(null)

const fetchBots = async () => {
    try {
        const response = await apiGet('/bots')
        if (response.ok) {
            const data = await response.json()
            bots.value = data.data || []
        }
    } catch (err) {
        console.error('Error fetching bots:', err)
        error.value = 'Ошибка загрузки списка ботов'
    }
}

const loadSettings = async () => {
    if (!selectedBotId.value) {
        settings.value = null
        return
    }

    loading.value = true
    error.value = null

    try {
        const response = await apiGet(`/bot-menu-settings/${selectedBotId.value}`)
        if (!response.ok) {
            throw new Error('Ошибка загрузки настроек')
        }
        const data = await response.json()
        settings.value = data.data
    } catch (err) {
        error.value = err.message || 'Ошибка загрузки настроек'
        settings.value = null
    } finally {
        loading.value = false
    }
}

const updateSettings = async () => {
    if (!selectedBotId.value || !settings.value) return

    saving.value = true
    error.value = null

    try {
        const response = await apiPut(`/bot-menu-settings/${selectedBotId.value}`, {
            menu_enabled: settings.value.menu_enabled,
            menu_type: settings.value.menu_type,
        })

        if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.message || 'Ошибка сохранения настроек')
        }

        Swal.fire({
            title: 'Настройки сохранены',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        })

        // Обновляем настройки из ответа
        const data = await response.json()
        if (data.data) {
            settings.value = data.data
        }
    } catch (err) {
        error.value = err.message || 'Ошибка сохранения настроек'
        Swal.fire({
            title: 'Ошибка',
            text: err.message || 'Ошибка сохранения настроек',
            icon: 'error',
            confirmButtonText: 'ОК'
        })
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    fetchBots()
})
</script>

