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

        // Загружаем настройки из localStorage при инициализации
        this.loadSettings()
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
    enableMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = true
            this.saveSettings()
        }
    }

    /**
     * Отключить метод
     * @param {string} methodValue - значение метода
     */
    disableMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = false
            this.saveSettings()
        }
    }

    /**
     * Переключить состояние метода
     * @param {string} methodValue - значение метода
     * @returns {boolean} Новое состояние (включен/выключен)
     */
    toggleMethod(methodValue) {
        if (this.methods[methodValue]) {
            this.methods[methodValue].enabled = !this.methods[methodValue].enabled
            this.saveSettings()
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
    enableAll() {
        Object.values(this.methods).forEach(method => {
            method.enabled = true
        })
        this.saveSettings()
    }

    /**
     * Отключить все методы
     */
    disableAll() {
        Object.values(this.methods).forEach(method => {
            method.enabled = false
        })
        this.saveSettings()
    }

    /**
     * Включить/отключить группу методов
     * @param {string} groupKey - ключ группы
     * @param {boolean} enabled - включить или отключить
     */
    setGroupEnabled(groupKey, enabled) {
        Object.values(this.methods).forEach(method => {
            if (method.group === groupKey) {
                method.enabled = enabled
            }
        })
        this.saveSettings()
    }

    /**
     * Сохранить настройки в localStorage
     */
    saveSettings() {
        const settings = {}
        Object.keys(this.methods).forEach(key => {
            settings[key] = this.methods[key].enabled
        })
        localStorage.setItem('blockMethodsSettings', JSON.stringify(settings))
    }

    /**
     * Загрузить настройки из localStorage
     */
    loadSettings() {
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
        } catch (error) {
            console.error('Ошибка загрузки настроек методов:', error)
        }
    }

    /**
     * Сбросить настройки к значениям по умолчанию
     */
    resetSettings() {
        Object.values(this.methods).forEach(method => {
            method.enabled = true
        })
        this.saveSettings()
    }
}

// Экспортируем singleton экземпляр
export const blockMethodsManager = new BlockMethodsManager()

