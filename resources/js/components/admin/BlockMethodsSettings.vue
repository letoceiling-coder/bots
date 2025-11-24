<template>
    <div class="space-y-6">
        <div v-if="loading" class="text-center py-8">
            <p class="text-muted-foreground">Загрузка настроек...</p>
        </div>
        <template v-else>
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Настройки методов блоков</h2>
            <div class="flex gap-2">
                <button
                    @click="enableAll"
                    class="px-4 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                >
                    Включить все
                </button>
                <button
                    @click="disableAll"
                    class="px-4 py-2 bg-secondary text-secondary-foreground rounded hover:bg-secondary/90 transition-colors"
                >
                    Отключить все
                </button>
                <button
                    @click="resetSettings"
                    class="px-4 py-2 bg-muted text-muted-foreground rounded hover:bg-muted/90 transition-colors"
                >
                    Сбросить
                </button>
            </div>
        </div>

        <div class="space-y-6">
            <div
                v-for="group in methodGroups"
                :key="group.key"
                class="border border-border rounded-lg p-4"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ group.label }}</h3>
                    <div class="flex gap-2">
                        <button
                            @click="toggleGroup(group.key, true)"
                            class="text-xs px-2 py-1 bg-green-500/20 text-green-600 rounded hover:bg-green-500/30 transition-colors"
                        >
                            Включить группу
                        </button>
                        <button
                            @click="toggleGroup(group.key, false)"
                            class="text-xs px-2 py-1 bg-red-500/20 text-red-600 rounded hover:bg-red-500/30 transition-colors"
                        >
                            Отключить группу
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <label
                        v-for="method in group.methods"
                        :key="method.value"
                        class="flex items-center gap-2 p-3 border border-border rounded hover:bg-muted/50 transition-colors cursor-pointer"
                        :class="{ 'opacity-50': !method.enabled }"
                    >
                        <input
                            type="checkbox"
                            :checked="method.enabled"
                            @change="toggleMethod(method.value)"
                            class="w-4 h-4"
                        />
                        <span class="text-sm flex-1">{{ method.label }}</span>
                        <span
                            v-if="method.enabled"
                            class="text-xs text-green-600 bg-green-500/20 px-2 py-1 rounded"
                        >
                            Включен
                        </span>
                        <span
                            v-else
                            class="text-xs text-red-600 bg-red-500/20 px-2 py-1 rounded"
                        >
                            Отключен
                        </span>
                    </label>
                </div>
            </div>
        </div>
        </template>
    </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from 'vue'
import { blockMethodsManager } from '../../utils/BlockMethodsManager.js'

