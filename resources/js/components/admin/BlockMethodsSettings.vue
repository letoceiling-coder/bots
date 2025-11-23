<template>
    <div class="space-y-6">
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
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { blockMethodsManager } from '../../utils/BlockMethodsManager.js'

export default {
    name: 'BlockMethodsSettings',
    setup() {
        const methods = ref({})

        // Загружаем методы при монтировании
        onMounted(() => {
            methods.value = blockMethodsManager.getAllMethods()
        })

        // Группируем методы для отображения
        const methodGroups = computed(() => {
            const grouped = blockMethodsManager.getGroupedMethods(false)
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
        const toggleMethod = (methodValue) => {
            blockMethodsManager.toggleMethod(methodValue)
            // Обновляем локальное состояние
            methods.value = { ...blockMethodsManager.getAllMethods() }
        }

        // Переключить группу методов
        const toggleGroup = (groupKey, enabled) => {
            blockMethodsManager.setGroupEnabled(groupKey, enabled)
            // Обновляем локальное состояние
            methods.value = { ...blockMethodsManager.getAllMethods() }
        }

        // Включить все методы
        const enableAll = () => {
            blockMethodsManager.enableAll()
            methods.value = { ...blockMethodsManager.getAllMethods() }
        }

        // Отключить все методы
        const disableAll = () => {
            blockMethodsManager.disableAll()
            methods.value = { ...blockMethodsManager.getAllMethods() }
        }

        // Сбросить настройки
        const resetSettings = () => {
            if (confirm('Вы уверены, что хотите сбросить все настройки методов к значениям по умолчанию?')) {
                blockMethodsManager.resetSettings()
                methods.value = { ...blockMethodsManager.getAllMethods() }
            }
        }

        return {
            methodGroups,
            toggleMethod,
            toggleGroup,
            enableAll,
            disableAll,
            resetSettings
        }
    }
}
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

