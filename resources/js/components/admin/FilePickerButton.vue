<template>
  <div class="space-y-2">
    <button
      @click="openModal"
      type="button"
      class="w-full h-10 px-3 border border-border rounded bg-background hover:bg-accent/10 transition-colors flex items-center justify-center gap-2"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span>{{ hasSelectedFiles ? 'Изменить файл' : 'Выбрать файл' }}</span>
    </button>

    <!-- Превью выбранных файлов -->
    <div v-if="hasSelectedFiles" class="space-y-2">
      <div class="grid grid-cols-3 gap-2">
        <div
          v-for="(file, index) in displayFiles"
          :key="index"
          class="group relative aspect-square rounded-lg overflow-hidden border border-border bg-muted/30"
        >
          <!-- Превью изображения -->
          <img
            v-if="isImageUrl(getFilePreview(file))"
            :src="getFilePreview(file)"
            :alt="getFileName(file)"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
          <!-- Превью видео -->
          <video
            v-else-if="isVideoUrl(getFilePreview(file))"
            :src="getFilePreview(file)"
            class="w-full h-full object-cover"
            muted
          />
          <!-- Иконка для других типов файлов -->
          <div
            v-else
            class="w-full h-full flex flex-col items-center justify-center bg-muted/50 p-2"
          >
            <div class="text-2xl mb-1">📄</div>
            <p class="text-xs text-muted-foreground text-center truncate w-full px-1">
              {{ getFileExtension(getFileName(file))?.toUpperCase() || 'FILE' }}
            </p>
          </div>
          <!-- Кнопка удаления -->
          <button
            @click.stop="removeFile(index)"
            class="absolute top-1 right-1 w-6 h-6 flex items-center justify-center bg-destructive/90 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-destructive"
            title="Удалить"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <!-- Overlay с названием -->
          <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <p class="text-white text-xs text-center truncate" :title="getFileName(file)">
              {{ getFileName(file) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm"
        @click.self="closeModal"
      >
        <div class="bg-card border border-border rounded-lg shadow-xl w-full h-full max-w-7xl max-h-[90vh] flex flex-col relative z-[10000]">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-border">
            <h3 class="text-lg font-semibold text-foreground">
              Выбор файла{{ countFile > 1 ? ` (максимум ${countFile})` : '' }}
            </h3>
            <button
              @click="closeModal"
              class="text-muted-foreground hover:text-foreground transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Media Component -->
          <div class="flex-1 overflow-hidden overflow-y-auto p-4">
            <Media
              :selection-mode="true"
              :count-file="countFile"
              :path="path"
              :selected-files="internalSelectedFiles"
              @update:model-value="handleFileSelection"
              @file-selected="handleFileSelected"
            />
          </div>

          <!-- Footer with Select Button (only for countFile > 1) -->
          <div
            v-if="countFile > 1 && internalSelectedFiles.length > 0"
            class="p-4 border-t border-border flex items-center justify-between"
          >
            <span class="text-sm text-muted-foreground">
              Выбрано: {{ internalSelectedFiles.length }} / {{ countFile }}
            </span>
            <button
              @click="confirmSelection"
              class="px-4 py-2 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors"
            >
              Выбрать
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import Media from '../../pages/admin/Media.vue'

export default {
  name: 'FilePickerButton',
  components: {
    Media
  },
  props: {
    modelValue: {
      type: [Object, Array, String],
      default: null
    },
    countFile: {
      type: Number,
      default: 1
    },
    path: {
      type: String,
      default: 'webp'
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const showModal = ref(false)
    const internalSelectedFiles = ref([])
    const selectedFilesCache = ref([]) // Кэш для хранения полных объектов файлов

    const openModal = () => {
      // Инициализируем выбранные файлы из modelValue и кэша
      // Если есть кэш, используем его (там полные объекты файлов)
      if (selectedFilesCache.value.length > 0) {
        internalSelectedFiles.value = [...selectedFilesCache.value]
      } else if (props.modelValue) {
        if (typeof props.modelValue === 'string') {
          // Если это строка, не инициализируем internalSelectedFiles
          // Пользователь должен выбрать файл заново
          internalSelectedFiles.value = []
        } else if (props.countFile === 1) {
          // Если это объект и countFile = 1
          internalSelectedFiles.value = [props.modelValue]
        } else if (Array.isArray(props.modelValue)) {
          // Если это массив
          internalSelectedFiles.value = [...props.modelValue]
        } else {
          internalSelectedFiles.value = []
        }
      } else {
        internalSelectedFiles.value = []
      }
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      // Сбрасываем выбранные файлы при закрытии без подтверждения
      if (props.countFile > 1) {
        internalSelectedFiles.value = []
      }
    }

    const handleFileSelection = (processedValue) => {
      // Media возвращает обработанное значение через path prop
      // Это может быть строка (URL) или массив строк
      // Но нам нужно сохранить полные объекты файлов в internalSelectedFiles
      // Поэтому мы не обновляем internalSelectedFiles здесь
      // internalSelectedFiles обновляется через handleFileSelected
    }

    const handleFileSelected = (file) => {
      // Эта функция вызывается при выборе файла в Media
      // file - это полный объект файла
      
      // Проверяем, не выбран ли уже этот файл
      const isAlreadySelected = internalSelectedFiles.value.some(f => f.id === file.id)
      
      if (isAlreadySelected) {
        // Убираем из выбранных
        internalSelectedFiles.value = internalSelectedFiles.value.filter(f => f.id !== file.id)
        selectedFilesCache.value = selectedFilesCache.value.filter(f => f.id !== file.id)
      } else {
        if (props.countFile === 1) {
          // Если countFile = 1, заменяем выбранный файл
          internalSelectedFiles.value = [file]
          selectedFilesCache.value = [file]
          // Обрабатываем файл через path и возвращаем
          const processedFile = processFile(file)
          emit('update:modelValue', processedFile)
          closeModal()
        } else {
          // Если countFile > 1, проверяем лимит
          if (internalSelectedFiles.value.length >= props.countFile) {
            alert(`Можно выбрать не более ${props.countFile} файлов`)
            return
          }
          // Добавляем в выбранные
          internalSelectedFiles.value.push(file)
          selectedFilesCache.value.push(file)
        }
      }
    }

    const processFile = (file) => {
      if (!file) return null

      // Если path указан через точку (например, "variations.webp")
      if (props.path.includes('.')) {
        const pathParts = props.path.split('.')
        let result = file
        for (const part of pathParts) {
          if (result && typeof result === 'object' && part in result) {
            result = result[part]
          } else {
            return null
          }
        }
        return result
      }

      // Если path - просто поле объекта
      if (props.path && props.path !== 'webp') {
        return file[props.path] || null
      }

      // По умолчанию возвращаем поле webp или url
      return file.webp || file.url || file
    }

    const confirmSelection = () => {
      if (internalSelectedFiles.value.length === 0) {
        return
      }
      // Сохраняем полные объекты в кэш
      selectedFilesCache.value = [...internalSelectedFiles.value]
      // Обрабатываем все выбранные файлы через path
      const processedFiles = internalSelectedFiles.value.map(file => processFile(file))
      if (props.countFile === 1) {
        emit('update:modelValue', processedFiles[0])
      } else {
        emit('update:modelValue', processedFiles)
      }
      closeModal()
    }

    // Computed для проверки наличия выбранных файлов
    const hasSelectedFiles = computed(() => {
      return (props.modelValue && (typeof props.modelValue === 'string' || typeof props.modelValue === 'object')) || selectedFilesCache.value.length > 0
    })

    // Computed для отображения файлов
    const displayFiles = computed(() => {
      // Если есть кэш с полными объектами, используем его
      if (selectedFilesCache.value.length > 0) {
        return selectedFilesCache.value
      }
      // Если modelValue - строка (URL), создаем временный объект для отображения
      if (typeof props.modelValue === 'string' && props.modelValue) {
        return [{
          url: props.modelValue,
          webp: props.modelValue,
          original_name: props.modelValue.split('/').pop() || 'Файл',
          type: isImageUrl(props.modelValue) ? 'photo' : isVideoUrl(props.modelValue) ? 'video' : 'document'
        }]
      }
      // Если modelValue - объект
      if (props.modelValue && typeof props.modelValue === 'object' && !Array.isArray(props.modelValue)) {
        return [props.modelValue]
      }
      // Если modelValue - массив
      if (Array.isArray(props.modelValue)) {
        return props.modelValue
      }
      return []
    })

    // Удаление файла
    const removeFile = (index) => {
      if (props.countFile === 1) {
        // Если один файл, просто очищаем
        selectedFilesCache.value = []
        internalSelectedFiles.value = []
        emit('update:modelValue', null)
      } else {
        // Если несколько файлов, удаляем по индексу
        selectedFilesCache.value.splice(index, 1)
        internalSelectedFiles.value.splice(index, 1)
        // Обновляем modelValue
        const processedFiles = internalSelectedFiles.value.map(file => processFile(file))
        emit('update:modelValue', processedFiles)
      }
    }

    // Получить превью файла
    const getFilePreview = (file) => {
      if (!file) return ''
      // Если это строка (URL)
      if (typeof file === 'string') {
        return file
      }
      // Если это объект, используем path для получения URL
      if (props.path === 'webp' && file.webp) {
        return file.webp
      }
      if (props.path === 'url' && file.url) {
        return file.url
      }
      // Пробуем получить URL из различных полей
      return file.url || file.webp || file.original_url || ''
    }

    // Получить имя файла
    const getFileName = (file) => {
      if (!file) return ''
      if (typeof file === 'string') {
        return file.split('/').pop() || 'Файл'
      }
      return file.original_name || file.name || 'Файл'
    }

    // Получить расширение файла
    const getFileExtension = (fileName) => {
      if (!fileName) return ''
      const parts = fileName.split('.')
      return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : ''
    }

    // Проверка, является ли URL изображением
    const isImageUrl = (url) => {
      if (!url || typeof url !== 'string') return false
      const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp']
      return imageExtensions.some(ext => url.toLowerCase().includes(ext))
    }

    // Проверка, является ли URL видео
    const isVideoUrl = (url) => {
      if (!url || typeof url !== 'string') return false
      const videoExtensions = ['.mp4', '.avi', '.mov', '.webm', '.mkv', '.wmv', '.flv']
      return videoExtensions.some(ext => url.toLowerCase().includes(ext))
    }

    // Обработка ошибки загрузки изображения
    const handleImageError = (e) => {
      e.target.style.display = 'none'
    }

    // Синхронизация с внешним modelValue
    watch(() => props.modelValue, (newValue) => {
      if (!showModal.value) {
        // Если modelValue - строка (URL), не синхронизируем с internalSelectedFiles
        if (typeof newValue === 'string') {
          internalSelectedFiles.value = []
          selectedFilesCache.value = []
        } else if (newValue && typeof newValue === 'object') {
          if (props.countFile === 1) {
            internalSelectedFiles.value = [newValue]
            selectedFilesCache.value = [newValue]
          } else if (Array.isArray(newValue)) {
            internalSelectedFiles.value = [...newValue]
            selectedFilesCache.value = [...newValue]
          } else {
            internalSelectedFiles.value = []
            selectedFilesCache.value = []
          }
        } else {
          internalSelectedFiles.value = []
          selectedFilesCache.value = []
        }
      }
    })

    return {
      showModal,
      internalSelectedFiles,
      openModal,
      closeModal,
      handleFileSelection,
      handleFileSelected,
      confirmSelection,
      processFile,
      hasSelectedFiles,
      displayFiles,
      removeFile,
      getFilePreview,
      getFileName,
      getFileExtension,
      isImageUrl,
      isVideoUrl,
      handleImageError
    }
  }
}
</script>

