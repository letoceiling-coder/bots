/**
 * Класс для управления доступными методами блоков
 */
export class BlockMethodsManager {
    constructor() {
        // Определяем все доступные методы с их метаданными
        this.methods = {
            // Отправка сообщений
            sendMessage: {
                value: 'sendMessage',
                label: 'Отправить сообщение',
                group: 'messages',
                enabled: true
            },
            sendDice: {
                value: 'sendDice',
                label: '🎲 Отправить кубик',
                group: 'messages',
                enabled: true
            },
            sendPoll: {
                value: 'sendPoll',
                label: '📊 Отправить опрос',
                group: 'messages',
                enabled: true
            },
            sendVenue: {
                value: 'sendVenue',
                label: '📍 Отправить локацию',
                group: 'messages',
                enabled: true
            },
            sendContact: {
                value: 'sendContact',
                label: '👤 Отправить контакт',
                group: 'messages',
                enabled: true
            },
            // Медиа
            sendPhoto: {
                value: 'sendPhoto',
                label: '📷 Фото',
                group: 'media',
                enabled: true
            },
            sendVideo: {
                value: 'sendVideo',
                label: '🎥 Видео',
                group: 'media',
                enabled: true
            },
            sendDocument: {
                value: 'sendDocument',
                label: '📄 Документ',
                group: 'media',
                enabled: true
            },
            sendAudio: {
                value: 'sendAudio',
                label: '🎵 Аудио',
                group: 'media',
                enabled: true
            },
            sendVoice: {
                value: 'sendVoice',
                label: '🎤 Голосовое',
                group: 'media',
                enabled: true
            },
            sendVideoNote: {
                value: 'sendVideoNote',
                label: '🎬 Видео-кружок',
                group: 'media',
                enabled: true
            },
            sendAnimation: {
                value: 'sendAnimation',
                label: '🎞️ Анимация/GIF',
                group: 'media',
                enabled: true
            },
            sendSticker: {
                value: 'sendSticker',
                label: '😊 Стикер',
                group: 'media',
                enabled: true
            },
            sendLocation: {
                value: 'sendLocation',
                label: '📍 Локация',
                group: 'media',
                enabled: true
            },
            sendMediaGroup: {
                value: 'sendMediaGroup',
                label: '🖼️ Группа медиа',
                group: 'media',
                enabled: true
            },
            // Редактирование
            editMessageText: {
                value: 'editMessageText',
                label: 'Редактировать текст',
                group: 'editing',
                enabled: true
            },
            editMessageCaption: {
                value: 'editMessageCaption',
                label: 'Редактировать подпись',
                group: 'editing',
                enabled: true
            },
            // Управление
            deleteMessage: {
                value: 'deleteMessage',
                label: 'Удалить сообщение',
                group: 'management',
                enabled: true
            },
            pinChatMessage: {
                value: 'pinChatMessage',
                label: 'Закрепить сообщение',
                group: 'management',
                enabled: true
            },
            unpinChatMessage: {
                value: 'unpinChatMessage',
                label: 'Открепить сообщение',
                group: 'management',
                enabled: true
            },
            sendChatAction: {
                value: 'sendChatAction',
                label: '⏳ Индикатор действия',
                group: 'management',
                enabled: true
            },
            // Кнопки
            replyKeyboard: {
                value: 'replyKeyboard',
                label: 'Reply-кнопки',
                group: 'buttons',
                enabled: true
            },
            inlineKeyboard: {
                value: 'inlineKeyboard',
                label: 'Inline кнопки',
                group: 'buttons',
                enabled: true
            },
            // Специальные функции
            question: {
                value: 'question',
                label: 'Задать вопрос',
                group: 'special',
                enabled: true
            },
            managerChat: {
                value: 'managerChat',
                label: '💬 Чат с менеджером',
                group: 'special',
                enabled: true
            },
            apiRequest: {
                value: 'apiRequest',
                label: '🌐 API Запрос',
                group: 'special',
                enabled: true
            },
            apiButtons: {
                value: 'apiButtons',
                label: '🔘 API Кнопки',
                group: 'special',
                enabled: true
            },
            apiMediaGroup: {
                value: 'apiMediaGroup',
                label: '🖼️ API Группа медиа',
                group: 'special',
                enabled: true
            },
            assistant: {
                value: 'assistant',
                label: '🤖 AI Ассистент (ChatGPT)',
                group: 'special',
                enabled: true
            }
        }

        // Названия групп
        this.groupLabels = {
            messages: 'Отправка сообщений',
            media: 'Медиа',
            editing: 'Редактирование',
            management: 'Управление',
            buttons: 'Кнопки',
            special: 'Специальные функции'
        }

        // Флаг загрузки настроек
        this.settingsLoaded = false
        // Загружаем настройки из БД при инициализации (асинхронно)
        this.loadSettingsFromAPI()
    }

    /**
     * Получить все методы, сгруппированные по категориям
     * @param {boolean} onlyEnabled - возвращать только включенные методы
     * @returns {Object} Объект с группами методов
     */
    getGroupedMethods(onlyEnabled = true) {
        const grouped = {}

        Object.values(this.methods).forEach(method => {
            if (onlyEnabled && !method.enabled) {
                return
            }

            if (!grouped[method.group]) {
                grouped[method.group] = []
            }

            grouped[method.group].push(method)
        })

        return grouped
    }

