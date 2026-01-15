<script setup>
import { Link, useForm, Head } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
});

// --- HELPER: QUEST STYLES ---
const getQuestVisuals = (type) => {
    const map = {
        'Boss Fight': { 
            color: 'text-red-400', 
            border: 'border-red-500/50', 
            bg: 'bg-red-500/10', 
            icon: '👹',
            shadow: 'shadow-[0_0_15px_rgba(239,68,68,0.15)]',
            dot: 'bg-red-500'
        },
        'Main Quest': { 
            color: 'text-yellow-400', 
            border: 'border-yellow-500/50', 
            bg: 'bg-yellow-500/10', 
            icon: '👑',
            shadow: 'shadow-[0_0_15px_rgba(234,179,8,0.15)]',
            dot: 'bg-yellow-500'
        },
        'Side Quest': { 
            color: 'text-blue-400', 
            border: 'border-blue-500/50', 
            bg: 'bg-blue-500/10', 
            icon: '🔍',
            shadow: 'shadow-none',
            dot: 'bg-blue-500'
        },
        'Daily Grind': { 
            color: 'text-emerald-400', 
            border: 'border-emerald-500/50', 
            bg: 'bg-emerald-500/10', 
            icon: '♻️',
            shadow: 'shadow-none',
            dot: 'bg-emerald-500'
        },
        'Learning': { 
            color: 'text-purple-400', 
            border: 'border-purple-500/50', 
            bg: 'bg-purple-500/10', 
            icon: '🧠',
            shadow: 'shadow-[0_0_10px_rgba(168,85,247,0.1)]',
            dot: 'bg-purple-500'
        }
    };

    return map[type] || { 
        color: 'text-slate-400', 
        border: 'border-slate-600', 
        bg: 'bg-slate-800', 
        icon: '📜',
        shadow: 'shadow-none',
        dot: 'bg-slate-500'
    };
};

// --- EDIT NOTE LOGIC ---
const editingId = ref(null);
const editForm = useForm({ note: "" });

