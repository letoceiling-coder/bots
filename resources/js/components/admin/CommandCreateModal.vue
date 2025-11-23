<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold mb-4">Создать команду</h3>
            
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Команда <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.command"
                        type="text"
                        class="w-full h-10 px-3 border border-border rounded bg-background"
                        placeholder="/start"
                        :class="{ 'border-destructive': errors.command }"
                        @input="validateCommand"
                    />
                    <p v-if="errors.command" class="text-xs text-destructive mt-1">{{ errors.command }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Команда должна начинаться с /, содержать только латинские буквы, цифры и подчеркивания
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Длина: {{ form.command.length }}/32 символов
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium mb-1 block">
                        Описание <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full px-3 py-2 border border-border rounded bg-background"
                        :class="{ 'border-destructive': errors.description }"
                        placeholder="Описание команды"
                        @input="validateDescription"
                    ></textarea>
                    <p v-if="errors.description" class="text-xs text-destructive mt-1">{{ errors.description }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                        Длина: {{ form.description.length }}/256 символов
                    </p>
                </div>

                <div class="flex gap-2 pt-4">
                    <button
                        type="button"
                        @click="handleCancel"
                        class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                    >
                        Отмена
                    </button>
                    <button
                        type="submit"
                        :disabled="!isValid"
                        class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Создать
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue'

export default {
    name: 'CommandCreateModal',
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'create'],
    setup(props, { emit }) {
        const form = ref({
            command: '',
            description: ''
        })

        const errors = ref({
            command: '',
            description: ''
        })

        // Валидация команды по Telegram API требованиям
        const validateCommand = () => {
            errors.value.command = ''
            
            if (!form.value.command) {
                errors.value.command = 'Команда обязательна'
                return false
            }

            // Команда должна начинаться с /
            if (!form.value.command.startsWith('/')) {
                errors.value.command = 'Команда должна начинаться с /'
                return false
            }

            // Длина команды: 1-32 символа (включая /)
            if (form.value.command.length < 1 || form.value.command.length > 32) {
                errors.value.command = 'Длина команды должна быть от 1 до 32 символов'
                return false
            }

            // Команда может содержать только латинские буквы, цифры и подчеркивания после /
            const commandBody = form.value.command.substring(1)
            if (!/^[a-z0-9_]+$/i.test(commandBody)) {
                errors.value.command = 'Команда может содержать только латинские буквы, цифры и подчеркивания'
                return false
            }

            // Команда не может быть только /
            if (commandBody.length === 0) {
                errors.value.command = 'Команда не может быть только /'
                return false
            }

            return true
        }

        // Валидация описания по Telegram API требованиям
        const validateDescription = () => {
            errors.value.description = ''
            
            if (!form.value.description) {
                errors.value.description = 'Описание обязательно'
                return false
            }

            // Длина описания: 3-256 символов
            if (form.value.description.length < 3 || form.value.description.length > 256) {
                errors.value.description = 'Длина описания должна быть от 3 до 256 символов'
                return false
            }

            return true
        }

        const isValid = computed(() => {
            return form.value.command && 
                   form.value.description && 
                   !errors.value.command && 
                   !errors.value.description &&
                   validateCommand() &&
                   validateDescription()
        })

        const handleSubmit = () => {
            if (validateCommand() && validateDescription()) {
                emit('create', {
                    command: form.value.command,
                    description: form.value.description
                })
                resetForm()
            }
        }

        const handleCancel = () => {
            resetForm()
            emit('close')
        }

        const resetForm = () => {
            form.value = {
                command: '',
                description: ''
            }
            errors.value = {
                command: '',
                description: ''
            }
        }

        watch(() => props.show, (newVal) => {
            if (!newVal) {
                resetForm()
            }
        })

        return {
            form,
            errors,
            isValid,
            validateCommand,
            validateDescription,
            handleSubmit,
            handleCancel
        }
    }
}
</script>