    /**
     * Получить список методов для select в формате optgroup
     * @returns {Array} Массив объектов с группами и методами
     */
    getMethodsForSelect() {
        const grouped = this.getGroupedMethods(true)
        const result = []

        // Порядок групп
        const groupOrder = ['messages', 'media', 'editing', 'management', 'buttons', 'special']

        groupOrder.forEach(groupKey => {
            if (grouped[groupKey] && grouped[groupKey].length > 0) {
                result.push({
                    label: this.groupLabels[groupKey],
                    methods: grouped[groupKey]
                })
            }
        })

        return result
    }

    /**
     * Включить метод
     * @param {string} methodValue - значение метода
     */
    async enableMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = true
            await this.saveSettings()
        }
    }

    /**
     * Отключить метод
     * @param {string} methodValue - значение метода
     */
    async disableMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = false
            await this.saveSettings()
        }
    }

    /**
     * Переключить состояние метода
     * @param {string} methodValue - значение метода
     * @returns {boolean} Новое состояние (включен/выключен)
     */
    async toggleMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = !this.methods[methodValue].enabled
            await this.saveSettings()
            return this.methods[methodValue].enabled
        }
        return false
    }

    /**
     * Проверить, включен ли метод
     * @param {string} methodValue - значение метода
     * @returns {boolean}
     */
    isMethodEnabled(methodValue) {
        return this.methods[methodValue]?.enabled ?? false
    }

    /**
     * Получить все методы (включенные и выключенные)
     * @returns {Object}
     */
    getAllMethods() {
        return this.methods
    }

    /**
     * Получить только включенные методы
     * @returns {Array}
     */
    getEnabledMethods() {
        return Object.values(this.methods).filter(method => method.enabled)
    }

    /**
     * Включить все методы
     */
    async enableAll() {
        Object.values(this.methods).forEach(method => {
            method.enabled = true
        })
        await this.saveSettings()
    }

    /**
     * Отключить все методы
     */
    async disableAll() {
        Object.values(this.methods).forEach(method => {
            method.enabled = false
        })
        await this.saveSettings()
    }

    /**
     * Включить/отключить группу методов
     * @param {string} groupKey - ключ группы
     * @param {boolean} enabled - включить или отключить
     */
    async setGroupEnabled(groupKey, enabled) {
        Object.values(this.methods).forEach(method => {
            if (method.group === groupKey) {
                method.enabled = enabled
            }
        })
        await this.saveSettings()
    }

    /**
     * Сохранить настройки в БД через API
     */
    async saveSettings() {
        try {
            const settings = {}
            Object.keys(this.methods).forEach(key => {
                settings[key] = this.methods[key].enabled
            })

            console.log('Saving block methods settings:', settings)

            const response = await fetch('/api/v1/settings/block-methods', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                },
                body: JSON.stringify({ methods: settings }),
            })

            if (!response.ok) {
                const errorText = await response.text()
                console.error('Ошибка сохранения настроек методов:', response.status, errorText)
                // Fallback: сохраняем в localStorage при ошибке
                localStorage.setItem('blockMethodsSettings', JSON.stringify(settings))
                throw new Error(`HTTP ${response.status}: ${errorText}`)
            }

            const result = await response.json()
            console.log('Block methods settings saved successfully:', result)
        } catch (error) {
            console.error('Ошибка сохранения настроек методов:', error)
            // Fallback: сохраняем в localStorage при ошибке
            const settings = {}
            Object.keys(this.methods).forEach(key => {
                settings[key] = this.methods[key].enabled
            })
            localStorage.setItem('blockMethodsSettings', JSON.stringify(settings))
            throw error
        }
    }

    /**
     * Загрузить настройки из БД через API
     */
    async loadSettingsFromAPI() {
        try {
            const response = await fetch('/api/v1/settings/block-methods', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                },
            })

            if (response.ok) {
                const data = await response.json()
                const settings = data.data || {}
                
                // Применяем настройки из БД
                Object.keys(settings).forEach(key => {
                    if (this.methods[key]) {
                        this.methods[key].enabled = settings[key]
                    }
                })
                
                this.settingsLoaded = true
            } else {
                // Fallback: загружаем из localStorage
                this.loadSettingsFromLocalStorage()
            }
        } catch (error) {
            console.error('Ошибка загрузки настроек методов из API:', error)
            // Fallback: загружаем из localStorage
            this.loadSettingsFromLocalStorage()
        }
    }

    /**
     * Загрузить настройки из localStorage (fallback)
     */
    loadSettingsFromLocalStorage() {
        try {
            const settings = localStorage.getItem('blockMethodsSettings')
            if (settings) {
                const parsed = JSON.parse(settings)
                Object.keys(parsed).forEach(key => {
                    if (this.methods[key]) {
                        this.methods[key].enabled = parsed[key]
                    }
                })
            }
            this.settingsLoaded = true
        } catch (error) {
            console.error('Ошибка загрузки настроек методов из localStorage:', error)
            this.settingsLoaded = true
        }
    }

    /**
     * Загрузить настройки (старый метод для совместимости)
     */
    loadSettings() {
        this.loadSettingsFromAPI()
    }

    /**
     * Сбросить настройки к значениям по умолчанию
     */
    async resetSettings() {
        Object.values(this.methods).forEach(method => {
            method.enabled = true
        })
        await this.saveSettings()
    }
}

// Экспортируем singleton экземпляр
export const blockMethodsManager = new BlockMethodsManager()

