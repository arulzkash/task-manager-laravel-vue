<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    items: Array,
    me: Object,
});
</script>

<template>
    <Head title="Leaderboard" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 text-slate-200 md:p-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="flex items-center gap-2 text-3xl font-black text-white">
                <span>🏆</span>
                Leaderboard
            </h1>
            <div class="text-xs text-slate-400">Rank: current → best → active7 → last active</div>
        </div>

        <!-- YOU -->
        <div v-if="me" class="rounded-2xl border border-slate-700 bg-slate-800/80 p-5">
            <div class="text-xs font-bold uppercase text-slate-400">You</div>

            <div class="mt-2 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    <div class="truncate text-lg font-black text-white">
                        #{{ me.rank }} — {{ me.user.name }}
                        <span
                            class="ml-2 rounded-full border border-slate-600 px-2 py-0.5 text-xs font-bold text-slate-300"
                        >
                            {{ me.status }}
                        </span>
                    </div>

                    <div class="mt-1 text-xs text-slate-400">
                        Last active:
                        <span class="font-mono text-slate-300">{{ me.last_active_at ?? '-' }}</span>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <span
                            v-for="b in me.badges"
                            :key="b.id"
                            class="rounded-full border border-slate-700 bg-slate-900/40 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                        >
                            {{ b.category }} · {{ b.name }}
                        </span>
                        <span
                            v-if="!me.badges || me.badges.length === 0"
                            class="text-xs italic text-slate-500"
                        >
                            No badges yet
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 gap-3">
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Current</div>
                        <div class="text-xl font-black text-orange-400">{{ me.streak_current }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Best</div>
                        <div class="text-xl font-black text-indigo-400">{{ me.streak_best }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Active7</div>
                        <div class="text-xl font-black text-slate-200">{{ me.active_days_last_7d }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOP PLAYERS -->
        <div class="space-y-3">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Top Players</div>

            <div
                v-for="row in items"
                :key="row.user.id"
                class="flex flex-col gap-4 rounded-2xl border border-slate-700 bg-slate-800/60 p-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex min-w-0 items-start gap-4">
                    <div class="w-14 font-black text-slate-300">#{{ row.rank }}</div>

                    <div class="min-w-0">
                        <div class="truncate font-black text-white">
                            {{ row.user.name }}
                            <span
                                class="ml-2 rounded-full border border-slate-600 px-2 py-0.5 text-xs font-bold text-slate-300"
                            >
                                {{ row.status }}
                            </span>
                        </div>

                        <div class="mt-1 text-xs text-slate-400">
                            Last active:
                            <span class="font-mono text-slate-300">{{ row.last_active_at ?? '-' }}</span>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-if="row.badge_top"
                                class="rounded-full border border-slate-700 bg-slate-900/40 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-300"
                            >
                                {{ row.badge_top.category }} · {{ row.badge_top.name }}
                            </span>
                            <span v-else class="text-xs italic text-slate-500">No badge</span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 gap-3">
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Cur</div>
                        <div class="text-xl font-black text-orange-400">{{ row.streak_current }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Best</div>
                        <div class="text-xl font-black text-indigo-400">{{ row.streak_best }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-500">A7</div>
                        <div class="text-xl font-black text-slate-200">{{ row.active_days_last_7d }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
