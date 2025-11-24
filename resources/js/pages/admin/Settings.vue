<template>
    <div class="settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки</h1>
            <p class="text-muted-foreground mt-1">Управление настройками системы</p>
        </div>

        <div v-if="loading" class="text-center py-8">
            <p class="text-muted-foreground">Загрузка настроек...</p>
        </div>

        <div v-else-if="error" class="bg-destructive/10 border border-destructive rounded-lg p-4 mb-6">
            <p class="text-destructive">{{ error }}</p>
            <button @click="loadSettings" class="mt-2 px-4 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90">
                Повторить
            </button>
        </div>

        <div v-else class="space-y-6">
            <!-- Группы настроек -->
            <div
                v-for="(settings, group) in groupedSettings"
                :key="group"
                class="bg-card rounded-lg border border-border p-6"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">{{ getGroupLabel(group) }}</h2>
                    <button
                        v-if="hasChanges(group)"
                        @click="saveGroup(group)"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                    >
                        Сохранить группу
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="setting in settings"
                        :key="setting.id"
                        class="border-b border-border pb-4 last:border-0"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium mb-1">
                                    {{ setting.key }}
                                    <span v-if="setting.description" class="text-xs text-muted-foreground ml-2">
                                        ({{ setting.description }})
                                    </span>
                                </label>
                                
                                <!-- String input -->
                                <input
                                    v-if="setting.type === 'string'"
                                    v-model="setting.value"
                                    @input="markAsChanged(setting)"
                                    type="text"
                                    class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                                    :placeholder="setting.description || setting.key"
                                />

                                <!-- Number input -->
                                <input
                                    v-else-if="setting.type === 'number' || setting.type === 'integer' || setting.type === 'float'"
                                    v-model.number="setting.value"
                                    @input="markAsChanged(setting)"
                                    type="number"
                                    class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                                    :step="setting.type === 'float' ? '0.01' : '1'"
                                />

                                <!-- Boolean input -->
                                <label v-else-if="setting.type === 'boolean'" class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="setting.value"
                                        @change="markAsChanged(setting)"
                                        type="checkbox"
                                        class="w-4 h-4"
                                    />
                                    <span class="text-sm">{{ setting.value ? 'Включено' : 'Выключено' }}</span>
                                </label>

                                <!-- JSON input -->
                                <textarea
                                    v-else-if="setting.type === 'json'"
                                    :value="typeof setting.value === 'object' ? JSON.stringify(setting.value, null, 2) : setting.value"
                                    @input="(e) => { try { setting.value = JSON.parse(e.target.value); markAsChanged(setting); } catch { setting.value = e.target.value; markAsChanged(setting); } }"
                                    rows="4"
                                    class="w-full px-3 py-2 border border-border rounded bg-background text-foreground font-mono text-sm"
                                    placeholder='{"key": "value"}'
                                ></textarea>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    v-if="setting._changed"
                                    @click="saveSetting(setting)"
                                    class="px-3 py-1 text-xs bg-primary text-primary-foreground rounded hover:bg-primary/90"
                                >
                                    Сохранить
                                </button>
                                <span
                                    v-if="setting.is_public"
                                    class="px-2 py-1 text-xs bg-green-500/20 text-green-600 rounded"
                                >
                                    Публичная
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Добавить новую настройку -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold mb-4">Добавить настройку</h2>
                <form @submit.prevent="addSetting" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Ключ *</label>
                            <input
                                v-model="newSetting.key"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                                placeholder="setting_key"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Группа</label>
                            <input
                                v-model="newSetting.group"
                                type="text"
                                class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                                placeholder="general"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Тип</label>
                            <select
                                v-model="newSetting.type"
                                class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                            >
                                <option value="string">String</option>
                                <option value="number">Number</option>
                                <option value="integer">Integer</option>
                                <option value="float">Float</option>
                                <option value="boolean">Boolean</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Значение</label>
                            <input
                                v-model="newSetting.value"
                                type="text"
                                class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Описание</label>
                        <input
                            v-model="newSetting.description"
                            type="text"
                            class="w-full px-3 py-2 border border-border rounded bg-background text-foreground"
                        />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="newSetting.is_public"
                                type="checkbox"
                                class="w-4 h-4"
                            />
                            <span class="text-sm">Публичная настройка</span>
                        </label>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90"
                        >
                            Добавить
                        </button>
                    </div>
                </form>
            </div>

            <!-- Настройки методов блоков (старый компонент) -->
            <div class="bg-card rounded-lg border border-border p-6">
                <BlockMethodsSettings />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiGet, apiPost, apiPut } from '../../utils/api'
