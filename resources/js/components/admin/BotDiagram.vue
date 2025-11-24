<template>
    <div 
        class="bot-diagram-container bg-background border border-border rounded-lg overflow-auto relative"
        :class="{ 'cursor-grabbing': isDragging, 'cursor-grab': !isDragging && isSpacePressed, 'cursor-move': isSpacePressed && !isDragging }"
        @wheel="handleWheel"
        @mousedown="handleMouseDown"
        @contextmenu.prevent
        ref="containerRef"
    >
        <div 
            ref="diagramContainer" 
            class="diagram-canvas relative"
            :style="{ 
                transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${zoom})`, 
                transformOrigin: 'top left',
                width: `${svgWidth}px`,
                height: `${svgHeight}px`,
                minHeight: '600px'
            }"
        >
            <!-- SVG для связей между блоками -->
            <svg 
                class="absolute inset-0 pointer-events-none"
                style="z-index: 1;"
                :width="svgWidth"
                :height="svgHeight"
            >
                <defs>
                    <!-- Маркер для обычных связей (nextBlockId) -->
                    <marker
                        id="arrowhead"
                        markerWidth="8"
                        markerHeight="8"
                        refX="7"
                        refY="2.5"
                        orient="auto"
                        markerUnits="userSpaceOnUse"
                    >
                        <path
                            d="M0,0 L0,5 L7,2.5 z"
                            fill="#6b7280"
                            opacity="0.7"
                        />
                    </marker>
                    <!-- Маркер для связей от кнопок (target_block_id) -->
                    <marker
                        id="arrowhead-button"
                        markerWidth="8"
                        markerHeight="8"
                        refX="7"
                        refY="2.5"
                        orient="auto"
                        markerUnits="userSpaceOnUse"
                    >
                        <path
                            d="M0,0 L0,5 L7,2.5 z"
                            fill="#eab308"
                            opacity="0.8"
                        />
                    </marker>
                    <!-- Маркер для невалидных связей (target_block_id указывает на несуществующий блок) -->
                    <marker
                        id="arrowhead-error"
                        markerWidth="8"
                        markerHeight="8"
                        refX="7"
                        refY="2.5"
                        orient="auto"
                        markerUnits="userSpaceOnUse"
                    >
                        <path
                            d="M0,0 L0,5 L7,2.5 z"
                            fill="#ef4444"
                            opacity="0.8"
                        />
                    </marker>
                </defs>
                <!-- Обычные связи (nextBlockId) -->
                <path
                    v-for="connection in connections"
                    :key="`${connection.from}-${connection.to}`"
                    :d="connection.path"
                    stroke="#6b7280"
                    stroke-width="1.5"
                    fill="none"
                    marker-end="url(#arrowhead)"
                    class="transition-all"
                    opacity="0.6"
                    style="filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));"
                />
                <!-- Связи от кнопок (target_block_id) -->
                <path
                    v-for="connection in filteredButtonConnections"
                    :key="`button-${connection.fromBlock.id}-${connection.rowIndex}-${connection.btnIndex}-${connection.toBlock?.id || 'invalid'}`"
                    :d="connection.path"
                    :stroke="connection.isValid ? '#eab308' : '#ef4444'"
                    stroke-width="2"
                    fill="none"
                    :marker-end="connection.isValid ? 'url(#arrowhead-button)' : 'url(#arrowhead-error)'"
                    class="button-connection transition-all"
                    :class="{ 'opacity-50': !connection.isValid }"
                    :opacity="connection.isValid ? 0.7 : 0.5"
                    :style="connection.isValid ? 'filter: drop-shadow(0 1px 1px rgba(234, 179, 8, 0.3));' : 'stroke-dasharray: 5,5;'"
                    :title="connection.tooltip"
                />
            </svg>

            <!-- Блоки -->
            <DiagramBlock
                v-for="block in blocks"
                :key="block.id"
                :block="block"
                @click="$emit('block-click', $event)"
                @settings="$emit('block-settings', $event)"
                @move="handleBlockMove"
                @delete="$emit('block-delete', $event)"
            />

            <!-- Пустое состояние -->
            <div 
                v-if="blocks.length === 0"
                class="flex items-center justify-center h-full text-muted-foreground"
            >
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm">Область для диаграммы</p>
                    <p class="text-xs mt-1 opacity-75">Создайте блок или команду для начала</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import DiagramBlock from './DiagramBlock.vue'

export default {
    name: 'BotDiagram',
    components: {
        DiagramBlock
    },
    props: {
        bot: {
            type: Object,
            default: null
        },
        blocks: {
            type: Array,
            default: () => []
        },
        zoom: {
            type: Number,
            default: 1
        },
        panOffset: {
            type: Object,
            default: () => ({ x: 0, y: 0 })
        },
        showButtonConnections: {
            type: Boolean,
            default: true
        }
    },
    emits: ['block-move', 'block-click', 'block-settings', 'block-delete', 'zoom-change', 'pan-change'],
    setup(props, { emit }) {
        const diagramContainer = ref(null)
        const containerRef = ref(null)
        
        // Состояние для перетаскивания области диаграммы
        const isDragging = ref(false)
        const dragStart = ref({ x: 0, y: 0 })
        const scrollStart = ref({ left: 0, top: 0 })
        const isSpacePressed = ref(false)
        const panOffset = ref({ x: props.panOffset.x, y: props.panOffset.y })

        // Синхронизируем panOffset с props
        watch(() => props.panOffset, (newValue) => {
            if (newValue) {
                panOffset.value = { x: newValue.x, y: newValue.y }
            }
        }, { deep: true })

        // Константы размеров блока
        const BLOCK_WIDTH = 120
        const BLOCK_HEIGHT = 100
        const HEADER_HEIGHT = 25 // Высота заголовка блока
        const ROW_HEIGHT = 24 // Высота ряда кнопок (h-6 = 24px)
        const BUTTON_HEIGHT = 24 // Высота одной кнопки
        const BUTTON_SPACING = 4 // Отступ между кнопками

        // Вычисляем связи между блоками с правильными координатами и избеганием пересечений
        const connections = computed(() => {
            const conns = []
            const connectionsBySource = new Map() // Группируем по источнику
            
            // Сначала собираем все связи
            props.blocks.forEach(block => {
                if (block.nextBlockId) {
                    const fromBlock = block
                    const toBlock = props.blocks.find(b => b.id === block.nextBlockId)
                    
                    if (toBlock) {
                        if (!connectionsBySource.has(fromBlock.id)) {
                            connectionsBySource.set(fromBlock.id, [])
                        }
                        connectionsBySource.get(fromBlock.id).push({
                            from: fromBlock,
                            to: toBlock
                        })
                    }
                }
            })

            // Обрабатываем каждую группу связей из одного источника
            connectionsBySource.forEach((group, sourceId) => {
                // Сортируем по Y координате целевого блока
                group.sort((a, b) => {
                    const aY = (a.to.y || 0) + BLOCK_HEIGHT / 2
                    const bY = (b.to.y || 0) + BLOCK_HEIGHT / 2
                    return aY - bY
                })

                // Для каждой связи вычисляем смещение по Y, чтобы избежать пересечений
                group.forEach((connection, index) => {
                    const fromBlock = connection.from
                    const toBlock = connection.to
                    
                    const fromX = fromBlock.x || 0
                    const fromY = fromBlock.y || 0
                    const toX = toBlock.x || 0
                    const toY = toBlock.y || 0

                    // Базовые точки выхода и входа
                    const fromExitX = fromX + BLOCK_WIDTH
                    const baseFromExitY = fromY + BLOCK_HEIGHT / 2
                    const toEnterX = toX
                    const baseToEnterY = toY + BLOCK_HEIGHT / 2

                    // Вычисляем смещение для избежания пересечений
                    // Если несколько стрелок из одного блока, распределяем их вертикально
                    const totalConnections = group.length
                    const spacing = totalConnections > 1 ? 20 : 0 // Расстояние между стрелками
                    const offset = totalConnections > 1 
                        ? (index - (totalConnections - 1) / 2) * spacing 
                        : 0

                    const fromExitY = baseFromExitY + offset
                    const toEnterY = baseToEnterY

                    // Вычисляем расстояние между блоками
                    const distance = toEnterX - fromExitX
                    
                    // Вертикальное расстояние между блоками
                    const verticalDistance = Math.abs(toEnterY - fromExitY)
                    
                    // Вычисляем контрольные точки для плавной кривой
                    // Используем разные уровни изгиба для разных стрелок
                    const horizontalOffset = Math.min(distance * 0.4, 80)
                    const verticalOffset = Math.max(verticalDistance * 0.3, 15) + (index * 10)
                    
                    const controlX1 = fromExitX + horizontalOffset
                    const controlY1 = fromExitY + (toEnterY > fromExitY ? verticalOffset : -verticalOffset)
                    const controlX2 = toEnterX - horizontalOffset
                    const controlY2 = toEnterY - (toEnterY > fromExitY ? verticalOffset : -verticalOffset)
                    
                    const path = `M ${fromExitX} ${fromExitY} 
                                 C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${toEnterX} ${toEnterY}`
                    
                    conns.push({
                        from: fromBlock.id,
                        to: toBlock.id,
                        x1: fromExitX,
                        y1: fromExitY,
                        x2: toEnterX,
                        y2: toEnterY,
                        path: path,
                        index: index
                    })
                })
            })

            return conns
        })

        // Вычисляем связи от кнопок inline-клавиатуры к блокам
        const buttonConnections = computed(() => {
            const buttonConns = []
            const connectionsByTarget = new Map() // Группируем по целевому блоку для избежания пересечений
            
            // Проходим по всем блокам
            props.blocks.forEach(block => {
                // Ищем блоки с inlineKeyboard
                if (block.method === 'inlineKeyboard') {
                    const methodData = block.methodData || block.method_data || {}
                    const inlineKeyboard = methodData.inline_keyboard || []
                    
                    if (Array.isArray(inlineKeyboard) && inlineKeyboard.length > 0) {
                        // Проходим по всем рядам кнопок
                        inlineKeyboard.forEach((row, rowIndex) => {
                            if (Array.isArray(row)) {
                                // Проходим по всем кнопкам в ряду
                                row.forEach((button, btnIndex) => {
                                    // Если у кнопки есть target_block_id
                                    if (button && button.target_block_id) {
                                        // Находим целевой блок
                                        const targetBlock = props.blocks.find(b => b.id === button.target_block_id)
                                        
                                        const isValid = !!targetBlock
                                        
                                        // Вычисляем координаты выхода от кнопки
                                        const fromX = (block.x || 0) + BLOCK_WIDTH
                                        // Вычисляем Y координату: верх блока + заголовок + позиция ряда + центр кнопки
                                        // Учитываем отступы между рядами (BUTTON_SPACING)
                                        const fromY = (block.y || 0) + 
                                                     HEADER_HEIGHT + 
                                                     (rowIndex * (ROW_HEIGHT + BUTTON_SPACING)) + 
                                                     (BUTTON_HEIGHT / 2)
                                        
                                        // Координаты входа в целевой блок
                                        let toX = 0
                                        let toY = 0
                                        
                                        if (targetBlock) {
                                            toX = targetBlock.x || 0
                                            toY = (targetBlock.y || 0) + BLOCK_HEIGHT / 2
                                        } else {
                                            // Если блок не найден, используем координаты из target_block_id как fallback
                                            // (можно использовать координаты последнего блока или центр диаграммы)
                                            const lastBlock = props.blocks[props.blocks.length - 1]
                                            if (lastBlock) {
                                                toX = (lastBlock.x || 0) + BLOCK_WIDTH + 100
                                                toY = (lastBlock.y || 0) + BLOCK_HEIGHT / 2
                                            } else {
                                                toX = fromX + 200
                                                toY = fromY
                                            }
                                        }
                                        
                                        // Группируем связи по целевому блоку для избежания пересечений
                                        const targetKey = targetBlock?.id || 'invalid'
                                        if (!connectionsByTarget.has(targetKey)) {
                                            connectionsByTarget.set(targetKey, [])
                                        }
                                        
                                        connectionsByTarget.get(targetKey).push({
                                            fromBlock: block,
                                            toBlock: targetBlock,
                                            button: button,
                                            rowIndex: rowIndex,
                                            btnIndex: btnIndex,
                                            fromX: fromX,
                                            fromY: fromY,
                                            toX: toX,
                                            toY: toY,
                                            isValid: isValid
                                        })
                                    }
                                })
                            }
                        })
                    }
                }
            })
            
            // Обрабатываем каждую группу связей к одному целевому блоку
            connectionsByTarget.forEach((group, targetKey) => {
                // Сортируем по позиции кнопки (сверху вниз)
                group.sort((a, b) => {
                    const aY = a.fromY
                    const bY = b.fromY
                    return aY - bY
                })
                
                // Для каждой связи вычисляем смещение по Y, чтобы избежать пересечений
                group.forEach((connection, index) => {
                    const fromX = connection.fromX
                    let fromY = connection.fromY
                    const toX = connection.toX
                    let toY = connection.toY
                    
                    // Если несколько связей к одному блоку, распределяем точки входа по вертикали
                    if (group.length > 1 && connection.isValid) {
                        const totalConnections = group.length
                        const spacing = Math.min(15, BLOCK_HEIGHT / (totalConnections + 1))
                        const offset = (index - (totalConnections - 1) / 2) * spacing
                        toY = toY + offset
                    }
                    
                    // Вычисляем расстояние между блоками
                    const distance = toX - fromX
                    
                    // Вертикальное расстояние между блоками
                    const verticalDistance = Math.abs(toY - fromY)
                    
                    // Вычисляем контрольные точки для плавной кривой
                    const horizontalOffset = Math.min(distance * 0.4, 80)
                    const verticalOffset = Math.max(verticalDistance * 0.3, 15) + (index * 8)
                    
                    const controlX1 = fromX + horizontalOffset
                    const controlY1 = fromY + (toY > fromY ? verticalOffset : -verticalOffset)
                    const controlX2 = toX - horizontalOffset
                    const controlY2 = toY - (toY > fromY ? verticalOffset : -verticalOffset)
                    
                    const path = `M ${fromX} ${fromY} 
                                 C ${controlX1} ${controlY1}, ${controlX2} ${controlY2}, ${toX} ${toY}`
                    
                    // Формируем tooltip
                    const buttonText = connection.button.text || connection.button.callback_data || 'Кнопка'
                    const targetLabel = connection.toBlock?.label || connection.toBlock?.id || 'Не найден'
                    const tooltip = connection.isValid 
                        ? `Кнопка: "${buttonText}" → Блок: "${targetLabel}"`
                        : `Кнопка: "${buttonText}" → Блок не найден (ID: ${connection.button.target_block_id})`
                    
                    buttonConns.push({
                        fromBlock: connection.fromBlock,
                        toBlock: connection.toBlock,
                        button: connection.button,
                        rowIndex: connection.rowIndex,
                        btnIndex: connection.btnIndex,
                        fromX: fromX,
                        fromY: fromY,
                        toX: toX,
                        toY: toY,
                        path: path,
                        isValid: connection.isValid,
                        tooltip: tooltip
                    })
                })
            })
            
            return buttonConns
        })

        // Фильтрованные связи от кнопок (с учетом настройки показа)
        const filteredButtonConnections = computed(() => {
            return props.showButtonConnections ? buttonConnections.value : []
        })

        // Размеры SVG для правильного отображения
        const svgWidth = computed(() => {
            if (props.blocks.length === 0) return 1000
            const maxX = Math.max(...props.blocks.map(b => (b.x || 0) + BLOCK_WIDTH))
            return Math.max(1000, maxX + 200)
        })

        const svgHeight = computed(() => {
            if (props.blocks.length === 0) return 600
            const maxY = Math.max(...props.blocks.map(b => (b.y || 0) + BLOCK_HEIGHT))
            return Math.max(600, maxY + 200)
        })

        const handleBlockMove = (data) => {
            emit('block-move', data)
        }

        // Обработка колесика мыши для zoom
        const handleWheel = (event) => {
            // Проверяем, зажата ли клавиша Ctrl (или Cmd на Mac)
            if (event.ctrlKey || event.metaKey) {
                event.preventDefault()
                event.stopPropagation()
                
                // Определяем направление прокрутки
                // deltaY > 0 означает прокрутку вниз (уменьшение)
                // deltaY < 0 означает прокрутку вверх (увеличение)
                const delta = event.deltaY > 0 ? -0.05 : 0.05
                const newZoom = Math.max(0.5, Math.min(2, props.zoom + delta))
                
                // Округляем до 2 знаков после запятой для плавности
                const roundedZoom = Math.round(newZoom * 100) / 100
                
                // Эмитим событие только если zoom изменился
                if (roundedZoom !== props.zoom) {
                    emit('zoom-change', roundedZoom)
                }
            }
            // Если Ctrl не зажат, разрешаем обычную прокрутку
        }

        // Обработка нажатия клавиш
        const handleKeyDown = (event) => {
            if (event.code === 'Space' && !isSpacePressed.value) {
                isSpacePressed.value = true
                event.preventDefault()
            }
        }
        
        const handleKeyUp = (event) => {
            if (event.code === 'Space') {
                isSpacePressed.value = false
                if (isDragging.value) {
                    isDragging.value = false
                }
            }
        }

        // Обработка начала перетаскивания области диаграммы
        const handleMouseDown = (event) => {
            if (!containerRef.value) {
                return
            }
            
            // Перетаскивание только при нажатии на пустую область (не на блок)
            const target = event.target
            
            // Проверяем, что клик не на блоке или его дочерних элементах
            const isBlock = target.closest('.diagram-block')
            // Проверяем, что клик не на SVG (связи между блоками)
            const isSvg = target.tagName === 'svg' || target.closest('svg')
            // Проверяем, что клик не на пустом состоянии
            const isEmptyState = target.closest('.flex.items-center.justify-center')
            
            // Начинаем перетаскивание если:
            // 1. Средняя кнопка мыши (любая область, даже на блоке)
            // 2. Правая кнопка мыши (любая область)
            // 3. Левая кнопка мыши при зажатой Space (любая область)
            // 4. Левая кнопка мыши на пустой области (не на блоке, не на SVG, не на пустом состоянии)
            const canDrag = event.button === 1 || 
                          event.button === 2 || 
                          (event.button === 0 && isSpacePressed.value) ||
                          (event.button === 0 && !isBlock && !isSvg && !isEmptyState)
            
            if (canDrag) {
                // Важно: предотвращаем стандартное поведение и останавливаем всплытие ПЕРЕД установкой флага
                event.preventDefault()
                event.stopPropagation()
                
                isDragging.value = true
                dragStart.value = {
                    x: event.clientX,
                    y: event.clientY
                }
                // Сохраняем текущее смещение панорамирования
                scrollStart.value = {
                    left: panOffset.value.x,
                    top: panOffset.value.y
                }
                
                // Блокируем контекстное меню при правой кнопке мыши
                if (event.button === 2) {
                    event.preventDefault()
                }
                
                // Устанавливаем курсор на весь документ
                document.body.style.cursor = 'grabbing'
                document.body.style.userSelect = 'none'
            }
        }

        // Обработка движения мыши при перетаскивании
        const handleMouseMove = (event) => {
            // Проверяем, что перетаскивание активно
            if (!isDragging.value) {
                return
            }
            
            // Вычисляем смещение по осям X и Y
            const deltaX = event.clientX - dragStart.value.x
            const deltaY = event.clientY - dragStart.value.y
            
            // Обновляем смещение панорамирования
            // При перетаскивании вправо (deltaX > 0) контент должен двигаться вправо (panOffset.x увеличивается)
            // При перетаскивании влево (deltaX < 0) контент должен двигаться влево (panOffset.x уменьшается)
            panOffset.value = {
                x: scrollStart.value.left + deltaX,
                y: scrollStart.value.top + deltaY
            }
            
            // Эмитим изменение panOffset
            emit('pan-change', { ...panOffset.value })
        }

        // Обработка окончания перетаскивания
        const handleMouseUp = (event) => {
            if (isDragging.value) {
                isDragging.value = false
                
                // Восстанавливаем курсор и выделение текста
                document.body.style.cursor = ''
                document.body.style.userSelect = ''
                
                if (event) {
                    event.preventDefault()
                    event.stopPropagation()
                }
            }
        }
        
        // Обработка глобальных событий мыши и клавиатуры для корректной работы перетаскивания
        onMounted(() => {
            document.addEventListener('mousemove', handleMouseMove)
            document.addEventListener('mouseup', handleMouseUp)
            window.addEventListener('keydown', handleKeyDown)
            window.addEventListener('keyup', handleKeyUp)
        })
        
        onUnmounted(() => {
            document.removeEventListener('mousemove', handleMouseMove)
            document.removeEventListener('mouseup', handleMouseUp)
            window.removeEventListener('keydown', handleKeyDown)
            window.removeEventListener('keyup', handleKeyUp)
        })

        return {
            diagramContainer,
            containerRef,
            connections,
            buttonConnections,
            filteredButtonConnections,
            svgWidth,
            svgHeight,
            handleBlockMove,
            handleWheel,
            isDragging,
            isSpacePressed,
            panOffset,
            handleMouseDown,
            handleMouseMove,
            handleMouseUp,
            handleKeyDown,
            handleKeyUp
        }
    }
}
</script>

<style scoped>
.bot-diagram-container {
    position: relative;
    overflow: auto;
    overflow-x: auto;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
    /* Убеждаемся, что контейнер может прокручиваться по обеим осям */
    width: 100%;
    height: 100%;
}

.bot-diagram-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.bot-diagram-container::-webkit-scrollbar-track {
    background: transparent;
}

.bot-diagram-container::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 4px;
}

.bot-diagram-container::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.7);
}

.diagram-canvas {
    position: relative;
    overflow: visible;
    /* Убеждаемся, что контейнер может расширяться по обеим осям */
    flex-shrink: 0;
}

/* Стили для связей от кнопок */
.button-connection {
    cursor: pointer;
    transition: opacity 0.2s, stroke-width 0.2s;
}

.button-connection:hover {
    opacity: 1 !important;
    stroke-width: 3;
}
</style>

