<script setup>
import { Link, useForm, Head } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logs: Object, 
    filters: Object,
    rewardOptions: Array,
});

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
    editForm.patch(`/logs/treasury/${editingId.value}`, {
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
    reward_id: props.filters?.reward_id ?? "",
    sort: props.filters?.sort ?? "purchased_at",
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
    filterForm.get("/logs/treasury", { preserveScroll: true, preserveState: true });
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
        const dateKey = new Date(log.purchased_at).toDateString();
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(log);
    });
    return groups;
});

const formatTime = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const formatGroupDate = (dateStr) => {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (d.toDateString() === today.toDateString()) return "Today's Expenses";
    if (d.toDateString() === yesterday.toDateString()) return "Yesterday's Spending";
    return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
};
</script>

<template>
    <Head title="Spending Log" />

    <div class="p-4 md:p-8 max-w-5xl mx-auto space-y-8 text-gray-200">
        
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div class="w-12 h-12 bg-yellow-600 rounded-lg flex items-center justify-center text-2xl shadow-lg shadow-yellow-500/20">🧾</div>
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight">Merchant's Ledger</h1>
                <p class="text-slate-400 text-sm">Track where your hard-earned gold goes.</p>
            </div>
        </div>

        <div class="bg-slate-800 p-1 rounded-xl border border-slate-700 shadow-lg">
            <div class="grid grid-cols-3 gap-1 p-1 bg-slate-900/50 rounded-lg mb-4">
                <button @click="filterMode = 'preset'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'preset' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Presets</button>
                <button @click="filterMode = 'date'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'date' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Single Date</button>
                <button @click="filterMode = 'range'" class="py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-all" :class="filterMode === 'range' ? 'bg-slate-700 text-white shadow' : 'text-slate-500 hover:text-slate-300'">Date Range</button>
            </div>

            <div class="px-4 pb-4 space-y-4">
                
                <div>
                    <label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">Filter by Item</label>
                    <select v-model="filterForm.reward_id" @change="apply" class="input-dark w-full">
                        <option value="">Show All Transactions</option>
                        <option v-for="r in rewardOptions" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                    </select>
                </div>

                <div>
                    <div v-if="filterMode === 'preset'" class="flex flex-wrap gap-2">
                        <button v-for="p in ['all', 'today', '7d', 'month']" :key="p" @click="setPreset(p)" class="px-4 py-2 rounded-lg text-sm font-medium border transition-all" :class="filterForm.period === p ? 'bg-yellow-600 border-yellow-500 text-white shadow-md' : 'bg-slate-700 border-slate-600 text-slate-400 hover:bg-slate-600'">{{ p === '7d' ? 'Last 7 Days' : p.toUpperCase() }}</button>
                    </div>
                    
                    <div v-if="filterMode === 'date'" class="flex gap-2 items-end animate-fade-in">
                        <div class="flex-1">
                            <label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">Pick Date</label>
                            <input type="date" v-model="filterForm.date" class="input-dark w-full" />
                        </div>
                        <button @click="apply" class="bg-yellow-600 hover:bg-yellow-500 text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-lg">Go</button>
                    </div>

                    <div v-if="filterMode === 'range'" class="flex gap-3 items-end animate-fade-in">
                        <div class="flex-1"><label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">From</label><input type="date" v-model="filterForm.from" class="input-dark w-full" /></div>
                        <div class="flex-1"><label class="text-[10px] uppercase font-bold text-slate-500 mb-1 block">To</label><input type="date" v-model="filterForm.to" class="input-dark w-full" /></div>
                        <button @click="apply" class="bg-yellow-600 hover:bg-yellow-500 text-white px-6 py-2.5 rounded-lg font-bold text-sm shadow-lg">Filter</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="logs.data.length === 0" class="text-center py-20 opacity-50 border-2 border-dashed border-slate-700 rounded-2xl">
            <div class="text-6xl mb-4">💰</div>
            <div class="text-lg font-medium text-slate-300">No transactions recorded.</div>
            <p class="text-sm text-slate-500 mt-2">Your gold is safe... for now.</p>
        </div>

        <div v-else class="space-y-12">
            
            <div v-for="(groupLogs, dateKey) in groupedLogs" :key="dateKey" class="relative">
                
                <div class="sticky top-[70px] z-20 mb-6 flex justify-center md:justify-start">
                    <span class="bg-slate-900 border border-slate-700 text-slate-300 px-6 py-2 rounded-full text-xs font-bold uppercase tracking-widest shadow-xl ring-4 ring-slate-900">
                        {{ formatGroupDate(dateKey) }}
                    </span>
                </div>

                <div class="absolute left-6 md:left-8 top-12 bottom-0 w-0.5 bg-slate-800"></div>

                <div class="space-y-4">
                    <div v-for="log in groupLogs" :key="log.id" class="relative pl-16 md:pl-20 group">
                        
                        <div class="absolute left-[18px] md:left-[26px] top-6 w-4 h-4 rounded-full border-4 border-slate-900 z-10 bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.5)]"></div>

                        <div class="bg-slate-800/50 hover:bg-slate-800 rounded-xl border border-slate-700/50 p-4 transition-all hover:border-yellow-500/30 hover:shadow-md flex flex-col md:flex-row gap-4 justify-between">
                            
                            <div class="flex items-start gap-4 flex-1">
                                <div class="w-12 h-12 rounded-lg bg-slate-900/50 flex items-center justify-center text-2xl border border-white/5 shrink-0">
                                    🎁
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-mono text-slate-500">{{ formatTime(log.purchased_at) }}</span>
                                        <span class="text-[10px] bg-slate-700 text-slate-300 px-2 py-0.5 rounded border border-slate-600">
                                            Qty: {{ log.qty }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-200">
                                        {{ log.reward?.name ?? "(Discontinued Item)" }}
                                    </h3>

                                    <div class="mt-2">
                                        <template v-if="editingId === log.id">
                                            <textarea 
                                                v-model="editForm.note" 
                                                rows="2" 
                                                class="input-dark w-full text-sm resize-none bg-slate-900 border-slate-600" 
                                                placeholder="Details..."
                                            ></textarea>
                                            <div class="flex justify-end gap-2 mt-2">
                                                <button @click="cancelEdit" class="text-xs text-slate-400 hover:text-white px-2">Cancel</button>
                                                <button @click="saveEdit" :disabled="editForm.processing" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded">Save</button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div v-if="log.note" class="text-sm text-slate-400 italic border-l-2 border-slate-600 pl-3 leading-relaxed">
                                                "{{ log.note }}"
                                            </div>
                                            <button 
                                                @click="startEdit(log)" 
                                                class="mt-1 text-[10px] text-slate-600 hover:text-yellow-500 font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                {{ log.note ? 'Edit Note' : '+ Add Note' }}
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end justify-center min-w-[100px] border-t md:border-t-0 md:border-l border-slate-700/50 pt-3 md:pt-0 md:pl-4">
                                <div class="text-[10px] text-slate-500 uppercase font-bold mb-0.5">Total Paid</div>
                                <div class="text-xl font-mono font-black text-yellow-500">
                                    -{{ log.cost_coin }} G
                                </div>
                                <div class="text-[10px] text-slate-600 font-mono">
                                    {{ log.unit_cost_coin }} G / unit
                                </div>
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
                        'bg-yellow-600 text-white shadow': link.active,
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
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-yellow-500 transition-all placeholder-slate-500;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
/* Calendar Icon White Fix */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>