export default {
    name: 'BlockMethodsSettings',
    setup() {
        const methods = reactive({})
        const loading = ref(true)
        const updateTrigger = ref(0) // Триггер для принудительного обновления

        // Загружаем методы при монтировании
        onMounted(async () => {
            // Ждем загрузки настроек из БД
            if (!blockMethodsManager.settingsLoaded) {
                await blockMethodsManager.loadSettingsFromAPI()
            }
            // Копируем методы в реактивный объект
            const allMethods = blockMethodsManager.getAllMethods()
            Object.keys(allMethods).forEach(key => {
                methods[key] = { ...allMethods[key] }
            })
            loading.value = false
        })

        // Группируем методы для отображения (зависит от methods и updateTrigger для реактивности)
        const methodGroups = computed(() => {
            // Используем updateTrigger для принудительного обновления
            updateTrigger.value
            
            // Используем текущие методы из реактивного объекта methods
            const grouped = {}
            
            // Группируем методы из текущего состояния
            Object.values(methods).forEach(method => {
                if (method && method.group) {
                    if (!grouped[method.group]) {
                        grouped[method.group] = []
                    }
                    grouped[method.group].push(method)
                }
            })
            
            const groupLabels = {
                messages: 'Отправка сообщений',
                media: 'Медиа',
                editing: 'Редактирование',
                management: 'Управление',
                buttons: 'Кнопки',
                special: 'Специальные функции'
            }
            const groupOrder = ['messages', 'media', 'editing', 'management', 'buttons', 'special']

            return groupOrder.map(groupKey => ({
                key: groupKey,
                label: groupLabels[groupKey],
                methods: grouped[groupKey] || []
            })).filter(group => group.methods.length > 0)
        })

        // Переключить метод
        const toggleMethod = async (methodValue) => {
            try {
                // Сначала обновляем локальное состояние для мгновенной реакции UI
                if (methods[methodValue]) {
                    methods[methodValue].enabled = !methods[methodValue].enabled
                    updateTrigger.value++ // Принудительно обновляем computed
                }
                
                // Затем сохраняем в БД
                await blockMethodsManager.toggleMethod(methodValue)
                
                // Синхронизируем с менеджером на случай, если что-то пошло не так
                const allMethods = blockMethodsManager.getAllMethods()
                if (allMethods[methodValue]) {
                    methods[methodValue].enabled = allMethods[methodValue].enabled
                    updateTrigger.value++
                }
            } catch (error) {
                console.error('Ошибка при переключении метода:', error)
                // Откатываем изменение при ошибке
                if (methods[methodValue]) {
                    methods[methodValue].enabled = !methods[methodValue].enabled
                    updateTrigger.value++
                }
            }
        }

        // Переключить группу методов
        const toggleGroup = async (groupKey, enabled) => {
            try {
                // Сначала обновляем локальное состояние
                Object.values(methods).forEach(method => {
                    if (method && method.group === groupKey) {
                        method.enabled = enabled
                    }
                })
                updateTrigger.value++
                
                // Затем сохраняем в БД
                await blockMethodsManager.setGroupEnabled(groupKey, enabled)
                
                // Синхронизируем с менеджером
                const allMethods = blockMethodsManager.getAllMethods()
                Object.keys(allMethods).forEach(key => {
                    if (allMethods[key].group === groupKey) {
                        methods[key] = { ...allMethods[key] }
                    }
                })
                updateTrigger.value++
            } catch (error) {
                console.error('Ошибка при переключении группы:', error)
            }
        }

        // Включить все методы
        const enableAll = async () => {
            try {
                // Сначала обновляем локальное состояние
                Object.values(methods).forEach(method => {
                    if (method) {
                        method.enabled = true
                    }
                })
                updateTrigger.value++
                
                // Затем сохраняем в БД
                await blockMethodsManager.enableAll()
                
                // Синхронизируем с менеджером
                const allMethods = blockMethodsManager.getAllMethods()
                Object.keys(allMethods).forEach(key => {
                    methods[key] = { ...allMethods[key] }
                })
                updateTrigger.value++
            } catch (error) {
                console.error('Ошибка при включении всех методов:', error)
            }
        }

        // Отключить все методы
        const disableAll = async () => {
            try {
                // Сначала обновляем локальное состояние
                Object.values(methods).forEach(method => {
                    if (method) {
                        method.enabled = false
                    }
                })
                updateTrigger.value++
                
                // Затем сохраняем в БД
                await blockMethodsManager.disableAll()
                
                // Синхронизируем с менеджером
                const allMethods = blockMethodsManager.getAllMethods()
                Object.keys(allMethods).forEach(key => {
                    methods[key] = { ...allMethods[key] }
                })
                updateTrigger.value++
            } catch (error) {
                console.error('Ошибка при отключении всех методов:', error)
            }
        }

        // Сбросить настройки
        const resetSettings = async () => {
            if (confirm('Вы уверены, что хотите сбросить все настройки методов к значениям по умолчанию?')) {
                try {
                    // Сначала обновляем локальное состояние
                    Object.values(methods).forEach(method => {
                        if (method) {
                            method.enabled = true
                        }
                    })
                    updateTrigger.value++
                    
                    // Затем сохраняем в БД
                    await blockMethodsManager.resetSettings()
                    
                    // Синхронизируем с менеджером
                    const allMethods = blockMethodsManager.getAllMethods()
                    Object.keys(allMethods).forEach(key => {
                        methods[key] = { ...allMethods[key] }
                    })
                    updateTrigger.value++
                } catch (error) {
                    console.error('Ошибка при сбросе настроек:', error)
                }
            }
        }

        return {
            methodGroups,
            toggleMethod,
            toggleGroup,
            enableAll,
            disableAll,
            resetSettings,
            loading
        }
    }
}
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

