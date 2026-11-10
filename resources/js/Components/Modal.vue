<script setup>
import { onMounted, onBeforeUnmount } from 'vue';

defineProps({ titre: String, sous: String });
const emit = defineEmits(['close']);
const onKey = (e) => e.key === 'Escape' && emit('close');
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div class="veil" @mousedown.self="emit('close')">
            <div class="modal" role="dialog" aria-modal="true">
                <h3>{{ titre }}</h3>
                <p v-if="sous" class="sub">{{ sous }}</p>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
