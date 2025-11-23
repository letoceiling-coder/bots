/**
 * Валидация полей для методов Telegram Bot API
 * Согласно официальной документации: https://core.telegram.org/bots/api
 */

export const telegramApiValidation = {
    // sendMessage
    sendMessage: {
        text: {
            required: true,
            min: 1,
            max: 4096,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Текст сообщения обязателен'
                }
                if (value.length > 4096) {
                    return 'Текст сообщения не может превышать 4096 символов'
                }
                return null
            }
        },
        parse_mode: {
            required: false,
            options: ['HTML', 'Markdown', 'MarkdownV2', ''],
            validate: (value) => {
                if (value && !['HTML', 'Markdown', 'MarkdownV2'].includes(value)) {
                    return 'Недопустимый режим парсинга'
                }
                return null
            }
        }
    },

    // sendDice
    sendDice: {
        emoji: {
            required: false,
            options: ['🎲', '🎯', '🏀', '⚽', '🎳', '🎰'],
            default: '🎲',
            validate: (value) => {
                if (value && !['🎲', '🎯', '🏀', '⚽', '🎳', '🎰'].includes(value)) {
                    return 'Недопустимый эмодзи кубика'
                }
                return null
            }
        }
    },

    // sendPoll
    sendPoll: {
        question: {
            required: true,
            min: 1,
            max: 300,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Вопрос обязателен'
                }
                if (value.length > 300) {
                    return 'Вопрос не может превышать 300 символов'
                }
                return null
            }
        },
        options: {
            required: true,
            min: 2,
            max: 10,
            validate: (options) => {
                if (!Array.isArray(options) || options.length < 2) {
                    return 'Должно быть минимум 2 варианта ответа'
                }
                if (options.length > 10) {
                    return 'Максимум 10 вариантов ответа'
                }
                for (let i = 0; i < options.length; i++) {
                    const option = options[i]
                    if (!option || typeof option !== 'string' || option.trim().length === 0) {
                        return `Вариант ответа ${i + 1} не может быть пустым`
                    }
                    if (option.length > 100) {
                        return `Вариант ответа ${i + 1} не может превышать 100 символов`
                    }
                }
                return null
            }
        },
        is_anonymous: {
            required: false,
            type: 'boolean',
            default: true
        },
        type: {
            required: false,
            options: ['quiz', 'regular'],
            default: 'regular',
            validate: (value) => {
                if (value && !['quiz', 'regular'].includes(value)) {
                    return 'Недопустимый тип опроса'
                }
                return null
            }
        }
    },

    // sendVenue
    sendVenue: {
        latitude: {
            required: true,
            type: 'number',
            min: -90,
            max: 90,
            validate: (value) => {
                if (value === null || value === undefined || value === '') {
                    return 'Широта обязательна'
                }
                const num = parseFloat(value)
                if (isNaN(num)) {
                    return 'Широта должна быть числом'
                }
                if (num < -90 || num > 90) {
                    return 'Широта должна быть от -90 до 90'
                }
                return null
            }
        },
        longitude: {
            required: true,
            type: 'number',
            min: -180,
            max: 180,
            validate: (value) => {
                if (value === null || value === undefined || value === '') {
                    return 'Долгота обязательна'
                }
                const num = parseFloat(value)
                if (isNaN(num)) {
                    return 'Долгота должна быть числом'
                }
                if (num < -180 || num > 180) {
                    return 'Долгота должна быть от -180 до 180'
                }
                return null
            }
        },
        title: {
            required: true,
            min: 1,
            max: 64,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Название обязательно'
                }
                if (value.length > 64) {
                    return 'Название не может превышать 64 символа'
                }
                return null
            }
        },
        address: {
            required: true,
            min: 1,
            max: 64,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Адрес обязателен'
                }
                if (value.length > 64) {
                    return 'Адрес не может превышать 64 символа'
                }
                return null
            }
        }
    },

    // sendContact
    sendContact: {
        phone_number: {
            required: true,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Номер телефона обязателен'
                }
                // Базовая валидация формата телефона
                if (!/^\+?[1-9]\d{1,14}$/.test(value.replace(/\s/g, ''))) {
                    return 'Некорректный формат номера телефона'
                }
                return null
            }
        },
        first_name: {
            required: true,
            min: 1,
            max: 255,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Имя обязательно'
                }
                if (value.length > 255) {
                    return 'Имя не может превышать 255 символов'
                }
                return null
            }
        },
        last_name: {
            required: false,
            max: 255,
            validate: (value) => {
                if (value && value.length > 255) {
                    return 'Фамилия не может превышать 255 символов'
                }
                return null
            }
        }
    },

    // replyKeyboard
    replyKeyboard: {
        keyboard: {
            required: true,
            validate: (keyboard) => {
                if (!Array.isArray(keyboard) || keyboard.length === 0) {
                    return 'Клавиатура должна содержать хотя бы один ряд кнопок'
                }
                if (keyboard.length > 8) {
                    return 'Максимум 8 рядов кнопок'
                }
                for (let i = 0; i < keyboard.length; i++) {
                    const row = keyboard[i]
                    if (!Array.isArray(row) || row.length === 0) {
                        return `Ряд ${i + 1} должен содержать хотя бы одну кнопку`
                    }
                    if (row.length > 12) {
                        return `Ряд ${i + 1} не может содержать более 12 кнопок`
                    }
                    for (let j = 0; j < row.length; j++) {
                        const button = row[j]
                        if (!button || typeof button !== 'object') {
                            return `Кнопка в ряду ${i + 1}, позиция ${j + 1} должна быть объектом`
                        }
                        if (!button.text || button.text.trim().length === 0) {
                            return `Текст кнопки в ряду ${i + 1}, позиция ${j + 1} обязателен`
                        }
                        if (button.text.length > 64) {
                            return `Текст кнопки в ряду ${i + 1}, позиция ${j + 1} не может превышать 64 символа`
                        }
                    }
                }
                return null
            }
        },
        resize_keyboard: {
            required: false,
            type: 'boolean',
            default: false
        },
        one_time_keyboard: {
            required: false,
            type: 'boolean',
            default: false
        }
    },

    // inlineKeyboard
    inlineKeyboard: {
        inline_keyboard: {
            required: true,
            validate: (keyboard) => {
                if (!Array.isArray(keyboard) || keyboard.length === 0) {
                    return 'Клавиатура должна содержать хотя бы один ряд кнопок'
                }
                if (keyboard.length > 8) {
                    return 'Максимум 8 рядов кнопок'
                }
                for (let i = 0; i < keyboard.length; i++) {
                    const row = keyboard[i]
                    if (!Array.isArray(row) || row.length === 0) {
                        return `Ряд ${i + 1} должен содержать хотя бы одну кнопку`
                    }
                    if (row.length > 13) {
                        return `Ряд ${i + 1} не может содержать более 13 кнопок`
                    }
                    for (let j = 0; j < row.length; j++) {
                        const button = row[j]
                        if (!button || typeof button !== 'object') {
                            return `Кнопка в ряду ${i + 1}, позиция ${j + 1} должна быть объектом`
                        }
                        if (!button.text || button.text.trim().length === 0) {
                            return `Текст кнопки в ряду ${i + 1}, позиция ${j + 1} обязателен`
                        }
                        if (button.text.length > 64) {
                            return `Текст кнопки в ряду ${i + 1}, позиция ${j + 1} не может превышать 64 символа`
                        }
                        // Проверка наличия хотя бы одного callback_data, url, или другого действия
                        if (!button.callback_data && !button.url && !button.web_app && !button.login_url && !button.switch_inline_query && !button.switch_inline_query_current_chat && !button.callback_game && !button.pay) {
                            return `Кнопка в ряду ${i + 1}, позиция ${j + 1} должна иметь хотя бы одно действие (callback_data, url и т.д.)`
                        }
                        if (button.callback_data && button.callback_data.length > 64) {
                            return `callback_data в ряду ${i + 1}, позиция ${j + 1} не может превышать 64 байта`
                        }
                    }
                }
                return null
            }
        }
    },

    // editMessageText
    editMessageText: {
        text: {
            required: true,
            min: 1,
            max: 4096,
            validate: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Текст сообщения обязателен'
                }
                if (value.length > 4096) {
                    return 'Текст сообщения не может превышать 4096 символов'
                }
                return null
            }
        },
        parse_mode: {
            required: false,
            options: ['HTML', 'Markdown', 'MarkdownV2', ''],
            validate: (value) => {
                if (value && !['HTML', 'Markdown', 'MarkdownV2'].includes(value)) {
                    return 'Недопустимый режим парсинга'
                }
                return null
            }
        }
    },

    // editMessageCaption
    editMessageCaption: {
        caption: {
            required: true,
            min: 0,
            max: 1024,
            validate: (value) => {
                if (value && value.length > 1024) {
                    return 'Подпись не может превышать 1024 символа'
                }
                return null
            }
        },
        parse_mode: {
            required: false,
            options: ['HTML', 'Markdown', 'MarkdownV2', ''],
            validate: (value) => {
                if (value && !['HTML', 'Markdown', 'MarkdownV2'].includes(value)) {
                    return 'Недопустимый режим парсинга'
                }
                return null
            }
        }
    },

    // deleteMessage
    deleteMessage: {
        message_id: {
            required: true,
            type: 'number',
            validate: (value) => {
                if (value === null || value === undefined || value === '') {
                    return 'ID сообщения обязательно'
                }
                const num = parseInt(value)
                if (isNaN(num) || num <= 0) {
                    return 'ID сообщения должен быть положительным числом'
                }
                return null
            }
        }
    },

    // pinChatMessage
    pinChatMessage: {
        message_id: {
            required: true,
            type: 'number',
            validate: (value) => {
                if (value === null || value === undefined || value === '') {
                    return 'ID сообщения обязательно'
                }
                const num = parseInt(value)
                if (isNaN(num) || num <= 0) {
                    return 'ID сообщения должен быть положительным числом'
                }
                return null
            }
        },
        disable_notification: {
            required: false,
            type: 'boolean',
            default: false
        }
    }
}

/**
 * Валидация данных для конкретного метода
 */
export function validateMethodData(method, data) {
    const validation = telegramApiValidation[method]
    if (!validation) {
        return { valid: true, errors: {} }
    }

    const errors = {}
    let isValid = true

    for (const [field, rules] of Object.entries(validation)) {
        const value = data[field]
        const error = rules.validate ? rules.validate(value) : null

        if (error) {
            errors[field] = error
            isValid = false
        } else if (rules.required && (value === null || value === undefined || value === '')) {
            errors[field] = `Поле "${field}" обязательно`
            isValid = false
        }
    }

    return { valid: isValid, errors }
}

