<template>
    <div class="bot-diagram-container bg-background border border-border rounded-lg overflow-hidden relative">
        <div 
            ref="diagramContainer" 
            class="diagram-canvas w-full h-full min-h-[600px] relative"
            :style="{ transform: `scale(${zoom})`, transformOrigin: 'top left' }"
        >
            <!-- SVG для связей между блоками -->
            <svg 
                class="absolute inset-0 pointer-events-none"
                style="z-index: 1;"
                :width="svgWidth"
                :height="svgHeight"
            >
                <defs>
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
                </defs>
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
import { ref, computed, watch } from 'vue'
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
        }
    },
    emits: ['block-move', 'block-click', 'block-settings', 'block-delete'],
    setup(props, { emit }) {
        const diagramContainer = ref(null)

        // Константы размеров блока
        const BLOCK_WIDTH = 120
        const BLOCK_HEIGHT = 100

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

        return {
            diagramContainer,
            connections,
            svgWidth,
            svgHeight,
            handleBlockMove
        }
    }
}
</script>

<style scoped>
.bot-diagram-container {
    position: relative;
}

.diagram-canvas {
    position: relative;
    overflow: visible;
}
</style>

