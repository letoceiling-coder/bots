<template>
    <div class="settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки</h1>
            <p class="text-muted-foreground mt-1">Настройки системы</p>
        </div>
        <div class="bg-card rounded-lg border border-border p-6">
            <BlockMethodsSettings />
        </div>
    </div>
</template>

<script>
import BlockMethodsSettings from '../../components/admin/BlockMethodsSettings.vue'

export default {
    name: 'Settings',
    components: {
        BlockMethodsSettings
    }
};
</script>