const startEdit = (log) => {
    editingId.value = log.id;
    editForm.note = log.note ?? "";
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = () => {
    editForm.patch(`/logs/completions/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

// --- SMART FILTER LOGIC ---
const filterMode = ref('preset'); 
if (props.filters.date) filterMode.value = 'date';
else if (props.filters.from || props.filters.to) filterMode.value = 'range';
else filterMode.value = 'preset';

const filterForm = useForm({
    period: props.filters?.period ?? "all",
    date: props.filters?.date ?? "",
    from: props.filters?.from ?? "",
    to: props.filters?.to ?? "",
    sort: props.filters?.sort ?? "completed_at",
    dir: props.filters?.dir ?? "desc",
});

watch(filterMode, (newMode) => {
    if (newMode === 'preset') {
        filterForm.date = ""; filterForm.from = ""; filterForm.to = ""; filterForm.period = "all";
    } else if (newMode === 'date') {
        filterForm.period = "custom"; filterForm.from = ""; filterForm.to = "";
    } else if (newMode === 'range') {
        filterForm.period = "custom"; filterForm.date = "";
    }
});

const apply = () => {
    filterForm.get("/logs/completions", { preserveScroll: true, preserveState: true });
};

const setPreset = (p) => {
    filterForm.period = p;
    apply();
};

// --- DATA GROUPING ---
const groupedLogs = computed(() => {
    if (!props.logs.data) return {};
    const groups = {};
    props.logs.data.forEach(log => {
        const dateKey = new Date(log.completed_at).toDateString();
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(log);
    });
    return groups;
});

const formatTime = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

// --- UPDATE LOGIC DATE ---
const formatGroupDate = (dateStr) => {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    // Cek apakah tanggalnya sama persis dengan hari ini
    if (d.toDateString() === today.toDateString()) {
        return "Today's Conquests";
    }
    
    // Cek apakah tanggalnya sama persis dengan kemarin
    if (d.toDateString() === yesterday.toDateString()) {
        return "Yesterday's Feats";
    }

    // Sisanya format lengkap
    return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
};
</script>

<template>
    <Head title="Quest Chronicles" />

    <div class="p-4 md:p-8 max-w-5xl mx-auto space-y-8 text-gray-200">
        
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center text-2xl shadow-lg shadow-indigo-500/20">📜</div>
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight">Adventure Chronicles</h1>
                <p class="text-slate-400 text-sm">A complete record of your heroic deeds.</p>
            </div>
        </div>

        <div class="bg-slate-800 p-1 rounded-xl border border-slate-700 shadow-lg">
            <div class="grid grid-cols-3 gap-1 p-1 bg-slate-900/50 rounded-lg mb-4">
                <button @click="filterMode = 'preset'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'preset' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Presets</button>
                <button @click="filterMode = 'date'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'date' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Single Date</button>
                <button @click="filterMode = 'range'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'range' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Date Range</button>
            </div>

            <div class="px-4 pb-4">
                <div v-if="filterMode === 'preset'" class="flex flex-wrap gap-2">
                    <button v-for="p in ['all', 'today', '7d', 'month']" :key="p" @click="setPreset(p)" class="px-4 py-2 rounded-lg text-sm font-medium border transition-all" :class="filterForm.period === p ? 'bg-indigo-600 border-indigo-500 text-white shadow-md' : 'bg-slate-700 border-slate-600 text-slate-400 hover:bg-slate-600'">{{ p === '7d' ? 'Last 7 Days' : p.toUpperCase() }}</button>
                </div>
                <div v-if="filterMode === 'date'" class="flex gap-2 items-end animate-fade-in">
                    <div class="flex-1">
                        <label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">Pick Date</label>
                        <input type="date" v-model="filterForm.date" class="input-dark w-full" />
                    </div>
                    <button @click="apply" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-lg">Go</button>
                </div>
                <div v-if="filterMode === 'range'" class="flex gap-3 items-end animate-fade-in">
                    <div class="flex-1"><label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">From</label><input type="date" v-model="filterForm.from" class="input-dark w-full" /></div>
                    <div class="flex-1"><label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">To</label><input type="date" v-model="filterForm.to" class="input-dark w-full" /></div>
                    <button @click="apply" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-lg">Filter</button>
                </div>
            </div>
        </div>

        <div v-if="logs.data.length === 0" class="text-center py-20 opacity-50 border-2 border-dashed border-slate-700 rounded-2xl">
            <div class="text-6xl mb-4">📜</div>
            <div class="text-lg font-medium text-slate-300">The pages are blank...</div>
            <p class="text-sm text-slate-500 mt-2">Go forth and complete some quests!</p>
        </div>

        <div v-else class="space-y-12">
            
            <div v-for="(groupLogs, dateKey) in groupedLogs" :key="dateKey" class="relative">
                
                <div class="sticky top-[70px] z-20 mb-6 flex justify-center md:justify-start">
                    <span class="bg-slate-900 border border-slate-700 text-slate-300 px-6 py-2 rounded-full text-xs font-bold uppercase tracking-widest shadow-xl ring-4 ring-slate-900">
                        {{ formatGroupDate(dateKey) }}
                    </span>
                </div>

                <div class="absolute left-6 md:left-8 top-12 bottom-0 w-0.5 bg-slate-800"></div>

                <div class="space-y-6">
                    <div v-for="log in groupLogs" :key="log.id" class="relative pl-16 md:pl-20 group">
                        
                        <div class="absolute left-[18px] md:left-[26px] top-6 w-4 h-4 rounded-full border-4 border-slate-900 z-10 transition-colors duration-300"
                             :class="getQuestVisuals(log.quest?.type).dot">
                        </div>

                        <div 
                            class="rounded-xl border p-4 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden"
                            :class="[
                                getQuestVisuals(log.quest?.type).border, 
                                getQuestVisuals(log.quest?.type).bg,
                                getQuestVisuals(log.quest?.type).shadow,
                                'hover:bg-slate-800 bg-slate-800/60' // Hover normalize
                            ]"
                        >
                            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                                
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-slate-900/50 flex items-center justify-center text-2xl border border-white/5 shrink-0">
                                        {{ getQuestVisuals(log.quest?.type).icon }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-mono text-slate-500">{{ formatTime(log.completed_at) }}</span>
                                            <span 
                                                class="text-[10px] font-black uppercase tracking-wider opacity-80"
                                                :class="getQuestVisuals(log.quest?.type).color"
                                            >
                                                {{ log.quest?.type }}
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-bold text-white leading-tight">
                                            {{ log.quest?.name ?? "Unknown Quest" }}
                                        </h3>
                                    </div>
                                </div>

                                <div class="flex items-center gap-0 bg-slate-900 rounded-lg border border-slate-700 overflow-hidden shrink-0 shadow-sm mt-2 md:mt-0">
                                    <div class="px-3 py-1.5 border-r border-slate-700 flex items-center gap-1.5">
                                        <span class="text-indigo-400 text-xs font-black">+{{ log.xp_awarded }}</span>
                                        <span class="text-[9px] text-slate-500 font-bold">XP</span>
                                    </div>
                                    <div class="px-3 py-1.5 flex items-center gap-1.5">
                                        <span class="text-yellow-400 text-xs font-black">+{{ log.coin_awarded }}</span>
                                        <span class="text-[9px] text-slate-500 font-bold">G</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-white/5">
                                <template v-if="editingId === log.id">
                                    <textarea 
                                        v-model="editForm.note" 
                                        rows="3" 
                                        class="input-dark w-full text-sm resize-none bg-slate-900 border-slate-600 focus:border-indigo-500" 
                                        placeholder="Journal entry..."
                                        autofocus
                                    ></textarea>
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button @click="cancelEdit" class="text-xs text-slate-400 hover:text-white px-3 py-1">Cancel</button>
                                        <button @click="saveEdit" :disabled="editForm.processing" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded shadow">Update Entry</button>
                                    </div>
                                </template>
                                
                                <template v-else>
                                    <div v-if="log.note" class="flex gap-3">
                                        <div class="w-0.5 bg-slate-600 rounded-full my-1"></div> <div class="text-sm text-slate-300 italic leading-relaxed whitespace-pre-wrap flex-1">
                                            {{ log.note }}
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-slate-600 italic pl-2">No journal entry recorded.</div>
                                    
                                    <div class="mt-2 pl-2">
                                        <button 
                                            @click="startEdit(log)" 
                                            class="text-[10px] text-slate-500 hover:text-white font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0"
                                        >
                                            {{ log.note ? 'Edit Journal ✏️' : '+ Write Journal' }}
                                        </button>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div v-if="logs.links.length > 3" class="flex justify-center pt-8">
            <div class="flex gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                <Link
                    v-for="(link, k) in logs.links"
                    :key="k"
                    :href="link.url || ''"
                    v-html="link.label"
                    class="px-3 py-2 text-xs font-medium rounded-md transition-colors"
                    :class="{
                        'bg-indigo-600 text-white shadow': link.active,
                        'text-slate-400 hover:bg-slate-700 hover:text-white': !link.active && link.url,
                        'text-slate-600 opacity-50 cursor-not-allowed': !link.url
                    }"
                />
            </div>
        </div>

    </div>
</template>

<style scoped>
.input-dark {
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-500;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>