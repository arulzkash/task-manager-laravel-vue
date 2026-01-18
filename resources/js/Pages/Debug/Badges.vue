<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    me: Object,
});
</script>

<template>
    <Head title="Debug Badges" />

    <div class="mx-auto max-w-3xl p-6 text-slate-200">
        <h1 class="mb-4 text-2xl font-bold">Debug Badges</h1>

        <div class="mb-4 rounded-lg border border-slate-700 bg-slate-800 p-4">
            <div>
                <b>User:</b>
                {{ me.name }} (id {{ me.id }})
            </div>
            <div>
                <b>streak_current:</b>
                {{ me.streak_current }}
            </div>
            <div>
                <b>streak_best:</b>
                {{ me.streak_best }}
            </div>
            <div>
                <b>freezes_used_total:</b>
                {{ me.freezes_used_total }}
            </div>
            <div>
                <b>streak_resets_total:</b>
                {{ me.streak_resets_total }}
            </div>
        </div>

        <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
            <h2 class="mb-2 font-bold">Badges ({{ me.badges.length }})</h2>
            <ul class="list-disc space-y-1 pl-6">
                <li v-for="b in me.badges" :key="b.key">
                    <b>{{ b.name }}</b>
                    — {{ b.key }} ({{ b.category }}) earned: {{ b.earned_at }}
                </li>
            </ul>
            <div v-if="me.badges.length === 0" class="text-slate-400">No badges yet.</div>
        </div>
    </div>
</template>
