<template>
    <div class="dashboard-page space-y-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Панель управления</h1>
            <p class="text-muted-foreground mt-1">Добро пожаловать в админ панель</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Управление кнопкой меню бота -->
            <BotMenuSettings />
            
            <!-- Статистика -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold mb-2">Статистика</h2>
                <p class="text-muted-foreground">Модуль не подключен</p>
            </div>
        </div>
    </div>
</template>

<script>
import BotMenuSettings from '../../components/admin/BotMenuSettings.vue'

export default {
    name: 'Dashboard',
    components: {
        BotMenuSettings,
    },
};
</script>