import BlockMethodsSettings from '../../components/admin/BlockMethodsSettings.vue'

const loading = ref(false)
const error = ref(null)
const settings = ref([])
const changedSettings = ref(new Set())

const newSetting = ref({
    key: '',
    value: '',
    type: 'string',
    group: 'general',
    description: '',
    is_public: false,
})

const groupedSettings = computed(() => {
    const grouped = {}
    settings.value.forEach(setting => {
        if (!grouped[setting.group]) {
            grouped[setting.group] = []
        }
        grouped[setting.group].push(setting)
    })
    return grouped
})

const getGroupLabel = (group) => {
    const labels = {
        general: 'Общие настройки',
        telegram: 'Telegram',
        email: 'Email',
        system: 'Система',
        bot: 'Боты',
        block_methods: 'Методы блоков',
    }
    return labels[group] || group.charAt(0).toUpperCase() + group.slice(1)
}

const hasChanges = (group) => {
    return settings.value
        .filter(s => s.group === group)
        .some(s => changedSettings.value.has(s.id))
}

const markAsChanged = (setting) => {
    changedSettings.value.add(setting.id)
    setting._changed = true
}

const loadSettings = async () => {
    loading.value = true
    error.value = null
    try {
        const response = await apiGet('/settings')
        if (!response.ok) {
            throw new Error('Ошибка загрузки настроек')
        }
        const data = await response.json()
        
        // Преобразуем объект сгруппированных настроек в плоский массив
        // Исключаем настройки методов блоков (block_methods), так как у них есть отдельный компонент
        const flatSettings = []
        Object.entries(data.data || {}).forEach(([group, groupSettings]) => {
            if (group !== 'block_methods') {
                groupSettings.forEach(setting => {
                    flatSettings.push({
                        ...setting,
                        _changed: false,
                    })
                })
            }
        })
        settings.value = flatSettings
        changedSettings.value.clear()
    } catch (err) {
        error.value = err.message || 'Ошибка загрузки настроек'
    } finally {
        loading.value = false
    }
}

const saveSetting = async (setting) => {
    try {
        const response = await apiPut(`/settings/${setting.id}`, {
            value: setting.value,
            type: setting.type,
            group: setting.group,
            description: setting.description,
            is_public: setting.is_public,
        })

        if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.message || 'Ошибка сохранения')
        }

        changedSettings.value.delete(setting.id)
        setting._changed = false
    } catch (err) {
        alert(err.message || 'Ошибка сохранения настройки')
    }
}

const saveGroup = async (group) => {
    const groupSettings = settings.value.filter(s => s.group === group && changedSettings.value.has(s.id))
    
    if (groupSettings.length === 0) {
        return
    }

    try {
        const response = await apiPost('/settings/bulk-update', {
            settings: groupSettings.map(s => ({
                key: s.key,
                value: s.value,
            })),
        })

        if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.message || 'Ошибка сохранения')
        }

        groupSettings.forEach(setting => {
            changedSettings.value.delete(setting.id)
            setting._changed = false
        })
    } catch (err) {
        alert(err.message || 'Ошибка сохранения настроек')
    }
}

const addSetting = async () => {
    try {
        const response = await apiPost('/settings', newSetting.value)

        if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.message || 'Ошибка создания настройки')
        }

        const data = await response.json()
        settings.value.push({
            ...data.data,
            _changed: false,
        })

        // Сброс формы
        newSetting.value = {
            key: '',
            value: '',
            type: 'string',
            group: 'general',
            description: '',
            is_public: false,
        }
    } catch (err) {
        alert(err.message || 'Ошибка создания настройки')
    }
}

onMounted(() => {
    loadSettings()
})
</script>

