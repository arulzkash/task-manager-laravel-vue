<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, useForm, router, Head } from "@inertiajs/vue3";
import confetti from "canvas-confetti";
import { useAudio } from '@/Composables/useAudio';

defineOptions({ layout: AppLayout });

const { playSfx } = useAudio();

const props = defineProps({
    habits: Array,
    filters: Object,
});

// --- CREATE FORM ---
const createForm = useForm({
    name: "",
    start_date: "",
});

const submitCreate = () => {
    createForm.post('/habits', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showToast("✨ New Ritual Established!");
        },
    });
};

// --- ACTIONS ---
const toggleHabit = (h) => {
    router.patch(`/habits/${h.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (!h.done_today) { // Kalau tadinya belum done, sekarang jadi done
                playSfx('toggle-habit');
            }
        }
    });
};

const archiveHabit = (h) => {
    if (confirm(`Archive ritual "${h.name}"? It will stop appearing in daily list.`)) {
        router.patch(`/habits/${h.id}/archive`, {}, { preserveScroll: true });
    }
};

const unarchiveHabit = (h) => {
    router.patch(`/habits/${h.id}/unarchive`, {}, { preserveScroll: true });
};

const setView = (view) => {
    router.get("/habits", { view }, { preserveScroll: true, preserveState: true });
};

// --- VISUALS ---

const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎉</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};
</script>

<template>
    <Head title="Rituals" />

    <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8 text-gray-200">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                    <span>🛡️</span> Daily Rituals
                </h1>
                <p class="text-slate-400 text-sm mt-1">Consistency builds character. Maintain your streaks.</p>
            </div>
        </div>

        <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <h3 class="text-lg font-bold text-white mb-4">Establish New Ritual</h3>
            <form @submit.prevent="submitCreate" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Ritual Name</label>
                    <input v-model="createForm.name" placeholder="e.g. Morning Meditation" class="input-dark w-full" />
                    <div v-if="createForm.errors.name" class="text-red-400 text-xs mt-1">{{ createForm.errors.name }}</div>
                </div>
                <div class="w-full md:w-48">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Start Date</label>
                    <input type="date" v-model="createForm.start_date" class="input-dark w-full text-sm" />
                </div>
                <button type="submit" :disabled="createForm.processing" class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-6 rounded-lg transition-all shadow-lg active:scale-95">
                    + Add
                </button>
            </form>
        </div>

        <div class="flex gap-2 border-b border-slate-700 pb-1">
            <button 
                @click="setView('active')" 
                class="px-4 py-2 text-sm font-bold rounded-t-lg transition-colors relative"
                :class="filters?.view === 'active' || !filters?.view ? 'text-emerald-400 bg-slate-800 border-t border-x border-slate-700' : 'text-slate-500 hover:text-slate-300'"
            >
                Active Rituals
                <div v-if="filters?.view === 'active' || !filters?.view" class="absolute bottom-[-5px] left-0 w-full h-1 bg-slate-800"></div>
            </button>
            <button 
                @click="setView('archived')" 
                class="px-4 py-2 text-sm font-bold rounded-t-lg transition-colors relative"
                :class="filters?.view === 'archived' ? 'text-emerald-400 bg-slate-800 border-t border-x border-slate-700' : 'text-slate-500 hover:text-slate-300'"
            >
                Archived
                <div v-if="filters?.view === 'archived'" class="absolute bottom-[-5px] left-0 w-full h-1 bg-slate-800"></div>
            </button>
            <button 
                @click="setView('all')" 
                class="px-4 py-2 text-sm font-bold rounded-t-lg transition-colors relative"
                :class="filters?.view === 'all' ? 'text-emerald-400 bg-slate-800 border-t border-x border-slate-700' : 'text-slate-500 hover:text-slate-300'"
            >
                All Records
                <div v-if="filters?.view === 'all'" class="absolute bottom-[-5px] left-0 w-full h-1 bg-slate-800"></div>
            </button>
        </div>

        <div v-if="habits.length === 0" class="text-center py-16 border-2 border-dashed border-slate-700 rounded-2xl opacity-50">
            <div class="text-5xl mb-4">🍃</div>
            <h3 class="text-xl font-bold text-slate-400">No rituals found.</h3>
            <p class="text-sm text-slate-500">Start small. Consistency is key.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
            <div 
                v-for="habit in habits" 
                :key="habit.id" 
                class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center justify-between group hover:border-emerald-500/30 transition-all shadow-md"
                :class="{'opacity-60 grayscale': habit.end_date}"
            >
                <div class="flex items-center gap-4 flex-1">
                    
                    <label class="relative cursor-pointer">
                        <input 
                            type="checkbox" 
                            :checked="habit.done_today" 
                            @change="toggleHabit(habit)"
                            class="peer sr-only"
                        />
                        <div class="w-12 h-12 rounded-xl bg-slate-900 border-2 border-slate-600 flex items-center justify-center transition-all peer-checked:bg-emerald-500 peer-checked:border-emerald-400 peer-checked:shadow-[0_0_15px_rgba(16,185,129,0.5)] group-hover:border-slate-500">
                            <span class="text-2xl opacity-0 peer-checked:opacity-100 transition-opacity transform peer-checked:scale-110">✓</span>
                        </div>
                    </label>

                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-lg font-bold text-white group-hover:text-emerald-300 transition-colors">
                                {{ habit.name }}
                            </h4>
                            <span v-if="habit.end_date" class="text-[10px] bg-slate-700 text-slate-400 px-1.5 py-0.5 rounded uppercase font-bold">Archived</span>
                        </div>
                        
                        <div class="flex items-center gap-4 text-xs text-slate-400 mt-1">
                            <div class="flex items-center gap-1" :class="habit.streak > 0 ? 'text-orange-400 font-bold' : 'text-slate-600'">
                                <span class="text-base">🔥</span>
                                <span>{{ habit.streak }} Day Streak</span>
                            </div>
                            
                            <div class="hidden sm:block">
                                Start: {{ habit.start_date }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link 
                        :href="`/habits/${habit.id}`" 
                        class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded-lg transition-colors font-medium"
                    >
                        📅 Calendar
                    </Link>
                    
                    <button 
                        v-if="!habit.end_date"
                        @click="archiveHabit(habit)"
                        class="p-2 text-slate-500 hover:text-red-400 transition-colors"
                        title="Archive"
                    >
                        📦
                    </button>
                    <button 
                        v-else
                        @click="unarchiveHabit(habit)"
                        class="p-2 text-slate-500 hover:text-emerald-400 transition-colors"
                        title="Unarchive"
                    >
                        ♻️
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.input-dark {
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all placeholder-slate-600;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>